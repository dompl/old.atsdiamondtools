<?php
/*
 * Plugin Name: AG WooCommerce Barclay ePDQ Payment Gateway
 * Plugin URI: https://weareag.co.uk/product/ag-barclays-epdq-payment-gateway-woocommerce/
 * Description: Add Barclays ePDQ payment gateway to WooCommerce.
 * Author: We are AG
 * Author URL: https://www.weareag.co.uk/
 * File: index.php
 * Project: AG-woocommerce-epdq-payment-gateway
 * -----
 * Modified By: Aaron Bowie - We are AG
 * -----
 * Version: 4.2.10
 * WC requires at least: 3.0.0
 * WC tested up to: 6.2
 * License: GPL3
 */


use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;

defined( 'ABSPATH' ) || die( "No script kiddies please!" );

/**
 * AG ePDQ server
 * @class    AG_ePDQ_server
 * @version  4.2.10
 * @category Class
 * @author   We are AG
 */
class AG_ePDQ_server {


	public static $AGversion = "4.2.10";
	public static $AG_ePDQ_slug = "AGWooCommerceBarclayePDQPaymentGateway";
	public static $pluginName = 'AG_ePDQ';
	public static $short_title = 'AG ePDQ';


	/**
	 * Construct the plugin
	 */
	public function __construct() {

		load_plugin_textdomain( 'ag_epdq_server', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

		$this->define_constants();

		add_action( 'plugins_loaded', array( $this, 'woocommerce_ag_epdq_init' ), 0 );
		add_action( 'admin_enqueue_scripts', array( $this, 'ag_admin_css' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'ag_checkout_css' ) );
		add_filter( 'woocommerce_payment_gateways', array( $this, 'woocommerce_add_epdq_gateway' ) );

		// If the site supports Gutenberg Blocks, support the Checkout block
		add_action( 'woocommerce_blocks_loaded', array( $this, 'ag_blocks_support' ) );


		$this->AG_classes();


		if ( ! AG_licence::valid_licence() ) {
			return;
		}

	}


	public function ag_blocks_support() {
		if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
			add_action( 'woocommerce_blocks_payment_method_type_registration',
				function ( $registry ) {
					$registry->register( new Automattic\WooCommerce\Blocks\Payments\Integrations\epdq_checkout() );
				}
			);
		}
	}

	/**
	 * Register gateway with Woo
	 */
	public function woocommerce_add_epdq_gateway( $methods ) {
		$methods[] = 'epdq_checkout';

		return $methods;
	}

	private function define_constants() {
		define( 'AG_ePDQ_server_path', plugin_dir_url( __FILE__ ) );
		define( 'AG_ePDQ_path', plugin_dir_path( __FILE__ ) );
		define( 'AG_ePDQ_class', AG_ePDQ_path . 'inc/classes/' );
		define( 'AG_ePDQ_core', AG_ePDQ_path . 'inc/core/' );
		define( 'AG_ePDQ_blocks', AG_ePDQ_path . 'inc/blocks/' );
		define( 'AG_ePDQ_admin', admin_url() );
		define('AG_path', plugin_dir_url(__FILE__));
	}

	public function ag_admin_css() {
		wp_enqueue_style( 'ag-admin', AG_ePDQ_server_path . 'inc/assets/css/admin-style.css', false, self::$AGversion );
	}

	public function ag_checkout_css() {
		wp_enqueue_style( 'ag-ePDQ', AG_ePDQ_server_path . 'inc/assets/css/style.css', false, self::$AGversion );
	}


	public function woocommerce_ag_epdq_init() {
		if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
			return;
		}

		require_once AG_ePDQ_class . 'class-main.php';
		require_once AG_ePDQ_class . 'class-epdq-error-codes.php';
		require_once AG_ePDQ_class . 'class-gdpr.php';
		require_once AG_ePDQ_class . 'class-settings.php';
		require_once AG_ePDQ_class . 'class-crypt.php';
		require_once AG_ePDQ_class . 'class-order-status-check.php';
		require_once AG_ePDQ_blocks . 'ePDQ-class.php';

		//require_once AG_ePDQ_class . 'class-epdq-score.php';
		//require_once AG_ePDQ_class . 'AG_3DS_Score/class-3d-score.php';
		//require_once AG_ePDQ_class . 'AG_3DS_Score/class-display-3d-score.php';


		//ePDQ_3D_score::run_instance(array(
		//	'parent_slug'   => self::$AG_ePDQ_slug,
		//	'plugin_short_title'         => 'Barclays ePDQ',
		//	'plugin_version'       => self::$AGversion,
		//	'payment_method_id'		=>	'epdq_checkout',
		//));

