<?php
/**
 * Orders list screen: search, filter, bulk actions.
 *
 * @package Nasm_Order_Tracking
 */

namespace Nasm\OrderTracking\Admin;

use Nasm\OrderTracking\Database\OrdersRepository;
use Nasm\OrderTracking\Database\StatusesRepository;
use Nasm\OrderTracking\Database\ActivityLogger;

defined( 'ABSPATH' ) || exit;

class OrdersListPage {

	/**
	 * Render the orders screen, handling delete/bulk-delete actions first.
	 */
	public function render() {
		if ( ! current_user_can( AdminMenu::CAPABILITY ) ) {
			return;
		}

		$this->maybe_handle_actions();

		$table = new OrdersTable();
		$table->prepare_items();

		$statuses = StatusesRepository::all();
		$carriers = OrdersRepository::distinct_carriers();

		include NASM_OT_PATH . 'templates/admin/orders-list.php';
	}

	/**
	 * Handle single-row delete and bulk-delete requests.
	 */
	protected function maybe_handle_actions() {
		// Single delete via row action link.
		if ( isset( $_GET['action'], $_GET['order_id'] ) && 'delete' === $_GET['action'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$order_id = absint( $_GET['order_id'] );
			check_admin_referer( 'nasm_ot_delete_order_' . $order_id );

			OrdersRepository::delete( $order_id );
			ActivityLogger::log( 'order_deleted', 'order', $order_id, __( 'Order deleted from list screen.', 'nasm-order-tracking' ) );

			wp_safe_redirect( AdminMenu::url( 'orders', array( 'deleted' => 1 ) ) );
			exit;
		}

		// Bulk delete via the list table's bulk action form.
		if ( isset( $_POST['order_ids'] ) && isset( $_POST['action'] ) && 'delete' === $_POST['action'] ) {
			check_admin_referer( 'bulk-orders' );

			$ids = array_map( 'absint', (array) $_POST['order_ids'] );
			foreach ( $ids as $id ) {
				OrdersRepository::delete( $id );
				ActivityLogger::log( 'order_deleted', 'order', $id, __( 'Order deleted via bulk action.', 'nasm-order-tracking' ) );
			}

			wp_safe_redirect( AdminMenu::url( 'orders', array( 'deleted' => count( $ids ) ) ) );
			exit;
		}
	}
}
