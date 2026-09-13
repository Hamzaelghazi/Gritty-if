<?php
/**
 * Plugin settings screen: company info, shipping defaults, branding, email.
 *
 * @package Nasm_Order_Tracking
 */

namespace Nasm\OrderTracking\Admin;

use Nasm\OrderTracking\Helpers\Settings;
use Nasm\OrderTracking\Database\ActivityLogger;

defined( 'ABSPATH' ) || exit;

class SettingsPage {

	const NONCE_ACTION = 'nasm_ot_save_settings';

	/**
	 * Render the settings screen, handling submission first.
	 */
	public function render() {
		if ( ! current_user_can( AdminMenu::CAPABILITY ) ) {
			return;
		}

		$saved = false;

		if ( ! empty( $_POST['nasm_ot_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nasm_ot_nonce'] ) ), self::NONCE_ACTION ) ) {
			$this->handle_submit();
			$saved = true;
		}

		$settings = Settings::all();

		include NASM_OT_PATH . 'templates/admin/settings.php';
	}

	/**
	 * Sanitize and persist submitted settings.
	 */
	protected function handle_submit() {
		check_admin_referer( self::NONCE_ACTION, 'nasm_ot_nonce' );

		$fields = array(
			'company_name'        => 'sanitize_text_field',
			'support_email'       => 'sanitize_email',
			'support_phone'       => 'sanitize_text_field',
			'business_address'    => 'sanitize_textarea_field',
			'warehouse_address'   => 'sanitize_textarea_field',
			'business_hours'      => 'sanitize_textarea_field',
			'timezone'            => 'sanitize_text_field',
			'processing_time'     => 'sanitize_text_field',
			'freight_delivery'    => 'sanitize_text_field',
			'free_shipping_note'  => 'sanitize_text_field',
			'brand_primary_color' => 'sanitize_hex_color',
			'brand_accent_color'  => 'sanitize_hex_color',
			'email_from_name'     => 'sanitize_text_field',
			'email_from_address'  => 'sanitize_email',
		);

		$values = array();
		foreach ( $fields as $key => $sanitizer ) {
			if ( isset( $_POST[ $key ] ) ) {
				$values[ $key ] = call_user_func( $sanitizer, wp_unslash( $_POST[ $key ] ) );
			}
		}

		$values['notify_on_update']         = empty( $_POST['notify_on_update'] ) ? 0 : 1;
		$values['delete_data_on_uninstall'] = empty( $_POST['delete_data_on_uninstall'] ) ? 0 : 1;

		if ( isset( $_POST['company_logo_id'] ) ) {
			$values['company_logo_id'] = absint( $_POST['company_logo_id'] );
		}

		Settings::update( $values );
		ActivityLogger::log( 'settings_updated', 'settings', 0 );
	}
}
