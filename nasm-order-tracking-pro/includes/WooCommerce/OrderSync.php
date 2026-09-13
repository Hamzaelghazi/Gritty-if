<?php
/**
 * Automatic WooCommerce → tracking-record sync.
 *
 * This is what turns the plugin from "type every order in by hand" into
 * "orders show up on their own". It listens to WooCommerce order events and
 * creates a matching tracking record in the plugin's own tables, and (optionally)
 * keeps the tracking status in step when the WooCommerce order status changes.
 *
 * It still respects the original design rule: tracking data lives in the
 * plugin's own tables, never in WooCommerce order meta. The link between a
 * WooCommerce order and its tracking record is the order number (the existing
 * UNIQUE key on the orders table), so no schema change is required and imports
 * are naturally idempotent — the same order is never tracked twice.
 *
 * @package Nasm_Order_Tracking
 */

namespace Nasm\OrderTracking\WooCommerce;

use Nasm\OrderTracking\Database\OrdersRepository;
use Nasm\OrderTracking\Database\StatusHistoryRepository;
use Nasm\OrderTracking\Database\ActivityLogger;
use Nasm\OrderTracking\Helpers\Settings;
use Nasm\OrderTracking\Emails\Mailer;

defined( 'ABSPATH' ) || exit;

class OrderSync {

	/**
	 * Default mapping of WooCommerce order status (without the "wc-" prefix)
	 * to a plugin tracking status slug. An empty string means "don't sync".
	 *
	 * @var array<string,string>
	 */
	const DEFAULT_STATUS_MAP = array(
		'pending'        => 'order_received',
		'processing'     => 'processing',
		'on-hold'        => 'order_received',
		'completed'      => 'delivered',
		'cancelled'      => 'cancelled',
		'refunded'       => 'returned',
		'failed'         => 'order_received',
		'checkout-draft' => '',
	);

