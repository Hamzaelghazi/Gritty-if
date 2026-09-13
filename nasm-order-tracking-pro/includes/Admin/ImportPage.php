<?php
/**
 * CSV import screen for bulk-creating tracking records.
 *
 * @package Nasm_Order_Tracking
 */

namespace Nasm\OrderTracking\Admin;

use Nasm\OrderTracking\Database\OrdersRepository;
use Nasm\OrderTracking\Database\StatusHistoryRepository;
use Nasm\OrderTracking\Database\ActivityLogger;
use Nasm\OrderTracking\Helpers\Sanitizer;

defined( 'ABSPATH' ) || exit;

class ImportPage {

	const NONCE_ACTION = 'nasm_ot_import';

	/**
	 * Expected CSV column headers, in order.
	 *
	 * @var string[]
	 */
	protected static $expected_columns = array(
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
	);

	/**
	 * Render the import screen, handling upload first.
	 */
	public function render() {
		if ( ! current_user_can( AdminMenu::CAPABILITY ) ) {
			return;
		}

		$summary = null;

		if ( ! empty( $_POST['nasm_ot_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nasm_ot_nonce'] ) ), self::NONCE_ACTION ) ) {
			$summary = $this->handle_upload();
		}

		$expected_columns = self::$expected_columns;

		include NASM_OT_PATH . 'templates/admin/import.php';
	}

	/**
	 * Validate and process the uploaded CSV file.
	 *
	 * @return array{created:int, skipped:int, errors:string[]}
	 */
	protected function handle_upload() {
		check_admin_referer( self::NONCE_ACTION, 'nasm_ot_nonce' );

		$summary = array(
			'created' => 0,
			'skipped' => 0,
			'errors'  => array(),
		);

		if ( empty( $_FILES['csv_file']['tmp_name'] ) || ! is_uploaded_file( $_FILES['csv_file']['tmp_name'] ) ) {
			$summary['errors'][] = __( 'Please choose a CSV file to upload.', 'nasm-order-tracking' );
			return $summary;
		}

		$file_name = sanitize_file_name( $_FILES['csv_file']['name'] );
		if ( ! preg_match( '/\.csv$/i', $file_name ) ) {
			$summary['errors'][] = __( 'Please upload a .csv file.', 'nasm-order-tracking' );
			return $summary;
		}

		$handle = fopen( $_FILES['csv_file']['tmp_name'], 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( ! $handle ) {
			$summary['errors'][] = __( 'Could not read the uploaded file.', 'nasm-order-tracking' );
			return $summary;
		}

		$header = fgetcsv( $handle );
		if ( ! $header ) {
			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			$summary['errors'][] = __( 'The CSV file appears to be empty.', 'nasm-order-tracking' );
			return $summary;
		}

		$header = array_map( static fn( $col ) => sanitize_key( trim( $col ) ), $header );
		$row_number = 1;

		while ( ( $row = fgetcsv( $handle ) ) !== false ) {
			$row_number++;

			if ( count( array_filter( $row, static fn( $v ) => '' !== trim( (string) $v ) ) ) === 0 ) {
				continue; // Skip blank lines.
			}

			$assoc = array();
			foreach ( $header as $index => $key ) {
				$assoc[ $key ] = $row[ $index ] ?? '';
			}

			$data = Sanitizer::order_fields( $assoc );

			if ( empty( $data['order_number'] ) || empty( $data['last_name'] ) ) {
				$summary['skipped']++;
				$summary['errors'][] = sprintf(
					/* translators: %d: CSV row number */
					__( 'Row %d skipped: order number and last name are required.', 'nasm-order-tracking' ),
					$row_number
				);
				continue;
			}

			$data['current_status'] = $data['current_status'] ?: 'order_received';
			$data['created_by']     = get_current_user_id();

			$result = OrdersRepository::create( $data );

			if ( is_wp_error( $result ) ) {
				$summary['skipped']++;
				$summary['errors'][] = sprintf(
					/* translators: 1: CSV row number, 2: error message */
					__( 'Row %1$d skipped: %2$s', 'nasm-order-tracking' ),
					$row_number,
					$result->get_error_message()
				);
				continue;
			}

			StatusHistoryRepository::add( $result, $data['current_status'], __( 'Imported via CSV.', 'nasm-order-tracking' ), '', get_current_user_id() );
			$summary['created']++;
		}

		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		ActivityLogger::log( 'csv_import', 'order', 0, sprintf( '%d created, %d skipped', $summary['created'], $summary['skipped'] ) );

		return $summary;
	}
}
