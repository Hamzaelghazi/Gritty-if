<?php
/**
 * Shared top navigation for all Nasm Tracking admin screens.
 *
 * Expects $active to be set to one of: dashboard, orders, add-tracking,
 * statuses, wc-sync, settings, import, export, activity-logs.
 *
 * @package Nasm_Order_Tracking
 */

use Nasm\OrderTracking\Admin\AdminMenu;

defined( 'ABSPATH' ) || exit;

$active = $active ?? 'dashboard';

$tabs = array(
	'dashboard'     => __( 'Dashboard', 'nasm-order-tracking' ),
	'orders'        => __( 'Orders', 'nasm-order-tracking' ),
	'add-tracking'  => __( 'Add Tracking', 'nasm-order-tracking' ),
	'statuses'      => __( 'Statuses', 'nasm-order-tracking' ),
	'wc-sync'       => __( 'WooCommerce Sync', 'nasm-order-tracking' ),
	'settings'      => __( 'Settings', 'nasm-order-tracking' ),
	'import'        => __( 'Import', 'nasm-order-tracking' ),
	'export'        => __( 'Export', 'nasm-order-tracking' ),
	'activity-logs' => __( 'Activity Logs', 'nasm-order-tracking' ),
);
?>
<div class="nasm-ot-header">
	<div class="nasm-ot-header__brand">
		<span class="dashicons dashicons-location-alt"></span>
		<span><?php esc_html_e( 'Nasm Tracking', 'nasm-order-tracking' ); ?></span>
	</div>
	<nav class="nasm-ot-tabs">
		<?php foreach ( $tabs as $slug => $label ) : ?>
			<a href="<?php echo esc_url( AdminMenu::url( 'dashboard' === $slug ? '' : $slug ) ); ?>"
				class="nasm-ot-tabs__item <?php echo $active === $slug ? 'is-active' : ''; ?>">
				<?php echo esc_html( $label ); ?>
			</a>
		<?php endforeach; ?>
	</nav>
</div>
