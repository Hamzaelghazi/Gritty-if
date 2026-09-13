<?php
/**
 * Orders list screen template.
 *
 * Available variables: $table, $statuses, $carriers.
 *
 * @package Nasm_Order_Tracking
 */

use Nasm\OrderTracking\Admin\AdminMenu;

defined( 'ABSPATH' ) || exit;

$active       = 'orders';
$current_s    = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$current_status = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$current_carrier = isset( $_GET['carrier'] ) ? sanitize_text_field( wp_unslash( $_GET['carrier'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
?>
<div class="wrap nasm-ot-wrap">
	<?php include NASM_OT_PATH . 'templates/admin/partials/nav.php'; ?>

	<div class="nasm-ot-page">
		<div class="nasm-ot-page__head">
			<h1><?php esc_html_e( 'Orders', 'nasm-order-tracking' ); ?></h1>
			<a href="<?php echo esc_url( AdminMenu::url( 'add-tracking' ) ); ?>" class="button button-primary nasm-ot-btn">
				<?php esc_html_e( '+ Add Tracking', 'nasm-order-tracking' ); ?>
			</a>
		</div>

		<?php if ( isset( $_GET['deleted'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Order(s) deleted.', 'nasm-order-tracking' ); ?></p></div>
		<?php endif; ?>

		<form method="get" class="nasm-ot-filters">
			<input type="hidden" name="page" value="nasm-tracking-orders" />
			<input type="search" name="s" value="<?php echo esc_attr( $current_s ); ?>" placeholder="<?php esc_attr_e( 'Search order #, name, email, tracking #…', 'nasm-order-tracking' ); ?>" />

			<select name="status">
				<option value=""><?php esc_html_e( 'All Statuses', 'nasm-order-tracking' ); ?></option>
				<?php foreach ( $statuses as $status ) : ?>
					<option value="<?php echo esc_attr( $status['slug'] ); ?>" <?php selected( $current_status, $status['slug'] ); ?>>
						<?php echo esc_html( $status['label'] ); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<select name="carrier">
				<option value=""><?php esc_html_e( 'All Carriers', 'nasm-order-tracking' ); ?></option>
				<?php foreach ( $carriers as $carrier ) : ?>
					<option value="<?php echo esc_attr( $carrier ); ?>" <?php selected( $current_carrier, $carrier ); ?>>
						<?php echo esc_html( $carrier ); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<input type="date" name="date_from" value="<?php echo esc_attr( isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '' ); ?>" />
			<input type="date" name="date_to" value="<?php echo esc_attr( isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '' ); ?>" />

			<button type="submit" class="button"><?php esc_html_e( 'Filter', 'nasm-order-tracking' ); ?></button>
			<?php if ( $current_s || $current_status || $current_carrier ) : ?>
				<a class="button-link" href="<?php echo esc_url( AdminMenu::url( 'orders' ) ); ?>"><?php esc_html_e( 'Clear', 'nasm-order-tracking' ); ?></a>
			<?php endif; ?>
		</form>

		<form method="post" class="nasm-ot-table-form">
			<?php wp_nonce_field( 'bulk-orders' ); ?>
			<?php $table->display(); ?>
		</form>
	</div>
</div>
