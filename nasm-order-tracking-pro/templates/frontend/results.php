<?php
/**
 * Customer-facing tracking results, rendered server-side and returned via AJAX.
 *
 * Available variables: $order, $history, $timeline, $settings.
 *
 * @package Nasm_Order_Tracking
 */

use Nasm\OrderTracking\Helpers\Formatter;

defined( 'ABSPATH' ) || exit;

$status_label = Formatter::status_label( $order['current_status'] );
$status_color = Formatter::status_color( $order['current_status'] );
$customer     = trim( $order['first_name'] . ' ' . $order['last_name'] );
?>
<div class="nasm-ot-result">

	<div class="nasm-ot-result__summary">
		<div class="nasm-ot-result__summary-main">
			<span class="nasm-ot-eyebrow"><?php esc_html_e( 'Order', 'nasm-order-tracking' ); ?> #<?php echo esc_html( $order['order_number'] ); ?></span>
			<h2><?php echo esc_html( $customer ); ?></h2>
			<span class="nasm-ot-status-pill" style="background-color:<?php echo esc_attr( $status_color ); ?>1a;color:<?php echo esc_attr( $status_color ); ?>;border-color:<?php echo esc_attr( $status_color ); ?>33;">
				<span class="dashicons <?php echo esc_attr( $status['icon'] ?? 'dashicons-yes' ); ?>"></span>
				<?php echo esc_html( $status_label ); ?>
			</span>
		</div>
		<?php if ( ! empty( $order['estimated_delivery'] ) ) : ?>
			<div class="nasm-ot-result__eta">
				<span><?php esc_html_e( 'Estimated Delivery', 'nasm-order-tracking' ); ?></span>
				<strong><?php echo esc_html( mysql2date( get_option( 'date_format' ), $order['estimated_delivery'] ) ); ?></strong>
			</div>
		<?php endif; ?>
	</div>

	<div class="nasm-ot-progress">
		<div class="nasm-ot-progress__bar">
			<div class="nasm-ot-progress__fill" style="width:<?php echo esc_attr( $timeline['percent'] ); ?>%;"></div>
		</div>
	</div>

	<?php if ( ! empty( $timeline['exception'] ) ) : ?>
		<div class="nasm-ot-exception nasm-ot-exception--frontend" style="border-color:<?php echo esc_attr( $timeline['exception']['color'] ); ?>;">
			<span class="dashicons <?php echo esc_attr( $timeline['exception']['icon'] ); ?>" style="color:<?php echo esc_attr( $timeline['exception']['color'] ); ?>;"></span>
			<div>
				<strong><?php echo esc_html( $timeline['exception']['label'] ); ?></strong>
				<?php if ( ! empty( $timeline['exception']['note'] ) ) : ?>
					<p><?php echo esc_html( $timeline['exception']['note'] ); ?></p>
				<?php endif; ?>
			</div>
		</div>
	<?php endif; ?>

	<ol class="nasm-ot-timeline nasm-ot-timeline--frontend">
		<?php foreach ( $timeline['steps'] as $step ) : ?>
			<li class="nasm-ot-timeline__step is-<?php echo esc_attr( $step['state'] ); ?>">
				<span class="nasm-ot-timeline__dot"><span class="dashicons <?php echo esc_attr( $step['icon'] ); ?>"></span></span>
				<div class="nasm-ot-timeline__body">
					<strong><?php echo esc_html( $step['label'] ); ?></strong>
					<?php if ( $step['occurred_at'] ) : ?>
						<time><?php echo esc_html( mysql2date( get_option( 'date_format' ) . ' \a\t g:i a', $step['occurred_at'] ) ); ?></time>
					<?php endif; ?>
					<?php if ( ! empty( $step['note'] ) ) : ?>
						<p><?php echo esc_html( $step['note'] ); ?></p>
					<?php endif; ?>
				</div>
			</li>
		<?php endforeach; ?>
	</ol>

	<div class="nasm-ot-detail-cards">
		<div class="nasm-ot-detail-card">
			<h3><?php esc_html_e( 'Order Details', 'nasm-order-tracking' ); ?></h3>
			<dl>
				<?php if ( ! empty( $order['order_date'] ) ) : ?>
					<div><dt><?php esc_html_e( 'Order Date', 'nasm-order-tracking' ); ?></dt><dd><?php echo esc_html( mysql2date( get_option( 'date_format' ), $order['order_date'] ) ); ?></dd></div>
				<?php endif; ?>
				<?php if ( ! empty( $order['tracking_number'] ) ) : ?>
					<div><dt><?php esc_html_e( 'Tracking Number', 'nasm-order-tracking' ); ?></dt><dd><?php echo esc_html( $order['tracking_number'] ); ?></dd></div>
				<?php endif; ?>
				<?php if ( ! empty( $order['carrier'] ) ) : ?>
					<div><dt><?php esc_html_e( 'Carrier', 'nasm-order-tracking' ); ?></dt><dd><?php echo esc_html( $order['carrier'] ); ?></dd></div>
				<?php endif; ?>
				<?php if ( ! empty( $order['shipping_method'] ) ) : ?>
					<div><dt><?php esc_html_e( 'Shipping Method', 'nasm-order-tracking' ); ?></dt><dd><?php echo esc_html( $order['shipping_method'] ); ?></dd></div>
				<?php endif; ?>
				<?php if ( ! empty( $order['warehouse'] ) ) : ?>
					<div><dt><?php esc_html_e( 'Warehouse', 'nasm-order-tracking' ); ?></dt><dd><?php echo esc_html( $order['warehouse'] ); ?></dd></div>
				<?php endif; ?>
			</dl>

			<?php if ( ! empty( $order['delivery_address'] ) ) : ?>
				<button type="button" class="nasm-ot-toggle" id="nasm-ot-address-toggle" aria-expanded="false">
					<?php esc_html_e( 'Show Delivery Address', 'nasm-order-tracking' ); ?>
				</button>
				<div class="nasm-ot-address" id="nasm-ot-address" hidden>
					<?php echo nl2br( esc_html( $order['delivery_address'] ) ); ?>
				</div>
			<?php endif; ?>
		</div>

		<div class="nasm-ot-detail-card nasm-ot-support-card">
			<h3><?php esc_html_e( 'Need Help?', 'nasm-order-tracking' ); ?></h3>
			<p><?php esc_html_e( "Our support team is happy to help with any questions about your order.", 'nasm-order-tracking' ); ?></p>
			<?php if ( ! empty( $settings['support_email'] ) ) : ?>
				<a href="mailto:<?php echo esc_attr( $settings['support_email'] ); ?>" class="nasm-ot-support-link">
					<span class="dashicons dashicons-email"></span> <?php echo esc_html( $settings['support_email'] ); ?>
				</a>
			<?php endif; ?>
			<?php if ( ! empty( $settings['support_phone'] ) ) : ?>
				<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $settings['support_phone'] ) ); ?>" class="nasm-ot-support-link">
					<span class="dashicons dashicons-phone"></span> <?php echo esc_html( $settings['support_phone'] ); ?>
				</a>
			<?php endif; ?>
			<?php if ( ! empty( $settings['business_hours'] ) ) : ?>
				<p class="nasm-ot-hours"><?php echo nl2br( esc_html( $settings['business_hours'] ) ); ?></p>
			<?php endif; ?>
		</div>
	</div>

	<button type="button" class="nasm-ot-search-again" id="nasm-ot-search-again"><?php esc_html_e( '← Track another order', 'nasm-order-tracking' ); ?></button>
</div>
