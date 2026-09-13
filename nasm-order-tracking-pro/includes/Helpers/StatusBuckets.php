<?php
/**
 * Canonical groupings of individual status slugs into the coarse buckets
 * shown on the dashboard (Processing / In Transit). Kept in one place so
 * the dashboard's stat cards and the Orders list deep-link filter can't
 * drift apart from each other.
 *
 * @package Nasm_Order_Tracking
 */

namespace Nasm\OrderTracking\Helpers;

defined( 'ABSPATH' ) || exit;

class StatusBuckets {

	/**
	 * Bucket key => slugs it contains.
	 *
	 * @return array<string, string[]>
	 */
	public static function map() {
		return array(
			'processing' => array( 'order_received', 'payment_confirmed', 'processing', 'preparing_shipment' ),
			'in_transit' => array( 'picked_up', 'in_transit', 'arrived_hub', 'out_for_delivery' ),
		);
	}

	/**
	 * Slugs belonging to a bucket key, or an empty array if the key is unknown.
	 *
	 * @param string $bucket Bucket key.
	 * @return string[]
	 */
	public static function slugs( $bucket ) {
		$map = self::map();
		return $map[ $bucket ] ?? array();
	}

	/**
	 * Sum status counts (as returned by OrdersRepository::dashboard_counts())
	 * across every slug in a bucket.
	 *
	 * @param array  $by_status Counts keyed by status slug.
	 * @param string $bucket    Bucket key.
	 * @return int
	 */
	public static function count( array $by_status, $bucket ) {
		$total = 0;
		foreach ( self::slugs( $bucket ) as $slug ) {
			$total += (int) ( $by_status[ $slug ] ?? 0 );
		}
		return $total;
	}
}
