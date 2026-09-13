<?php
/**
 * Branded order status update email.
 *
 * Available variables: $order, $status_label, $note, $settings, $tracking_url.
 *
 * @package Nasm_Order_Tracking
 */

defined( 'ABSPATH' ) || exit;

$primary = ! empty( $settings['brand_primary_color'] ) ? $settings['brand_primary_color'] : '#1b1f27';
$accent  = ! empty( $settings['brand_accent_color'] ) ? $settings['brand_accent_color'] : '#2c6ecb';
$logo_id = ! empty( $settings['company_logo_id'] ) ? (int) $settings['company_logo_id'] : 0;
$logo    = $logo_id ? wp_get_attachment_image_url( $logo_id, 'medium' ) : '';
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo esc_html( get_bloginfo( 'name' ) ); ?></title>
</head>
<body style="margin:0;padding:0;background-color:#f4f5f7;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f5f7;padding:32px 0;">
	<tr>
		<td align="center">
			<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:12px;overflow:hidden;">
				<tr>
					<td style="background-color:<?php echo esc_attr( $primary ); ?>;padding:28px 32px;text-align:center;">
						<?php if ( $logo ) : ?>
							<img src="<?php echo esc_url( $logo ); ?>" alt="<?php echo esc_attr( $settings['company_name'] ); ?>" style="max-height:40px;">
						<?php else : ?>
							<span style="color:#ffffff;font-size:20px;font-weight:700;letter-spacing:0.5px;"><?php echo esc_html( $settings['company_name'] ); ?></span>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<td style="padding:32px;">
						<h1 style="margin:0 0 8px;font-size:22px;color:#1b1f27;"><?php esc_html_e( 'Your order has been updated', 'nasm-order-tracking' ); ?></h1>
						<p style="margin:0 0 24px;font-size:15px;color:#5a6270;line-height:1.6;">
							<?php
							printf(
								/* translators: %s: order number */
								esc_html__( 'Order #%s is now:', 'nasm-order-tracking' ),
								esc_html( $order['order_number'] )
							);
							?>
						</p>
						<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f5f7;border-radius:8px;margin-bottom:24px;">
							<tr>
								<td style="padding:20px 24px;">
									<span style="display:inline-block;background-color:<?php echo esc_attr( $accent ); ?>;color:#ffffff;font-size:13px;font-weight:600;padding:6px 14px;border-radius:999px;"><?php echo esc_html( $status_label ); ?></span>
									<?php if ( ! empty( $note ) ) : ?>
										<p style="margin:14px 0 0;font-size:14px;color:#3a3f47;line-height:1.6;"><?php echo esc_html( $note ); ?></p>
									<?php endif; ?>
								</td>
							</tr>
						</table>

						<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;color:#3a3f47;margin-bottom:24px;">
							<?php if ( ! empty( $order['tracking_number'] ) ) : ?>
							<tr>
								<td style="padding:6px 0;color:#8a94a6;"><?php esc_html_e( 'Tracking Number', 'nasm-order-tracking' ); ?></td>
								<td style="padding:6px 0;text-align:right;font-weight:600;"><?php echo esc_html( $order['tracking_number'] ); ?></td>
							</tr>
							<?php endif; ?>
							<?php if ( ! empty( $order['carrier'] ) ) : ?>
							<tr>
								<td style="padding:6px 0;color:#8a94a6;"><?php esc_html_e( 'Carrier', 'nasm-order-tracking' ); ?></td>
								<td style="padding:6px 0;text-align:right;font-weight:600;"><?php echo esc_html( $order['carrier'] ); ?></td>
							</tr>
							<?php endif; ?>
							<?php if ( ! empty( $order['estimated_delivery'] ) ) : ?>
							<tr>
								<td style="padding:6px 0;color:#8a94a6;"><?php esc_html_e( 'Estimated Delivery', 'nasm-order-tracking' ); ?></td>
								<td style="padding:6px 0;text-align:right;font-weight:600;"><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $order['estimated_delivery'] ) ) ); ?></td>
							</tr>
							<?php endif; ?>
						</table>

						<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
							<tr>
								<td align="center">
									<a href="<?php echo esc_url( $tracking_url ); ?>" style="display:inline-block;background-color:<?php echo esc_attr( $accent ); ?>;color:#ffffff;text-decoration:none;font-size:15px;font-weight:600;padding:14px 32px;border-radius:8px;"><?php esc_html_e( 'Track Your Order', 'nasm-order-tracking' ); ?></a>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td style="padding:24px 32px;background-color:#f4f5f7;text-align:center;">
						<p style="margin:0 0 4px;font-size:13px;color:#8a94a6;"><?php echo esc_html( $settings['support_email'] ); ?> &middot; <?php echo esc_html( $settings['support_phone'] ); ?></p>
						<p style="margin:0;font-size:12px;color:#b0b6c0;">&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( $settings['company_name'] ); ?></p>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
</body>
</html>
