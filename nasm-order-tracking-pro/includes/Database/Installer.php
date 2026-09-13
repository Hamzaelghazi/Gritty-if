<?php
/**
 * Creates and upgrades the custom database tables used by the plugin.
 *
 * @package Nasm_Order_Tracking
 */

namespace Nasm\OrderTracking\Database;

defined( 'ABSPATH' ) || exit;

class Installer {

	/**
	 * Create/upgrade all custom tables using dbDelta and seed default statuses.
	 */
	public static function install() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();

		$orders_table   = self::orders_table();
		$history_table  = self::history_table();
		$statuses_table = self::statuses_table();
		$logs_table     = self::logs_table();

		$sql = "CREATE TABLE {$orders_table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			order_number VARCHAR(64) NOT NULL,
			first_name VARCHAR(100) NOT NULL DEFAULT '',
			last_name VARCHAR(100) NOT NULL DEFAULT '',
			email VARCHAR(190) NOT NULL DEFAULT '',
			tracking_number VARCHAR(100) NOT NULL DEFAULT '',
			carrier VARCHAR(100) NOT NULL DEFAULT '',
			shipping_method VARCHAR(100) NOT NULL DEFAULT '',
			warehouse VARCHAR(190) NOT NULL DEFAULT '',
			delivery_address TEXT NULL,
			order_date DATE NULL,
			estimated_delivery DATE NULL,
			current_status VARCHAR(64) NOT NULL DEFAULT 'order_received',
			admin_notes TEXT NULL,
			public_note TEXT NULL,
			notify_customer TINYINT(1) NOT NULL DEFAULT 0,
			created_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY order_number (order_number),
			KEY last_name (last_name),
			KEY email (email),
			KEY tracking_number (tracking_number),
			KEY current_status (current_status),
			KEY carrier (carrier)
		) {$charset_collate};";

		$sql .= "CREATE TABLE {$history_table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			order_id BIGINT UNSIGNED NOT NULL,
			status VARCHAR(64) NOT NULL,
			note TEXT NULL,
			occurred_at DATETIME NOT NULL,
			created_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY order_id (order_id),
			KEY status (status)
		) {$charset_collate};";

		$sql .= "CREATE TABLE {$statuses_table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			slug VARCHAR(64) NOT NULL,
			label VARCHAR(150) NOT NULL,
			icon VARCHAR(64) NOT NULL DEFAULT 'dashicons-yes',
			color VARCHAR(20) NOT NULL DEFAULT '#2c6ecb',
			description VARCHAR(255) NOT NULL DEFAULT '',
			sort_order INT NOT NULL DEFAULT 0,
			is_active TINYINT(1) NOT NULL DEFAULT 1,
			is_system TINYINT(1) NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			UNIQUE KEY slug (slug)
		) {$charset_collate};";

		$sql .= "CREATE TABLE {$logs_table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			action VARCHAR(100) NOT NULL,
			object_type VARCHAR(50) NOT NULL DEFAULT '',
			object_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			details TEXT NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY object (object_type, object_id),
			KEY created_at (created_at)
		) {$charset_collate};";

		dbDelta( $sql );

		self::seed_default_statuses();

		update_option( 'nasm_ot_db_version', NASM_OT_DB_VERSION );
	}

	/**
	 * Insert the default status set if the statuses table is empty.
	 */
	protected static function seed_default_statuses() {
		global $wpdb;

		$table = self::statuses_table();
		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( $count > 0 ) {
			return;
		}

		$defaults = array(
			array( 'order_received', __( 'Order Received', 'nasm-order-tracking' ), 'dashicons-yes', '#8a94a6', 1 ),
			array( 'payment_confirmed', __( 'Payment Confirmed', 'nasm-order-tracking' ), 'dashicons-yes-alt', '#8a94a6', 2 ),
			array( 'processing', __( 'Order Processing', 'nasm-order-tracking' ), 'dashicons-update', '#2c6ecb', 3 ),
			array( 'preparing_shipment', __( 'Preparing Shipment', 'nasm-order-tracking' ), 'dashicons-archive', '#2c6ecb', 4 ),
			array( 'picked_up', __( 'Picked Up By Carrier', 'nasm-order-tracking' ), 'dashicons-car', '#2c6ecb', 5 ),
			array( 'in_transit', __( 'In Transit', 'nasm-order-tracking' ), 'dashicons-airplane', '#2c6ecb', 6 ),
			array( 'arrived_hub', __( 'Arrived At Distribution Center', 'nasm-order-tracking' ), 'dashicons-location', '#2c6ecb', 7 ),
			array( 'out_for_delivery', __( 'Out For Delivery', 'nasm-order-tracking' ), 'dashicons-migrate', '#e08a1e', 8 ),
			array( 'delivered', __( 'Delivered', 'nasm-order-tracking' ), 'dashicons-saved', '#2fa84f', 9 ),
			array( 'delayed', __( 'Delayed', 'nasm-order-tracking' ), 'dashicons-warning', '#d94f4f', 10 ),
			array( 'returned', __( 'Returned', 'nasm-order-tracking' ), 'dashicons-undo', '#8a4fd9', 11 ),
			array( 'cancelled', __( 'Cancelled', 'nasm-order-tracking' ), 'dashicons-dismiss', '#8a94a6', 12 ),
		);

		$now = current_time( 'mysql' );

		foreach ( $defaults as $status ) {
			$wpdb->insert(
				$table,
				array(
					'slug'        => $status[0],
					'label'       => $status[1],
					'icon'        => $status[2],
					'color'       => $status[3],
					'sort_order'  => $status[4],
					'description' => '',
					'is_active'   => 1,
					'is_system'   => 1,
				),
				array( '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%d' )
			);
		}

		unset( $now );
	}

	/**
	 * Orders table name.
	 *
	 * @return string
	 */
	public static function orders_table() {
		global $wpdb;
		return $wpdb->prefix . 'nasm_orders';
	}

	/**
	 * Status history table name.
	 *
	 * @return string
	 */
	public static function history_table() {
		global $wpdb;
		return $wpdb->prefix . 'nasm_order_status_history';
	}

	/**
	 * Statuses table name.
	 *
	 * @return string
	 */
	public static function statuses_table() {
		global $wpdb;
		return $wpdb->prefix . 'nasm_statuses';
	}

	/**
	 * Activity log table name.
	 *
	 * @return string
	 */
	public static function logs_table() {
		global $wpdb;
		return $wpdb->prefix . 'nasm_activity_log';
	}
}
