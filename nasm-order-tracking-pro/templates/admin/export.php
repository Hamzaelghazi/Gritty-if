<?php
/**
 * Export screen template.
 *
 * @package Nasm_Order_Tracking
 */

use Nasm\OrderTracking\Admin\AdminMenu;
use Nasm\OrderTracking\Admin\ExportPage;
use Nasm\OrderTracking\Database\OrdersRepository;

defined( 'ABSPATH' ) || exit;

$active = 'export';
$total  = OrdersRepository::dashboard_counts()['total'];
?>
<div class="wrap nasm-ot-wrap">
	<?php include NASM_OT_PATH . 'templates/admin/partials/nav.php'; ?>

	<div class="nasm-ot-page">
		<div class="nasm-ot-page__head">
			<h1><?php esc_html_e( 'Export Orders', 'nasm-order-tracking' ); ?></h1>
		</div>

		<div class="nasm-ot-panel">
			<div class="nasm-ot-panel__head"><h2><?php esc_html_e( 'Download CSV', 'nasm-order-tracking' ); ?></h2></div>
			<p><?php echo esc_html( sprintf( __( '%d order(s) will be included in the export.', 'nasm-order-tracking' ), $total ) ); ?></p>
			<a class="button button-primary" href="<?php echo esc_url( wp_nonce_url( AdminMenu::url( 'export', array( 'download' => 1 ) ), ExportPage::NONCE_ACTION ) ); ?>">
				<?php esc_html_e( 'Download CSV', 'nasm-order-tracking' ); ?>
			</a>
		</div>
	</div>
</div>
