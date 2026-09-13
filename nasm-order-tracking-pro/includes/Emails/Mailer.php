<?php
/**
 * Sends branded status-update emails to customers.
 *
 * @package Nasm_Order_Tracking
 */

namespace Nasm\OrderTracking\Emails;

use Nasm\OrderTracking\Helpers\Settings;
use Nasm\OrderTracking\Helpers\Formatter;

defined( 'ABSPATH' ) || exit;

class Mailer {

	/**
	 * Send a "your order has been updated" email to the customer on an order.
	 *
	 * @param array $order Order row (array).
	 * @param array $latest_history Most recent status history row, if any.
	 * @return bool
	 */
	public static function send_status_update( array $order, array $latest_history = array() ) {
		if ( empty( $order['email'] ) || ! is_email( $order['email'] ) ) {
			return false;
		}

		$settings = Settings::all();
		$label    = Formatter::status_label( $order['current_status'] );

		$subject = sprintf(
			/* translators: %s: order number */
			__( 'Your Order #%s Has Been Updated', 'nasm-order-tracking' ),
			$order['order_number']
		);

		$tracking_url = self::tracking_page_url();

		add_filter( 'wp_mail_from', array( __CLASS__, 'filter_from_address' ) );
		add_filter( 'wp_mail_from_name', array( __CLASS__, 'filter_from_name' ) );
		add_filter( 'wp_mail_content_type', array( __CLASS__, 'filter_content_type' ) );

		$body = self::render_template(
			array(
				'order'        => $order,
				'status_label' => $label,
				'note'         => $latest_history['note'] ?? $order['public_note'],
				'settings'     => $settings,
				'tracking_url' => $tracking_url,
			)
		);

		$sent = wp_mail( $order['email'], $subject, $body );

		remove_filter( 'wp_mail_from', array( __CLASS__, 'filter_from_address' ) );
		remove_filter( 'wp_mail_from_name', array( __CLASS__, 'filter_from_name' ) );
		remove_filter( 'wp_mail_content_type', array( __CLASS__, 'filter_content_type' ) );

		return $sent;
	}

	/**
	 * URL of the public tracking page.
	 *
	 * @return string
	 */
	protected static function tracking_page_url() {
		$page_id = get_option( 'nasm_ot_tracking_page_id' );
		if ( $page_id && get_post( $page_id ) ) {
			return get_permalink( $page_id );
		}
		return home_url( '/track-order/' );
	}

	/**
	 * Render the HTML email body from the template file.
	 *
	 * @param array $vars Variables available to the template.
	 * @return string
	 */
	protected static function render_template( array $vars ) {
		$order        = $vars['order'];
		$status_label = $vars['status_label'];
		$note         = $vars['note'];
		$settings     = $vars['settings'];
		$tracking_url = $vars['tracking_url'];

		ob_start();
		include NASM_OT_PATH . 'templates/emails/status-update.php';
		return ob_get_clean();
	}

	/**
	 * wp_mail_from filter callback.
	 *
	 * @return string
	 */
	public static function filter_from_address() {
		return sanitize_email( Settings::get( 'email_from_address', get_option( 'admin_email' ) ) );
	}

	/**
	 * wp_mail_from_name filter callback.
	 *
	 * @return string
	 */
	public static function filter_from_name() {
		return Settings::get( 'email_from_name', get_bloginfo( 'name' ) );
	}

	/**
	 * wp_mail_content_type filter callback.
	 *
	 * @return string
	 */
	public static function filter_content_type() {
		return 'text/html';
	}
}
