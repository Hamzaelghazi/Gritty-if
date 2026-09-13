<?php
/**
 * "WooCommerce Sync" admin screen.
 *
 * One place to switch automatic order importing on/off, choose which
 * WooCommerce statuses import (and how they map to tracking statuses), and
 * run a one-time catch-up import of orders that already exist in WooCommerce.
 *
 * @package Nasm_Order_Tracking
 */

namespace Nasm\OrderTracking\Admin;

use Nasm\OrderTracking\Helpers\Settings;
use Nasm\OrderTracking\Database\ActivityLogger;
use Nasm\OrderTracking\Database\StatusesRepository;
use Nasm\OrderTracking\WooCommerce\OrderSync;

defined( 'ABSPATH' ) || exit;

class WcSyncPage {

	const NONCE_ACTION   = 'nasm_ot_save_wc_sync';
	const BACKFILL_ACTION = 'nasm_ot_wc_backfill';

	/**
	 * Register the backfill AJAX endpoint.
	 */
	public function register() {
		add_action( 'wp_ajax_' . self::BACKFILL_ACTION, array( $this, 'handle_backfill' ) );
	}

	/**
	 * Render the screen, saving submitted settings first.
	 */
	public function render() {
		if ( ! current_user_can( AdminMenu::CAPABILITY ) ) {
			return;
		}

		$saved = false;

		if ( ! empty( $_POST['nasm_ot_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nasm_ot_nonce'] ) ), self::NONCE_ACTION ) ) {
			$this->handle_submit();
			$saved = true;
		}

		$settings     = Settings::all();
		$sync         = new OrderSync();
		$wc_statuses  = function_exists( 'wc_get_order_statuses' ) ? wc_get_order_statuses() : array();
		$statuses     = StatusesRepository::all( true );
		$status_map   = $sync->status_map();
		$trigger      = $sync->trigger_statuses();
		$backfill_total = $sync->backfill_total();

		include NASM_OT_PATH . 'templates/admin/wc-sync.php';
	}

	/**
	 * Sanitize and persist the sync settings.
	 */
	protected function handle_submit() {
		check_admin_referer( self::NONCE_ACTION, 'nasm_ot_nonce' );

		$values = array(
			'wc_sync_enabled'        => empty( $_POST['wc_sync_enabled'] ) ? 0 : 1,
			'wc_sync_update_status'  => empty( $_POST['wc_sync_update_status'] ) ? 0 : 1,
			'wc_sync_notify'         => empty( $_POST['wc_sync_notify'] ) ? 0 : 1,
			'wc_sync_default_status' => isset( $_POST['wc_sync_default_status'] ) ? sanitize_key( wp_unslash( $_POST['wc_sync_default_status'] ) ) : 'order_received',
		);

		// Which WooCommerce statuses trigger an import.
		$trigger = array();
		if ( ! empty( $_POST['wc_sync_statuses'] ) && is_array( $_POST['wc_sync_statuses'] ) ) {
			foreach ( wp_unslash( $_POST['wc_sync_statuses'] ) as $status ) {
				$slug = sanitize_key( $status );
				$slug = 0 === strpos( $slug, 'wc-' ) ? substr( $slug, 3 ) : $slug;
				if ( '' !== $slug ) {
					$trigger[] = $slug;
				}
			}
		}
		$values['wc_sync_statuses'] = array_values( array_unique( $trigger ) );

		// WooCommerce status → tracking status map.
		$map = array();
		if ( ! empty( $_POST['wc_status_map'] ) && is_array( $_POST['wc_status_map'] ) ) {
			foreach ( wp_unslash( $_POST['wc_status_map'] ) as $wc_status => $plugin_slug ) {
				$wc_key = sanitize_key( $wc_status );
				$wc_key = 0 === strpos( $wc_key, 'wc-' ) ? substr( $wc_key, 3 ) : $wc_key;
				if ( '' !== $wc_key ) {
					$map[ $wc_key ] = sanitize_key( $plugin_slug );
				}
			}
		}
		$values['wc_status_map'] = $map;

		Settings::update( $values );
		ActivityLogger::log( 'wc_sync_settings_updated', 'settings', 0 );
	}

	/**
	 * AJAX: import one batch of existing WooCommerce orders.
	 *
	 * The front-end calls this repeatedly with an advancing offset until the
	 * response reports done, so a large store never imports in one long request.
	 */
	public function handle_backfill() {
		check_ajax_referer( 'nasm_ot_admin', 'nonce' );

		if ( ! current_user_can( AdminMenu::CAPABILITY ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'nasm-order-tracking' ) ), 403 );
		}

		if ( ! function_exists( 'wc_get_orders' ) ) {
			wp_send_json_error( array( 'message' => __( 'WooCommerce is not active.', 'nasm-order-tracking' ) ), 400 );
		}

		$offset = isset( $_POST['offset'] ) ? max( 0, absint( $_POST['offset'] ) ) : 0;
		$batch  = isset( $_POST['batch'] ) ? absint( $_POST['batch'] ) : 20;
		$batch  = min( 50, max( 1, $batch ) );

		$sync    = new OrderSync();
		$summary = $sync->backfill( $batch, $offset );

		$next_offset = $offset + $summary['processed'];
		$done        = $summary['processed'] < $batch || $next_offset >= $summary['total'];

		if ( $done ) {
			ActivityLogger::log( 'wc_backfill_completed', 'order', 0, sprintf( 'Backfill finished at offset %d.', $next_offset ) );
		}

		wp_send_json_success(
			array(
				'processed'  => $summary['processed'],
				'created'    => $summary['created'],
				'exists'     => $summary['exists'],
				'ignored'    => $summary['ignored'],
				'errors'     => $summary['errors'],
				'total'      => $summary['total'],
				'nextOffset' => $next_offset,
				'done'       => $done,
			)
		);
	}
}
