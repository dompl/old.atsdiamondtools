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
 * Version: 4.8.4
 * Update URI: https://api.freemius.com
 * Requires Plugins: woocommerce
 * WC requires at least: 7.1
 * WC tested up to: 9.7.0
 * Requires PHP: 8.1
 * License: GPL3
 */

use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;

defined( 'ABSPATH' ) || die( "No script kiddies please!" );

/**
 * AG ePDQ server
 *
 * @class    AG_ePDQ_server
 * @version  4.8.4
 * @category Class
 * @author   We are AG
 */
class AG_ePDQ_server {

	public static $AGversion = "4.8.4";
	public static $AG_ePDQ_slug = "AGWooCommerceBarclayePDQPaymentGateway";
	public static $pluginName = 'AG_ePDQ';


	/**
	 * Construct the plugin
	 */
	public function __construct() {

		load_plugin_textdomain( 'ag_epdq_server', FALSE, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

		$this->define_constants();

		add_action( 'plugins_loaded', array( $this, 'woocommerce_ag_epdq_init' ), 0 );

		add_action( 'wp_enqueue_scripts', array( $this, 'ag_epdq_block_css' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'ag_epdq_block_css' ) );

		add_filter( 'woocommerce_payment_gateways', array( $this, 'woocommerce_add_epdq_gateway' ) );

		// If the site supports Gutenberg Blocks, support the Checkout block
		add_action( 'woocommerce_blocks_loaded', array( $this, 'ag_blocks_support' ) );

		add_action( 'admin_footer', array( 'AG_ePDQ_Helpers', 'reorder_gateways_and_add_card_icons' ) );

		// Support for HPOS
		add_action( 'before_woocommerce_init', function() {

			if( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, TRUE );
			}
		} );

		$this->AG_classes();

		if( ! AG_licence::valid_licence() ) {
			return;
		}

	}

	private function define_constants() {

		define( 'AG_ePDQ_server_path', plugin_dir_url( __FILE__ ) );
		define( 'AG_ePDQ_path', plugin_dir_path( __FILE__ ) );
		define( 'AG_ePDQ_class', AG_ePDQ_path . 'inc/classes/' );
		define( 'AG_ePDQ_core', AG_ePDQ_path . 'inc/core/' );
		define( 'AG_ePDQ_blocks', AG_ePDQ_path . 'inc/blocks/' );
		define( 'AG_ePDQ_token', AG_ePDQ_path . 'inc/classes/Tokenize/' );
		define( 'AG_ePDQ_admin', admin_url() );
		define( 'AG_ePDQ_url', plugin_dir_url( __FILE__ ) );
		define( 'AG_ePDQ_sub', AG_ePDQ_path . 'inc/classes/Subscriptions/' );
		define( 'AG_ePDQ_webhook', AG_ePDQ_path . 'inc/classes/Webhook/' );

	}

