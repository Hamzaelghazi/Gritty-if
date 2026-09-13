<?php
/**
 * Plugin Name:       Nasm Order Tracking Pro
 * Plugin URI:        https://nasminc.com
 * Description:       Custom, carrier-independent order tracking system for WooCommerce. Manually managed tracking timelines with a branded customer-facing tracking page.
 * Version:           1.1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Nasm Inc
 * Author URI:        https://nasminc.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       nasm-order-tracking
 * Domain Path:       /languages
 *
 * @package Nasm_Order_Tracking
 */

defined( 'ABSPATH' ) || exit;

define( 'NASM_OT_VERSION', '1.1.0' );
define( 'NASM_OT_FILE', __FILE__ );
define( 'NASM_OT_PATH', plugin_dir_path( __FILE__ ) );
define( 'NASM_OT_URL', plugin_dir_url( __FILE__ ) );
define( 'NASM_OT_BASENAME', plugin_basename( __FILE__ ) );
define( 'NASM_OT_DB_VERSION', '1.0.0' );

require_once NASM_OT_PATH . 'includes/Core/Autoloader.php';
\Nasm\OrderTracking\Core\Autoloader::register();

register_activation_hook( __FILE__, array( '\Nasm\OrderTracking\Core\Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( '\Nasm\OrderTracking\Core\Deactivator', 'deactivate' ) );

/**
 * Boot the plugin once all plugins are loaded so we can safely check for WooCommerce.
 */
function nasm_order_tracking_bootstrap() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'nasm_order_tracking_missing_woocommerce_notice' );
		return;
	}

	\Nasm\OrderTracking\Core\Plugin::instance()->run();
}
add_action( 'plugins_loaded', 'nasm_order_tracking_bootstrap' );

/**
 * Admin notice shown when WooCommerce is not active.
 */
function nasm_order_tracking_missing_woocommerce_notice() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	echo '<div class="notice notice-error"><p>' .
		esc_html__( 'Nasm Order Tracking Pro requires WooCommerce to be installed and active.', 'nasm-order-tracking' ) .
		'</p></div>';
}
