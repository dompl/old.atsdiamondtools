<?php
/**
 * Plugin Name: WooCommerce Address Validation
 * Plugin URI: http://www.woocommerce.com/products/postcodeaddress-validation/
 * Description: Adds Address Validation and Postcode Lookup to WooCommerce via Loqate, SmartyStreets, and more!
 * Author: SkyVerge
 * Author URI: http://www.woocommerce.com
 * Version: 2.3.3
 * Text Domain: woocommerce-address-validation
 * Domain Path: /i18n/languages/
 *
 * Copyright: (c) 2013-2018, SkyVerge, Inc. (info@skyverge.com)
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package     WC-Address-Validation
 * @author      SkyVerge
 * @category    Integration
 * @copyright   Copyright (c) 2013-2018, SkyVerge, Inc.
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 *
 * Woo: 182775:d65b52fcfbf887386516007aed10451d
 * WC requires at least: 2.6.14
 * WC tested up to: 3.5.0
 */

defined( 'ABSPATH' ) or exit;

// Required functions
if ( ! function_exists( 'woothemes_queue_update' ) ) {
	require_once( plugin_dir_path( __FILE__ ) . 'woo-includes/woo-functions.php' );
}

// Plugin updates
woothemes_queue_update( plugin_basename( __FILE__ ), 'd65b52fcfbf887386516007aed10451d', '182775' );

// WC active check
if ( ! is_woocommerce_active() ) {
	return;
}

// Required library class
if ( ! class_exists( 'SV_WC_Framework_Bootstrap' ) ) {
	require_once( plugin_dir_path( __FILE__ ) . 'lib/skyverge/woocommerce/class-sv-wc-framework-bootstrap.php' );
}

SV_WC_Framework_Bootstrap::instance()->register_plugin( '4.9.0', __( 'WooCommerce Address Validation', 'woocommerce-address-validation' ), __FILE__, 'init_woocommerce_address_validation', array(
	'minimum_wc_version'   => '2.6.14',
	'minimum_wp_version'   => '4.4',
	'backwards_compatible' => '4.4',
) );

