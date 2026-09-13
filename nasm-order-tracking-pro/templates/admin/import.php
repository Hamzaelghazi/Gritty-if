<?php
/**
 * CSV import screen template.
 *
 * Available variables: $summary, $expected_columns.
 *
 * @package Nasm_Order_Tracking
 */

use Nasm\OrderTracking\Admin\ImportPage;

defined( 'ABSPATH' ) || exit;

$active = 'import';
?>
<div class="wrap nasm-ot-wrap">
	<?php include NASM_OT_PATH . 'templates/admin/partials/nav.php'; ?>

	<div class="nasm-ot-page">
		<div class="nasm-ot-page__head">
			<h1><?php esc_html_e( 'Import Orders', 'nasm-order-tracking' ); ?></h1>
		</div>

		<?php if ( $summary ) : ?>
			<div class="notice notice-<?php echo $summary['created'] ? 'success' : 'warning'; ?> is-dismissible">
				<p>
					<?php
					printf(
						/* translators: 1: number created, 2: number skipped */
						esc_html__( 'Import finished: %1$d record(s) created, %2$d skipped.', 'nasm-order-tracking' ),
						(int) $summary['created'],
						(int) $summary['skipped']
					);
					?>
				</p>
				<?php if ( ! empty( $summary['errors'] ) ) : ?>
					<ul>
						<?php foreach ( array_slice( $summary['errors'], 0, 20 ) as $error ) : ?>
							<li><?php echo esc_html( $error ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<div class="nasm-ot-panel">
			<div class="nasm-ot-panel__head"><h2><?php esc_html_e( 'Upload CSV', 'nasm-order-tracking' ); ?></h2></div>
			<form method="post" enctype="multipart/form-data" class="nasm-ot-form">
				<?php wp_nonce_field( ImportPage::NONCE_ACTION, 'nasm_ot_nonce' ); ?>
				<label>
					<span><?php esc_html_e( 'CSV File', 'nasm-order-tracking' ); ?></span>
					<input type="file" name="csv_file" accept=".csv" required />
				</label>
				<p class="description">
					<?php esc_html_e( 'Expected columns (header row required):', 'nasm-order-tracking' ); ?>
					<code><?php echo esc_html( implode( ', ', $expected_columns ) ); ?></code>
				</p>
				<div class="nasm-ot-form__actions">
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Import', 'nasm-order-tracking' ); ?></button>
				</div>
			</form>
		</div>
	</div>
</div>
