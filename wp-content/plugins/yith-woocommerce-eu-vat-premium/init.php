<?php
/**
 * Plugin Name: YITH WooCommerce EU VAT Premium
 * Plugin URI: https://yithemes.com/themes/plugins/yith-woocommerce-eu-vat/
 * Description: <code><strong>YITH WooCommerce EU VAT</strong></code> allows you to be fully compliance with EU VAT laws, storing checkout data and filling the EU VAT MOSS report for digital goods. <a href="https://yithemes.com/" target="_blank">Get more plugins for your e-commerce shop on <strong>YITH</strong></a>.
 * Version: 1.4.0
 * Author: YITH
 * Author URI: https://yithemes.com/
 * Text Domain: yith-woocommerce-eu-vat
 * Domain Path: /languages/
 * WC requires at least: 3.3.0
 * WC tested up to: 3.7.x
 **/

/*  Copyright 2013-2015  Your Inspiration Themes  (email : plugins@yithemes.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

//region    ****    Check if prerequisites are satisfied before enabling and using current plugin
if ( ! function_exists( 'is_plugin_active' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
}

if ( ! function_exists( 'yith_ywev_premium_install_woocommerce_admin_notice' ) ) {

	/**
	 * Show a notice if WooCommerce is not enabled
	 *
	 * @author Lorenzo Giuffrida
	 * @since  1.0.0
	 */
	function yith_ywev_premium_install_woocommerce_admin_notice() {
		?>
		<div class="error">
			<p><?php _e( 'YITH WooCommerce EU VAT is enabled but not effective. It requires WooCommerce in order to work.', 'yith-woocommerce-eu-vat' ); ?></p>
		</div>
		<?php
	}
}

/**
 * Check if a free version is currently active and try disabling before activating this one
 */
if ( ! function_exists( 'yit_deactive_free_version' ) ) {
	require_once 'plugin-fw/yit-deactive-plugin.php';
}
yit_deactive_free_version( 'YITH_YWEV_FREE_INIT', plugin_basename( __FILE__ ) );


if ( ! function_exists( 'yith_plugin_registration_hook' ) ) {
	require_once 'plugin-fw/yit-plugin-registration-hook.php';
}
register_activation_hook( __FILE__, 'yith_plugin_registration_hook' );

//endregion

//region    ****    Define constants
defined( 'YITH_YWEV_INIT' ) || define( 'YITH_YWEV_INIT', plugin_basename( __FILE__ ) );
defined( 'YITH_YWEV_PREMIUM' ) || define( 'YITH_YWEV_PREMIUM', '1' );
defined( 'YITH_YWEV_SLUG' ) || define( 'YITH_YWEV_SLUG', 'yith-woocommerce-eu-vat' );
defined( 'YITH_YWEV_SECRET_KEY' ) || define( 'YITH_YWEV_SECRET_KEY', 'GErfQG1E8vlj5Jx0nZ8s' );
defined( 'YITH_YWEV_VERSION' ) || define( 'YITH_YWEV_VERSION', '1.4.0' );
defined( 'YITH_YWEV_FILE' ) || define( 'YITH_YWEV_FILE', __FILE__ );
defined( 'YITH_YWEV_DIR' ) || define( 'YITH_YWEV_DIR', plugin_dir_path( __FILE__ ) );
defined( 'YITH_YWEV_URL' ) || define( 'YITH_YWEV_URL', plugins_url( '/', __FILE__ ) );
defined( 'YITH_YWEV_ASSETS_URL' ) || define( 'YITH_YWEV_ASSETS_URL', YITH_YWEV_URL . 'assets' );
defined( 'YITH_YWEV_SCRIPT_URL' ) || define( 'YITH_YWEV_SCRIPT_URL', YITH_YWEV_ASSETS_URL . '/js/' );

defined( 'YITH_YWEV_TEMPLATE_DIR' ) || define( 'YITH_YWEV_TEMPLATE_DIR', YITH_YWEV_DIR . 'templates' );
defined( 'YITH_YWEV_ASSETS_IMAGES_URL' ) || define( 'YITH_YWEV_ASSETS_IMAGES_URL', YITH_YWEV_ASSETS_URL . '/images/' );
defined( 'YITH_YWEV_LIB_DIR' ) || define( 'YITH_YWEV_LIB_DIR', YITH_YWEV_DIR . 'lib/' );
defined( 'YITH_YWEV_VIEWS_PATH' ) || define( 'YITH_YWEV_VIEWS_PATH', YITH_YWEV_DIR . 'views/' );
//endregion

/* Plugin Framework Version Check */
if ( ! function_exists( 'yit_maybe_plugin_fw_loader' ) && file_exists( YITH_YWEV_DIR . 'plugin-fw/init.php' ) ) {
	require_once( YITH_YWEV_DIR . 'plugin-fw/init.php' );
}
yit_maybe_plugin_fw_loader( YITH_YWEV_DIR );

if ( ! function_exists( 'yith_ywev_premium_init' ) ) {
	/**
	 * init the plugin
	 *
	 * @author Lorenzo Giuffrida
	 * @since  1.0.0
	 */
	function yith_ywev_premium_init() {

		/**
		 * Load text domain and start plugin
		 */
		load_plugin_textdomain( 'yith-woocommerce-eu-vat', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

		// Load required classes and functions
		require_once( YITH_YWEV_LIB_DIR . 'class.yith-ywev-plugin-fw-loader.php' );
		require_once( YITH_YWEV_LIB_DIR . 'class.yith-woocommerce-eu-vat.php' );
		require_once( YITH_YWEV_LIB_DIR . 'class.yith-woocommerce-eu-vat-premium.php' );

		// Let's start the game!
		YITH_YWEV();
	}
}
add_action( 'yith_ywev_premium_init', 'yith_ywev_premium_init' );

if ( ! function_exists( 'YITH_YWEV' ) ) {
	/**
	 * Retrieve YITH WooCommerce EU VAT instance
	 *
	 *
	 * @return YITH_WooCommerce_EU_VAT|YITH_WooCommerce_EU_VAT_Premium
	 */
	function YITH_YWEV() {
		return YITH_WooCommerce_EU_VAT_Premium::get_instance();
	}
}

if ( ! function_exists( 'yith_ywev_premium_install' ) ) {
	/**
	 * Install the plugin
	 *
	 * @author Lorenzo Giuffrida
	 * @since  1.0.0
	 */
	function yith_ywev_premium_install() {

		if ( ! function_exists( 'WC' ) ) {
			add_action( 'admin_notices', 'yith_ywev_premium_install_woocommerce_admin_notice' );
		} else {
			do_action( 'yith_ywev_premium_init' );
		}
	}
}

add_action( 'plugins_loaded', 'yith_ywev_premium_install', 11 );