<?php
/**
 * Manage the list of order statuses (icon, color, description, order).
 *
 * @package Nasm_Order_Tracking
 */

namespace Nasm\OrderTracking\Admin;

use Nasm\OrderTracking\Database\StatusesRepository;
use Nasm\OrderTracking\Database\ActivityLogger;

defined( 'ABSPATH' ) || exit;

class StatusesPage {

	const NONCE_ACTION = 'nasm_ot_save_status';

	/**
	 * Render the statuses screen, handling create/update/delete first.
	 */
	public function render() {
		if ( ! current_user_can( AdminMenu::CAPABILITY ) ) {
			return;
		}

		$errors = array();

		if ( ! empty( $_POST['nasm_ot_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nasm_ot_nonce'] ) ), self::NONCE_ACTION ) ) {
			$errors = $this->handle_submit();
		}

		if ( isset( $_GET['action'], $_GET['status_id'] ) && 'delete' === $_GET['action'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$status_id = absint( $_GET['status_id'] );
			check_admin_referer( 'nasm_ot_delete_status_' . $status_id );

			$result = StatusesRepository::delete( $status_id );
			if ( is_wp_error( $result ) ) {
				$errors[] = $result->get_error_message();
			} else {
				ActivityLogger::log( 'status_deleted', 'status', $status_id );
				wp_safe_redirect( AdminMenu::url( 'statuses', array( 'deleted' => 1 ) ) );
				exit;
			}
		}

		$statuses = StatusesRepository::all();

		include NASM_OT_PATH . 'templates/admin/statuses.php';
	}

	/**
	 * Handle create/update submission.
	 *
	 * @return string[] Error messages, empty on success (which redirects).
	 */
	protected function handle_submit() {
		check_admin_referer( self::NONCE_ACTION, 'nasm_ot_nonce' );

		$data = array(
			'label'       => sanitize_text_field( wp_unslash( $_POST['label'] ?? '' ) ),
			'slug'        => sanitize_key( wp_unslash( $_POST['slug'] ?? '' ) ),
			'icon'        => sanitize_text_field( wp_unslash( $_POST['icon'] ?? 'dashicons-marker' ) ),
			'color'       => sanitize_hex_color( wp_unslash( $_POST['color'] ?? '#2c6ecb' ) ),
			'description' => sanitize_text_field( wp_unslash( $_POST['description'] ?? '' ) ),
			'sort_order'  => isset( $_POST['sort_order'] ) ? absint( $_POST['sort_order'] ) : 0,
			'is_active'   => empty( $_POST['is_active'] ) ? 0 : 1,
		);

		$status_id = isset( $_POST['status_id'] ) ? absint( $_POST['status_id'] ) : 0;

		if ( $status_id ) {
			$result = StatusesRepository::update( $status_id, $data );
			$action = 'status_updated';
		} else {
			$result = StatusesRepository::create( $data );
			$action = 'status_created';
			$status_id = is_wp_error( $result ) ? 0 : $result;
		}

		if ( is_wp_error( $result ) ) {
			return array( $result->get_error_message() );
		}

		ActivityLogger::log( $action, 'status', $status_id, $data['label'] );

		wp_safe_redirect( AdminMenu::url( 'statuses', array( 'saved' => 1 ) ) );
		exit;
	}
}
