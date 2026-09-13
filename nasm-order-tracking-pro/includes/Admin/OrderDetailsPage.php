<?php
/**
 * Single order details screen: cards, timeline, notes, and row actions.
 *
 * @package Nasm_Order_Tracking
 */

namespace Nasm\OrderTracking\Admin;

use Nasm\OrderTracking\Database\OrdersRepository;
use Nasm\OrderTracking\Database\StatusHistoryRepository;
use Nasm\OrderTracking\Database\ActivityLogger;
use Nasm\OrderTracking\Helpers\Timeline;
use Nasm\OrderTracking\Emails\Mailer;

defined( 'ABSPATH' ) || exit;

class OrderDetailsPage {

	/**
	 * Render the order details screen.
	 */
	public function render() {
		if ( ! current_user_can( AdminMenu::CAPABILITY ) ) {
			return;
		}

		$order_id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$order    = $order_id ? OrdersRepository::find( $order_id ) : null;

		if ( ! $order ) {
			echo '<div class="wrap nasm-ot-wrap"><div class="notice notice-error"><p>' . esc_html__( 'Order not found.', 'nasm-order-tracking' ) . '</p></div></div>';
			return;
		}

		$error = $this->maybe_handle_actions( $order );

		$history  = StatusHistoryRepository::for_order( $order_id );
		$timeline = Timeline::build( $order, $history );

		include NASM_OT_PATH . 'templates/admin/order-details.php';
	}

	/**
	 * Handle duplicate/email/print-adjacent actions triggered from this screen.
	 *
	 * @param array $order Current order row.
	 * @return string Error message to display, if any (page continues rendering after non-redirect actions).
	 */
	protected function maybe_handle_actions( array $order ) {
		if ( ! isset( $_GET['do'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return '';
		}

		$do = sanitize_key( wp_unslash( $_GET['do'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( 'duplicate' === $do ) {
			check_admin_referer( 'nasm_ot_duplicate_' . $order['id'] );

			$copy                 = $order;
			unset( $copy['id'] );
			$copy['order_number'] = $order['order_number'] . '-COPY-' . wp_generate_password( 4, false );

			$new_id = OrdersRepository::create( $copy );

			if ( is_wp_error( $new_id ) ) {
				return $new_id->get_error_message();
			}

			ActivityLogger::log( 'order_duplicated', 'order', $new_id, sprintf( 'Duplicated from order #%s', $order['order_number'] ) );
			wp_safe_redirect( AdminMenu::url( 'add-tracking', array( 'order_id' => $new_id ) ) );
			exit;
		}

		if ( 'email' === $do ) {
			check_admin_referer( 'nasm_ot_email_' . $order['id'] );

			$history = StatusHistoryRepository::for_order( $order['id'] );
			$latest  = end( $history ) ?: array();
			$sent    = Mailer::send_status_update( $order, $latest ?: array() );

			ActivityLogger::log( $sent ? 'email_sent' : 'email_failed', 'order', $order['id'], 'Manual resend from order details screen.' );

			wp_safe_redirect( AdminMenu::url( 'order-details', array( 'order_id' => $order['id'], 'emailed' => $sent ? 1 : 0 ) ) );
			exit;
		}

		return '';
	}
}
