<?php
/**
 * Data access layer for the nasm_orders table.
 *
 * @package Nasm_Order_Tracking
 */

namespace Nasm\OrderTracking\Database;

defined( 'ABSPATH' ) || exit;

class OrdersRepository {

	/**
	 * Columns that may be written via create()/update().
	 *
	 * @var string[]
	 */
	protected static $writable_columns = array(
		'order_number',
		'first_name',
		'last_name',
		'email',
		'tracking_number',
		'carrier',
		'shipping_method',
		'warehouse',
		'delivery_address',
		'order_date',
		'estimated_delivery',
		'current_status',
		'admin_notes',
		'public_note',
		'notify_customer',
		'created_by',
	);

	/**
	 * Fetch a single order by primary key.
	 *
	 * @param int $id Order row id.
	 * @return array|null
	 */
	public static function find( $id ) {
		global $wpdb;

		$table = Installer::orders_table();
		$row   = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		return $row ?: null;
	}

	/**
	 * Fetch a single order by its human-readable order number.
	 *
	 * @param string $order_number Order number.
	 * @return array|null
	 */
	public static function find_by_order_number( $order_number ) {
		global $wpdb;

		$table = Installer::orders_table();
		$row   = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE order_number = %s", $order_number ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		return $row ?: null;
	}

