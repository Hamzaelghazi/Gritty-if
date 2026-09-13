<?php
/**
 * Dashboard screen template.
 *
 * Available variables: $counts, $recent, $activity, $processing, $in_transit,
 * $delivered, $delayed, $has_orders, $tracking_page_url, $company_name.
 *
 * @package Nasm_Order_Tracking
 */

use Nasm\OrderTracking\Admin\AdminMenu;
use Nasm\OrderTracking\Helpers\Formatter;

defined( 'ABSPATH' ) || exit;

$active = 'dashboard';
?>
<div class="wrap nasm-ot-wrap">
	<?php include NASM_OT_PATH . 'templates/admin/partials/nav.php'; ?>

	<div class="nasm-ot-page">
		<div class="nasm-ot-page__head">
			<h1><?php esc_html_e( 'Dashboard', 'nasm-order-tracking' ); ?></h1>
			<a href="<?php echo esc_url( AdminMenu::url( 'add-tracking' ) ); ?>" class="button button-primary nasm-ot-btn">
				<?php esc_html_e( '+ Add Tracking', 'nasm-order-tracking' ); ?>
			</a>
		</div>

		<?php if ( ! $has_orders ) : ?>
			<div class="nasm-ot-welcome">
				<div class="nasm-ot-welcome__text">
					<h2><?php esc_html_e( "Welcome! Let's track your first order.", 'nasm-order-tracking' ); ?></h2>
					<p><?php esc_html_e( "You manage every tracking update yourself — no carrier accounts to connect. Here's the whole workflow:", 'nasm-order-tracking' ); ?></p>
					<ol class="nasm-ot-steps">
						<li>
							<strong><?php esc_html_e( 'Add your first order', 'nasm-order-tracking' ); ?></strong>
							<span><?php esc_html_e( 'Order number, customer last name, and a starting status — that\'s all it takes.', 'nasm-order-tracking' ); ?></span>
						</li>
						<li>
							<strong><?php esc_html_e( 'Update the status as it ships', 'nasm-order-tracking' ); ?></strong>
							<span><?php esc_html_e( 'Each change is saved to the order\'s timeline, so the customer always sees full history.', 'nasm-order-tracking' ); ?></span>
						</li>
						<li>
							<strong><?php esc_html_e( 'Customers track it themselves', 'nasm-order-tracking' ); ?></strong>
							<span><?php esc_html_e( 'They enter their order number and last name on your Track Order page.', 'nasm-order-tracking' ); ?></span>
						</li>
					</ol>
					<div class="nasm-ot-welcome__actions">
						<a href="<?php echo esc_url( AdminMenu::url( 'add-tracking' ) ); ?>" class="button button-primary button-hero"><?php esc_html_e( 'Add Your First Order', 'nasm-order-tracking' ); ?></a>
						<a href="<?php echo esc_url( AdminMenu::url( 'settings' ) ); ?>" class="button button-hero"><?php esc_html_e( 'Review Settings First', 'nasm-order-tracking' ); ?></a>
					</div>
				</div>
			</div>
		<?php endif; ?>

		<div class="nasm-ot-quicklinks">
			<a href="<?php echo esc_url( AdminMenu::url( 'add-tracking' ) ); ?>" class="nasm-ot-quicklink">
				<span class="dashicons dashicons-plus-alt2"></span>
				<span><?php esc_html_e( 'Add Tracking', 'nasm-order-tracking' ); ?></span>
			</a>
			<?php if ( $tracking_page_url ) : ?>
				<a href="<?php echo esc_url( $tracking_page_url ); ?>" target="_blank" rel="noopener noreferrer" class="nasm-ot-quicklink">
					<span class="dashicons dashicons-admin-links"></span>
					<span><?php esc_html_e( 'View Track Order Page', 'nasm-order-tracking' ); ?></span>
				</a>
			<?php endif; ?>
			<a href="<?php echo esc_url( AdminMenu::url( 'import' ) ); ?>" class="nasm-ot-quicklink">
				<span class="dashicons dashicons-upload"></span>
				<span><?php esc_html_e( 'Import Orders (CSV)', 'nasm-order-tracking' ); ?></span>
			</a>
			<a href="<?php echo esc_url( AdminMenu::url( 'statuses' ) ); ?>" class="nasm-ot-quicklink">
				<span class="dashicons dashicons-tag"></span>
				<span><?php esc_html_e( 'Customize Statuses', 'nasm-order-tracking' ); ?></span>
			</a>
		</div>

		<div class="nasm-ot-cards">
			<a class="nasm-ot-card" href="<?php echo esc_url( AdminMenu::url( 'orders' ) ); ?>">
				<span class="nasm-ot-card__icon"><span class="dashicons dashicons-clipboard"></span></span>
				<span class="nasm-ot-card__label"><?php esc_html_e( 'Total Orders', 'nasm-order-tracking' ); ?></span>
				<span class="nasm-ot-card__value"><?php echo esc_html( number_format_i18n( $counts['total'] ) ); ?></span>
			</a>
			<a class="nasm-ot-card nasm-ot-card--blue" href="<?php echo esc_url( AdminMenu::url( 'orders', array( 'bucket' => 'processing' ) ) ); ?>">
				<span class="nasm-ot-card__icon"><span class="dashicons dashicons-update"></span></span>
				<span class="nasm-ot-card__label"><?php esc_html_e( 'Processing', 'nasm-order-tracking' ); ?></span>
				<span class="nasm-ot-card__value"><?php echo esc_html( number_format_i18n( $processing ) ); ?></span>
			</a>
			<a class="nasm-ot-card nasm-ot-card--amber" href="<?php echo esc_url( AdminMenu::url( 'orders', array( 'bucket' => 'in_transit' ) ) ); ?>">
				<span class="nasm-ot-card__icon"><span class="dashicons dashicons-airplane"></span></span>
				<span class="nasm-ot-card__label"><?php esc_html_e( 'In Transit', 'nasm-order-tracking' ); ?></span>
				<span class="nasm-ot-card__value"><?php echo esc_html( number_format_i18n( $in_transit ) ); ?></span>
			</a>
			<a class="nasm-ot-card nasm-ot-card--green" href="<?php echo esc_url( AdminMenu::url( 'orders', array( 'status' => 'delivered' ) ) ); ?>">
				<span class="nasm-ot-card__icon"><span class="dashicons dashicons-saved"></span></span>
				<span class="nasm-ot-card__label"><?php esc_html_e( 'Delivered', 'nasm-order-tracking' ); ?></span>
				<span class="nasm-ot-card__value"><?php echo esc_html( number_format_i18n( $delivered ) ); ?></span>
			</a>
			<a class="nasm-ot-card nasm-ot-card--red" href="<?php echo esc_url( AdminMenu::url( 'orders', array( 'status' => 'delayed' ) ) ); ?>">
				<span class="nasm-ot-card__icon"><span class="dashicons dashicons-warning"></span></span>
				<span class="nasm-ot-card__label"><?php esc_html_e( 'Delayed', 'nasm-order-tracking' ); ?></span>
				<span class="nasm-ot-card__value"><?php echo esc_html( number_format_i18n( $delayed ) ); ?></span>
			</a>
			<div class="nasm-ot-card nasm-ot-card--static">
				<span class="nasm-ot-card__icon"><span class="dashicons dashicons-clock"></span></span>
				<span class="nasm-ot-card__label"><?php esc_html_e( "Today's Updates", 'nasm-order-tracking' ); ?></span>
				<span class="nasm-ot-card__value"><?php echo esc_html( number_format_i18n( $counts['today_updates'] ) ); ?></span>
			</div>
		</div>

		<div class="nasm-ot-columns">
			<div class="nasm-ot-panel">
				<div class="nasm-ot-panel__head">
					<h2><?php esc_html_e( 'Recently Updated Orders', 'nasm-order-tracking' ); ?></h2>
					<a href="<?php echo esc_url( AdminMenu::url( 'orders' ) ); ?>"><?php esc_html_e( 'View all', 'nasm-order-tracking' ); ?></a>
				</div>
				<?php if ( empty( $recent ) ) : ?>
					<div class="nasm-ot-empty">
						<span class="dashicons dashicons-clipboard"></span>
						<p><?php esc_html_e( 'No orders yet. Once you add tracking, updates will show up here.', 'nasm-order-tracking' ); ?></p>
					</div>
				<?php else : ?>
					<table class="nasm-ot-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Order #', 'nasm-order-tracking' ); ?></th>
								<th><?php esc_html_e( 'Customer', 'nasm-order-tracking' ); ?></th>
								<th><?php esc_html_e( 'Status', 'nasm-order-tracking' ); ?></th>
								<th><?php esc_html_e( 'Updated', 'nasm-order-tracking' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $recent as $order ) : ?>
								<tr>
									<td>
										<a href="<?php echo esc_url( AdminMenu::url( 'order-details', array( 'order_id' => $order['id'] ) ) ); ?>">
											#<?php echo esc_html( $order['order_number'] ); ?>
										</a>
									</td>
									<td><?php echo esc_html( trim( $order['first_name'] . ' ' . $order['last_name'] ) ); ?></td>
									<td><?php echo wp_kses_post( Formatter::status_badge_html( $order['current_status'] ) ); ?></td>
									<td><?php echo esc_html( human_time_diff( strtotime( $order['updated_at'] ), current_time( 'timestamp' ) ) . ' ' . __( 'ago', 'nasm-order-tracking' ) ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>

			<div class="nasm-ot-panel">
				<div class="nasm-ot-panel__head">
					<h2><?php esc_html_e( 'Recent Activity', 'nasm-order-tracking' ); ?></h2>
					<a href="<?php echo esc_url( AdminMenu::url( 'activity-logs' ) ); ?>"><?php esc_html_e( 'View all', 'nasm-order-tracking' ); ?></a>
				</div>
				<?php if ( empty( $activity ) ) : ?>
					<div class="nasm-ot-empty">
						<span class="dashicons dashicons-backup"></span>
						<p><?php esc_html_e( 'Activity like new orders and status changes will show up here.', 'nasm-order-tracking' ); ?></p>
					</div>
				<?php else : ?>
					<ul class="nasm-ot-activity">
						<?php foreach ( $activity as $entry ) : ?>
							<li>
								<span class="nasm-ot-activity__dot"></span>
								<div>
									<p><?php echo esc_html( str_replace( '_', ' ', $entry['action'] ) ); ?></p>
									<time><?php echo esc_html( human_time_diff( strtotime( $entry['created_at'] ), current_time( 'timestamp' ) ) . ' ' . __( 'ago', 'nasm-order-tracking' ) ); ?></time>
								</div>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
