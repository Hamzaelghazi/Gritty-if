<?php
/**
 * Central place for sanitizing tracking-record form input.
 *
 * @package Nasm_Order_Tracking
 */

namespace Nasm\OrderTracking\Helpers;

defined( 'ABSPATH' ) || exit;

class Sanitizer {

	/**
	 * Sanitize a raw $_POST array into a safe order/tracking data array.
	 *
	 * @param array $input Raw request data.
	 * @return array
	 */
	public static function order_fields( array $input ) {
		$data = array(
			'order_number'       => sanitize_text_field( $input['order_number'] ?? '' ),
			'first_name'         => sanitize_text_field( $input['first_name'] ?? '' ),
			'last_name'          => sanitize_text_field( $input['last_name'] ?? '' ),
			'email'              => sanitize_email( $input['email'] ?? '' ),
			'tracking_number'    => sanitize_text_field( $input['tracking_number'] ?? '' ),
			'carrier'            => sanitize_text_field( $input['carrier'] ?? '' ),
			'shipping_method'    => sanitize_text_field( $input['shipping_method'] ?? '' ),
			'warehouse'          => sanitize_text_field( $input['warehouse'] ?? '' ),
			'delivery_address'   => sanitize_textarea_field( $input['delivery_address'] ?? '' ),
			'order_date'         => self::sanitize_date( $input['order_date'] ?? '' ),
			'estimated_delivery' => self::sanitize_date( $input['estimated_delivery'] ?? '' ),
			'current_status'     => sanitize_key( $input['current_status'] ?? 'order_received' ),
			'admin_notes'        => sanitize_textarea_field( $input['admin_notes'] ?? '' ),
			'public_note'        => sanitize_textarea_field( $input['public_note'] ?? '' ),
			'notify_customer'    => empty( $input['notify_customer'] ) ? 0 : 1,
		);

		return $data;
	}

	/**
	 * Sanitize a Y-m-d date string; returns '' if invalid.
	 *
	 * @param string $value Raw date string.
	 * @return string
	 */
	public static function sanitize_date( $value ) {
		$value = sanitize_text_field( $value );

		if ( '' === $value ) {
			return '';
		}

		$date = \DateTime::createFromFormat( 'Y-m-d', $value );

		return ( $date && $date->format( 'Y-m-d' ) === $value ) ? $value : '';
	}
}
