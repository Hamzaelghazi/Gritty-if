<?php
/**
 * Runs on plugin deactivation. Does not drop tables or delete data.
 *
 * @package Nasm_Order_Tracking
 */

namespace Nasm\OrderTracking\Core;

defined( 'ABSPATH' ) || exit;

class Deactivator {

	/**
	 * Clear scheduled events. Data and tables are intentionally preserved;
	 * full removal only happens via uninstall.php when the plugin is deleted.
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( 'nasm_ot_daily_maintenance' );
		flush_rewrite_rules();
	}
}
