<?php
/**
 * Registers the "Nasm Tracking" admin sidebar and routes each submenu page.
 *
 * @package Nasm_Order_Tracking
 */

namespace Nasm\OrderTracking\Admin;

defined( 'ABSPATH' ) || exit;

class AdminMenu {

	const CAPABILITY = 'manage_woocommerce';
	const SLUG       = 'nasm-tracking';

	/**
	 * Register WordPress hooks.
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_notices', array( $this, 'maybe_render_tracking_page_conflict_notice' ) );
	}

	/**
	 * Build the sidebar menu tree.
	 */
	public function add_menu() {
		add_menu_page(
			__( 'Nasm Tracking', 'nasm-order-tracking' ),
			__( 'Nasm Tracking', 'nasm-order-tracking' ),
			self::CAPABILITY,
			self::SLUG,
			array( new DashboardPage(), 'render' ),
			'dashicons-location-alt',
			56
		);

		$pages = array(
			'dashboard'     => array( __( 'Dashboard', 'nasm-order-tracking' ), array( new DashboardPage(), 'render' ) ),
			'orders'        => array( __( 'Orders', 'nasm-order-tracking' ), array( new OrdersListPage(), 'render' ) ),
			'add-tracking'  => array( __( 'Add Tracking', 'nasm-order-tracking' ), array( new AddTrackingPage(), 'render' ) ),
			'order-details' => array( null, array( new OrderDetailsPage(), 'render' ) ),
			'statuses'      => array( __( 'Statuses', 'nasm-order-tracking' ), array( new StatusesPage(), 'render' ) ),
			'wc-sync'       => array( __( 'WooCommerce Sync', 'nasm-order-tracking' ), array( new WcSyncPage(), 'render' ) ),
			'settings'      => array( __( 'Settings', 'nasm-order-tracking' ), array( new SettingsPage(), 'render' ) ),
			'import'        => array( __( 'Import', 'nasm-order-tracking' ), array( new ImportPage(), 'render' ) ),
			'export'        => array( __( 'Export', 'nasm-order-tracking' ), array( new ExportPage(), 'render' ) ),
			'activity-logs' => array( __( 'Activity Logs', 'nasm-order-tracking' ), array( new ActivityLogPage(), 'render' ) ),
		);

		foreach ( $pages as $slug => $page ) {
			list( $label, $callback ) = $page;

			add_submenu_page(
				self::SLUG,
				$label ?: __( 'Order Details', 'nasm-order-tracking' ),
				$label ?: __( 'Order Details', 'nasm-order-tracking' ),
				self::CAPABILITY,
				'dashboard' === $slug ? self::SLUG : self::SLUG . '-' . $slug,
				$callback
			);
		}

		// Hide the order-details page from the visible submenu list, but keep it routable.
		remove_submenu_page( self::SLUG, self::SLUG . '-order-details' );
	}

	/**
	 * Enqueue admin CSS/JS only on plugin screens.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_assets( $hook ) {
		if ( strpos( $hook, self::SLUG ) === false ) {
			return;
		}

		wp_enqueue_style( 'nasm-ot-admin', NASM_OT_URL . 'assets/css/admin.css', array(), NASM_OT_VERSION );
		wp_enqueue_media();
		wp_enqueue_script( 'nasm-ot-admin', NASM_OT_URL . 'assets/js/admin.js', array( 'jquery', 'wp-color-picker' ), NASM_OT_VERSION, true );
		wp_enqueue_style( 'wp-color-picker' );

		wp_localize_script(
			'nasm-ot-admin',
			'nasmOtAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'nasm_ot_admin' ),
				'i18n'    => array(
					'confirmDelete'  => __( 'Are you sure you want to delete this tracking record? This cannot be undone.', 'nasm-order-tracking' ),
					'noOrdersFound'  => __( 'No matching orders found.', 'nasm-order-tracking' ),
					'unknownCustomer' => __( 'Unknown customer', 'nasm-order-tracking' ),
				),
			)
		);
	}

	/**
	 * If activation found an existing page at the "track-order" slug that
	 * didn't already contain our shortcode, it left that page untouched and
	 * created ours at a different slug instead. Tell the admin so they can
	 * reconcile the two rather than wondering why the tracking page setting
	 * points somewhere unexpected.
	 */
	public function maybe_render_tracking_page_conflict_notice() {
		$conflict_id = get_option( 'nasm_ot_tracking_page_conflict_id' );

		if ( ! $conflict_id || ! current_user_can( self::CAPABILITY ) ) {
			return;
		}

		$conflict_page = get_post( $conflict_id );
		$our_page_id   = get_option( 'nasm_ot_tracking_page_id' );

		if ( ! $conflict_page ) {
			delete_option( 'nasm_ot_tracking_page_conflict_id' );
			return;
		}

		printf(
			'<div class="notice notice-warning is-dismissible"><p>%s</p><p><a href="%s" class="button button-primary">%s</a> <a href="%s" class="button">%s</a></p></div>',
			sprintf(
				/* translators: %s: existing page title */
				esc_html__( 'Nasm Order Tracking: a page called "%s" already exists at the /track-order/ URL, so a separate tracking page was created instead of replacing it.', 'nasm-order-tracking' ),
				esc_html( $conflict_page->post_title )
			),
			esc_url( get_edit_post_link( $conflict_id, 'raw' ) ),
			esc_html__( 'Edit existing page', 'nasm-order-tracking' ),
			esc_url( $our_page_id ? get_edit_post_link( $our_page_id, 'raw' ) : self::url( 'settings' ) ),
			esc_html__( 'Edit new tracking page', 'nasm-order-tracking' )
		);
	}

	/**
	 * Build the URL for a given submenu page.
	 *
	 * @param string $slug Submenu slug suffix (e.g. 'orders', 'add-tracking').
	 * @param array  $args Extra query args.
	 * @return string
	 */
	public static function url( $slug = '', array $args = array() ) {
		$page = $slug ? self::SLUG . '-' . $slug : self::SLUG;
		$url  = add_query_arg( array_merge( array( 'page' => $page ), $args ), admin_url( 'admin.php' ) );
		return $url;
	}
}
