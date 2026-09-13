<?php
/**
 * Central orchestrator. Wires admin, frontend, and email components together.
 *
 * @package Nasm_Order_Tracking
 */

namespace Nasm\OrderTracking\Core;

use Nasm\OrderTracking\Admin\AdminMenu;
use Nasm\OrderTracking\Admin\WcOrderSearch;
use Nasm\OrderTracking\Admin\WcSyncPage;
use Nasm\OrderTracking\Frontend\Shortcode;
use Nasm\OrderTracking\Frontend\Ajax;
use Nasm\OrderTracking\Database\Installer;
use Nasm\OrderTracking\WooCommerce\OrderSync;

defined( 'ABSPATH' ) || exit;

class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	protected static $instance = null;

	/**
	 * Get the shared instance.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Wire up all hooks. Safe to call once per request.
	 */
	public function run() {
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'admin_init', array( $this, 'maybe_upgrade_db' ) );
		add_action( 'nasm_ot_daily_maintenance', array( $this, 'run_daily_maintenance' ) );

		if ( is_admin() ) {
			( new AdminMenu() )->register();
			( new WcOrderSearch() )->register();
			( new WcSyncPage() )->register();
		}

		// Live order syncing must run on the front-end too, since orders are
		// created during customer checkout.
		( new OrderSync() )->register();

		( new Shortcode() )->register();
		( new Ajax() )->register();
	}

	/**
	 * Load plugin translations.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'nasm-order-tracking', false, dirname( NASM_OT_BASENAME ) . '/languages' );
	}

	/**
	 * Run dbDelta again if the plugin was updated and the schema changed.
	 */
	public function maybe_upgrade_db() {
		if ( get_option( 'nasm_ot_db_version' ) !== NASM_OT_DB_VERSION ) {
			Installer::install();
		}
	}

	/**
	 * Placeholder for scheduled housekeeping (e.g. flagging stale "in transit" orders as delayed).
	 * Intentionally conservative: it only logs, it never silently changes customer-facing status.
	 */
	public function run_daily_maintenance() {
		do_action( 'nasm_ot_daily_maintenance_run' );
	}
}
