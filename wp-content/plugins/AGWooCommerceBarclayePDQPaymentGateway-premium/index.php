<?php
/*
 * Plugin Name: AG WooCommerce Barclay ePDQ Payment Gateway (Premium)
 * Plugin URI: https://weareag.co.uk/product/ag-barclays-epdq-payment-gateway-woocommerce/
 * Description: Add Barclays ePDQ payment gateway to WooCommerce 3.5+.
 * Author: We are AG
 * Author URL: https://www.weareag.co.uk/
 * File: index.php
 * Project: AG-woocommerce-epdq-payment-gateway
 * -----
 * Last Modified: Thursday, 8th November 2018 1:21:35 am
 * Modified By: Aaron Bowie - We are AG
 * -----
 * Version: 2.12.2
 * WC requires at least: 3.0.0
 * WC tested up to: 3.5.4
 * License: GPL3
 */

defined( 'ABSPATH' ) or die( "No script kiddies please!" );
define( 'AG_ePDQ_server_path', plugin_dir_url( __FILE__ ) );

/**
 * AG ePDQ server
 * @class    AG_ePDQ_server
 * @version  2.12.2
 * @category Class
 * @author   We are AG
 */
class AG_ePDQ_server {


	/**
	 * Plugin version
	 *
	 * @var string
	 */
	public static $AGversion = "2.12.2";


	/**
	 * Plugin slug
	 *
	 * @var string
	 */
	public static $AG_ePDQ_slug = "AGWooCommerceBarclayePDQPaymentGateway";


	/**
	 * Construct the plugin
	 */
	public function __construct() {

		load_plugin_textdomain( 'ag-epdq-server', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

		$this->AG_classes();

		add_action( 'plugins_loaded', array($this, 'woocommerce_ag_epdq_init'), 0  );
		add_filter( 'woocommerce_payment_gateways', array($this, 'woocommerce_add_epdq_gateway') );


		if ( ! AG_licence::valid_licence() ) {
			return;
		}
	}

	/**
	 * Register gateway with Woo
	 */
	public function woocommerce_add_epdq_gateway( $methods )  {

		$methods[] = 'epdq_checkout';
		return $methods;
	}
	public function woocommerce_ag_epdq_init() {

		if ( !class_exists( 'WC_Payment_Gateway' ) ) {
			return;
		}
		require_once plugin_dir_path( __FILE__ ) . 'inc/class.epdq.php';
		require_once plugin_dir_path( __FILE__ ) . 'inc/ag-epdq-error-codes.php';
		require_once plugin_dir_path( __FILE__ ) . 'inc/ag-gdpr.php';
	}

	/**
	 * Gateway classes
	 */
	public function AG_classes() {

		include_once 'inc/core/class-ag-welcome.php';
		include_once 'inc/core/class-ag-start-here-docs.php';
		include_once 'inc/core/class-ag-up-sell.php';
		include_once 'inc/core/class-ag-gateway-tips.php';
		include_once 'inc/core/class-ag-licence.php';

		AG_licence::run_instance( array(
			'basename' => plugin_basename( __FILE__ ),
			'urls'     => array(
				'product'  => 'https://weareag.co.uk/product/ag-barclays-epdq-payment-gateway-woocommerce/',
				'welcome' => admin_url( 'admin.php?page='. self::$AG_ePDQ_slug ),
				'account'  => admin_url( 'admin.php?page='. self::$AG_ePDQ_slug ),
			),
			'paths'    => array(
				'plugin' => plugin_dir_path( __FILE__ ),
			),
			'freemius' => array(
				'id'         => '2715',
				'slug'       => self::$AG_ePDQ_slug,
				'public_key' => 'pk_8024ebca6d61cc1b38ff7a933bc15',
				'trial'            => array(
					'days'               => 7,
					'is_require_payment' => true,
				),
				'menu'       => array(
					'slug'    => self::$AG_ePDQ_slug,
					'first-path'     => 'admin.php?page=AG_plugins',
					'parent'         => array(
						'slug' => 'AG_plugins',
					),
				),
			),
		) );

		AG_welcome_screen::run_instance(array(
			'parent_slug'   => self::$AG_ePDQ_slug,
			'main_slug'   => self::$AG_ePDQ_slug,
			'collection' => '25-barclays-epdq-payment-gateway',
			'plugin_title'         => 'Barclays ePDQ payment gateway (Barclaycard) for WooCommerce',
			'plugin_version'       => self::$AGversion,
		));

		AG_start_here_docs::run_instance(array(
			'start_here' => 'category/28-category',
			'troubleshooting' => 'category/33-category',
			'plugin_slug'   => self::$AG_ePDQ_slug
		));

		AG_up_sell::run_instance(array(
			'plugins'   => array(
				'sagepay_direct',
				'visa_checkout',
				'adyen',
			),
			'plugin_slug'   => self::$AG_ePDQ_slug,
		));


		AG_gateway_tips::run_instance(array(
			'tips'   => array(
				'PCI',
				'payments_101',
			),
			'plugin_slug'   => self::$AG_ePDQ_slug,
		));

	}



	public static function activate() {
		if ( ! get_option( 'AG_ePDQ_server' ) ) {
			add_option( 'AG_ePDQ_server', true );
		}
	}

}


$AG_ePDQ_server = new AG_ePDQ_server();
register_activation_hook( __FILE__, array( $AG_ePDQ_server, 'activate' ) );
