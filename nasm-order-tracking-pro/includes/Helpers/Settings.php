<?php
/**
 * Thin wrapper around the plugin's settings option.
 *
 * @package Nasm_Order_Tracking
 */

namespace Nasm\OrderTracking\Helpers;

defined( 'ABSPATH' ) || exit;

class Settings {

	const OPTION_KEY = 'nasm_ot_settings';

	/**
	 * Cached settings array for the current request.
	 *
	 * @var array|null
	 */
	protected static $cache = null;

	/**
	 * Get a single setting value.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Fallback value.
	 * @return mixed
	 */
	public static function get( $key, $default = '' ) {
		$all = self::all();
		return array_key_exists( $key, $all ) ? $all[ $key ] : $default;
	}

	/**
	 * Get all settings, merged with sane defaults so new keys never come back empty.
	 *
	 * @return array
	 */
	public static function all() {
		if ( null !== self::$cache ) {
			return self::$cache;
		}

		$defaults = array(
			'company_name'        => 'Nasm Inc',
			'company_logo_id'     => 0,
			'support_email'       => 'contact@nasminc.com',
			'support_phone'       => '+1 786 885 7387',
			'business_address'    => "3449 NW 36th St\nMiami, FL 33142\nUnited States",
			'warehouse_address'   => "11000 NW 121st Way\nMedley, FL 33178\nUnited States",
			'business_hours'      => "Mon–Fri: 9 AM – 6 PM\nSaturday: 10 AM – 4 PM\nSunday: Closed",
			'timezone'            => 'America/New_York',
			'processing_time'     => '3–5 Business Days',
			'freight_delivery'    => '8–12 Business Days',
			'free_shipping_note'  => 'Free Shipping',
			'brand_primary_color' => '#1b1f27',
			'brand_accent_color'  => '#1c61e7',
			'email_from_name'     => 'Nasm Inc',
			'email_from_address'  => 'contact@nasminc.com',
			'notify_on_update'    => 1,
			'delete_data_on_uninstall' => 0,

			// Automatic WooCommerce order syncing.
			'wc_sync_enabled'        => 1,
			'wc_sync_statuses'       => array( 'processing', 'on-hold', 'completed' ),
			'wc_sync_update_status'  => 1,
			'wc_sync_notify'         => 0,
			'wc_sync_default_status' => 'order_received',
			'wc_status_map'          => array(),
		);

		$saved = get_option( self::OPTION_KEY, array() );

		self::$cache = wp_parse_args( $saved, $defaults );

		return self::$cache;
	}

	/**
	 * Persist settings, merging with existing values.
	 *
	 * @param array $values New values to store.
	 */
	public static function update( array $values ) {
		$current       = self::all();
		$merged        = array_merge( $current, $values );
		self::$cache   = $merged;
		update_option( self::OPTION_KEY, $merged );
	}
}
