<?php

// save wpeliteplugins product license key
add_action( 'admin_init', 'wpeliteplugins_upd_save_products_license' );

if ( ! function_exists( 'wpeliteplugins_upd_save_products_license' ) ) {
	/**
	 * Save product license and email
	 *
	 * @package WPElite Plugins Updater
	 * @since 1.0.0
	 */
	function wpeliteplugins_upd_save_products_license() {
		if ( ! empty( $_POST['wpeliteplugins_upd_submit'] ) ) {// If click on save button

			$purchase_codes = wpeliteplugins_get_plugins_purchase_code();
			$post_lickey    = $_POST['wpelitepluginsupd_lickey'];
			foreach ( $post_lickey as $plugin_key => $license_key ) {
				$purchase_codes[ $plugin_key ] = $license_key;
			}
			wpeliteplugins_save_plugins_purchase_code( $purchase_codes );

			$wpelitepluginsupd_email = wpeliteplugins_get_plugins_purchase_email();
			$post_email              = $_POST['wpelitepluginsupd_email'];
			foreach ( $post_email as $plugin_key => $email_key ) {
				$wpelitepluginsupd_email[ $plugin_key ] = $email_key;
			}
			wpeliteplugins_save_plugins_purchase_email( $wpelitepluginsupd_email );
			wp_redirect( add_query_arg( array( 'message' => '1' ) ) );
		}
	}
}

// add admin menu pages
$menu_hook = is_multisite() ? 'network_admin_menu' : 'admin_menu';
add_action( $menu_hook, 'wpeliteplugins_upd_admin_menu' );

if ( ! function_exists( 'wpeliteplugins_upd_admin_menu' ) ) {
	/**
	 * Add Admin Menu
	 *
	 * Handles to add admin menus
	 *
	 * @package WPElite Plugins Updater
	 * @since 1.0.0
	 */
	function wpeliteplugins_upd_admin_menu() {
		add_dashboard_page( __( 'The WPElitePlugins Updater', 'woo_min_max_quantities' ), __( 'WPElitePlugins Updater', 'woo_min_max_quantities' ), 'manage_options', 'wpeliteplugins-upd-helper', 'wpeliteplugins_upd_helper_screen' );
	}
}


if ( ! function_exists( 'wpeliteplugins_upd_helper_screen' ) ) {
	/**
	 * wpeliteplugins Helper Page
	 *
	 * Handles to display wpeliteplugins helper page
	 *
	 * @package WPElite Plugins Updater
	 * @since 1.0.0
	 */
	function wpeliteplugins_upd_helper_screen() {
		include_once dirname( __FILE__ ) . '/views/html-upd-helper.php';
	}
}

if ( ! function_exists( 'wpeliteplugins_get_plugins_purchase_code' ) ) {
	/**
	 * Get All Plugins Purchase Code
	 *
	 * Handle to get all plugins purchase code
	 *
	 * @package WPElite Plugins Updater
	 * @since 1.0.0
	 */
	function wpeliteplugins_get_plugins_purchase_code( $plugin_slug = '' ) {
		if ( is_multisite() ) {
			$purchase_codes = get_site_option( 'wpelitepluginsupd_lickey' );
		} else {
			$purchase_codes = get_option( 'wpelitepluginsupd_lickey' );
		}
		$purchase_codes = isset( $purchase_codes[ $plugin_slug ] ) ? $purchase_codes[ $plugin_slug ] : $purchase_codes;
		return $purchase_codes;
	}
}

if ( ! function_exists( 'wpeliteplugins_save_plugins_purchase_code' ) ) {
	/**
	 * Save All Plugins Purchase Code
	 *
	 * Handle to save all plugins purchase code
	 *
	 * @package WPElite Plugins Updater
	 * @since 1.0.0
	 */
	function wpeliteplugins_save_plugins_purchase_code( $purchase_codes = array() ) {

		$purchase_codes = apply_filters( 'wpeliteplugins_save_plugins_purchase_code', $purchase_codes );

		if ( is_multisite() ) {
			update_site_option( 'wpelitepluginsupd_lickey', $purchase_codes );
		} else {
			update_option( 'wpelitepluginsupd_lickey', $purchase_codes );
		}
	}
}

if ( ! function_exists( 'wpeliteplugins_get_plugins_purchase_email' ) ) {
	/**
	 * Get Plugin Purchase Email
	 *
	 * Handle to add plugin into queue for get update using email
	 *
	 * @package WPElite Plugins Updater
	 * @since 1.0.0
	 */
	function wpeliteplugins_get_plugins_purchase_email( $plugin_slug = '' ) {
		if ( is_multisite() ) {
			$purchase_emails = get_site_option( 'wpelitepluginsupd_email' );
		} else {
			$purchase_emails = get_option( 'wpelitepluginsupd_email' );
		}
		$purchase_emails = isset( $purchase_emails[ $plugin_slug ] ) ? $purchase_emails[ $plugin_slug ] : $purchase_emails;
		return $purchase_emails;
	}
}

if ( ! function_exists( 'wpeliteplugins_save_plugins_purchase_email' ) ) {
	/**
	 * Save All Plugins Purchased Product Email
	 *
	 * Handle to save all plugins purchase email
	 *
	 * @package WPElite Plugins Updater
	 * @since 1.0.0
	 */
	function wpeliteplugins_save_plugins_purchase_email( $purchase_emails = array() ) {

		$purchase_emails = apply_filters( 'wpeliteplugins_save_plugins_purchase_email', $purchase_emails );

		if ( is_multisite() ) {
			update_site_option( 'wpelitepluginsupd_email', $purchase_emails );
		} else {
			update_option( 'wpelitepluginsupd_email', $purchase_emails );
		}
	}
}

if ( ! function_exists( 'wpeliteplugins_queue_update' ) ) {
	/**
	 * Add activated plugins to global variable
	 * 
	 * @package WPElite Plugins Updater
	 * @since 1.0.0
	 */
	function wpeliteplugins_queue_update( $file, $plugin_key = '' ) {

		global $wpeliteplugins_queued_updates;

		if ( ! isset( $wpeliteplugins_queued_updates ) ) {
			$wpeliteplugins_queued_updates = array();
		}

		$wpeliteplugins_queued_updates[ $file ] = $plugin_key;
	}
}