function init_woocommerce_address_validation() {

/**
 * # WooCommerce Address Validation Main Plugin Class
 *
 * @since 1.0
 */
class WC_Address_Validation extends SV_WC_Plugin {


	/** plugin version number */
	const VERSION = '2.3.3';

	/** @var WC_Address_Validation single instance of this plugin */
	protected static $instance;

	/** plugin id */
	const PLUGIN_ID = 'address_validation';

	/** @var \WC_Address_Validation_Handler instance */
	protected $handler;

	/** @var \WC_Address_Validation_Admin instance */
	protected $admin;


	/**
	 * Setup main plugin class
	 *
	 * @since 1.0
	 * @return \WC_Address_Validation
	 */
	public function __construct() {

		parent::__construct(
			self::PLUGIN_ID,
			self::VERSION,
			array(
				'text_domain'        => 'woocommerce-address-validation',
				'display_php_notice' => true,
			)
		);

		spl_autoload_register( array( $this, 'autoload_providers' ) );

		// Load validation / admin / ajax after WC loads
		add_action( 'sv_wc_framework_plugins_loaded', array( $this, 'includes' ) );

		// postcode lookup
		add_action( 'wp_ajax_wc_address_validation_lookup_postcode',        array( $this, 'lookup_postcode' ) );
		add_action( 'wp_ajax_nopriv_wc_address_validation_lookup_postcode', array( $this, 'lookup_postcode' ) );
		add_action( 'wc_ajax_wc_address_validation_lookup_postcode',        array( $this, 'lookup_postcode' ) );
	}


	/**
	 * Auto-load classes
	 *
	 * @since 1.0
	 * @param string $class class name to load
	 */
	public function autoload_providers( $class ) {

		$class = strtolower( $class );

		if ( 0 === strpos( $class, 'wc_address_validation_provider_' ) ) {

			$path = $this->get_plugin_path() . '/includes/providers/';
			$file = 'class-' . str_replace( '_', '-', $class ) . '.php';

			if ( is_readable( $path . $file ) ) {
				require_once( $path . $file );
			}
		}
	}


	/**
	 * Load validation/lookup providers + handler after WC loads (need access to WC_Settings_API class), and admin/ajax classes
	 *
	 * @since 1.0
	 */
	public function includes() {

		// Base validation provider
		require_once( $this->get_plugin_path() . '/includes/abstract-wc-address-validation-provider.php' );

		// load providers
		$this->handler = $this->load_class( '/includes/class-wc-address-validation-handler.php', 'WC_Address_Validation_Handler' );

		if ( is_admin() && ! is_ajax() ) {

			// Admin
			$this->admin = $this->load_class( '/includes/admin/class-wc-address-validation-admin.php', 'WC_Address_Validation_Admin' );
		}
	}


	/**
	 * Return admin class instance
	 *
	 * @since 1.9.0
	 * @return \WC_Address_Validation_Admin
	 */
	public function get_admin_instance() {
		return $this->admin;
	}


	/**
	 * Return handler class instance
	 *
	 * @since 1.9.0
	 * @return \WC_Address_Validation_Handler
	 */
	public function get_handler_instance() {
		return $this->handler;
	}


	/** Admin Methods ******************************************************/


	/**
	 * Render a notice for the user to switch to Loqate after upgrade from pre 2.0.0
	 *
	 * @since 2.0.0
	 * @see SV_WC_Plugin::add_admin_notices()
	 */
	public function add_admin_notices() {

		// show any dependency notices
		parent::add_admin_notices();

		if ( get_option( 'wc_address_validation_encourage_addressy_upgrade_switch' ) ) {

			// add notice to encourage users to switch to Addressy
			$this->get_admin_notice_handler()->add_admin_notice(
				sprintf(
					/* translators: Placeholders: %1$s, %3$s - opening <a> tag, %2$s, %4$s - closing </a> tag */
					__( 'Address Validation has been upgraded! We\'ve added support for Loqate, which can perform address lookups for all countries you sell to. %1$sAdjust your settings%2$s or %3$ssign up for a free account%4$s.', 'woocommerce-address-validation' ),
					'<a href="' . esc_url( $this->get_settings_url() ) . '">', '</a>',
					'<a href="https://www.loqate.com/partners/ADRSY11126">', '</a>'
				),
				'addressy-switch-notice',
				array(
					'always_show_on_settings' => false,
					'notice_class'            => 'updated'
				)
			);

		}
	}


	/** AJAX methods ******************************************************/


	/**
	 * Handler for AJAX postcode lookup call
	 *
	 * @since 1.0
	 */
	public function lookup_postcode() {

		check_ajax_referer( 'wc_address_validation', 'security' );

		header( 'Content-Type: application/json; charset=utf-8' );

		$postcode     = ( isset( $_GET['postcode'] ) ) ? sanitize_text_field( $_GET['postcode'] ) : '';

		$house_number = ( isset( $_GET['house_number'] ) ) ? sanitize_text_field( $_GET['house_number'] ) : '';

		$results = $this->get_handler_instance()->get_active_provider()->lookup_postcode( $postcode, $house_number );

		// add a helper notice to the top of the select box
		array_unshift( $results, array( 'value' => 'none', 'name' => __( 'Select your address to populate the form.', 'woocommerce-address-validation' ) ) );

		echo json_encode( $results );

		exit;
	}


	/** Helper methods ******************************************************/


	/**
	 * Main Address Validation Instance, ensures only one instance is/can be loaded
	 *
	 * @since 1.5.0
	 * @see wc_address_validation()
	 * @return WC_Address_Validation
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}


	/**
	 * Gets the plugin documentation url
	 *
	 * @since 1.6.1
	 * @see SV_WC_Plugin::get_documentation_url()
	 * @return string documentation URL
	 */
	public function get_documentation_url() {
		return 'http://docs.woocommerce.com/document/address-validation/';
	}


	/**
	 * Gets the plugin support URL
	 *
	 * @since 1.7.0
	 * @see SV_WC_Plugin::get_support_url()
	 * @return string
	 */
	public function get_support_url() {
		return 'https://woocommerce.com/my-account/marketplace-ticket-form/';
	}


	/**
	 * Returns the plugin name, localized
	 *
	 * @since 1.1
	 * @see SV_WC_Plugin::get_plugin_name()
	 * @return string the plugin name
	 */
	public function get_plugin_name() {

		return __( 'WooCommerce Address Validation', 'woocommerce-address-validation' );
	}


	/**
	 * Returns __FILE__
	 *
	 * @since 1.1
	 * @see SV_WC_Plugin::get_file()
	 * @return string the full path and filename of the plugin file
	 */
	protected function get_file() {

		return __FILE__;
	}


	/**
	 * Gets the URL to the settings page
	 *
	 * @since 1.1
	 * @see SV_WC_Plugin::is_plugin_settings()
	 * @param string $_ unused
	 * @return string URL to the settings page
	 */
	public function get_settings_url( $_ = '' ) {

		return admin_url( 'admin.php?page=wc-settings&tab=address_validation' );
	}


	/**
	 * Install default settings
	 *
	 * @since 1.0
	 * @see SV_WC_Plugin::install()
	 */
	public function install() {

		$this->admin = ! $this->admin instanceof WC_Address_Validation_Admin ? $this->load_class( '/includes/admin/class-wc-address-validation-admin.php', 'WC_Address_Validation_Admin' ) : $this->admin;

		// not installed, install default options
		foreach ( $this->admin->get_settings_page()->get_settings() as $setting ) {

			if ( isset( $setting['default'] ) ) {
				add_option( $setting['id'], $setting['default'] );
			}
		}
	}


	/**
	 * Perform any version-related changes
	 *
	 * @since 2.0.0
	 * @see SV_WC_Plugin::upgrade()
	 * @param int $installed_version the currently installed version of the plugin
	 */
	public function upgrade( $installed_version ) {

		// upgrade to 2.0.0
		if ( version_compare( $installed_version, '2.0.0', '<' ) ) {

			// encourage users to switch to Addressy after upgrade
			update_option( 'wc_address_validation_encourage_addressy_upgrade_switch', true );
		}
	}


} // end \WC_Address_Validation class


/**
 * Returns the One True Instance of <plugin>
 *
 * @since 1.5.0
 * @return WC_Address_Validation
 */
function wc_address_validation() {
	return WC_Address_Validation::instance();
}


// fire it up!
wc_address_validation();

} // init_woocommerce_address_validation()
