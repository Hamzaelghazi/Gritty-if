<?php
/**
 * Add/Edit tracking record screen.
 *
 * @package Nasm_Order_Tracking
 */

namespace Nasm\OrderTracking\Admin;

use Nasm\OrderTracking\Database\OrdersRepository;
use Nasm\OrderTracking\Database\StatusHistoryRepository;
use Nasm\OrderTracking\Database\StatusesRepository;
use Nasm\OrderTracking\Database\ActivityLogger;
use Nasm\OrderTracking\Helpers\Sanitizer;
use Nasm\OrderTracking\Helpers\Settings;
use Nasm\OrderTracking\Emails\Mailer;

defined( 'ABSPATH' ) || exit;

class AddTrackingPage {

	const NONCE_ACTION = 'nasm_ot_save_tracking';

	/**
	 * Render the add/edit screen, handling form submission first.
	 */
	public function render() {
		if ( ! current_user_can( AdminMenu::CAPABILITY ) ) {
			return;
		}

		$errors  = array();
		$success = false;

		$order_id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$order    = $order_id ? OrdersRepository::find( $order_id ) : null;

		if ( ! empty( $_POST['nasm_ot_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nasm_ot_nonce'] ) ), self::NONCE_ACTION ) ) {
			$result = $this->handle_submit( $order );

			if ( is_wp_error( $result ) ) {
				$errors[] = $result->get_error_message();
			} else {
				wp_safe_redirect( AdminMenu::url( 'order-details', array( 'order_id' => $result, 'saved' => 1 ) ) );
				exit;
			}
		}

		$statuses = StatusesRepository::all( true );

		include NASM_OT_PATH . 'templates/admin/add-tracking.php';
	}

	/**
	 * Validate, save, and log a submitted tracking form.
	 *
	 * @param array|null $existing Existing order row when editing, null when creating.
	 * @return int|\WP_Error New/updated order id, or WP_Error on validation failure.
	 */
	protected function handle_submit( $existing ) {
		check_admin_referer( self::NONCE_ACTION, 'nasm_ot_nonce' );

		$data = Sanitizer::order_fields( wp_unslash( $_POST ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash

		if ( empty( $data['order_number'] ) ) {
			return new \WP_Error( 'nasm_ot_validation', __( 'Order number is required.', 'nasm-order-tracking' ) );
		}
		if ( empty( $data['last_name'] ) ) {
			return new \WP_Error( 'nasm_ot_validation', __( 'Customer last name is required.', 'nasm-order-tracking' ) );
		}

		$data['created_by'] = get_current_user_id();

		$previous_status = $existing['current_status'] ?? null;

		if ( $existing ) {
			$result   = OrdersRepository::update( $existing['id'], $data );
			$order_id = $existing['id'];
		} else {
			$result   = OrdersRepository::create( $data );
			$order_id = is_wp_error( $result ) ? 0 : $result;
		}

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$status_changed = ( null === $previous_status ) || ( $previous_status !== $data['current_status'] );

		if ( $status_changed ) {
			$occurred_at = '';
			if ( ! empty( $_POST['status_occurred_at'] ) ) {
				$raw = sanitize_text_field( wp_unslash( $_POST['status_occurred_at'] ) );
				$ts  = strtotime( $raw );
				if ( $ts ) {
					$occurred_at = gmdate( 'Y-m-d H:i:s', $ts );
				}
			}

			StatusHistoryRepository::add( $order_id, $data['current_status'], $data['public_note'], $occurred_at, get_current_user_id() );
			ActivityLogger::log(
				$existing ? 'order_status_updated' : 'order_created',
				'order',
				$order_id,
				sprintf( 'Status set to %s', $data['current_status'] )
			);
		} elseif ( ! $existing ) {
			ActivityLogger::log( 'order_created', 'order', $order_id, 'Order created.' );
		} else {
			ActivityLogger::log( 'order_updated', 'order', $order_id, 'Order details updated.' );
		}

		if ( ! empty( $data['notify_customer'] ) ) {
			$fresh_order = OrdersRepository::find( $order_id );
			$history     = StatusHistoryRepository::for_order( $order_id );
			$latest      = end( $history ) ?: array();
			$sent        = Mailer::send_status_update( $fresh_order, $latest ?: array() );
			ActivityLogger::log( $sent ? 'email_sent' : 'email_failed', 'order', $order_id, 'Notification on save.' );
		}

		return $order_id;
	}
}
