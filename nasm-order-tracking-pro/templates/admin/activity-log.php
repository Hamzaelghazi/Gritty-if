<?php
/**
 * Activity log screen template.
 *
 * Available variables: $logs, $total, $total_pages, $page.
 *
 * @package Nasm_Order_Tracking
 */

use Nasm\OrderTracking\Admin\AdminMenu;

defined( 'ABSPATH' ) || exit;

$active = 'activity-logs';
?>
<div class="wrap nasm-ot-wrap">
	<?php include NASM_OT_PATH . 'templates/admin/partials/nav.php'; ?>

	<div class="nasm-ot-page">
		<div class="nasm-ot-page__head">
			<h1><?php esc_html_e( 'Activity Logs', 'nasm-order-tracking' ); ?></h1>
		</div>

		<div class="nasm-ot-panel">
			<table class="nasm-ot-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'When', 'nasm-order-tracking' ); ?></th>
						<th><?php esc_html_e( 'User', 'nasm-order-tracking' ); ?></th>
						<th><?php esc_html_e( 'Action', 'nasm-order-tracking' ); ?></th>
						<th><?php esc_html_e( 'Object', 'nasm-order-tracking' ); ?></th>
						<th><?php esc_html_e( 'Details', 'nasm-order-tracking' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $logs ) ) : ?>
						<tr><td colspan="5"><?php esc_html_e( 'No activity recorded yet.', 'nasm-order-tracking' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $logs as $log ) : ?>
							<?php $user = $log['user_id'] ? get_userdata( $log['user_id'] ) : null; ?>
							<tr>
								<td><?php echo esc_html( mysql2date( get_option( 'date_format' ) . ' g:i a', $log['created_at'] ) ); ?></td>
								<td><?php echo esc_html( $user ? $user->display_name : __( 'System', 'nasm-order-tracking' ) ); ?></td>
								<td><?php echo esc_html( str_replace( '_', ' ', $log['action'] ) ); ?></td>
								<td><?php echo esc_html( trim( $log['object_type'] . ' #' . $log['object_id'], ' #' ) ); ?></td>
								<td><?php echo esc_html( $log['details'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>

			<?php if ( $total_pages > 1 ) : ?>
				<div class="nasm-ot-pagination">
					<?php for ( $i = 1; $i <= $total_pages; $i++ ) : ?>
						<a href="<?php echo esc_url( AdminMenu::url( 'activity-logs', array( 'paged' => $i ) ) ); ?>" class="<?php echo $i === $page ? 'is-active' : ''; ?>"><?php echo esc_html( $i ); ?></a>
					<?php endfor; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>
