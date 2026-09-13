<?php
/**
 * Statuses management screen template.
 *
 * Available variables: $statuses, $errors.
 *
 * @package Nasm_Order_Tracking
 */

use Nasm\OrderTracking\Admin\AdminMenu;
use Nasm\OrderTracking\Admin\StatusesPage;

defined( 'ABSPATH' ) || exit;

$active = 'statuses';
?>
<div class="wrap nasm-ot-wrap">
	<?php include NASM_OT_PATH . 'templates/admin/partials/nav.php'; ?>

	<div class="nasm-ot-page">
		<div class="nasm-ot-page__head">
			<h1><?php esc_html_e( 'Statuses', 'nasm-order-tracking' ); ?></h1>
		</div>

		<?php if ( isset( $_GET['saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Status saved.', 'nasm-order-tracking' ); ?></p></div>
		<?php endif; ?>
		<?php if ( ! empty( $errors ) ) : ?>
			<div class="notice notice-error"><p><?php echo esc_html( implode( ' ', $errors ) ); ?></p></div>
		<?php endif; ?>

		<div class="nasm-ot-columns">
			<div class="nasm-ot-panel nasm-ot-panel--wide">
				<div class="nasm-ot-panel__head"><h2><?php esc_html_e( 'Existing Statuses', 'nasm-order-tracking' ); ?></h2></div>
				<table class="nasm-ot-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Order', 'nasm-order-tracking' ); ?></th>
							<th><?php esc_html_e( 'Status', 'nasm-order-tracking' ); ?></th>
							<th><?php esc_html_e( 'Slug', 'nasm-order-tracking' ); ?></th>
							<th><?php esc_html_e( 'Active', 'nasm-order-tracking' ); ?></th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $statuses as $status ) : ?>
							<tr>
								<td><?php echo esc_html( $status['sort_order'] ); ?></td>
								<td>
									<span class="nasm-ot-badge" style="background-color:<?php echo esc_attr( $status['color'] ); ?>1a;color:<?php echo esc_attr( $status['color'] ); ?>;">
										<span class="dashicons <?php echo esc_attr( $status['icon'] ); ?>"></span>
										<?php echo esc_html( $status['label'] ); ?>
									</span>
								</td>
								<td><code><?php echo esc_html( $status['slug'] ); ?></code></td>
								<td><?php echo $status['is_active'] ? esc_html__( 'Yes', 'nasm-order-tracking' ) : esc_html__( 'No', 'nasm-order-tracking' ); ?></td>
								<td class="nasm-ot-table__actions">
									<a href="#" class="nasm-ot-edit-status"
										data-id="<?php echo esc_attr( $status['id'] ); ?>"
										data-label="<?php echo esc_attr( $status['label'] ); ?>"
										data-slug="<?php echo esc_attr( $status['slug'] ); ?>"
										data-icon="<?php echo esc_attr( $status['icon'] ); ?>"
										data-color="<?php echo esc_attr( $status['color'] ); ?>"
										data-description="<?php echo esc_attr( $status['description'] ); ?>"
										data-sort="<?php echo esc_attr( $status['sort_order'] ); ?>"
										data-active="<?php echo esc_attr( $status['is_active'] ); ?>"
										data-system="<?php echo esc_attr( $status['is_system'] ); ?>">
										<?php esc_html_e( 'Edit', 'nasm-order-tracking' ); ?>
									</a>
									<?php if ( empty( $status['is_system'] ) ) : ?>
										|
										<a href="<?php echo esc_url( wp_nonce_url( AdminMenu::url( 'statuses', array( 'action' => 'delete', 'status_id' => $status['id'] ) ), 'nasm_ot_delete_status_' . $status['id'] ) ); ?>" class="nasm-ot-delete-link" data-confirm="1">
											<?php esc_html_e( 'Delete', 'nasm-order-tracking' ); ?>
										</a>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>

			<div class="nasm-ot-panel">
				<div class="nasm-ot-panel__head"><h2 id="nasm-ot-status-form-title"><?php esc_html_e( 'Add Status', 'nasm-order-tracking' ); ?></h2></div>
				<form method="post" class="nasm-ot-form" id="nasm-ot-status-form">
					<?php wp_nonce_field( StatusesPage::NONCE_ACTION, 'nasm_ot_nonce' ); ?>
					<input type="hidden" name="status_id" value="" />

					<label>
						<span><?php esc_html_e( 'Label', 'nasm-order-tracking' ); ?></span>
						<input type="text" name="label" required />
					</label>
					<label>
						<span><?php esc_html_e( 'Slug', 'nasm-order-tracking' ); ?></span>
						<input type="text" name="slug" />
						<small class="description"><?php esc_html_e( 'Leave blank to auto-generate. Cannot be changed after creation.', 'nasm-order-tracking' ); ?></small>
					</label>
					<label>
						<span><?php esc_html_e( 'Icon (Dashicon class)', 'nasm-order-tracking' ); ?></span>
						<input type="text" name="icon" value="dashicons-marker" />
					</label>
					<label>
						<span><?php esc_html_e( 'Color', 'nasm-order-tracking' ); ?></span>
						<input type="text" name="color" value="#2c6ecb" class="nasm-ot-color-picker" />
					</label>
					<label>
						<span><?php esc_html_e( 'Description', 'nasm-order-tracking' ); ?></span>
						<textarea name="description" rows="2"></textarea>
					</label>
					<label>
						<span><?php esc_html_e( 'Sort Order', 'nasm-order-tracking' ); ?></span>
						<input type="number" name="sort_order" value="0" />
					</label>
					<label class="nasm-ot-checkbox">
						<input type="checkbox" name="is_active" value="1" checked />
						<span><?php esc_html_e( 'Active', 'nasm-order-tracking' ); ?></span>
					</label>

					<div class="nasm-ot-form__actions">
						<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Status', 'nasm-order-tracking' ); ?></button>
						<button type="button" class="button" id="nasm-ot-status-reset"><?php esc_html_e( 'Cancel', 'nasm-order-tracking' ); ?></button>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
