<?php
/**
 * Read-only activity log viewer.
 *
 * @package Nasm_Order_Tracking
 */

namespace Nasm\OrderTracking\Admin;

use Nasm\OrderTracking\Database\ActivityLogger;

defined( 'ABSPATH' ) || exit;

class ActivityLogPage {

	/**
	 * Render the activity log screen.
	 */
	public function render() {
		if ( ! current_user_can( AdminMenu::CAPABILITY ) ) {
			return;
		}

		$page = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$result     = ActivityLogger::query( 30, $page );
		$logs       = $result['items'];
		$total      = $result['total'];
		$total_pages = max( 1, (int) ceil( $total / 30 ) );

		include NASM_OT_PATH . 'templates/admin/activity-log.php';
	}
}
