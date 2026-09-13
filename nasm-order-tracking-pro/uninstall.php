<?php
/**
 * Uninstall routine. Only removes data if the site owner explicitly opted in
 * via Settings → "Delete all tracking data when this plugin is uninstalled".
 *
 * @package Nasm_Order_Tracking
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$settings = get_option( 'nasm_ot_settings', array() );

if ( empty( $settings['delete_data_on_uninstall'] ) ) {
	return;
}

global $wpdb;

$tables = array(
	$wpdb->prefix . 'nasm_order_status_history',
	$wpdb->prefix . 'nasm_orders',
	$wpdb->prefix . 'nasm_statuses',
	$wpdb->prefix . 'nasm_activity_log',
);

foreach ( $tables as $table ) {
	$wpdb->query( "DROP TABLE IF EXISTS `{$table}`" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

$tracking_page_id = get_option( 'nasm_ot_tracking_page_id' );
if ( $tracking_page_id ) {
	wp_delete_post( $tracking_page_id, true );
}

delete_option( 'nasm_ot_settings' );
delete_option( 'nasm_ot_db_version' );
delete_option( 'nasm_ot_tracking_page_id' );

wp_clear_scheduled_hook( 'nasm_ot_daily_maintenance' );
