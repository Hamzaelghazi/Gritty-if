<?php
/**
 * Order details screen template.
 *
 * Available variables: $order, $history, $timeline, $error.
 *
 * @package Nasm_Order_Tracking
 */

use Nasm\OrderTracking\Admin\AdminMenu;

defined( 'ABSPATH' ) || exit;

$active = 'orders';
?>
<div class="wrap nasm-ot-wrap" id="nasm-ot-printable">
	<div class="nasm-ot-no-print">
		<?php include NASM_OT_PATH . 'templates/admin/partials/nav.php'; ?>
	</div>

	<div class="nasm-ot-page">
		<div class="nasm-ot-page__head nasm-ot-no-print">
			<h1>
				<a href="<?php echo esc_url( AdminMenu::url( 'orders' ) ); ?>" class="nasm-ot-back">&larr;</a>
				<?php echo esc_html( sprintf( __( 'Order #%s', 'nasm-order-tracking' ), $order['order_number'] ) ); ?>
			</h1>
			<div class="nasm-ot-actions">
				<a class="button" href="<?php echo esc_url( AdminMenu::url( 'add-tracking', array( 'order_id' => $order['id'] ) ) ); ?>"><?php esc_html_e( 'Edit', 'nasm-order-tracking' ); ?></a>
				<a class="button" href="<?php echo esc_url( wp_nonce_url( AdminMenu::url( 'order-details', array( 'order_id' => $order['id'], 'do' => 'duplicate' ) ), 'nasm_ot_duplicate_' . $order['id'] ) ); ?>"><?php esc_html_e( 'Duplicate', 'nasm-order-tracking' ); ?></a>
				<a class="button" href="<?php echo esc_url( wp_nonce_url( AdminMenu::url( 'order-details', array( 'order_id' => $order['id'], 'do' => 'email' ) ), 'nasm_ot_email_' . $order['id'] ) ); ?>"><?php esc_html_e( 'Email Customer', 'nasm-order-tracking' ); ?></a>
				<button type="button" class="button" onclick="window.print()"><?php esc_html_e( 'Print', 'nasm-order-tracking' ); ?></button>
				<a class="button button-link-delete" href="<?php echo esc_url( wp_nonce_url( AdminMenu::url( 'orders', array( 'action' => 'delete', 'order_id' => $order['id'] ) ), 'nasm_ot_delete_order_' . $order['id'] ) ); ?>" data-confirm="1"><?php esc_html_e( 'Delete', 'nasm-order-tracking' ); ?></a>
			</div>
		</div>

		<?php if ( isset( $_GET['emailed'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$emailed_ok = '1' === sanitize_text_field( wp_unslash( $_GET['emailed'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			?>
			<div class="notice notice-<?php echo $emailed_ok ? 'success' : 'error'; ?> is-dismissible">
				<p><?php echo $emailed_ok ? esc_html__( 'Email sent to customer.', 'nasm-order-tracking' ) : esc_html__( 'Could not send email — check the customer email address.', 'nasm-order-tracking' ); ?></p>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $error ) ) : ?>
			<div class="notice notice-error is-dismissible"><p><?php echo esc_html( $error ); ?></p></div>
		<?php endif; ?>

		<div class="nasm-ot-columns">
			<div class="nasm-ot-panel">
				<div class="nasm-ot-panel__head"><h2><?php esc_html_e( 'Customer & Order Info', 'nasm-order-tracking' ); ?></h2></div>
				<table class="nasm-ot-kv">
					<tr><th><?php esc_html_e( 'Customer', 'nasm-order-tracking' ); ?></th><td><?php echo esc_html( trim( $order['first_name'] . ' ' . $order['last_name'] ) ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Email', 'nasm-order-tracking' ); ?></th><td><?php echo esc_html( $order['email'] ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Order Date', 'nasm-order-tracking' ); ?></th><td><?php echo $order['order_date'] ? esc_html( mysql2date( get_option( 'date_format' ), $order['order_date'] ) ) : '&#8212;'; ?></td></tr>
					<tr><th><?php esc_html_e( 'Estimated Delivery', 'nasm-order-tracking' ); ?></th><td><?php echo $order['estimated_delivery'] ? esc_html( mysql2date( get_option( 'date_format' ), $order['estimated_delivery'] ) ) : '&#8212;'; ?></td></tr>
					<tr><th><?php esc_html_e( 'Tracking Number', 'nasm-order-tracking' ); ?></th><td><?php echo esc_html( $order['tracking_number'] ?: '—' ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Shipping Method', 'nasm-order-tracking' ); ?></th><td><?php echo esc_html( $order['shipping_method'] ?: '—' ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Carrier', 'nasm-order-tracking' ); ?></th><td><?php echo esc_html( $order['carrier'] ?: '—' ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Warehouse', 'nasm-order-tracking' ); ?></th><td><?php echo esc_html( $order['warehouse'] ?: '—' ); ?></td></tr>
					<?php if ( ! empty( $order['delivery_address'] ) ) : ?>
						<tr><th><?php esc_html_e( 'Delivery Address', 'nasm-order-tracking' ); ?></th><td><?php echo nl2br( esc_html( $order['delivery_address'] ) ); ?></td></tr>
					<?php endif; ?>
				</table>

				<?php if ( ! empty( $order['admin_notes'] ) ) : ?>
					<div class="nasm-ot-notes nasm-ot-no-print">
						<h3><?php esc_html_e( 'Internal Notes', 'nasm-order-tracking' ); ?></h3>
						<p><?php echo nl2br( esc_html( $order['admin_notes'] ) ); ?></p>
					</div>
				<?php endif; ?>
			</div>

			<div class="nasm-ot-panel nasm-ot-panel--wide">
				<div class="nasm-ot-panel__head"><h2><?php esc_html_e( 'Shipping Timeline', 'nasm-order-tracking' ); ?></h2></div>

				<?php if ( ! empty( $timeline['exception'] ) ) : ?>
					<div class="nasm-ot-exception" style="border-color:<?php echo esc_attr( $timeline['exception']['color'] ); ?>;">
						<span class="dashicons <?php echo esc_attr( $timeline['exception']['icon'] ); ?>" style="color:<?php echo esc_attr( $timeline['exception']['color'] ); ?>;"></span>
						<div>
							<strong><?php echo esc_html( $timeline['exception']['label'] ); ?></strong>
							<?php if ( ! empty( $timeline['exception']['note'] ) ) : ?>
								<p><?php echo esc_html( $timeline['exception']['note'] ); ?></p>
							<?php endif; ?>
						</div>
					</div>
				<?php endif; ?>

				<ol class="nasm-ot-timeline">
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

				<div class="nasm-ot-history">
					<h3><?php esc_html_e( 'Status History', 'nasm-order-tracking' ); ?></h3>
					<?php if ( empty( $history ) ) : ?>
						<p class="description"><?php esc_html_e( 'No status updates recorded yet.', 'nasm-order-tracking' ); ?></p>
					<?php else : ?>
						<ul class="nasm-ot-history__list">
							<?php foreach ( array_reverse( $history ) as $entry ) : ?>
								<li>
									<strong><?php echo esc_html( mysql2date( get_option( 'date_format' ), $entry['occurred_at'] ) ); ?></strong>
									<span><?php echo esc_html( mysql2date( 'g:i a', $entry['occurred_at'] ) ); ?></span>
									<em><?php echo esc_html( str_replace( '_', ' ', $entry['status'] ) ); ?></em>
									<?php if ( ! empty( $entry['note'] ) ) : ?>
										<p><?php echo esc_html( $entry['note'] ); ?></p>
									<?php endif; ?>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
</div>
