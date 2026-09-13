<?php
/**
 * Settings screen template.
 *
 * Available variables: $settings, $saved.
 *
 * @package Nasm_Order_Tracking
 */

use Nasm\OrderTracking\Admin\SettingsPage;

defined( 'ABSPATH' ) || exit;

$active   = 'settings';
$logo_id  = ! empty( $settings['company_logo_id'] ) ? (int) $settings['company_logo_id'] : 0;
$logo_url = $logo_id ? wp_get_attachment_image_url( $logo_id, 'medium' ) : '';
?>
<div class="wrap nasm-ot-wrap">
	<?php include NASM_OT_PATH . 'templates/admin/partials/nav.php'; ?>

	<div class="nasm-ot-page">
		<div class="nasm-ot-page__head">
			<h1><?php esc_html_e( 'Settings', 'nasm-order-tracking' ); ?></h1>
		</div>

		<?php if ( $saved ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'nasm-order-tracking' ); ?></p></div>
		<?php endif; ?>

		<form method="post" class="nasm-ot-form">
			<?php wp_nonce_field( SettingsPage::NONCE_ACTION, 'nasm_ot_nonce' ); ?>

			<div class="nasm-ot-panel">
				<div class="nasm-ot-panel__head"><h2><?php esc_html_e( 'Company', 'nasm-order-tracking' ); ?></h2></div>
				<div class="nasm-ot-grid">
					<label>
						<span><?php esc_html_e( 'Company Name', 'nasm-order-tracking' ); ?></span>
						<input type="text" name="company_name" value="<?php echo esc_attr( $settings['company_name'] ); ?>" />
					</label>
					<label class="nasm-ot-grid__full">
						<span><?php esc_html_e( 'Company Logo', 'nasm-order-tracking' ); ?></span>
						<div class="nasm-ot-logo-picker">
							<img src="<?php echo esc_url( $logo_url ); ?>" id="nasm-ot-logo-preview" style="<?php echo $logo_url ? '' : 'display:none;'; ?>max-height:60px;" />
							<input type="hidden" name="company_logo_id" id="nasm-ot-logo-id" value="<?php echo esc_attr( $logo_id ); ?>" />
							<button type="button" class="button" id="nasm-ot-logo-select"><?php esc_html_e( 'Select Logo', 'nasm-order-tracking' ); ?></button>
							<button type="button" class="button" id="nasm-ot-logo-remove" style="<?php echo $logo_url ? '' : 'display:none;'; ?>"><?php esc_html_e( 'Remove', 'nasm-order-tracking' ); ?></button>
						</div>
					</label>
					<label>
						<span><?php esc_html_e( 'Support Email', 'nasm-order-tracking' ); ?></span>
						<input type="email" name="support_email" value="<?php echo esc_attr( $settings['support_email'] ); ?>" />
					</label>
					<label>
						<span><?php esc_html_e( 'Support Phone', 'nasm-order-tracking' ); ?></span>
						<input type="text" name="support_phone" value="<?php echo esc_attr( $settings['support_phone'] ); ?>" />
					</label>
					<label class="nasm-ot-grid__full">
						<span><?php esc_html_e( 'Business Address', 'nasm-order-tracking' ); ?></span>
						<textarea name="business_address" rows="2"><?php echo esc_textarea( $settings['business_address'] ); ?></textarea>
					</label>
				</div>
			</div>

			<div class="nasm-ot-panel">
				<div class="nasm-ot-panel__head"><h2><?php esc_html_e( 'Shipping Information', 'nasm-order-tracking' ); ?></h2></div>
				<div class="nasm-ot-grid">
					<label class="nasm-ot-grid__full">
						<span><?php esc_html_e( 'Warehouse Address', 'nasm-order-tracking' ); ?></span>
						<textarea name="warehouse_address" rows="3"><?php echo esc_textarea( $settings['warehouse_address'] ); ?></textarea>
					</label>
					<label>
						<span><?php esc_html_e( 'Processing Time', 'nasm-order-tracking' ); ?></span>
						<input type="text" name="processing_time" value="<?php echo esc_attr( $settings['processing_time'] ); ?>" />
					</label>
					<label>
						<span><?php esc_html_e( 'Freight Delivery', 'nasm-order-tracking' ); ?></span>
						<input type="text" name="freight_delivery" value="<?php echo esc_attr( $settings['freight_delivery'] ); ?>" />
					</label>
					<label>
						<span><?php esc_html_e( 'Free Shipping Note', 'nasm-order-tracking' ); ?></span>
						<input type="text" name="free_shipping_note" value="<?php echo esc_attr( $settings['free_shipping_note'] ); ?>" />
					</label>
					<label class="nasm-ot-grid__full">
						<span><?php esc_html_e( 'Business Hours', 'nasm-order-tracking' ); ?></span>
						<textarea name="business_hours" rows="3"><?php echo esc_textarea( $settings['business_hours'] ); ?></textarea>
					</label>
					<label>
						<span><?php esc_html_e( 'Timezone', 'nasm-order-tracking' ); ?></span>
						<select name="timezone">
							<?php foreach ( timezone_identifiers_list() as $tz ) : ?>
								<option value="<?php echo esc_attr( $tz ); ?>" <?php selected( $settings['timezone'], $tz ); ?>><?php echo esc_html( $tz ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
				</div>
			</div>

			<div class="nasm-ot-panel">
				<div class="nasm-ot-panel__head"><h2><?php esc_html_e( 'Branding & Email', 'nasm-order-tracking' ); ?></h2></div>
				<div class="nasm-ot-grid">
					<label>
						<span><?php esc_html_e( 'Primary Color', 'nasm-order-tracking' ); ?></span>
						<input type="text" name="brand_primary_color" value="<?php echo esc_attr( $settings['brand_primary_color'] ); ?>" class="nasm-ot-color-picker" />
					</label>
					<label>
						<span><?php esc_html_e( 'Accent Color', 'nasm-order-tracking' ); ?></span>
						<input type="text" name="brand_accent_color" value="<?php echo esc_attr( $settings['brand_accent_color'] ); ?>" class="nasm-ot-color-picker" />
					</label>
					<label>
						<span><?php esc_html_e( 'Email "From" Name', 'nasm-order-tracking' ); ?></span>
						<input type="text" name="email_from_name" value="<?php echo esc_attr( $settings['email_from_name'] ); ?>" />
					</label>
					<label>
						<span><?php esc_html_e( 'Email "From" Address', 'nasm-order-tracking' ); ?></span>
						<input type="email" name="email_from_address" value="<?php echo esc_attr( $settings['email_from_address'] ); ?>" />
					</label>
					<label class="nasm-ot-checkbox">
						<input type="checkbox" name="notify_on_update" value="1" <?php checked( ! empty( $settings['notify_on_update'] ) ); ?> />
						<span><?php esc_html_e( 'Default "Notify Customer" checkbox to checked on new tracking entries', 'nasm-order-tracking' ); ?></span>
					</label>
				</div>
			</div>

			<div class="nasm-ot-panel">
				<div class="nasm-ot-panel__head"><h2><?php esc_html_e( 'Data', 'nasm-order-tracking' ); ?></h2></div>
				<div class="nasm-ot-grid">
					<label class="nasm-ot-checkbox">
						<input type="checkbox" name="delete_data_on_uninstall" value="1" <?php checked( ! empty( $settings['delete_data_on_uninstall'] ) ); ?> />
						<span><?php esc_html_e( 'Delete all tracking data when this plugin is uninstalled', 'nasm-order-tracking' ); ?></span>
					</label>
				</div>
			</div>

			<div class="nasm-ot-form__actions">
				<button type="submit" class="button button-primary button-hero"><?php esc_html_e( 'Save Settings', 'nasm-order-tracking' ); ?></button>
			</div>
		</form>
	</div>
</div>
