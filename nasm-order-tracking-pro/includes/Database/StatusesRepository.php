<?php
/**
 * Data access layer for the nasm_statuses table.
 *
 * @package Nasm_Order_Tracking
 */

namespace Nasm\OrderTracking\Database;

defined( 'ABSPATH' ) || exit;

class StatusesRepository {

	/**
	 * All statuses ordered for display.
	 *
	 * @param bool $active_only Only return active statuses.
	 * @return array
	 */
	public static function all( $active_only = false ) {
		global $wpdb;

		$table = Installer::statuses_table();
		$sql   = "SELECT * FROM {$table}";

		if ( $active_only ) {
			$sql .= ' WHERE is_active = 1';
		}

		$sql .= ' ORDER BY sort_order ASC, id ASC';

		return $wpdb->get_results( $sql, ARRAY_A ) ?: array(); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * All statuses keyed by slug for quick lookups.
	 *
	 * @return array
	 */
	public static function all_keyed() {
		$keyed = array();
		foreach ( self::all() as $status ) {
			$keyed[ $status['slug'] ] = $status;
		}
		return $keyed;
	}

	/**
	 * Fetch one status by slug.
	 *
	 * @param string $slug Status slug.
	 * @return array|null
	 */
	public static function find_by_slug( $slug ) {
		global $wpdb;

		$table = Installer::statuses_table();
		$row   = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE slug = %s", $slug ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		return $row ?: null;
	}

	/**
	 * Create a new custom status.
	 *
	 * @param array $data Status fields.
	 * @return int|\WP_Error
	 */
	public static function create( array $data ) {
		global $wpdb;

		$slug = sanitize_key( $data['slug'] ?? sanitize_title( $data['label'] ?? '' ) );

		if ( empty( $slug ) || empty( $data['label'] ) ) {
			return new \WP_Error( 'nasm_ot_invalid_status', __( 'A status needs at least a label.', 'nasm-order-tracking' ) );
		}

		if ( self::find_by_slug( $slug ) ) {
			return new \WP_Error( 'nasm_ot_duplicate_status', __( 'A status with this slug already exists.', 'nasm-order-tracking' ) );
		}

		$table = Installer::statuses_table();

		$max_sort   = (int) $wpdb->get_var( "SELECT MAX(sort_order) FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$sort_order = isset( $data['sort_order'] ) ? (int) $data['sort_order'] : $max_sort + 1;

		$inserted = $wpdb->insert(
			$table,
			array(
				'slug'        => $slug,
				'label'       => sanitize_text_field( $data['label'] ),
				'icon'        => sanitize_text_field( $data['icon'] ?? 'dashicons-marker' ),
				'color'       => sanitize_hex_color( $data['color'] ?? '#2c6ecb' ) ?: '#2c6ecb',
				'description' => sanitize_text_field( $data['description'] ?? '' ),
				'sort_order'  => $sort_order,
				'is_active'   => empty( $data['is_active'] ) ? 0 : 1,
				'is_system'   => 0,
			),
			array( '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d' )
		);

		return $inserted ? (int) $wpdb->insert_id : new \WP_Error( 'nasm_ot_db_error', __( 'Could not save the status.', 'nasm-order-tracking' ) );
	}

	/**
	 * Update an existing status. System statuses may have label/icon/color/description
	 * changed but not their slug.
	 *
	 * @param int   $id   Status row id.
	 * @param array $data Status fields.
	 * @return bool|\WP_Error
	 */
	public static function update( $id, array $data ) {
		global $wpdb;

		$table = Installer::statuses_table();
		$row   = array();

		foreach ( array( 'label', 'icon', 'color', 'description', 'sort_order', 'is_active' ) as $field ) {
			if ( array_key_exists( $field, $data ) ) {
				$row[ $field ] = $data[ $field ];
			}
		}

		if ( isset( $row['label'] ) ) {
			$row['label'] = sanitize_text_field( $row['label'] );
		}
		if ( isset( $row['icon'] ) ) {
			$row['icon'] = sanitize_text_field( $row['icon'] );
		}
		if ( isset( $row['color'] ) ) {
			$row['color'] = sanitize_hex_color( $row['color'] ) ?: '#2c6ecb';
		}
		if ( isset( $row['description'] ) ) {
			$row['description'] = sanitize_text_field( $row['description'] );
		}
		if ( isset( $row['sort_order'] ) ) {
			$row['sort_order'] = (int) $row['sort_order'];
		}
		if ( isset( $row['is_active'] ) ) {
			$row['is_active'] = $row['is_active'] ? 1 : 0;
		}

		$updated = $wpdb->update( $table, $row, array( 'id' => (int) $id ) );

		return false !== $updated ? true : new \WP_Error( 'nasm_ot_db_error', __( 'Could not update the status.', 'nasm-order-tracking' ) );
	}

	/**
	 * Delete a status. System (default) statuses cannot be deleted, only deactivated.
	 *
	 * @param int $id Status row id.
	 * @return bool|\WP_Error
	 */
	public static function delete( $id ) {
		global $wpdb;

		$table  = Installer::statuses_table();
		$status = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( ! $status ) {
			return new \WP_Error( 'nasm_ot_not_found', __( 'Status not found.', 'nasm-order-tracking' ) );
		}

		if ( ! empty( $status['is_system'] ) ) {
			return new \WP_Error( 'nasm_ot_system_status', __( 'Default statuses cannot be deleted, only deactivated.', 'nasm-order-tracking' ) );
		}

		$deleted = $wpdb->delete( $table, array( 'id' => (int) $id ) );

		return false !== $deleted ? true : new \WP_Error( 'nasm_ot_db_error', __( 'Could not delete the status.', 'nasm-order-tracking' ) );
	}
}