	/**
	 * Gateway classes
	 */
	private function AG_classes() {

		require_once AG_ePDQ_class . 'class-helpers.php';
		include_once AG_ePDQ_core . 'class-ag-welcome.php';
		include_once AG_ePDQ_core . 'class-ag-start-here-docs.php';
		include_once AG_ePDQ_core . 'class-ag-licence.php';
		require_once AG_ePDQ_class . 'class-epdq-wizard.php';
		require_once AG_ePDQ_core . 'class-setup-wizard.php';

		AG_ePDQ_Wizard_steps::run_instance( array(
			'plugin_slug'    => self::$AG_ePDQ_slug,
			'plugin_version' => self::$AGversion,
			'plugin_name'    => self::$pluginName,
		) );

		AG_licence::run_instance( array(
			'basename' => plugin_basename( __FILE__ ),
			'urls'     => array(
				'product' => 'https://weareag.co.uk/product/ag-barclays-epdq-payment-gateway-woocommerce/',
				'welcome' => admin_url( 'admin.php?page=' . self::$AG_ePDQ_slug ),
				'account' => admin_url( 'admin.php?page=' . self::$AG_ePDQ_slug ),
			),
			'paths'    => array(
				'plugin' => plugin_dir_path( __FILE__ ),
			),
			'freemius' => array(
				'id'              => '2715',
				'slug'            => self::$AG_ePDQ_slug,
				'public_key'      => 'pk_8024ebca6d61cc1b38ff7a933bc15',
				'trial'           => array(
					'days'               => 7,
					'is_require_payment' => TRUE,
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

			'update' => array(
				'plugin'          => 'AGWooCommerceBarclayePDQPaymentGateway',
				'name'            => 'Barclays ePDQ payment gateway (Barclaycard) for WooCommerce',
				'update_notice'   => FALSE,
				'message'         => '',
				'notice_name'     => 'ag_webhook',
				'disable_gateway' => FALSE,
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

	}

	public static function activate() {

		if( ! get_option( 'AG_ePDQ_server' ) ) {
			add_option( 'AG_ePDQ_server', TRUE );
		}
	}

	public static function plugin_url() {

		return untrailingslashit( plugins_url( '/', __FILE__ ) );
	}

	public function ag_blocks_support() {

		if( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
			require_once AG_ePDQ_blocks . 'ePDQ-class.php';

			add_action( 'woocommerce_blocks_payment_method_type_registration', function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {

				$payment_method_registry->register( new epdq_checkout_block );
			} );
		}
	}

	/**
	 * Register gateway with Woo
	 */
	public function woocommerce_add_epdq_gateway( $methods ) {

		$methods[] = 'epdq_checkout';

		return $methods;
	}


	public function ag_epdq_block_css() {

		if( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
			wp_enqueue_style( 'ag-block', AG_ePDQ_server_path . 'inc/assets/css/epdq-block.css', FALSE, self::$AGversion );
		}

	}

	public function woocommerce_ag_epdq_init() {

		if( ! class_exists( 'WC_Payment_Gateway' ) ) {
			return;
		}

		require_once AG_ePDQ_class . 'class-main.php';
		require_once AG_ePDQ_class . 'class-epdq-error-codes.php';
		require_once AG_ePDQ_class . 'class-gdpr.php';
		require_once AG_ePDQ_class . 'class-settings.php';
		require_once AG_ePDQ_class . 'class-crypt.php';
		require_once AG_ePDQ_class . 'StatusCheck/class-order-status-check.php';
		require_once AG_ePDQ_token . 'class-tokenization.php';
		require_once AG_ePDQ_class . 'Moto/class-moto.php';
		require_once AG_ePDQ_class . 'Fraudchecks/fraud-checks.php';
		require_once AG_ePDQ_class . 'class-epdq-authorization-capture.php';
		require_once AG_ePDQ_sub . 'class-subscriptions.php';
		require_once AG_ePDQ_webhook . 'class-webhook.php';
		require_once AG_ePDQ_class . 'class-order.php';

		ag_ePDQ_fraud_checks::run_instance( array(
			'plugin_name' => self::$pluginName,
			'short_title' => self::$AG_ePDQ_slug,
		) );

		AG_ePDQ_Wizard::run_instance( array(
			'plugin_slug'    => self::$AG_ePDQ_slug,
			'plugin_version' => self::$AGversion,
			'plugin_name'    => self::$pluginName,
		) );

		AG_ePDQ_order_status_check::run_instance( array(
			'plugin_slug'    => self::$AG_ePDQ_slug,
			'plugin_version' => self::$AGversion,
			'plugin_name'    => self::$pluginName,
		) );

		ag_capture::run_instance( array(
			'plugin_slug'    => self::$AG_ePDQ_slug,
			'plugin_version' => self::$AGversion,
			'plugin_name'    => self::$pluginName,
		) );

		ag_ePDQ_moto::run_instance( array(
			'plugin_name' => self::$AG_ePDQ_slug,
			'short_title' => self::$pluginName,
		) );

	}

}

$AG_ePDQ_server = new AG_ePDQ_server();
register_activation_hook( __FILE__, array( $AG_ePDQ_server, 'activate' ) );
