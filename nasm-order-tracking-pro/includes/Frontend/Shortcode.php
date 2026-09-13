<?php
/**
 * [nasm_order_tracking] shortcode: renders the customer-facing search form.
 *
 * @package Nasm_Order_Tracking
 */

namespace Nasm\OrderTracking\Frontend;

use Nasm\OrderTracking\Helpers\Settings;

defined( 'ABSPATH' ) || exit;

class Shortcode {

	const TAG = 'nasm_order_tracking';

	/**
	 * Register the shortcode.
	 */
	public function register() {
		add_shortcode( self::TAG, array( $this, 'render' ) );
	}

	/**
	 * Enqueue frontend assets only when the shortcode is actually used on the page.
	 */
	public function enqueue_assets() {
		wp_enqueue_style( 'nasm-ot-frontend', NASM_OT_URL . 'assets/css/frontend.css', array(), NASM_OT_VERSION );
		wp_enqueue_script( 'nasm-ot-frontend', NASM_OT_URL . 'assets/js/frontend.js', array(), NASM_OT_VERSION, true );

		wp_localize_script(
			'nasm-ot-frontend',
			'nasmOtFrontend',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'nasm_ot_frontend' ),
				'i18n'    => array(
					'notFound' => __( "We couldn't find an order matching that order number and last name. Please double-check your details and try again.", 'nasm-order-tracking' ),
					'error'    => __( 'Something went wrong. Please try again in a moment.', 'nasm-order-tracking' ),
					'loading'  => __( 'Searching…', 'nasm-order-tracking' ),
				),
			)
		);
	}

	/**
	 * Render the shortcode output.
	 *
	 * @return string
	 */
	public function render() {
		$this->enqueue_assets();

		$settings = Settings::all();

		ob_start();
		include NASM_OT_PATH . 'templates/frontend/search-form.php';
		return ob_get_clean();
	}
}
