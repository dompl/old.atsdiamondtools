<?php
/**
 * Plugin Name: reCAPTCHA for WooCommerce
 * Plugin URI: https://wpeliteplugins.com
 * Description: Add Google reCAPTCHA to WooCommerce Login, registration, lost password form and Checkout page.
 * Version: 1.2.2
 * Author: WPElitePlugins
 * Author URI: https://wpeliteplugins.com
 *
 * Text Domain: recaptcha-for-woocommerce
 * Domain Path: languages
 *
 * WC tested up to: 8.4.0
 *
 * @package reCAPTCHA for WooCommerce
 * @category Core
 * @author WPElitePlugins
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Basic plugin definitions
 *
 * @package reCAPTCHA for WooCommerce
 * @since 1.0.0
 */
if ( ! defined( 'WOORECAPTCHA_PLUGIN_VERSION' ) ) {
	define( 'WOORECAPTCHA_PLUGIN_VERSION', '1.2.2' ); // Plugin version number.
}
if ( ! defined( 'WOORECAPTCHA_DIR' ) ) {
	define( 'WOORECAPTCHA_DIR', dirname( __FILE__ ) ); // plugin dir.
}
if ( ! defined( 'WOORECAPTCHA_URL' ) ) {
	define( 'WOORECAPTCHA_URL', plugin_dir_url( __FILE__ ) ); // plugin url.
}
if ( ! defined( 'WOORECAPTCHA_ADMIN' ) ) {
	define( 'WOORECAPTCHA_ADMIN', WOORECAPTCHA_DIR . '/includes/admin' ); // plugin admin dir.
}
if ( ! defined( 'WOORECAPTCHA_PLUGIN_BASENAME' ) ) {
	define( 'WOORECAPTCHA_PLUGIN_BASENAME', basename( WOORECAPTCHA_DIR ) ); // Plugin base name.
}
if ( ! defined( 'WOORECAPTCHA_PLUGIN_KEY' ) ) {
	define( 'WOORECAPTCHA_PLUGIN_KEY', 'woorecaptcha' );
}

/**
 * Load Text Domain
 *
 * This gets the plugin ready for translation.
 *
 * @package reCAPTCHA for WooCommerce
 * @since 1.0.0
 */
function woorecaptcha_load_text_domain() {

	// Set filter for plugin's languages directory.
	$woorecaptcha_lang_dir = dirname( plugin_basename( __FILE__ ) ) . '/languages/';
	$woorecaptcha_lang_dir = apply_filters( 'woorecaptcha_languages_directory', $woorecaptcha_lang_dir );

	// Traditional WordPress plugin locale filter.
	$locale = apply_filters( 'plugin_locale', get_locale(), 'recaptcha-for-woocommerce' );
	$mofile = sprintf( '%1$s-%2$s.mo', 'recaptcha-for-woocommerce', $locale );

	// Setup paths to current locale file.
	$mofile_local  = $woorecaptcha_lang_dir . $mofile;
	$mofile_global = WP_LANG_DIR . '/' . WOORECAPTCHA_PLUGIN_BASENAME . '/' . $mofile;

	if ( file_exists( $mofile_global ) ) { // Look in global /wp-content/languages/recaptcha-for-woocommerce folder.
		load_textdomain( 'recaptcha-for-woocommerce', $mofile_global );
	} elseif ( file_exists( $mofile_local ) ) { // Look in local /wp-content/plugins/recaptcha-for-woocommerce/languages/ folder.
		load_textdomain( 'recaptcha-for-woocommerce', $mofile_local );
	} else { // Load the default language files.
		load_plugin_textdomain( 'recaptcha-for-woocommerce', false, $woorecaptcha_lang_dir );
	}
}

/**
 * Add plugin action links
 *
 * Adds a Settings, Support and Docs link to the plugin list.
 *
 * @param array $links plugin links.
 * @package reCAPTCHA for WooCommerce
 * @since 1.0.0
 */
function woorecaptcha_add_plugin_links( $links ) {
	$plugin_links = array(
		'<a href="admin.php?page=wc-settings&tab=woo_recaptcha">' . esc_html__( 'Settings', 'recaptcha-for-woocommerce' ) . '</a>',
		'<a href="https://documents.wpeliteplugins.com/recaptcha-for-woocommerce/">' . esc_html__( 'Docs', 'recaptcha-for-woocommerce' ) . '</a>',
	);

	return array_merge( $plugin_links, $links );
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'woorecaptcha_add_plugin_links' );

// add action to load plugin.
add_action( 'plugins_loaded', 'woorecaptcha_plugin_loaded' );

/**
 * Load Plugin
 *
 * Handles to load plugin after dependent plugin is loaded successfully
 *
 * @package reCAPTCHA for WooCommerce
 * @since 1.0.0
 */
function woorecaptcha_plugin_loaded() {

	// check Woocommerce is activated or not.
	if ( class_exists( 'Woocommerce' ) ) {

		// load first plugin text domain.
		woorecaptcha_load_text_domain();

		// global variables.
		global $woorecaptcha_scripts, $woorecaptcha_public, $woorecaptcha_settings_tabs;

		// Public Class to handles most of functionalities of public side.
		require_once WOORECAPTCHA_DIR . '/includes/class-woo-recaptcha-public.php';
		$woorecaptcha_public = new WooRecaptcha_Public();
		$woorecaptcha_public->add_hooks();

		// Script Class to manage all scripts and styles.
		include_once WOORECAPTCHA_DIR . '/includes/class-woo-recaptcha-scripts.php';
		$woorecaptcha_scripts = new WooRecaptcha_Scripts();
		$woorecaptcha_scripts->add_hooks();

		// Settings Tab class for handling settings tab content.
		require_once WOORECAPTCHA_ADMIN . '/class-woo-recaptcha-admin-settings-tabs.php';
		$woorecaptcha_settings_tabs = new WooRecaptcha_Settings_Tabs();
		$woorecaptcha_settings_tabs->add_hooks();
	}
}

if ( ! class_exists( 'WPElitePlugins_Updater' ) ) {
	require WOORECAPTCHA_DIR . '/updater/class-wpeliteplugins-updater.php';
}

new WPElitePlugins_Updater([
	'plugin_base' => plugin_basename( __FILE__ ),
	'plugin_slug' => WOORECAPTCHA_PLUGIN_BASENAME,
	'plugin_name' => 'reCAPTCHA for WooCommerce',
	'plugin_version' => WOORECAPTCHA_PLUGIN_VERSION,
	'item_id' => '17795842'
]);

add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );

