<?php
/**
 * Data access layer for the nasm_activity_log table.
 *
 * @package Nasm_Order_Tracking
 */

namespace Nasm\OrderTracking\Database;

defined( 'ABSPATH' ) || exit;

class ActivityLogger {

	/**
	 * Record an admin/system action.
	 *
	 * @param string $action      Short machine-readable action key, e.g. 'order_created'.
	 * @param string $object_type Type of object affected, e.g. 'order', 'status'.
	 * @param int    $object_id   Related row id.
	 * @param string $details     Human-readable details.
	 * @param int    $user_id     Defaults to the current user.
	 */
	public static function log( $action, $object_type = '', $object_id = 0, $details = '', $user_id = null ) {
		global $wpdb;

		if ( null === $user_id ) {
			$user_id = get_current_user_id();
		}

		$table = Installer::logs_table();

		$wpdb->insert(
			$table,
			array(
				'user_id'     => (int) $user_id,
				'action'      => sanitize_key( $action ),
				'object_type' => sanitize_key( $object_type ),
				'object_id'   => (int) $object_id,
				'details'     => $details,
				'created_at'  => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%d', '%s', '%s' )
		);
	}

	/**
	 * Paginated activity log entries, most recent first.
	 *
	 * @param int $per_page Rows per page.
	 * @param int $page     1-indexed page number.
	 * @return array{items: array, total: int}
	 */
	public static function query( $per_page = 30, $page = 1 ) {
		global $wpdb;

		$table    = Installer::logs_table();
		$per_page = max( 1, (int) $per_page );
		$page     = max( 1, (int) $page );
		$offset   = ( $page - 1 ) * $per_page;

		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$items = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d OFFSET %d", $per_page, $offset ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		return array(
			'items' => $items ?: array(),
			'total' => $total,
		);
	}

	/**
	 * Most recent entries for the dashboard widget.
	 *
	 * @param int $limit Number of rows.
	 * @return array
	 */
	public static function recent( $limit = 8 ) {
		$result = self::query( $limit, 1 );
		return $result['items'];
	}
}
