<?php
/*-----------------------------------------------------------------------------------*/

/*	Authipay Moto
/*-----------------------------------------------------------------------------------*/
defined( 'ABSPATH' ) || die( "No script kiddies please!" );

if( class_exists( 'ag_ePDQ_moto' ) ) {
	return;
}


class ag_ePDQ_moto {

	public static $single_instance = NULL;
	public static $args = array();

	public function __construct() {

		// Add Moto button
		add_action( 'woocommerce_order_item_add_action_buttons', array( $this, 'moto_button' ) );
		// Moto Ajax Call
		add_action( 'admin_enqueue_scripts', array( $this, 'moto_ajax_call' ) );
		// Ajax callback
		add_action( 'wp_ajax_ag_pre_moto_call', array( $this, 'ag_pre_moto_call' ) );
		// Allow admins to process customer orders on account.
		add_action( 'user_has_cap', array( $this, 'ag_allow_admin_to_pay_for_order' ), 10, 3 );
		// Change Moto checkout title.
		add_filter( 'woocommerce_endpoint_order-pay_title', array( $this, 'change_checkout_order_pay_title' ) );

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
	 * Add Moto button to order screen if not paid order.
	 *
	 * @param $order
	 */
	public static function moto_button( $order ) {

		if( $order->get_status() !== 'pending' ) {
			return;
		}

		if( ! $order->get_date_paid() ) {
			// $on_checkout = false
			echo '<button style="background: #007cba; color: white;" type="button" id="ag-moto" class="button ag-moto" data-order_url="' . esc_attr( $order->get_checkout_payment_url() ) . '" data-order_id="' . esc_attr( $order->get_id() ) . '" data-plugin="' . AG_ePDQ_server_path . '"> MOTO Payment</button>';


		}

	}

	
	public function moto_ajax_call() {

		wp_enqueue_script( self::$args['plugin_name'] . '-moto', AG_ePDQ_server_path . "inc/assets/js/ag-moto-script.js", array( 'jquery' ), NULL, TRUE );
		wp_localize_script( self::$args['plugin_name'] . '-moto', 'ag_moto_var', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'msg'     => __( 'Are you sure you wish to check the status of this order?', 'ag_epdq_server' ),
			'nonce'   => wp_create_nonce( self::$args['plugin_name'] . '-moto' ),
			'error'   => __( 'Something went wrong, and the MOTO payment could not be completed. Please try again.', 'ag_epdq_server' ),
		) );

	}

	/**
	 * Allow admins to process orders on customers behalf.
	 */
	public function ag_allow_admin_to_pay_for_order( $allcaps, $caps, $args ) {

		$user = wp_get_current_user();
		$allowed_roles = array( 'administrator', 'shop_manager' );

		// Check we are looking at the WooCommerce Pay For Order Page
		if( ! isset( $caps[0] ) || $caps[0] !== 'pay_for_order' ) {
			return $allcaps;
		}
		// Check that a Key is provided
		if( ! isset( $_GET['key'] ) ) {
			return $allcaps;
		}

		// Find the Related Order
		$order = wc_get_order( $args[2] );
		if( ! $order ) {
			return $allcaps;
		} # Invalid Order

		if( get_post_meta( $order->get_id(), 'is_moto', TRUE ) && array_intersect( $allowed_roles, $user->roles ) ) {

			$order_key = $order->get_order_key();
			$order_key_check = $_GET['key'];

			$error_message = __( "This order is a " . self::$args['short_title'] . " MOTO payment. Only store admins or shop managers can process the order.", "woocommerce", 'ag_epdq_server' );
			if( ! wc_has_notice( $error_message, 'notice' ) ) {
				wc_add_notice( $error_message, 'notice' );
			}

			$allcaps['pay_for_order'] = ( $order_key === $order_key_check );

		}

		return $allcaps;

	}

	public function ag_pre_moto_call() {

		check_ajax_referer( self::$args['plugin_name'] . '-moto', 'nonce' );

		// the data from the ajax call
		$order_id = (int) $_POST['order_id'];
		$order = new WC_Order( $order_id );

		// Is MOTO order
		$order->update_meta_data( 'is_moto', TRUE );
		$order->save();

	}


	public function change_checkout_order_pay_title( $title ) {

		global $wp;
		$user = wp_get_current_user();
		$allowed_roles = array( 'administrator', 'shop_manager' );

		if( get_post_meta( $wp->query_vars['order-pay'], 'is_moto', TRUE ) && array_intersect( $allowed_roles, $user->roles ) ) {

			return __( self::$args['short_title'] . " MOTO Checkout", "woocommerce", 'ag_epdq_server' );

		}

	}

}
