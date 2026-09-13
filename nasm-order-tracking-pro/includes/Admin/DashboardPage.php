<?php
/**
 * Admin dashboard: stat cards + recent activity.
 *
 * @package Nasm_Order_Tracking
 */

namespace Nasm\OrderTracking\Admin;

use Nasm\OrderTracking\Database\OrdersRepository;
use Nasm\OrderTracking\Database\ActivityLogger;
use Nasm\OrderTracking\Helpers\StatusBuckets;
use Nasm\OrderTracking\Helpers\Settings;

defined( 'ABSPATH' ) || exit;

class DashboardPage {

	/**
	 * Render the dashboard screen.
	 */
	public function render() {
		if ( ! current_user_can( AdminMenu::CAPABILITY ) ) {
			return;
		}

		$counts   = OrdersRepository::dashboard_counts();
		$recent   = OrdersRepository::recent( 8 );
		$activity = ActivityLogger::recent( 8 );

		$by_status  = $counts['by_status'];
		$processing = StatusBuckets::count( $by_status, 'processing' );
		$in_transit = StatusBuckets::count( $by_status, 'in_transit' );
		$delivered  = $by_status['delivered'] ?? 0;
		$delayed    = $by_status['delayed'] ?? 0;

		$has_orders         = $counts['total'] > 0;
		$tracking_page_id   = get_option( 'nasm_ot_tracking_page_id' );
		$tracking_page_url  = $tracking_page_id ? get_permalink( $tracking_page_id ) : '';
		$company_name       = Settings::get( 'company_name' );

		include NASM_OT_PATH . 'templates/admin/dashboard.php';
	}
}
