<?php
/**
 * CSV export screen: download all orders as a spreadsheet.
 *
 * @package Nasm_Order_Tracking
 */

namespace Nasm\OrderTracking\Admin;

use Nasm\OrderTracking\Database\OrdersRepository;
use Nasm\OrderTracking\Database\ActivityLogger;

defined( 'ABSPATH' ) || exit;

class ExportPage {

	const NONCE_ACTION = 'nasm_ot_export';

	/**
	 * Render the export screen, or stream a CSV download when requested.
	 */
	public function render() {
		if ( ! current_user_can( AdminMenu::CAPABILITY ) ) {
			return;
		}

		if ( isset( $_GET['download'] ) && check_admin_referer( self::NONCE_ACTION ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$this->stream_csv();
			exit;
		}

		include NASM_OT_PATH . 'templates/admin/export.php';
	}

	/**
	 * Output every order as a CSV file and terminate the request.
	 */
	protected function stream_csv() {
		$rows = OrdersRepository::query( array( 'per_page' => 100000, 'page' => 1 ) )['items'];

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=nasm-orders-export-' . gmdate( 'Y-m-d' ) . '.csv' );

		$output = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen

		fputcsv(
			$output,
			array(
				'order_number',
				'first_name',
				'last_name',
				'email',
				'tracking_number',
				'carrier',
				'shipping_method',
				'warehouse',
				'order_date',
				'estimated_delivery',
				'current_status',
				'admin_notes',
				'public_note',
				'created_at',
				'updated_at',
			)
		);

		foreach ( $rows as $row ) {
			fputcsv(
				$output,
				array_map(
					array( __CLASS__, 'escape_csv_cell' ),
					array(
						$row['order_number'],
						$row['first_name'],
						$row['last_name'],
						$row['email'],
						$row['tracking_number'],
						$row['carrier'],
						$row['shipping_method'],
						$row['warehouse'],
						$row['order_date'],
						$row['estimated_delivery'],
						$row['current_status'],
						$row['admin_notes'],
						$row['public_note'],
						$row['created_at'],
						$row['updated_at'],
					)
				)
			);
		}

		fclose( $output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		ActivityLogger::log( 'csv_export', 'order', 0, sprintf( '%d rows exported', count( $rows ) ) );
	}

	/**
	 * Neutralize CSV/spreadsheet formula injection: if a cell starts with a
	 * character a spreadsheet app would interpret as a formula trigger
	 * (=, +, -, @, tab, CR), prefix it with a single quote so it's forced
	 * to render as text instead of executing when the export is opened
	 * in Excel/Google Sheets.
	 *
	 * @param string $value Raw cell value.
	 * @return string
	 */
	protected static function escape_csv_cell( $value ) {
		$value = (string) $value;

		if ( '' !== $value && false !== strpos( "=+-@\t\r", $value[0] ) ) {
			return "'" . $value;
		}

		return $value;
	}
}
