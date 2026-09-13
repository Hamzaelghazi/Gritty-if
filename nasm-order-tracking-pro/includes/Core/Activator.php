<?php
/**
 * Runs on plugin activation.
 *
 * @package Nasm_Order_Tracking
 */

namespace Nasm\OrderTracking\Core;

use Nasm\OrderTracking\Database\Installer;

defined( 'ABSPATH' ) || exit;

class Activator {

	/**
	 * Create tables, seed defaults, and set up default settings/pages.
	 */
	public static function activate() {
		Installer::install();
		self::maybe_create_tracking_page();
		self::maybe_set_default_settings();

		if ( ! wp_next_scheduled( 'nasm_ot_daily_maintenance' ) ) {
			wp_schedule_event( time(), 'daily', 'nasm_ot_daily_maintenance' );
		}

		flush_rewrite_rules();
	}

	/**
	 * Create a "Track Order" page containing the shortcode if one doesn't exist yet.
	 *
	 * Never silently takes over an existing page at the "track-order" slug that
	 * doesn't already contain our shortcode: a store can easily already have a
	 * page there (e.g. WooCommerce's own [woocommerce_order_tracking] widget),
	 * and adopting it without inserting our shortcode would leave the "tracking
	 * page" setting pointing at a URL that silently keeps showing the old form.
	 */
	protected static function maybe_create_tracking_page() {
		$page_id      = get_option( 'nasm_ot_tracking_page_id' );
		$current_page = $page_id ? get_post( $page_id ) : null;

		if ( $current_page && self::has_shortcode( $current_page ) ) {
			return;
		}

		$existing = get_page_by_path( 'track-order' );

		if ( $existing && self::has_shortcode( $existing ) ) {
			update_option( 'nasm_ot_tracking_page_id', $existing->ID );
			return;
		}

		$slug = 'track-order';

		if ( $existing ) {
			// Slug is taken by a page with different content; don't touch it.
			// Fall back to a distinct slug and flag the conflict for review.
			$slug = 'order-tracking';
			update_option( 'nasm_ot_tracking_page_conflict_id', $existing->ID );
		}

		$page_id = wp_insert_post(
			array(
				'post_title'   => __( 'Track Your Order', 'nasm-order-tracking' ),
				'post_name'    => $slug,
				'post_content' => '[nasm_order_tracking]',
				'post_status'  => 'publish',
				'post_type'    => 'page',
			)
		);

		if ( $page_id && ! is_wp_error( $page_id ) ) {
			update_option( 'nasm_ot_tracking_page_id', $page_id );
		}
	}

	/**
	 * Whether a page's content already contains our shortcode.
	 *
	 * @param \WP_Post|null $page Page post object.
	 * @return bool
	 */
	protected static function has_shortcode( $page ) {
		return $page instanceof \WP_Post && has_shortcode( $page->post_content, 'nasm_order_tracking' );
	}

	/**
	 * Seed default plugin settings from the store's published shipping policy.
	 */
	protected static function maybe_set_default_settings() {
		if ( get_option( 'nasm_ot_settings' ) ) {
			return;
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
		);

		update_option( 'nasm_ot_settings', $defaults );
	}
}
