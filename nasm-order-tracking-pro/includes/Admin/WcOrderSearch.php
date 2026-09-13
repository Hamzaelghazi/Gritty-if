<?php
/**
 * Admin-only AJAX endpoint that searches existing WooCommerce orders so the
 * Add Tracking screen can autofill order number/customer details instead of
 * making the admin retype data that already exists in WooCommerce.
 *
 * Deliberately read-only: it never writes to WooCommerce orders and never
 * stores a link back to them. The plugin's own tracking data still lives
 * entirely in its own tables, per the original "don't use WooCommerce order
 * meta for tracking data" design.
 *
 * @package Nasm_Order_Tracking
 */

namespace Nasm\OrderTracking\Admin;

defined( 'ABSPATH' ) || exit;

class WcOrderSearch {

	const ACTION = 'nasm_ot_search_wc_orders';

	/**
	 * Register the AJAX hook.
	 */
	public function register() {
		add_action( 'wp_ajax_' . self::ACTION, array( $this, 'handle' ) );
	}

	/**
	 * Return up to 10 WooCommerce orders matching a search term as JSON.
	 */
	public function handle() {
		check_ajax_referer( 'nasm_ot_admin', 'nonce' );

		if ( ! current_user_can( AdminMenu::CAPABILITY ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'nasm-order-tracking' ) ), 403 );
		}

		if ( ! function_exists( 'wc_get_orders' ) ) {
			wp_send_json_error( array( 'message' => __( 'WooCommerce is not active.', 'nasm-order-tracking' ) ), 400 );
		}

		$term = isset( $_GET['term'] ) ? sanitize_text_field( wp_unslash( $_GET['term'] ) ) : '';

		if ( strlen( $term ) < 2 ) {
			wp_send_json_success( array() );
		}

		$orders = wc_get_orders(
			array(
				's'       => $term,
				'limit'   => 10,
				'orderby' => 'date',
				'order'   => 'DESC',
				'type'    => 'shop_order',
			)
		);

		$results = array();

		foreach ( $orders as $order ) {
			$results[] = array(
				'order_number' => $order->get_order_number(),
				'first_name'   => $order->get_billing_first_name(),
				'last_name'    => $order->get_billing_last_name(),
				'email'        => $order->get_billing_email(),
				'date'         => $order->get_date_created() ? $order->get_date_created()->date_i18n( get_option( 'date_format' ) ) : '',
				'total'        => wp_strip_all_tags( $order->get_formatted_order_total() ),
			);
		}

		wp_send_json_success( $results );
	}
}
