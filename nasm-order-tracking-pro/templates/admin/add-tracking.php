<?php
/**
 * Add/Edit tracking form template.
 *
 * Available variables: $order (array|null), $statuses, $errors, $order_id.
 *
 * @package Nasm_Order_Tracking
 */

use Nasm\OrderTracking\Admin\AddTrackingPage;
use Nasm\OrderTracking\Helpers\Settings;

defined( 'ABSPATH' ) || exit;

$active = 'add-tracking';
$order  = $order ?: array();

/**
 * Value helper: prefer POSTed value on validation failure, else stored value, else default.
 */
$val = function ( $key, $default = '' ) use ( $order ) {
	if ( isset( $_POST[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
	}
	return $order[ $key ] ?? $default;
};

$is_edit         = ! empty( $order );
$current_status  = $val( 'current_status', 'order_received' );
$default_warehouse = $order['warehouse'] ?? Settings::get( 'warehouse_address' );
?>
<div class="wrap nasm-ot-wrap">
	<?php include NASM_OT_PATH . 'templates/admin/partials/nav.php'; ?>

	<div class="nasm-ot-page">
		<div class="nasm-ot-page__head">
			<h1><?php echo $is_edit ? esc_html__( 'Edit Tracking', 'nasm-order-tracking' ) : esc_html__( 'Add Tracking', 'nasm-order-tracking' ); ?></h1>
		</div>

		<?php if ( isset( $_GET['saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Tracking record saved.', 'nasm-order-tracking' ); ?></p></div>
		<?php endif; ?>

		<?php if ( ! empty( $errors ) ) : ?>
			<div class="notice notice-error"><p><?php echo esc_html( implode( ' ', $errors ) ); ?></p></div>
		<?php endif; ?>

		<form method="post" class="nasm-ot-form">
			<?php wp_nonce_field( AddTrackingPage::NONCE_ACTION, 'nasm_ot_nonce' ); ?>

			<?php if ( ! $is_edit && function_exists( 'wc_get_orders' ) ) : ?>
				<div class="nasm-ot-panel nasm-ot-wc-search">
					<div class="nasm-ot-panel__head"><h2><?php esc_html_e( 'Import from WooCommerce', 'nasm-order-tracking' ); ?></h2></div>
					<p class="description"><?php esc_html_e( 'Search an existing WooCommerce order to fill in the order number, customer name, and email below — you\'ll still just add the tracking status yourself.', 'nasm-order-tracking' ); ?></p>
					<input type="text" id="nasm-ot-wc-order-search" placeholder="<?php esc_attr_e( 'Search by order number, customer name, or email…', 'nasm-order-tracking' ); ?>" autocomplete="off" />
					<ul id="nasm-ot-wc-order-results" class="nasm-ot-wc-results"></ul>
				</div>
			<?php endif; ?>

			<div class="nasm-ot-panel">
				<div class="nasm-ot-panel__head"><h2><?php esc_html_e( 'Order Information', 'nasm-order-tracking' ); ?></h2></div>
				<div class="nasm-ot-grid">
					<label>
						<span><?php esc_html_e( 'Order Number', 'nasm-order-tracking' ); ?> *</span>
						<input type="text" name="order_number" value="<?php echo esc_attr( $val( 'order_number' ) ); ?>" required />
					</label>
					<label>
						<span><?php esc_html_e( 'Order Date', 'nasm-order-tracking' ); ?></span>
						<input type="date" name="order_date" value="<?php echo esc_attr( $val( 'order_date' ) ); ?>" />
					</label>
					<label>
						<span><?php esc_html_e( 'Estimated Delivery', 'nasm-order-tracking' ); ?></span>
						<input type="date" name="estimated_delivery" value="<?php echo esc_attr( $val( 'estimated_delivery' ) ); ?>" />
					</label>
				</div>
			</div>

			<div class="nasm-ot-panel">
				<div class="nasm-ot-panel__head"><h2><?php esc_html_e( 'Customer', 'nasm-order-tracking' ); ?></h2></div>
				<div class="nasm-ot-grid">
					<label>
						<span><?php esc_html_e( 'First Name', 'nasm-order-tracking' ); ?></span>
						<input type="text" name="first_name" value="<?php echo esc_attr( $val( 'first_name' ) ); ?>" />
					</label>
					<label>
						<span><?php esc_html_e( 'Last Name', 'nasm-order-tracking' ); ?> *</span>
						<input type="text" name="last_name" value="<?php echo esc_attr( $val( 'last_name' ) ); ?>" required />
					</label>
					<label>
						<span><?php esc_html_e( 'Email', 'nasm-order-tracking' ); ?></span>
						<input type="email" name="email" value="<?php echo esc_attr( $val( 'email' ) ); ?>" />
					</label>
				</div>
			</div>

			<div class="nasm-ot-panel">
				<div class="nasm-ot-panel__head"><h2><?php esc_html_e( 'Shipping', 'nasm-order-tracking' ); ?></h2></div>
				<div class="nasm-ot-grid">
					<label>
						<span><?php esc_html_e( 'Tracking Number', 'nasm-order-tracking' ); ?></span>
						<input type="text" name="tracking_number" value="<?php echo esc_attr( $val( 'tracking_number' ) ); ?>" />
					</label>
					<label>
						<span><?php esc_html_e( 'Carrier', 'nasm-order-tracking' ); ?></span>
						<input type="text" name="carrier" value="<?php echo esc_attr( $val( 'carrier' ) ); ?>" list="nasm-ot-carriers" />
						<datalist id="nasm-ot-carriers">
							<option value="FedEx"></option>
							<option value="UPS"></option>
							<option value="USPS"></option>
							<option value="DHL"></option>
							<option value="Freight Carrier"></option>
						</datalist>
					</label>
					<label>
						<span><?php esc_html_e( 'Shipping Method', 'nasm-order-tracking' ); ?></span>
						<input type="text" name="shipping_method" value="<?php echo esc_attr( $val( 'shipping_method' ) ); ?>" />
					</label>
					<label>
						<span><?php esc_html_e( 'Warehouse', 'nasm-order-tracking' ); ?></span>
						<input type="text" name="warehouse" value="<?php echo esc_attr( $default_warehouse ); ?>" />
					</label>
					<label class="nasm-ot-grid__full">
						<span><?php esc_html_e( 'Delivery Address', 'nasm-order-tracking' ); ?></span>
						<textarea name="delivery_address" rows="2"><?php echo esc_textarea( $order['delivery_address'] ?? '' ); ?></textarea>
					</label>
				</div>
			</div>

			<div class="nasm-ot-panel">
				<div class="nasm-ot-panel__head"><h2><?php esc_html_e( 'Status', 'nasm-order-tracking' ); ?></h2></div>
				<div class="nasm-ot-grid">
					<label>
						<span><?php esc_html_e( 'Current Status', 'nasm-order-tracking' ); ?></span>
						<select name="current_status">
							<?php foreach ( $statuses as $status ) : ?>
								<option value="<?php echo esc_attr( $status['slug'] ); ?>" <?php selected( $current_status, $status['slug'] ); ?>>
									<?php echo esc_html( $status['label'] ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</label>
					<label>
						<span><?php esc_html_e( 'Status Update Date/Time', 'nasm-order-tracking' ); ?></span>
						<input type="datetime-local" name="status_occurred_at" />
						<small class="description"><?php esc_html_e( 'Leave blank to use the current date/time. Only used if the status changed.', 'nasm-order-tracking' ); ?></small>
					</label>
					<label class="nasm-ot-grid__full">
						<span><?php esc_html_e( 'Public Customer Note', 'nasm-order-tracking' ); ?></span>
						<textarea name="public_note" rows="2" placeholder="<?php esc_attr_e( 'e.g. Package departed our Miami warehouse.', 'nasm-order-tracking' ); ?>"><?php echo esc_textarea( $order['public_note'] ?? '' ); ?></textarea>
					</label>
					<label class="nasm-ot-grid__full">
						<span><?php esc_html_e( 'Internal Notes', 'nasm-order-tracking' ); ?></span>
						<textarea name="admin_notes" rows="2"><?php echo esc_textarea( $order['admin_notes'] ?? '' ); ?></textarea>
					</label>
					<label class="nasm-ot-checkbox">
						<input type="checkbox" name="notify_customer" value="1" <?php checked( ! empty( $order['notify_customer'] ) || ! empty( $_POST['notify_customer'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing ?> />
						<span><?php esc_html_e( 'Notify Customer by Email', 'nasm-order-tracking' ); ?></span>
					</label>
				</div>
			</div>

			<div class="nasm-ot-form__actions">
				<button type="submit" class="button button-primary button-hero"><?php esc_html_e( 'Save Tracking', 'nasm-order-tracking' ); ?></button>
			</div>
		</form>
	</div>
</div>
