<?php
/*
 * Author: We are AG
 * Author URI: https://www.weareag.co.uk/
 * File: class-epdq-authorization-capture.php
 * Project: AG-woocommerce-epdq-payment-gateway
 * -----
 * Created: 24 November 2022 14:27
 * -----
 * Version: 1.0.0
 * WC requires at least: 3.0.0
 * WC tested up to: 5.5
 * License: GPL3
*/

/*-----------------------------------------------------------------------------------*/

/*	AG Capture
/*-----------------------------------------------------------------------------------*/

use Automattic\WooCommerce\Utilities\OrderUtil;

defined( 'ABSPATH' ) || die( "No script kiddies please!" );

if( class_exists( 'ag_capture' ) ) {
	return;
}


class ag_capture {

	public static $single_instance = NULL;
	public static $args = array();

	public function __construct() {

		// Add capture button
		add_action( 'woocommerce_order_item_add_action_buttons', array( $this, 'capture_button' ) );
		// Capture Ajax Call
		add_action( 'admin_enqueue_scripts', array( $this, 'capture_ajax_call' ) );
		// Change capture checkout title.
		add_action( 'wp_ajax_ag_epdq_manually_capture', array( $this, 'ag_epdq_manually_capture' ) );

	}

	/**
	 * run
	 */
	public static function run_instance( $args = array() ) {

		if( self::$single_instance === NULL ) {
			self::$args = $args;
			self::$single_instance = new self();
		}

		return self::$single_instance;
	}

	/**
	 * Add capture button to order screen if not paid order.
	 *
	 * @param $order
	 */
	public static function capture_button( $order ) {

		// Check order is using our plugin
		if( $order->get_payment_method() !== 'epdq_checkout' ) {
			return;
		}

		// Not Authorised
		$status = $order->get_meta( 'STATUS' );
		if( $status !== '5' ) {
			return;
		}

		if( $order->has_status( array( 'processing', 'completed' ) ) ) {
			return;
		}
		echo '<button style="background: #007cba; color: white;" type="button" id="ag-capture-epdq" class="button ag-capture" data-order_url="' . esc_attr( get_edit_post_link( $order->get_id() ) ) . '" data-order_id="' . esc_attr( $order->get_id() ) . '" data-plugin="' . AG_ePDQ_server_path . '">ePDQ Capture Payment</button > '; // @phpstan-ignore-line

	}

	public function capture_ajax_call( $hook ) {

		global $post;

		if( 'post.php' == $hook && 'shop_order' == $post->post_type && isset( $_GET['action'] ) && 'edit' == $_GET['action'] ) {

			if( OrderUtil::custom_orders_table_usage_is_enabled() ) {
				$order = wc_get_order( AG_ePDQ_Helpers::AG_decode( $_GET['id'] ) );
			} else {
				global $post;
				$order = wc_get_order( $post->ID );
			}

			if( $order->get_payment_method() !== 'epdq_checkout' ) {
				return;
			}

			wp_enqueue_script( self::$args['plugin_name'] . '-ag-capture', AG_ePDQ_server_path . "inc/assets/js/ag-capture.js", array( 'jquery' ), NULL, TRUE ); // @phpstan-ignore-line
			wp_localize_script( self::$args['plugin_name'] . '-ag-capture', 'ag_epdq_capture_var', array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'msg'     => __( 'Are you sure you wish to check the status of this order? ', 'ag_epdq_server' ),
				'nonce'   => wp_create_nonce( self::$args['plugin_name'] . '-ag-capture' ),
				'error'   => __( 'Something went wrong, and the capture of payment could not be completed. Please try again. ', 'ag_epdq_server' ),
			) );

		}

	}

	/**
	 * AG Manually check status call.
	 */
	function ag_epdq_manually_capture() {

		check_ajax_referer( self::$args['plugin_name'] . '-ag-capture', 'nonce' );

		// the data from the ajax call
		$order_id = AG_ePDQ_Helpers::AG_decode( $_POST['order_id'] );
		$order = new WC_Order( $order_id );
		$settings = ePDQ_crypt::key_settings();
		$refund_settings = ePDQ_crypt::refund_settings();
		$environment_url = AG_ePDQ_Helpers::get_enviroment_url( 'maintenancedirect' );

		// Check order is using our plugin
		if( $order->get_payment_method() !== 'epdq_checkout' ) {
			return;
		}

		AG_ePDQ_Helpers::ag_log( 'Payment capture manually ran.', 'debug', 'yes' );

		$payid = $order->get_meta( 'PAYID' );
		if( ! $payid ) {
			AG_ePDQ_Helpers::ag_log( 'PAYID is missing from this order, you will need to manually refund this order in the ePDQ back office.', 'debug', 'yes' );

			return new WP_Error( 'error', __( 'PAYID is missing from this order, you will need to manually refund this order in the ePDQ back office.', 'ag_epdq_server' ) );
		}

		$data_post = array();
		$data_post['AMOUNT'] = $order->get_total() * 100;
		$data_post['OPERATION'] = 'SAS';
		$data_post['PAYID'] = $payid;

		if( get_woocommerce_currency() != 'GBP' && defined( 'ePDQ_PSPID' ) && defined( 'ePDQ_apiUser' ) && defined( 'ePDQ_apiPassword' ) ) {
			$data_post['PSPID'] = ePDQ_PSPID;
			$data_post['USERID'] = ePDQ_apiUser;
			$data_post['PSWD'] = ePDQ_apiPassword;
		} else {
			$data_post['PSPID'] = $refund_settings['REFID'];
			$data_post['USERID'] = $refund_settings['USERID'];
			$data_post['PSWD'] = $refund_settings['PSWD'];
		}

		$shasign_arg = [];
		if( isset( $settings['shain'] ) ) {

			ksort( $data_post );
			foreach( $data_post as $key => $value ) {
				if( $value == '' ) {
					continue;
				}
				$shasign_arg[] = $key . '=' . $value;
			}

			$SHAsig = hash( ePDQ_crypt::get_sha_method(), implode( $settings['shain'], $shasign_arg ) . $settings['shain'] );
			$data_post['SHASIGN'] = $SHAsig;
		}

		AG_ePDQ_Helpers::ag_log( print_r( $data_post, TRUE ), 'debug', 'yes' );

		// Post
		$returned = AG_ePDQ_Helpers::remote_post( $environment_url, $data_post );

		if( ! isset( $returned['STATUS'] ) ) {
			return;
		}

		if( defined( 'ag_support_debug' ) ) {
			AG_ePDQ_Helpers::ag_log( print_r( $returned, TRUE ), 'debug', 'yes' );
		}

		// Process order data and update order status
		epdq_order::process( $returned, '[Capture] ', $order );

	}

}