	/**
	 * Customer-facing lookup: order number + last name (case-insensitive, trimmed).
	 *
	 * @param string $order_number Order number as entered by the customer.
	 * @param string $last_name    Last name as entered by the customer.
	 * @return array|null
	 */
	public static function find_for_customer( $order_number, $last_name ) {
		global $wpdb;

		$table = Installer::orders_table();
		$row   = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE order_number = %s AND LOWER(last_name) = LOWER(%s) LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				trim( $order_number ),
				trim( $last_name )
			),
			ARRAY_A
		);

		return $row ?: null;
	}

	/**
	 * Insert a new order/tracking record.
	 *
	 * @param array $data Associative array of column => value.
	 * @return int|\WP_Error New row id or WP_Error on failure.
	 */
	public static function create( array $data ) {
		global $wpdb;

		if ( empty( $data['order_number'] ) ) {
			return new \WP_Error( 'nasm_ot_missing_order_number', __( 'Order number is required.', 'nasm-order-tracking' ) );
		}

		if ( self::find_by_order_number( $data['order_number'] ) ) {
			return new \WP_Error( 'nasm_ot_duplicate_order_number', __( 'An order with this order number already exists.', 'nasm-order-tracking' ) );
		}

		$table = Installer::orders_table();
		$now   = current_time( 'mysql' );

		$row = self::filter_columns( $data );

		$row['created_at'] = $now;
		$row['updated_at'] = $now;

		$inserted = $wpdb->insert( $table, $row );

		if ( false === $inserted ) {
			return new \WP_Error( 'nasm_ot_db_error', __( 'Could not save the tracking record.', 'nasm-order-tracking' ) );
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Update an existing order/tracking record.
	 *
	 * @param int   $id   Order row id.
	 * @param array $data Associative array of column => value.
	 * @return bool|\WP_Error
	 */
	public static function update( $id, array $data ) {
		global $wpdb;

		$table = Installer::orders_table();
		$row   = self::filter_columns( $data );

		if ( isset( $row['order_number'] ) ) {
			$existing = self::find_by_order_number( $row['order_number'] );
			if ( $existing && (int) $existing['id'] !== (int) $id ) {
				return new \WP_Error( 'nasm_ot_duplicate_order_number', __( 'Another order already uses this order number.', 'nasm-order-tracking' ) );
			}
		}

		$row['updated_at'] = current_time( 'mysql' );

		$updated = $wpdb->update( $table, $row, array( 'id' => (int) $id ) );

		if ( false === $updated ) {
			return new \WP_Error( 'nasm_ot_db_error', __( 'Could not update the tracking record.', 'nasm-order-tracking' ) );
		}

		return true;
	}

	/**
	 * Delete an order and its status history.
	 *
	 * @param int $id Order row id.
	 * @return bool
	 */
	public static function delete( $id ) {
		global $wpdb;

		$history_table = Installer::history_table();
		$orders_table  = Installer::orders_table();

		$wpdb->delete( $history_table, array( 'order_id' => (int) $id ) );
		$deleted = $wpdb->delete( $orders_table, array( 'id' => (int) $id ) );

		return false !== $deleted;
	}

	/**
	 * Paginated, filterable, searchable list of orders for the admin table.
	 *
	 * @param array $args {
	 *     @type string $search    Free-text search across order number, name, email, tracking number.
	 *     @type string|string[] $status Filter by current_status slug, or an array of slugs (IN clause).
	 *     @type string $carrier   Filter by carrier.
	 *     @type string $date_from Y-m-d.
	 *     @type string $date_to   Y-m-d.
	 *     @type string $orderby   Column to sort by.
	 *     @type string $order     ASC|DESC.
	 *     @type int    $per_page  Rows per page.
	 *     @type int    $page      1-indexed page number.
	 * }
	 * @return array{items: array, total: int}
	 */
	public static function query( array $args = array() ) {
		global $wpdb;

		$defaults = array(
			'search'    => '',
			'status'    => '',
			'carrier'   => '',
			'date_from' => '',
			'date_to'   => '',
			'orderby'   => 'created_at',
			'order'     => 'DESC',
			'per_page'  => 20,
			'page'      => 1,
		);
		$args  = wp_parse_args( $args, $defaults );
		$table = Installer::orders_table();

		$where  = array( '1=1' );
		$params = array();

		if ( '' !== $args['search'] ) {
			$like     = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where[]  = '(order_number LIKE %s OR first_name LIKE %s OR last_name LIKE %s OR email LIKE %s OR tracking_number LIKE %s)';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		if ( is_array( $args['status'] ) && ! empty( $args['status'] ) ) {
			$placeholders = implode( ', ', array_fill( 0, count( $args['status'] ), '%s' ) );
			$where[]      = "current_status IN ({$placeholders})";
			foreach ( $args['status'] as $status_slug ) {
				$params[] = $status_slug;
			}
		} elseif ( is_string( $args['status'] ) && '' !== $args['status'] ) {
			$where[]  = 'current_status = %s';
			$params[] = $args['status'];
		}

		if ( '' !== $args['carrier'] ) {
			$where[]  = 'carrier = %s';
			$params[] = $args['carrier'];
		}

		if ( '' !== $args['date_from'] ) {
			$where[]  = 'order_date >= %s';
			$params[] = $args['date_from'];
		}

		if ( '' !== $args['date_to'] ) {
			$where[]  = 'order_date <= %s';
			$params[] = $args['date_to'];
		}

		$allowed_orderby = array( 'id', 'order_number', 'last_name', 'order_date', 'estimated_delivery', 'current_status', 'created_at', 'updated_at' );
		$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'created_at';
		$order           = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';

		$per_page = max( 1, (int) $args['per_page'] );
		$page     = max( 1, (int) $args['page'] );
		$offset   = ( $page - 1 ) * $per_page;

		$where_sql = implode( ' AND ', $where );

		$count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";
		if ( ! empty( $params ) ) {
			$total = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $params ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		} else {
			$total = (int) $wpdb->get_var( $count_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		$data_sql      = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
		$data_params   = array_merge( $params, array( $per_page, $offset ) );
		$items         = $wpdb->get_results( $wpdb->prepare( $data_sql, $data_params ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return array(
			'items' => $items ?: array(),
			'total' => $total,
		);
	}

	/**
	 * Counts of orders grouped by current_status, plus a couple of derived totals.
	 *
	 * @return array
	 */
	public static function dashboard_counts() {
		global $wpdb;

		$table   = Installer::orders_table();
		$results = $wpdb->get_results( "SELECT current_status, COUNT(*) as cnt FROM {$table} GROUP BY current_status", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$counts = array();
		foreach ( $results as $row ) {
			$counts[ $row['current_status'] ] = (int) $row['cnt'];
		}

		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$today_updates = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE DATE(updated_at) = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				current_time( 'Y-m-d' )
			)
		);

		return array(
			'total'         => $total,
			'by_status'     => $counts,
			'today_updates' => $today_updates,
		);
	}

	/**
	 * Recently created or updated orders for the dashboard activity widget.
	 *
	 * @param int $limit Number of rows to return.
	 * @return array
	 */
	public static function recent( $limit = 10 ) {
		global $wpdb;

		$table = Installer::orders_table();

		return $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} ORDER BY updated_at DESC LIMIT %d", $limit ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		) ?: array();
	}

	/**
	 * Distinct carrier names currently in use, for filter dropdowns.
	 *
	 * @return string[]
	 */
	public static function distinct_carriers() {
		global $wpdb;

		$table = Installer::orders_table();

		$rows = $wpdb->get_col( "SELECT DISTINCT carrier FROM {$table} WHERE carrier != '' ORDER BY carrier ASC" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return $rows ?: array();
	}

	/**
	 * Restrict an associative array to known writable columns and cast types.
	 *
	 * @param array $data Raw associative array.
	 * @return array
	 */
	protected static function filter_columns( array $data ) {
		$row = array();

		foreach ( self::$writable_columns as $column ) {
			if ( array_key_exists( $column, $data ) ) {
				$row[ $column ] = $data[ $column ];
			}
		}

		if ( isset( $row['notify_customer'] ) ) {
			$row['notify_customer'] = $row['notify_customer'] ? 1 : 0;
		}

		if ( isset( $row['created_by'] ) ) {
			$row['created_by'] = (int) $row['created_by'];
		}

		foreach ( array( 'order_date', 'estimated_delivery' ) as $date_field ) {
			if ( isset( $row[ $date_field ] ) && '' === $row[ $date_field ] ) {
				$row[ $date_field ] = null;
			}
		}

		return $row;
	}
}
