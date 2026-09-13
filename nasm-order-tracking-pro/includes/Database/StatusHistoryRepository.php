<?php
/**
 * Data access layer for the nasm_order_status_history table.
 *
 * @package Nasm_Order_Tracking
 */

namespace Nasm\OrderTracking\Database;

defined( 'ABSPATH' ) || exit;

class StatusHistoryRepository {

	/**
	 * Append a status history entry for an order. History rows are append-only;
	 * nothing ever overwrites or deletes a previous entry.
	 *
	 * @param int    $order_id   Order row id.
	 * @param string $status     Status slug.
	 * @param string $note       Optional note describing the update.
	 * @param string $occurred_at MySQL datetime string; defaults to now.
	 * @param int    $created_by  User id performing the update.
	 * @return int|false New row id, or false on failure.
	 */
	public static function add( $order_id, $status, $note = '', $occurred_at = '', $created_by = 0 ) {
		global $wpdb;

		$table = Installer::history_table();
		$now   = current_time( 'mysql' );

		$inserted = $wpdb->insert(
			$table,
			array(
				'order_id'    => (int) $order_id,
				'status'      => sanitize_key( $status ),
				'note'        => $note,
				'occurred_at' => $occurred_at ? $occurred_at : $now,
				'created_by'  => (int) $created_by,
				'created_at'  => $now,
			),
			array( '%d', '%s', '%s', '%s', '%d', '%s' )
		);

		return $inserted ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Full history for a given order, oldest first (natural timeline order).
	 *
	 * @param int $order_id Order row id.
	 * @return array
	 */
	public static function for_order( $order_id ) {
		global $wpdb;

		$table = Installer::history_table();

		return $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE order_id = %d ORDER BY occurred_at ASC, id ASC", $order_id ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		) ?: array();
	}

	/**
	 * Delete all history rows for an order (used when an order is deleted).
	 *
	 * @param int $order_id Order row id.
	 */
	public static function delete_for_order( $order_id ) {
		global $wpdb;

		$table = Installer::history_table();
		$wpdb->delete( $table, array( 'order_id' => (int) $order_id ) );
	}
}