		//ePDQ_Display_Score::run_instance(array(
		//	'plugin_short_title'         => 'Barclays ePDQ',
		//	'plugin_version'       => self::$AGversion,
		//	'payment_method_id'		=>	'epdq_checkout',
		//));

		AG_ePDQ_Wizard::run_instance( array(
			'plugin_slug'    => self::$AG_ePDQ_slug,
			'plugin_version' => self::$AGversion,
			'plugin_name'    => self::$pluginName,
		) );

		AG_ePDQ_order_status_check::run_instance(array(
			'plugin_slug'   => self::$AG_ePDQ_slug,
			'plugin_version' => self::$AGversion,
			'plugin_name' => self::$pluginName,
		));

		//if (class_exists('WC_Subscriptions_Order') && class_exists('WC_Payment_Gateway_CC')) {
		//	require_once AG_ePDQ_class . 'class-wc-subscriptions.php';
		//}

	}


	/**
	 * Gateway classes
	 */
	private function AG_classes() {

		require_once AG_ePDQ_class . 'class-helpers.php';
		include_once AG_ePDQ_core . 'class-ag-welcome.php';
		include_once AG_ePDQ_core . 'class-ag-start-here-docs.php';
		include_once AG_ePDQ_core . 'class-ag-up-sell.php';
		include_once AG_ePDQ_core . 'class-ag-gateway-tips.php';
		include_once AG_ePDQ_core . 'class-ag-licence.php';
		require_once AG_ePDQ_class . 'class-epdq-wizard.php';
		require_once AG_ePDQ_core . 'class-setup-wizard.php';

		AG_ePDQ_Wizard_steps::run_instance( array(
			'plugin_slug'    => self::$AG_ePDQ_slug,
			'plugin_version' => self::$AGversion,
			'plugin_name'    => self::$pluginName,
		) );


		AG_licence::run_instance( array(
			'basename'    => plugin_basename( __FILE__ ),
			'urls'        => array(
				'product' => 'https://weareag.co.uk/product/ag-barclays-epdq-payment-gateway-woocommerce/',
				'welcome' => admin_url( 'admin.php?page=' . self::$AG_ePDQ_slug ),
				'account' => admin_url( 'admin.php?page=' . self::$AG_ePDQ_slug ),
			),
			'paths'       => array(
				'plugin' => plugin_dir_path( __FILE__ ),
			),
			'freemius'    => array(
				'id'              => '2715',
				'slug'            => self::$AG_ePDQ_slug,
				'public_key'      => 'pk_8024ebca6d61cc1b38ff7a933bc15',
				'trial'           => array(
					'days'               => 7,
					'is_require_payment' => true,
				),
				'has_affiliation' => 'customers',
				'menu'            => array(
					'slug'       => self::$AG_ePDQ_slug,
					'first-path' => '?page=AG_ePDQ-wizard',
					'parent'     => array(
						'slug' => 'AG_plugins',
					),
				),
			),
			'update'      => array(
				'plugin'          => 'AGWooCommerceBarclayePDQPaymentGateway',
				'name'            => 'Barclays ePDQ payment gateway (Barclaycard) for WooCommerce',
				'update_notice'   => false,
				'message'         => '',
				'notice_name'     => 'ag_new_update_info',
				'disable_gateway' => false,
			),
			'plugin_name' => self::$pluginName,
		) );

		AG_welcome_screen::run_instance( array(
			'parent_slug'    => self::$AG_ePDQ_slug,
			'main_slug'      => self::$AG_ePDQ_slug,
			'collection'     => 'barclays-epdq-payment-gateway/',
			'plugin_title'   => 'Barclays ePDQ payment gateway (Barclaycard) for WooCommerce',
			'plugin_version' => self::$AGversion,
			'plugin_name'    => self::$pluginName,
		) );

		AG_start_here_docs::run_instance( array(
			'start_here'      => 'setup-barclays-epdq-payment-gateway',
			'troubleshooting' => 'troubleshooting-barclays-epdq-payment-gateway/',
			'plugin_slug'     => self::$AG_ePDQ_slug
		) );

		AG_up_sell::run_instance( array(
			'plugins'     => array(
				'sagepay_direct',
				'lloyds',
				'adyen',
				'pay360',
			),
			'plugin_slug' => self::$AG_ePDQ_slug,
		) );


		AG_gateway_tips::run_instance( array(
			'plugin_slug' => self::$AG_ePDQ_slug,
		) );

	}


	public static function activate() {
		if ( ! get_option( 'AG_ePDQ_server' ) ) {
			add_option( 'AG_ePDQ_server', true );
		}
	}

}


$AG_ePDQ_server = new AG_ePDQ_server();
register_activation_hook( __FILE__, array( $AG_ePDQ_server, 'activate' ) );
