<?php
/**
 * Customer-facing tracking search form. Results are injected via AJAX.
 *
 * Available variables: $settings.
 *
 * @package Nasm_Order_Tracking
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="nasm-ot-tracking">
	<div class="nasm-ot-tracking__search" id="nasm-ot-search-panel">
		<div class="nasm-ot-tracking__icon">
			<span class="dashicons dashicons-location-alt"></span>
		</div>
		<h2><?php esc_html_e( 'Track Your Order', 'nasm-order-tracking' ); ?></h2>
		<p class="nasm-ot-tracking__subtitle">
			<?php esc_html_e( 'Enter your order number and the last name used during checkout to view your order status.', 'nasm-order-tracking' ); ?>
		</p>

		<form id="nasm-ot-track-form" class="nasm-ot-tracking__form" autocomplete="off">
			<label>
				<span><?php esc_html_e( 'Order Number', 'nasm-order-tracking' ); ?></span>
				<input type="text" name="order_number" id="nasm-ot-order-number" required placeholder="<?php esc_attr_e( 'e.g. 10482', 'nasm-order-tracking' ); ?>" />
			</label>
			<label>
				<span><?php esc_html_e( 'Customer Last Name', 'nasm-order-tracking' ); ?></span>
				<input type="text" name="last_name" id="nasm-ot-last-name" required placeholder="<?php esc_attr_e( 'e.g. Smith', 'nasm-order-tracking' ); ?>" />
			</label>
			<button type="submit" class="nasm-ot-tracking__submit">
				<span class="nasm-ot-btn-text"><?php esc_html_e( 'Track Order', 'nasm-order-tracking' ); ?></span>
				<span class="nasm-ot-spinner" aria-hidden="true"></span>
			</button>
		</form>

		<div class="nasm-ot-tracking__error" id="nasm-ot-error" role="alert" hidden></div>
	</div>

	<div class="nasm-ot-tracking__results" id="nasm-ot-results" hidden></div>
</div>
