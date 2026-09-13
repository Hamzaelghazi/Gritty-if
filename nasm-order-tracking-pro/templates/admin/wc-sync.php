<?php
/**
 * WooCommerce Sync screen template.
 *
 * Available variables:
 *   $settings, $saved, $wc_statuses, $statuses, $status_map, $trigger, $backfill_total.
 *
 * @package Nasm_Order_Tracking
 */

use Nasm\OrderTracking\Admin\WcSyncPage;

defined( 'ABSPATH' ) || exit;

$active = 'wc-sync';

/**
 * Render <option>s for the active plugin tracking statuses.
 *
 * @param array  $statuses     Active status rows.
 * @param string $selected     Currently selected slug.
 * @param bool   $allow_none   Whether to offer a "Don't sync" option.
 */
$render_status_options = static function ( $statuses, $selected, $allow_none = false ) {
	if ( $allow_none ) {
		printf(
			'<option value="" %s>%s</option>',
			selected( $selected, '', false ),
			esc_html__( '— Don’t sync —', 'nasm-order-tracking' )
		);
	}
	foreach ( $statuses as $status ) {
		printf(
			'<option value="%s" %s>%s</option>',
			esc_attr( $status['slug'] ),
			selected( $selected, $status['slug'], false ),
			esc_html( $status['label'] )
		);
	}
};
?>
<div class="wrap nasm-ot-wrap">
	<?php include NASM_OT_PATH . 'templates/admin/partials/nav.php'; ?>

	<div class="nasm-ot-page">
		<div class="nasm-ot-page__head">
			<h1><?php esc_html_e( 'WooCommerce Sync', 'nasm-order-tracking' ); ?></h1>
			<p class="description">
				<?php esc_html_e( 'Pull orders in from WooCommerce automatically so you never have to add them by hand.', 'nasm-order-tracking' ); ?>
			</p>
		</div>

		<?php if ( $saved ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'WooCommerce sync settings saved.', 'nasm-order-tracking' ); ?></p></div>
		<?php endif; ?>

		<?php if ( empty( $wc_statuses ) ) : ?>
			<div class="notice notice-error"><p><?php esc_html_e( 'WooCommerce does not appear to be active, so order syncing is unavailable.', 'nasm-order-tracking' ); ?></p></div>
		<?php endif; ?>

		<form method="post" class="nasm-ot-form">
			<?php wp_nonce_field( WcSyncPage::NONCE_ACTION, 'nasm_ot_nonce' ); ?>

			<div class="nasm-ot-panel">
				<div class="nasm-ot-panel__head"><h2><?php esc_html_e( 'Automatic Import', 'nasm-order-tracking' ); ?></h2></div>
				<div class="nasm-ot-grid">
					<label class="nasm-ot-checkbox nasm-ot-grid__full">
						<input type="checkbox" name="wc_sync_enabled" value="1" <?php checked( ! empty( $settings['wc_sync_enabled'] ) ); ?> />
						<span><strong><?php esc_html_e( 'Automatically create a tracking record when a WooCommerce order comes in', 'nasm-order-tracking' ); ?></strong></span>
					</label>

					<div class="nasm-ot-grid__full">
						<span class="nasm-ot-field-label"><?php esc_html_e( 'Import orders that reach these statuses', 'nasm-order-tracking' ); ?></span>
						<div class="nasm-ot-checkbox-list">
							<?php foreach ( $wc_statuses as $wc_slug => $wc_label ) : ?>
								<?php $bare = 0 === strpos( $wc_slug, 'wc-' ) ? substr( $wc_slug, 3 ) : $wc_slug; ?>
								<label class="nasm-ot-checkbox">
									<input type="checkbox" name="wc_sync_statuses[]" value="<?php echo esc_attr( $bare ); ?>" <?php checked( in_array( $bare, $trigger, true ) ); ?> />
									<span><?php echo esc_html( $wc_label ); ?></span>
								</label>
							<?php endforeach; ?>
						</div>
						<p class="description"><?php esc_html_e( 'An order is imported the moment it is created in one of these statuses, or the moment it transitions into one. Orders are never imported twice.', 'nasm-order-tracking' ); ?></p>
					</div>

					<label class="nasm-ot-checkbox nasm-ot-grid__full">
						<input type="checkbox" name="wc_sync_update_status" value="1" <?php checked( ! empty( $settings['wc_sync_update_status'] ) ); ?> />
						<span><?php esc_html_e( 'Update the tracking status when the WooCommerce order status changes', 'nasm-order-tracking' ); ?></span>
					</label>

					<label class="nasm-ot-checkbox nasm-ot-grid__full">
						<input type="checkbox" name="wc_sync_notify" value="1" <?php checked( ! empty( $settings['wc_sync_notify'] ) ); ?> />
						<span><?php esc_html_e( 'Email the customer when a record is imported or its status is synced', 'nasm-order-tracking' ); ?></span>
					</label>

					<label>
						<span><?php esc_html_e( 'Fallback tracking status', 'nasm-order-tracking' ); ?></span>
						<select name="wc_sync_default_status">
							<?php $render_status_options( $statuses, $settings['wc_sync_default_status'] ?? 'order_received' ); ?>
						</select>
					</label>
				</div>
			</div>

			<div class="nasm-ot-panel">
				<div class="nasm-ot-panel__head"><h2><?php esc_html_e( 'Status Mapping', 'nasm-order-tracking' ); ?></h2></div>
				<p class="description" style="padding:0 16px;">
					<?php esc_html_e( 'Choose which tracking status each WooCommerce status becomes. The fallback status above is used for anything left unset.', 'nasm-order-tracking' ); ?>
				</p>
				<table class="widefat striped nasm-ot-map-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'WooCommerce Status', 'nasm-order-tracking' ); ?></th>
							<th><?php esc_html_e( 'Tracking Status', 'nasm-order-tracking' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $wc_statuses as $wc_slug => $wc_label ) : ?>
							<?php
							$bare     = 0 === strpos( $wc_slug, 'wc-' ) ? substr( $wc_slug, 3 ) : $wc_slug;
							$selected = array_key_exists( $bare, $status_map ) ? $status_map[ $bare ] : '';
							?>
							<tr>
								<td><?php echo esc_html( $wc_label ); ?></td>
								<td>
									<select name="wc_status_map[<?php echo esc_attr( $bare ); ?>]">
										<?php $render_status_options( $statuses, $selected, true ); ?>
									</select>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>

			<div class="nasm-ot-form__actions">
				<button type="submit" class="button button-primary button-hero"><?php esc_html_e( 'Save Sync Settings', 'nasm-order-tracking' ); ?></button>
			</div>
		</form>

		<div class="nasm-ot-panel">
			<div class="nasm-ot-panel__head"><h2><?php esc_html_e( 'Import Existing Orders', 'nasm-order-tracking' ); ?></h2></div>
			<div style="padding:16px;">
				<p>
					<?php
					printf(
						/* translators: %s: number of orders */
						esc_html__( 'There are %s existing WooCommerce order(s) in the statuses selected above that can be imported now. Automatic sync only catches new orders — use this once to bring in orders placed before you turned it on. Orders that already have a tracking record are skipped.', 'nasm-order-tracking' ),
						'<strong id="nasm-ot-backfill-total">' . esc_html( number_format_i18n( $backfill_total ) ) . '</strong>'
					);
					?>
				</p>

				<p>
					<button type="button" class="button button-secondary button-hero" id="nasm-ot-backfill-btn" <?php disabled( 0, $backfill_total ); ?>>
						<?php esc_html_e( 'Import Existing Orders Now', 'nasm-order-tracking' ); ?>
					</button>
				</p>

				<div id="nasm-ot-backfill-progress" class="nasm-ot-progress" style="display:none;">
					<div class="nasm-ot-progress__bar"><span id="nasm-ot-backfill-bar" style="width:0;"></span></div>
					<p id="nasm-ot-backfill-status" class="description"></p>
				</div>
			</div>
		</div>
	</div>
</div>
