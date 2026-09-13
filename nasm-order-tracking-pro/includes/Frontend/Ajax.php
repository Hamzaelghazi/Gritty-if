<?php
/**
 * AJAX endpoint powering the customer-facing order lookup.
 *
 * @package Nasm_Order_Tracking
 */

namespace Nasm\OrderTracking\Frontend;

use Nasm\OrderTracking\Database\OrdersRepository;
use Nasm\OrderTracking\Database\StatusHistoryRepository;
use Nasm\OrderTracking\Helpers\Timeline;
use Nasm\OrderTracking\Helpers\Settings;

defined( 'ABSPATH' ) || exit;

class Ajax {

	/**
	 * Register AJAX hooks for logged-in and guest users.
	 */
	public function register() {
		add_action( 'wp_ajax_nasm_ot_track_order', array( $this, 'handle_track_order' ) );
		add_action( 'wp_ajax_nopriv_nasm_ot_track_order', array( $this, 'handle_track_order' ) );
	}

	/**
	 * Look up an order by order number + last name and return rendered HTML.
	 */
	public function handle_track_order() {
		check_ajax_referer( 'nasm_ot_frontend', 'nonce' );

		$order_number = isset( $_POST['order_number'] ) ? sanitize_text_field( wp_unslash( $_POST['order_number'] ) ) : '';
		$last_name    = isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '';

		if ( '' === $order_number || '' === $last_name ) {
			wp_send_json_error( array( 'message' => __( 'Please enter both your order number and last name.', 'nasm-order-tracking' ) ), 400 );
		}

		// Basic rate limiting per IP to slow down enumeration attempts.
		$this->rate_limit_or_die();

		$order = OrdersRepository::find_for_customer( $order_number, $last_name );

		if ( ! $order ) {
			wp_send_json_error( array( 'message' => __( "We couldn't find an order matching that order number and last name. Please double-check your details and try again.", 'nasm-order-tracking' ) ), 404 );
		}

		$history  = StatusHistoryRepository::for_order( $order['id'] );
		$timeline = Timeline::build( $order, $history );
		$settings = Settings::all();

		ob_start();
		include NASM_OT_PATH . 'templates/frontend/results.php';
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}

	/**
	 * Naive transient-based rate limiter: 20 lookups per IP per 10 minutes.
	 */
	protected function rate_limit_or_die() {
		$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
		$key = 'nasm_ot_rl_' . md5( $ip );

		$count = (int) get_transient( $key );

		if ( $count >= 20 ) {
			wp_send_json_error( array( 'message' => __( 'Too many attempts. Please try again in a few minutes.', 'nasm-order-tracking' ) ), 429 );
		}

		set_transient( $key, $count + 1, 10 * MINUTE_IN_SECONDS );
	}
}