	/**
	 * Register the WooCommerce hooks. Runs on every request (not admin-only)
	 * because new orders are created on the front-end checkout.
	 */
	public function register() {
		// A brand-new order (checkout, REST API, admin "Add order", etc.).
		add_action( 'woocommerce_new_order', array( $this, 'handle_new_order' ), 20, 1 );
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'handle_new_order' ), 20, 1 );
		add_action( 'woocommerce_store_api_checkout_order_processed', array( $this, 'handle_new_order_object' ), 20, 1 );

		// Later status transitions (pending → processing → completed, …).
		add_action( 'woocommerce_order_status_changed', array( $this, 'handle_status_changed' ), 20, 4 );
	}

	/* --------------------------------------------------------------------- *
	 * Hook callbacks
	 * --------------------------------------------------------------------- */

	/**
	 * Import a freshly created order, if auto-sync is on and its status qualifies.
	 *
	 * @param int $order_id WooCommerce order id.
	 */
	public function handle_new_order( $order_id ) {
		if ( ! $this->is_enabled() ) {
			return;
		}
		$this->import_order( (int) $order_id, 'auto' );
	}

	/**
	 * Variant for the Store API hook, which passes the order object.
	 *
	 * @param \WC_Order $order Order object.
	 */
	public function handle_new_order_object( $order ) {
		if ( ! $this->is_enabled() || ! is_a( $order, 'WC_Order' ) ) {
			return;
		}
		$this->import_order( $order->get_id(), 'auto' );
	}

	/**
	 * Keep the tracking record in step with WooCommerce status changes.
	 *
	 * @param int       $order_id WooCommerce order id.
	 * @param string    $from     Old status slug (no "wc-" prefix).
	 * @param string    $to       New status slug (no "wc-" prefix).
	 * @param \WC_Order $order    Order object.
	 */
	public function handle_status_changed( $order_id, $from, $to, $order ) {
		if ( ! $this->is_enabled() ) {
			return;
		}

		if ( ! is_a( $order, 'WC_Order' ) ) {
			$order = wc_get_order( $order_id );
		}
		if ( ! $order ) {
			return;
		}

		$existing = OrdersRepository::find_by_order_number( $order->get_order_number() );

		// Not tracked yet: import now if the new status is one we import on.
		if ( ! $existing ) {
			if ( in_array( $to, $this->trigger_statuses(), true ) ) {
				$this->import_order( (int) $order_id, 'auto' );
			}
			return;
		}

		// Already tracked: mirror the status change if that option is on.
		if ( ! $this->should_update_status() ) {
			return;
		}

		$new_slug = $this->map_status( $to );

		if ( '' === $new_slug || $new_slug === $existing['current_status'] ) {
			return;
		}

		$result = OrdersRepository::update( $existing['id'], array( 'current_status' => $new_slug ) );
		if ( is_wp_error( $result ) ) {
			return;
		}

		StatusHistoryRepository::add(
			$existing['id'],
			$new_slug,
			sprintf(
				/* translators: %s: WooCommerce order status name */
				__( 'Synced from WooCommerce status: %s', 'nasm-order-tracking' ),
				function_exists( 'wc_get_order_status_name' ) ? wc_get_order_status_name( $to ) : $to
			),
			'',
			0
		);

		ActivityLogger::log(
			'wc_status_synced',
			'order',
			$existing['id'],
			sprintf( 'WooCommerce %1$s → tracking %2$s', $to, $new_slug )
		);

		if ( $this->should_notify() ) {
			$this->notify( (int) $existing['id'] );
		}
	}

	/* --------------------------------------------------------------------- *
	 * Import
	 * --------------------------------------------------------------------- */

	/**
	 * Create a tracking record from a WooCommerce order.
	 *
	 * Idempotent: an order that already has a tracking record is left alone.
	 *
	 * @param int    $order_id WooCommerce order id.
	 * @param string $context  'auto' (live hook), 'manual' or 'backfill'.
	 * @return array{status:string,id:int} status is created|exists|ignored|error.
	 */
	public function import_order( $order_id, $context = 'auto' ) {
		if ( ! function_exists( 'wc_get_order' ) ) {
			return array( 'status' => 'error', 'id' => 0 );
		}

		$order = wc_get_order( $order_id );

		// Skip refunds and anything that isn't a real shop order.
		if ( ! $order || ! is_a( $order, 'WC_Order' ) ) {
			return array( 'status' => 'ignored', 'id' => 0 );
		}

		$wc_status = $order->get_status();

		// On live auto-imports, only import orders whose status qualifies. Manual
		// and backfill imports trust the caller (backfill already filters by query).
		if ( 'auto' === $context && ! in_array( $wc_status, $this->trigger_statuses(), true ) ) {
			return array( 'status' => 'ignored', 'id' => 0 );
		}

		$order_number = $order->get_order_number();

		$existing = OrdersRepository::find_by_order_number( $order_number );
		if ( $existing ) {
			return array( 'status' => 'exists', 'id' => (int) $existing['id'] );
		}

		$status_slug = $this->map_status( $wc_status );
		if ( '' === $status_slug ) {
			$status_slug = $this->default_status();
		}

		$last_name  = $order->get_billing_last_name() ?: $order->get_shipping_last_name();
		$first_name = $order->get_billing_first_name() ?: $order->get_shipping_first_name();

		$data = array(
			'order_number'     => $order_number,
			'first_name'       => $first_name,
			'last_name'        => $last_name,
			'email'            => $order->get_billing_email(),
			'shipping_method'  => $order->get_shipping_method(),
			'delivery_address' => $this->format_address( $order ),
			'order_date'       => $order->get_date_created() ? $order->get_date_created()->date( 'Y-m-d' ) : '',
			'current_status'   => $status_slug,
			'public_note'      => '',
			'notify_customer'  => $this->should_notify() ? 1 : 0,
			'created_by'       => 0,
		);

		$result = OrdersRepository::create( $data );

		if ( is_wp_error( $result ) ) {
			ActivityLogger::log(
				'wc_order_import_failed',
				'order',
				0,
				sprintf( 'Order %1$s: %2$s', $order_number, $result->get_error_message() )
			);
			return array( 'status' => 'error', 'id' => 0 );
		}

		$order_id_row = (int) $result;

		$occurred_at = $order->get_date_created() ? $order->get_date_created()->date( 'Y-m-d H:i:s' ) : '';

		StatusHistoryRepository::add(
			$order_id_row,
			$status_slug,
			__( 'Imported from WooCommerce.', 'nasm-order-tracking' ),
			$occurred_at,
			0
		);

		ActivityLogger::log(
			'wc_order_imported',
			'order',
			$order_id_row,
			sprintf( 'Order %1$s imported (%2$s).', $order_number, $context )
		);

		if ( $this->should_notify() ) {
			$this->notify( $order_id_row );
		}

		return array( 'status' => 'created', 'id' => $order_id_row );
	}

	/**
	 * Import a batch of existing WooCommerce orders (one-time catch-up).
	 *
	 * @param int $limit  Batch size.
	 * @param int $offset Offset into the ordered result set.
	 * @return array{created:int,exists:int,ignored:int,errors:int,processed:int,total:int}
	 */
	public function backfill( $limit = 20, $offset = 0 ) {
		$summary = array(
			'created'   => 0,
			'exists'    => 0,
			'ignored'   => 0,
			'errors'    => 0,
			'processed' => 0,
			'total'     => $this->backfill_total(),
		);

		if ( ! function_exists( 'wc_get_orders' ) ) {
			return $summary;
		}

		$ids = wc_get_orders(
			array(
				'limit'   => max( 1, (int) $limit ),
				'offset'  => max( 0, (int) $offset ),
				'orderby' => 'date',
				'order'   => 'ASC',
				'status'  => $this->trigger_statuses(),
				'type'    => 'shop_order',
				'return'  => 'ids',
			)
		);

		foreach ( $ids as $id ) {
			$result = $this->import_order( (int) $id, 'backfill' );
			$summary['processed']++;

			switch ( $result['status'] ) {
				case 'created':
					$summary['created']++;
					break;
				case 'exists':
					$summary['exists']++;
					break;
				case 'error':
					$summary['errors']++;
					break;
				default:
					$summary['ignored']++;
			}
		}

		return $summary;
	}

	/**
	 * Total number of WooCommerce orders that qualify for backfill.
	 *
	 * @return int
	 */
	public function backfill_total() {
		if ( ! function_exists( 'wc_get_orders' ) ) {
			return 0;
		}

		$result = wc_get_orders(
			array(
				'limit'    => 1,
				'status'   => $this->trigger_statuses(),
				'type'     => 'shop_order',
				'paginate' => true,
				'return'   => 'ids',
			)
		);

		return isset( $result->total ) ? (int) $result->total : 0;
	}

	/* --------------------------------------------------------------------- *
	 * Helpers
	 * --------------------------------------------------------------------- */

	/**
	 * Whether automatic sync is switched on.
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return (bool) Settings::get( 'wc_sync_enabled', 0 );
	}

	/**
	 * Whether tracking status should follow WooCommerce status changes.
	 *
	 * @return bool
	 */
	protected function should_update_status() {
		return (bool) Settings::get( 'wc_sync_update_status', 1 );
	}

	/**
	 * Whether imported/updated records should email the customer.
	 *
	 * @return bool
	 */
	protected function should_notify() {
		return (bool) Settings::get( 'wc_sync_notify', 0 );
	}

	/**
	 * Fallback tracking status when a WooCommerce status has no explicit mapping.
	 *
	 * @return string
	 */
	protected function default_status() {
		$slug = sanitize_key( (string) Settings::get( 'wc_sync_default_status', 'order_received' ) );
		return $slug ?: 'order_received';
	}

	/**
	 * WooCommerce statuses (no "wc-" prefix) that trigger an automatic import.
	 *
	 * @return string[]
	 */
	public function trigger_statuses() {
		$statuses = Settings::get( 'wc_sync_statuses', array( 'processing', 'on-hold', 'completed' ) );

		if ( ! is_array( $statuses ) ) {
			$statuses = array();
		}

		$statuses = array_values( array_filter( array_map( array( $this, 'strip_wc_prefix' ), $statuses ) ) );

		return $statuses ?: array( 'processing', 'on-hold', 'completed' );
	}

	/**
	 * The effective WooCommerce → tracking status map (defaults + admin overrides).
	 *
	 * @return array<string,string>
	 */
	public function status_map() {
		$saved = Settings::get( 'wc_status_map', array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}

		$map = self::DEFAULT_STATUS_MAP;

		foreach ( $saved as $wc_status => $plugin_slug ) {
			$map[ $this->strip_wc_prefix( $wc_status ) ] = sanitize_key( $plugin_slug );
		}

		return $map;
	}

	/**
	 * Map a single WooCommerce status to a tracking status slug.
	 *
	 * @param string $wc_status WooCommerce status (with or without "wc-").
	 * @return string Tracking status slug, or '' to skip.
	 */
	public function map_status( $wc_status ) {
		$wc_status = $this->strip_wc_prefix( $wc_status );
		$map       = $this->status_map();

		if ( array_key_exists( $wc_status, $map ) ) {
			return $map[ $wc_status ];
		}

		return $this->default_status();
	}

	/**
	 * Remove a leading "wc-" from a status slug.
	 *
	 * @param string $status Raw status slug.
	 * @return string
	 */
	protected function strip_wc_prefix( $status ) {
		$status = sanitize_key( (string) $status );
		return 0 === strpos( $status, 'wc-' ) ? substr( $status, 3 ) : $status;
	}

	/**
	 * Build a plain-text delivery address from a WooCommerce order,
	 * preferring the shipping address and falling back to billing.
	 *
	 * @param \WC_Order $order Order object.
	 * @return string
	 */
	protected function format_address( $order ) {
		$html = $order->get_formatted_shipping_address();

		if ( ! $html ) {
			$html = $order->get_formatted_billing_address();
		}

		if ( ! $html ) {
			return '';
		}

		$text = preg_replace( '/<br\s*\/?>/i', "\n", $html );

		return trim( wp_strip_all_tags( $text ) );
	}

	/**
	 * Send the branded status-update email for a tracking record's latest entry.
	 *
	 * @param int $order_row_id Tracking record id.
	 */
	protected function notify( $order_row_id ) {
		$order = OrdersRepository::find( $order_row_id );
		if ( ! $order || empty( $order['email'] ) ) {
			return;
		}

		$history = StatusHistoryRepository::for_order( $order_row_id );
		$latest  = end( $history ) ?: array();

		$sent = Mailer::send_status_update( $order, $latest ?: array() );

		ActivityLogger::log(
			$sent ? 'email_sent' : 'email_failed',
			'order',
			$order_row_id,
			'WooCommerce sync notification.'
		);
	}
}
