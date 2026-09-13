<?php
/**
 * WP_List_Table implementation for the Orders admin screen.
 *
 * @package Nasm_Order_Tracking
 */

namespace Nasm\OrderTracking\Admin;

use Nasm\OrderTracking\Database\OrdersRepository;
use Nasm\OrderTracking\Helpers\Formatter;
use Nasm\OrderTracking\Helpers\StatusBuckets;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( '\WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class OrdersTable extends \WP_List_Table {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'order',
				'plural'   => 'orders',
				'ajax'     => false,
			)
		);
	}

	/**
	 * Column definitions.
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'                 => '<input type="checkbox" />',
			'order_number'       => __( 'Order #', 'nasm-order-tracking' ),
			'customer'           => __( 'Customer', 'nasm-order-tracking' ),
			'tracking_number'    => __( 'Tracking #', 'nasm-order-tracking' ),
			'carrier'            => __( 'Carrier', 'nasm-order-tracking' ),
			'current_status'     => __( 'Status', 'nasm-order-tracking' ),
			'order_date'         => __( 'Order Date', 'nasm-order-tracking' ),
			'estimated_delivery' => __( 'Est. Delivery', 'nasm-order-tracking' ),
			'updated_at'         => __( 'Last Updated', 'nasm-order-tracking' ),
		);
	}

	/**
	 * Sortable columns.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		return array(
			'order_number'       => array( 'order_number', false ),
			'current_status'     => array( 'current_status', false ),
			'order_date'         => array( 'order_date', false ),
			'estimated_delivery' => array( 'estimated_delivery', false ),
			'updated_at'         => array( 'updated_at', true ),
		);
	}

	/**
	 * Bulk actions.
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		return array(
			'delete' => __( 'Delete', 'nasm-order-tracking' ),
		);
	}

	/**
	 * Checkbox column.
	 *
	 * @param array $item Row data.
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="order_ids[]" value="%d" />', (int) $item['id'] );
	}

	/**
	 * Default column renderer.
	 *
	 * @param array  $item        Row data.
	 * @param string $column_name Column key.
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'tracking_number':
			case 'carrier':
				return esc_html( $item[ $column_name ] );
			case 'order_date':
			case 'estimated_delivery':
				return $item[ $column_name ] ? esc_html( mysql2date( get_option( 'date_format' ), $item[ $column_name ] ) ) : '&#8212;';
			case 'updated_at':
				return esc_html( mysql2date( get_option( 'date_format' ) . ' g:i a', $item[ $column_name ] ) );
			default:
				return '';
		}
	}

	/**
	 * Order number column with row actions.
	 *
	 * @param array $item Row data.
	 * @return string
	 */
	public function column_order_number( $item ) {
		$view_url   = AdminMenu::url( 'order-details', array( 'order_id' => $item['id'] ) );
		$edit_url   = AdminMenu::url( 'add-tracking', array( 'order_id' => $item['id'] ) );
		$delete_url = wp_nonce_url( AdminMenu::url( 'orders', array( 'action' => 'delete', 'order_id' => $item['id'] ) ), 'nasm_ot_delete_order_' . $item['id'] );

		$actions = array(
			'view'   => sprintf( '<a href="%s">%s</a>', esc_url( $view_url ), esc_html__( 'View', 'nasm-order-tracking' ) ),
			'edit'   => sprintf( '<a href="%s">%s</a>', esc_url( $edit_url ), esc_html__( 'Edit', 'nasm-order-tracking' ) ),
			'delete' => sprintf( '<a href="%s" class="nasm-ot-delete-link" data-confirm="1">%s</a>', esc_url( $delete_url ), esc_html__( 'Delete', 'nasm-order-tracking' ) ),
		);

		return sprintf(
			'<a href="%s"><strong>#%s</strong></a>%s',
			esc_url( $view_url ),
			esc_html( $item['order_number'] ),
			$this->row_actions( $actions )
		);
	}

	/**
	 * Customer name + email column.
	 *
	 * @param array $item Row data.
	 * @return string
	 */
	public function column_customer( $item ) {
		$name = trim( $item['first_name'] . ' ' . $item['last_name'] );
		return sprintf( '%s<br><span class="description">%s</span>', esc_html( $name ), esc_html( $item['email'] ) );
	}

	/**
	 * Status badge column.
	 *
	 * @param array $item Row data.
	 * @return string
	 */
	public function column_current_status( $item ) {
		return Formatter::status_badge_html( $item['current_status'] );
	}

	/**
	 * Fetch and prepare rows for display.
	 */
	public function prepare_items() {
		$per_page = 20;
		$page     = $this->get_pagenum();

		$orderby = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'created_at'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$order   = isset( $_GET['order'] ) ? sanitize_key( wp_unslash( $_GET['order'] ) ) : 'desc'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		// A dashboard stat card can deep-link here with a "bucket" (a named
		// group of several status slugs, e.g. every "in transit" state)
		// instead of a single "status" slug.
		$status_filter = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['bucket'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$bucket_slugs = StatusBuckets::slugs( sanitize_key( wp_unslash( $_GET['bucket'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( ! empty( $bucket_slugs ) ) {
				$status_filter = $bucket_slugs;
			}
		}

		$args = array(
			'search'    => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'status'    => $status_filter,
			'carrier'   => isset( $_GET['carrier'] ) ? sanitize_text_field( wp_unslash( $_GET['carrier'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'date_from' => isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'date_to'   => isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'orderby'   => $orderby,
			'order'     => $order,
			'per_page'  => $per_page,
			'page'      => $page,
		);

		$result = OrdersRepository::query( $args );

		$this->items = $result['items'];

		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->set_pagination_args(
			array(
				'total_items' => $result['total'],
				'per_page'    => $per_page,
				'total_pages' => ceil( $result['total'] / $per_page ),
			)
		);
	}

	/**
	 * Message shown when there are no rows.
	 */
	public function no_items() {
		esc_html_e( 'No orders found.', 'nasm-order-tracking' );
	}
}
