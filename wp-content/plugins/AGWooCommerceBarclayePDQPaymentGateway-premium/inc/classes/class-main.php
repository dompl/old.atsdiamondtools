<?php
/*
 * Author: Aaron Bowie (support@weareag.co.uk)
 * Author URI: https://www.weareag.co.uk/
 * File: class.epdq.php
 * Project: AG-woocommerce-epdq-payment-gateway
 * -----
 * Modified By: Aaron Bowie - We are AG
 * -----
 * WC requires at least: 3.0.0
 * WC tested up to: 9.1.4
 * License: GPL3
 */

defined( 'ABSPATH' ) || die( "No script kiddies please!" );

class epdq_checkout extends WC_Payment_Gateway {

	// URL's
	protected const TEST_URL = 'https://mdepayments.epdq.co.uk/ncol/test/orderstandard.asp';
	protected const LIVE_URL = 'https://payments.epdq.co.uk/ncol/prod/orderstandard.asp';
	protected const REFUND_TEST_URL = 'https://mdepayments.epdq.co.uk/ncol/test/maintenancedirect.asp';
	protected const REFUND_LIVE_URL = 'https://payments.epdq.co.uk/ncol/prod/maintenancedirect.asp';

	private static $AG_ePDQ_doc = "https://weareag.co.uk/docs/";

	private $test_url;
	private $live_url;
	private $refund_test;
	private $refund_live;

	public $notice;
	public $status;
	public $threeds;
	public $fraudCheck;
	public $statusCheck;
	public $debug;
	public $access_key;
	public $sha_in;
	public $sha_out;
	public $sha_method;
	public $refund;
	public $logo;
	public $tip;
	public $template;
	public $pmlisttype;
	public $webhook;

	public $cardtypes;

	public $api_REFID;
	public $api_password;
	public $api_user;

	//wizard
	public $shaMethod;
	//public $payment_gateways;

	// webhook
	public $token;

	public $check_interval;
	public $order_status;
	public $autostatus;
	public $actionscheduler;
	public $cancelblock;
	public $cancel_interval;


	public function __construct() {

		$this->id = 'epdq_checkout';
		$this->method_title = 'AG ePDQ Checkout';
		$this->icon = apply_filters( 'woocommerce_epdq_checkout_icon', '' );
		//$this->has_fields = FALSE;

		if( ! AG_licence::valid_licence() ) {
			return;
		}

		$this->init_form_fields();
		$this->init_settings();

		// Turn settings into variables we can use
		foreach( $this->settings as $setting_key => $value ) {
			$this->$setting_key = $value;
		}

		$this->status = $this->get_option( 'status' ) ?? 'test';

		$this->test_url = self::TEST_URL;
		$this->live_url = self::LIVE_URL;
		$this->refund_test = self::REFUND_TEST_URL;
		$this->refund_live = self::REFUND_LIVE_URL;
		$this->notice = $this->notice ?? 'no';
		$this->threeds = $this->get_option( 'threeds' ) ?? 'no';
		$this->description = $this->display_checkout_description();
		$this->fraudCheck = $this->get_option( 'fraudCheck' ) ?? 'no';

		$this->supports = array(
			'products',
			'refunds',
			'tokenization',
			'subscriptions',
			'subscription_cancellation',
			'subscription_suspension',
			'subscription_reactivation',
			'subscription_amount_changes',
			'subscription_date_changes',
			'multiple_subscriptions',
		);

		// Save options
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array(
			$this,
			'process_admin_options'
		) );

		// Payment listener/API hook
		add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
		add_action( 'woocommerce_api_' . $this->id, array( $this, 'check_response' ) );
		add_action( 'woocommerce_api_' . $this->id . '_webhook', array( 'ag_epdq_webhook', 'webhook' ) );
		add_action( 'woocommerce_api_' . $this->id . '_token', array( 'ag_epdq_webhook', 'token' ) );

		add_action( 'woocommerce_scheduled_subscription_payment_' . $this->id, array(
			'ePDQ_Sub',
			'process_subscription_payment'
		), 10, 2 );
		add_action( 'woocommerce_order_action_wcs_retry_renewal_payment', array(
			'ePDQ_Sub',
			'ag_store_process_retry_renewal_payment'
		), 20, 1 );

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_script' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'ag_checkout_css' ) );

		add_action( 'woocommerce_before_account_payment_methods', array( $this, 'display_success_message' ) );

		add_filter( 'woocommerce_gateway_description', array( $this, 'display_token_to_customer' ), 20, 2 );

	}

	
	public function display_token_to_customer( $description, $payment_id ) {
		// Check if the payment ID is 'ag_fd_checkout' and the token is set to 'yes'
		if ( $payment_id === 'epdq_checkout' && isset( $this->token ) && $this->token === 'yes' ) {
			$saved_cards_html = AG_ePDQ_Token::selectSavedCards( get_current_user_id(), is_user_logged_in() );
	
			// Check if the saved cards HTML is already in the description to prevent duplication
			if (strpos($description, $saved_cards_html) === false) {
				return $description . $saved_cards_html;
			}
		}
	
		// Return the original description if conditions are not met or already appended
		return $description;
	}

	public function ag_checkout_css() {

		if( ! is_checkout() ) {
			return;
		}

		wp_enqueue_style( 'ag-ePDQ', AG_ePDQ_server_path . 'inc/assets/css/style.css', FALSE, AG_ePDQ_server::$AGversion );

		if( isset( $this->token ) && $this->token === 'yes' ) {
			wp_enqueue_script( 'epdq_checkout_token_script', AG_ePDQ_server_path . 'inc/assets/js/checkout-token.js', TRUE );
		}

	}


	public function display_success_message() {

		if( isset( $_GET['token_success'] ) && $_GET['token_success'] === '1' ) {
			wc_add_notice( 'Payment method added successfully!', 'success' );
		}
		if( isset( $_GET['token_success'] ) && $_GET['token_success'] === '0' ) {
			wc_add_notice( 'There was an issue adding your payment method!', 'error' );
		}
		wc_print_notices();

	}


	public function add_payment_method() {

		AG_ePDQ_Token::addNewCard();

		return [];
	}

	/**
	 * Plugin settings
	 *
	 * @return void
	 */
	public function init_form_fields() {

		$this->form_fields = array_merge( AG_ePDQ_Settings::form_fields(), AG_ePDQ_Settings::api_fields(), AG_ePDQ_Settings::form_advanced(), AG_ePDQ_Settings::status_check() );
	}

	public function admin_script() {

		$screen = get_current_screen();

		if( 'woocommerce_page_wc-settings' !== $screen->base ) {
			return;
		}

		wp_enqueue_script( 'ePDQ_settings_script', AG_ePDQ_server_path . 'inc/assets/js/admin-script.js' );  // @phpstan-ignore-line
		wp_enqueue_script( 'ePDQ_alert', 'https://unpkg.com/sweetalert/dist/sweetalert.min.js' );
		wp_enqueue_style( 'ag_ePDQ_admin', AG_ePDQ_server_path . 'inc/assets/css/admin-style.css', FALSE, AG_ePDQ_server::$AGversion ); // @phpstan-ignore-line

	}

	/**
	 * Test mode message added to checkout when test mode enabled
	 *
	 * @return string $input
	 */
	public function display_test_message(): string {

		return sprintf( __( '<br /><strong>TEST MODE ACTIVE.</strong><br />In test mode, you can use Visa card number 4444 3333 2222 1111 with any CVC and a valid expiration date or check the documentation <a target="_blank" href="%s">here</a> for more card numbers, steps on setting up and troubleshooting.', 'ag_epdq_server' ), 'https://www.weareag.co.uk/product/ag-barclays-epdq-payment-gateway-woocommerce/' );

	}

	/**
	 * Display notice to customer of redirect to ePDQ server
	 *
	 * @return string $input
	 */
	public function display_redirect_message(): string {

		$input = sprintf( __( '<span class="AG-redirect-icon"><img src="%s" /></span>', 'ag_epdq_server' ), AG_ePDQ_server_path . 'inc/assets/img/AG-ePDQ-redirect.png' );  // @phpstan-ignore-line
		$input .= __( '<p class="AG-redirect-notice">After clicking "Place order", you will be redirected to Barclays to complete your purchase securely.</p>', 'ag_epdq_server' );

		return $input;

	}

	/**
	 * Logic for displaying notices
	 *
	 * @return string $description
	 */
	public function display_checkout_description(): string {

		$description = '';

		if( $this->notice === 'yes' ) {
			$description .= $this->display_redirect_message();
		} else {
			$description .= $this->get_option( 'description' );
		}

		if( $this->status === 'test' ) {
			$description .= $this->display_test_message();
		}

		return $description;

	}


	/**
	 * Display card icons
	 *
	 * @return void
	 */
	public function get_icon() {

		$cardTypes = ( $this->cardtypes ?? '' );
		$icon = '';
		if( ! $cardTypes ) {
			// default behavior
			$icon = '<img src="' . AG_ePDQ_server_path . 'inc/assets/img/cards.gif" alt="' . $this->title . '" />';  // @phpstan-ignore-line
		} elseif( $cardTypes ) {
			// display icons for the selected card types
			foreach( $cardTypes as $cardtype ) {
				$icon .= '<img class="ePDQ-card-icons" src="' . AG_ePDQ_server_path . 'inc/assets/img/new-card/' . strtolower( $cardtype ) . '.png" alt="' . strtolower( $cardtype ) . '" />';  // @phpstan-ignore-line
			}
		}

		return apply_filters( 'woocommerce_gateway_icon', $icon, $this->id );
	}

	/**
	 * Display settings
	 *
	 * @return void
	 */
	public function admin_options() { ?>

        <h3><?php echo __( 'AG ePDQ Checkout Settings', 'ag_epdq_server' ); ?></h3>

        <h2 id="ag-nav" class="nav-tab-wrapper">
            <a href="#general" class="nav-tab nav-tab-active">General Settings</a> <a href="#rest" class="nav-tab">REST API</a> <a href="#statuccheck" class="nav-tab">Status Check</a> <a href="#advanced" class="nav-tab">Advanced</a>
        </h2>
        <div id="general" class="settings-tab-content" style="display: block;">
            <div class="wrapper-info">
                <div class="ag-notice">
                    <div class="subject">
                        <h3>General Settings</h3>
                        <p><?php echo __( 'This gateway directs customers to the secure Barclays payment server to process transactions. After completing the payment, customers will be automatically redirected back to your website.', 'ag_epdq_server' ) ?></p>
                        <i><?php echo __( 'Encounter setup difficulties? Our <a href="' . admin_url( '?page=AG_ePDQ-wizard' ) . '">Setup Wizard</a> can help streamline the process.', 'ag_epdq_server' ) ?></i>
                    </div>
                </div>
            </div>
            <table class="form-table">
				<?php echo $this->generate_settings_html( AG_ePDQ_Settings::form_fields() ); ?>
            </table>
        </div>
        <div id="rest" class="settings-tab-content" style="display: none;">
            <div class="wrapper-info">
                <div class="ag-notice">
                    <div class="subject">
                        <h3>REST API Settings</h3>
                        <p><?php echo __( 'These settings are crucial for enabling several key features including processing refunds, performing status checks, and managing subscription renewal payments. Configure these to ensure smooth operation of these functionalities.', 'ag_epdq_server' ) ?></p>
                    </div>
                </div>
            </div>
            <table class="form-table">
				<?php echo $this->generate_settings_html( AG_ePDQ_Settings::api_fields() ); ?>
            </table>
        </div>
        <div id="statuccheck" class="settings-tab-content" style="display: none;">
            <div class="wrapper-info">
                <div class="ag-notice">
                    <div class="subject">
                        <h3>Status Check Settings</h3>
                        <p><?php echo __( 'Welcome to the Status Check Settings page. Here, you will find a range of features and options designed to help you manage and automate the ePDQ status checking for your orders.', 'ag_epdq_server' ) ?><br/>
                            <strong><?php echo __( 'This feature relies on the proper configuration of the REST API. Please ensure that your REST API settings are correctly set up to utilise these status check functionalities effectively.', 'ag_epdq_server' ) ?></strong>.</p>

                    </div>
                </div>
            </div>
            <table class="form-table">
				<?php echo $this->generate_settings_html( AG_ePDQ_Settings::status_check() ); ?>
            </table>
        </div>
        <div id="advanced" class="settings-tab-content" style="display: none;">
            <div class="wrapper-info">
                <div class="ag-notice">
                    <div class="subject">
                        <h3>Advanced Settings</h3>
                        <p><?php echo __( 'Below you will find additional features and settings for the ePDQ plugin. Configure these options to further customise and enhance your plugin\'s functionality.', 'ag_epdq_server' ) ?></p>
                    </div>
                </div>
            </div>
            <table class="form-table">
				<?php echo $this->generate_settings_html( AG_ePDQ_Settings::form_advanced() ); ?>
            </table>
        </div>

        <p><strong>Need some help setting up this plugin?</strong> <a target="_blank" href="https://weareag.co.uk/docs/barclays-epdq-payment-gateway/">Click here</a></p>

		<?php

	}

	/**
	 * Process the payment and return the result
	 *
	 * @param $order_id
	 *
	 * @return array
	 */

	public function process_payment( $order_id ) {

		$order = new WC_Order( $order_id );

		// Storing data for 3Ds debugging.
		$orderdata = array(
			'customer_user_agent' => $_SERVER['HTTP_USER_AGENT'],
			'AG_sent_to_ePDQ'     => date( 'Y-m-d H:i:s', current_time( 'timestamp', 0 ) ),
		);

		AG_ePDQ_Helpers::update_order_meta_data( $order_id, $orderdata, $order );

		$order->update_meta_data( 'save_card', '' );
		$order->save();

		if( isset( $this->token ) && $this->token === 'yes' && isset( $_POST['saved_cards'] ) ) {
			$saved_cards = AG_ePDQ_Helpers::AG_decode( $_POST['saved_cards'] );
			$order->update_meta_data( 'use_saved_card', $saved_cards ?? NULL );
			$order->save();
		}

		// This is for debugging customer device type.
		if( defined( 'ag_support_debug' ) ) {
			AG_ePDQ_Helpers::ag_log( $_SERVER['HTTP_USER_AGENT'], 'debug', $this->debug );
		}

		return array(
			'result'   => 'success',
			'redirect' => $order->get_checkout_payment_url( TRUE )
		);
	}

	/**
	 * receipt_page
	 *
	 * @param $order_id
	 *
	 * @return void
	 */
	public function receipt_page( $order_id ) {

		$order = new WC_Order( $order_id );
		$settings = ePDQ_crypt::key_settings();
		$order_received_url = WC()->api_request_url( 'epdq_checkout' ) . '?idOrder=' . $order->get_id();
		$cancel_order_url = $order->get_cancel_order_url_raw();

		$hash_fields = array(
			$settings['pspid'],
			date( 'Y:m:d' ),
			$order->get_id(),
			$settings['shain'],
			get_bloginfo( 'name' )
		);
		$encrypted_string = ePDQ_crypt::ripemd_crypt( implode( $hash_fields ), $settings['shain'] );

		$fullName = remove_accents( $order->get_billing_first_name() . ' ' . str_replace( "'", "", $order->get_billing_last_name() ) );

		// Currency
		if( get_woocommerce_currency() !== 'GBP' && defined( 'ePDQ_PSPID' ) ) {
			$PSPID = ePDQ_PSPID;
		} else {
			$PSPID = $settings['pspid'];
		}

		// Use different PSPID (This is useful for stores that are franchisees)
		$ePDQ_PSPID = NULL;
		$multi_PSPID = apply_filters( 'ePDQ_PSPID', $ePDQ_PSPID, $order_id );
		if( ! empty( $multi_PSPID ) ) {
			$PSPID = $multi_PSPID;
		}

		// Products
		$order_item = $order->get_items();
		$product_name = [];
		$product_ids = [];
		foreach( $order_item as $product ) {
			$product_name[] = preg_replace( "/[^a-zA-Z0-9\s]/", "", str_replace( array(
					"-",
					" "
				), "", $product['name'] ) ) . " x" . $product['qty'];
			$product_ids[] = str_replace( '&', 'and', $product['product_id'] ) . " x" . $product['qty'];
		}
		$product_list_string = implode( ',', $product_name );
		$product_id_string = implode( ',', $product_ids );

		// If the items in the cart add to more than the character limit set by ePDQ then switch to product id.
		if( strlen( $product_list_string ) < 99 && get_locale() !== 'ar' ) {
			$product_list = $product_list_string;
		} elseif( strlen( $product_id_string ) < 99 ) {
			$product_list = $product_id_string;
		} else {
			// Fallback if both products name/id is more than character limit.
			$product_list = 'Please check order #' . $order_id . ' on website for information.';
			AG_ePDQ_Helpers::ag_log( 'Order #' . $order_id . ' had more products than allowed to pass over to ePDQ, default message was passed instead of product\'s name/id.', 'debug', $this->debug );
		}

		// Custom product data - this could be for custom meta data
		if( defined( 'ePDQ_custom_product_data' ) ) {
			$com = apply_filters( 'ePDQ_custom_product_data', $order );
		} else {
			$com = $product_list;
		}

		// Custom Merchant Ref - this could be for custom metadata
		$orderID = AG_ePDQ_Helpers::ag_get_order_id( $order );

		// Get customer token
		$savedCard = $order->get_meta( 'use_saved_card' );
		$customerToken = AG_ePDQ_Token::get( get_current_user_id(), is_user_logged_in(), $savedCard );
		// END

		// Challenge Indicator
		if( ( class_exists( 'WC_Subscriptions_Order' ) && wcs_order_contains_subscription( $order_id ) ) || ( isset( $this->token ) && $this->token === 'yes' ) ) {  // @phpstan-ignore-line
			$ChallengeIndicator = 04;
		} else {
			$ChallengeIndicator = 03;
		}

		$fields = array(
			'PSPID'                                  => $PSPID,
			'ORDERID'                                => $orderID,
			'AMOUNT'                                 => $order->get_total() * 100,
			'COMPLUS'                                => $encrypted_string,
			'CURRENCY'                               => get_woocommerce_currency(),
			'LANGUAGE'                               => get_locale(),
			'CN'                                     => $fullName,
			'COM'                                    => $com,
			'EMAIL'                                  => $order->get_billing_email(),
			'OWNERZIP'                               => preg_replace( '/[^A-Za-z0-9\. -]/', '', $order->get_billing_postcode() ),
			'OWNERADDRESS'                           => substr( preg_replace( '/[^A-Za-z0-9\. -]/', '', $order->get_billing_address_1() ), 0, 34 ),
			'OWNERADDRESS2'                          => substr( preg_replace( '/[^A-Za-z0-9\. -]/', '', $order->get_billing_address_2() ), 0, 34 ),
			'OWNERCTY'                               => substr( preg_replace( '/[^A-Za-z0-9\. -]/', '', $order->get_billing_country() ), 0, 34 ),
			'OWNERTOWN'                              => substr( preg_replace( '/[^A-Za-z0-9\. -]/', '', $order->get_billing_city() ), 0, 34 ),
			'OWNERTELNO'                             => $order->get_billing_phone(),
			'ACCEPTURL'                              => $order_received_url,
			'DECLINEURL'                             => $cancel_order_url,
			'HOMEURL'                                => $cancel_order_url,
			'TP'                                     => ( $this->template ?? '' ),
			'LOGO'                                   => ( $this->logo ?? '' ),
			'TITLE'                                  => '',
			'FLAG3D'                                 => 'Y',
			'MPI.THREEDSREQUESTORCHALLENGEINDICATOR' => $ChallengeIndicator
		);

		if( $order->get_shipping_address_1() ) {
			$fields['ECOM_SHIPTO_POSTAL_STREET_LINE1'] = substr( $order->get_shipping_address_1(), 0, 34 );
			$fields['ECOM_SHIPTO_POSTAL_STREET_LINE2'] = substr( $order->get_shipping_address_2(), 0, 34 );
			$fields['ECOM_SHIPTO_POSTAL_POSTALCODE'] = preg_replace( '/[^A-Za-z0-9\. -]/', '', $order->get_shipping_postcode() );
			$fields['ECOM_SHIPTO_POSTAL_COUNTY'] = preg_replace( '/[^A-Za-z0-9\. -]/', '', $order->get_shipping_country() );
			$fields['ECOM_SHIPTO_POSTAL_CITY'] = substr( $order->get_shipping_city(), 0, 34 );
			$fields['ECOM_SHIPTO_ONLINE_EMAIL'] = $order->get_billing_email();
			$fields['ECOM_SHIPTO_POSTAL_NAME_FIRST'] = substr( $order->get_shipping_first_name(), 0, 34 );
			$fields['ECOM_SHIPTO_POSTAL_NAME_LAST'] = substr( $order->get_shipping_last_name(), 0, 34 );
		}
		if( defined( 'ag_addmatch' ) ) {
			$fields['ADDMATCH'] = '1';

		}

		$site_name = get_bloginfo( 'name' );
		$m_site_name = preg_replace( "/[^a-zA-Z0-9]/", "", $site_name );
		$modified_site_name = str_replace( "039", "", $m_site_name );

		if( isset( $this->token ) && $this->token === 'yes' && ( class_exists( 'WC_Subscriptions_Order' ) && ! wcs_order_contains_subscription( $order_id ) ) ) {  // @phpstan-ignore-line
			$fields['ALIAS'] = $customerToken['token'] ?? '';
			$fields['ALIASOPERATION'] = 'BYPSP';
			$fields['ALIASUSAGE'] = $modified_site_name;
			$fields['COF_INITIATOR'] = 'CIT';
			$fields['BRAND'] = $customerToken['brand'] ?? '';
			$fields['PM'] = $customerToken['brand'] = 'CreditCard' ?? '';
		}

		if( class_exists( 'WC_Subscriptions_Order' ) && wcs_order_contains_subscription( $order_id ) ) {  // @phpstan-ignore-line

			$fields['ALIAS'] = 'VALUE';
			$fields['ALIASOPERATION'] = 'BYPSP';
			$fields['ALIASUSAGE'] = 'Setting up subscription for use on ' . $modified_site_name . ' Websites. Please authorise Barclaycard to store your details for your renewal payments.';
			$fields['COF_INITIATOR'] = 'CIT';
			$fields['COF_TRANSACTION'] = 'FIRST';
			$fields['COF_SCHEDULE'] = 'SCHED';
			$fields['BRAND'] = $customerToken['brand'] ?? '';
			$fields['PM'] = $customerToken['brand'] = 'CreditCard' ?? '';

		}

		//Server-to-server parameter
		$fields['PARAMVAR'] = $order->get_id();

		// Order card icons on ePDQ side
		$fields['PMLISTTYPE'] = ( $this->pmlisttype ?? '0' );

		// Hook to add extra para
		do_action( 'AG_ePDQ_extra_parameters' );

		$shasign_arg = array();
		ksort( $fields );
		foreach( $fields as $key => $value ) {
			if( $value == '' ) {
				continue;
			}
			$shasign_arg[] = $key . '=' . $value;
		}

		$shasign = hash( ePDQ_crypt::get_sha_method(), implode( $settings['shain'], $shasign_arg ) . $settings['shain'] );
		$fields['SHASIGN'] = $shasign;

		// Enable deeper debugging, useful for when the ePDQ team require data to debug.
		if( defined( 'ag_support_debug' ) ) {
			AG_ePDQ_Helpers::ag_log( print_r( $fields, TRUE ) . ' ' . print_r( $shasign, TRUE ), 'debug', $this->debug );
		}

		if( empty( $this->access_key ) || empty( $this->sha_in ) ) {

			AG_ePDQ_Helpers::ag_log( 'You are missing your PSPID and or SHA-IN', 'debug', $this->debug );
			wc_add_notice( 'ePDQ Bad Setup: You are missing your PSPID and or SHA-IN', 'error' );

			return;
		}

		$redirect_url = '';
		if( $this->status === 'test' ) {
			$redirect_url = $this->test_url;
		}
		if( $this->status === 'live' ) {
			$redirect_url = $this->live_url;
		}

		$query_string = http_build_query( $fields );
		$redirect_url .= '?' . $query_string;

		// Perform the redirect
		header( 'Location: ' . $redirect_url );
		exit;

	}

	/**
	 * Check payment response
	 *
	 * @return void
	 */
	public function check_response() {

		ob_clean();
		header( 'HTTP/1.1 200 OK' );
		$check_data = array();

		foreach( $_REQUEST as $key => $value ) {
			if( $value == "" ) {
				continue;
			}
			$check_data[ AG_ePDQ_Helpers::AG_escape( $key ) ] = AG_ePDQ_Helpers::AG_escape( $value );
			$datacheck[ AG_ePDQ_Helpers::AG_escape( strtoupper( $key ) ) ] = AG_ePDQ_Helpers::AG_escape( strtoupper( $value ) );
		}

		if( NULL === AG_ePDQ_Helpers::AG_get_request( 'STATUS' ) ) {
			AG_ePDQ_Helpers::ag_log( 'The transaction failed, ePDQ didn\'t send any data back. Please check you have setup the plugin correctly.', 'warning', $this->debug );
			wp_die( 'No data returned.' );
		}

		// Check if the nonce is valid
		$nonce = AG_ePDQ_Helpers::AG_escape( $_REQUEST['COMPLUS'] );
		$encrypted_string = ePDQ_crypt::complus_decrypt();
		if( ! hash_equals( $encrypted_string, $nonce ) ) {
			// Nonce check fail
			AG_ePDQ_Helpers::ag_log( 'Security check fail, please check the docs ' . self::$AG_ePDQ_doc . 'barclays-epdq-payment-gateway/troubleshooting-barclays-epdq-payment-gateway/security-check-fail/', 'warning', $this->debug );
			wp_die( 'Security check fail.' );
		}

		$SHA_check = $this->SHA_check( $check_data );
		if( $SHA_check ) {
			// Process
			$this->successful_transaction( $check_data );
		} else {

			AG_ePDQ_Helpers::ag_log( 'Transaction is unsuccessful due to a SHA-Out issue, please check the docs ' . self::$AG_ePDQ_doc . 'barclays-epdq-payment-gateway/troubleshooting-barclays-epdq-payment-gateway/transaction-is-unsuccessful-due-to-a-sha-out-issue/', 'warning', $this->debug );
			wp_die( 'Transaction is unsuccessful due to a SHA-Out issue' );

		}

	}

	/**
	 * Check SHA data
	 *
	 * @param $datatocheck
	 *
	 * @return bool
	 */
	protected function SHA_check( $datatocheck ) {

		$settings = ePDQ_crypt::key_settings();
		$SHA_out = $settings['shaout'];
		$origsig = $datatocheck['SHASIGN'];

		// Remove parameters before doing decryption
		unset( $datatocheck['SHASIGN'], $datatocheck['wc-api'], $datatocheck['idOrder'], $datatocheck['PARAMVAR'], $datatocheck['callback'], $datatocheck['doing_wp_cron'], $datatocheck['woocs_order_emails_is_sending'], $datatocheck['q'], $datatocheck['somdn_error_logs_export_errors'], $datatocheck['inner_section'], $datatocheck['woof_parse_query'] );

		uksort( $datatocheck, 'strcasecmp' );

		// Enable deeper debugging, useful for when the ePDQ team require data to debug.
		if( defined( 'ag_support_debug' ) ) {
			$args = array(
				'AAVADDRESS' => $datatocheck['AAVADDRESS'] ?? '',
				'ACCEPTANCE' => $datatocheck['ACCEPTANCE'] ?? '',
				'COMPLUS'    => $datatocheck['COMPLUS'] ?? '',
				'NCERROR'    => $datatocheck['NCERROR'] ?? '',
				'orderID'    => $datatocheck['orderID'] ?? '',
				'PAYID'      => $datatocheck['PAYID'] ?? '',
				'STATUS'     => $datatocheck['STATUS'] ?? ''
			);
			AG_ePDQ_Helpers::ag_log( 'Debug data sent back ' . print_r( $args, TRUE ), 'debug', $this->debug );
		}

		$SHAsig = '';
		foreach( $datatocheck as $key => $value ) {
			$SHAsig .= trim( strtoupper( $key ) ) . '=' . utf8_encode( trim( $value ) ) . $SHA_out;
		}

		$SHAsig = strtoupper( hash( ePDQ_crypt::get_sha_method(), $SHAsig ) );

		if( hash_equals( $SHAsig, $origsig ) ) {
			return TRUE;
		}

		return FALSE;

	}

	/**
	 * Successful transaction
	 *
	 * @param $args
	 *
	 * @return void
	 */
	public function successful_transaction( $args ) {

		$order = new WC_Order( $args['idOrder'] );

		// Catch and stop if order is already paid for.
		if( $order->has_status( array( 'processing', 'completed' ) ) ) {
			AG_ePDQ_Helpers::ag_log( 'Aborting, Order #' . $args['idOrder'] . ' is already paid for.', 'debug', 'yes' );
			wp_redirect( $order->get_checkout_order_received_url() );
			exit;
		}

		// Save payment token to user
		if( isset( $this->token ) && $this->token === 'yes' || ( class_exists( 'WC_Subscriptions_Order' ) && wcs_order_contains_subscription( $order ) ) ) {  // @phpstan-ignore-line
			AG_ePDQ_Token::save( $args, get_current_user_id(), is_user_logged_in() );
			// Drop BIN
			unset( $args['BIN'] );
			$order->update_meta_data( 'use_saved_card', '' );
			$order->save();
		}
		// END

		$order_notes = array(
			'Order ID                            : ' => $args['ORDERID'] ?? '',
			'Amount                              : ' => $args['AMOUNT'] ?? '',
			'Order Currency                      : ' => $args['CURRENCY'] ?? '',
			'Payment Method                      : ' => $args['PM'] ?? '',
			'Acceptance Code Returned By Acquirer: ' => $args['ACCEPTANCE'] ?? '',
			'Payment Reference In ePDQ System    : ' => $args['PAYID'] ?? '',
			'Error Code                          : ' => AG_errors::get_epdq_ncerror( $args['NCERROR'] ?? '' ),
			'Card Brand                          : ' => $args['BRAND'] ?? '',
			'Transaction Date                    : ' => $args['TRXDATE'] ?? '',
			'Cardholder/Customer Name            : ' => $args['CN'] ?? '',
			'Customer IP                         : ' => $args['IP'] ?? '',
			'AAV Result For Address              : ' => $args['AAVADDRESS'] ?? '',
			'Result for AAV Check                : ' => $args['AAVCHECK'] ?? '',
			'AAV Result For Postcode             : ' => $args['AAVZIP'] ?? '',
		);

		AG_ePDQ_Helpers::update_order_notes( $order, $order_notes );

		// Time customer took to process through ePDQ
		$order->update_meta_data( 'AG_returned_back', date( 'Y-m-d H:i:s', current_time( 'timestamp', 0 ) ) );
		$order->save();
		$start = new DateTime( $order->get_meta( 'AG_sent_to_ePDQ' ) );
		$finish = new DateTime( $order->get_meta( 'AG_returned_back' ) );

		if( $start !== NULL && $finish !== NULL ) {

			$interval = date_diff( $start, $finish );
			AG_ePDQ_Helpers::ag_log( 'Customer took ' . $interval->format( '%i Minute %s Seconds' ) . ' to process through ePDQ', 'debug', $this->debug );
			$time = __( 'Customer took ' . $interval->format( '%i Minute %s Seconds' ) . ' to process through ePDQ', 'ag_epdq_server' );
			$order->add_order_note( $time );

		}

		unset( $args['SHASIGN'], $args['COMPLUS'], $args['CARDNO'], $args['ALIAS'] );
		AG_ePDQ_Helpers::update_order_meta_data( $args['idOrder'], $args, $order );

		// Process order data and update order status
		epdq_order::process( $args, '[Payment] ', $order );

		wp_redirect( $order->get_checkout_order_received_url() );
	}

	/**
	 * Process refund
	 *
	 * @param $order_id
	 * @param $amount
	 * @param string $reason
	 *
	 * @return bool
	 */
	function process_refund( $order_id, $amount = NULL, $reason = '' ) {

		$order = new WC_Order( $order_id );
		$settings = ePDQ_crypt::key_settings();
		$refund_settings = ePDQ_crypt::refund_settings();
		$environment_url = AG_ePDQ_Helpers::get_enviroment_url( 'maintenancedirect' );

		$refund_amount = $amount * 100;
		$transaction_id = $order->get_meta( 'PAYID' );

		if( $this->status === 'test' ) {
			$environment_url = $this->refund_test;
		}
		if( $this->status === 'live' ) {
			$environment_url = $this->refund_live;
		}

		if( ! $transaction_id ) {
			AG_ePDQ_Helpers::ag_log( 'Refund failed: Transaction ID not found.', 'debug', $this->debug );

			return new WP_Error( 'error', __( 'Refund failed: Transaction ID not found.', 'ag_epdq_server' ) );
		}
		if( ! $refund_amount ) {
			AG_ePDQ_Helpers::ag_log( 'Refund failed: Amount invalid.', 'debug', $this->debug );

			return new WP_Error( 'error', __( 'Refund failed: Amount invalid.', 'ag_epdq_server' ) );
		}

		if( empty( $refund_settings['USERID'] ) ) {
			AG_ePDQ_Helpers::ag_log( 'Refund failed: API username has not been set.', 'debug', $this->debug );

			return new WP_Error( 'error', __( 'Refund failed: API username has not been set.', 'ag_epdq_server' ) );
		}

		if( empty( $refund_settings['PSWD'] ) ) {
			AG_ePDQ_Helpers::ag_log( 'Refund failed: API password has not been set.', 'debug', $this->debug );

			return new WP_Error( 'error', __( 'Refund failed: API password has not been set.', 'ag_epdq_server' ) );
		}

		if( AG_ePDQ_Helpers::ag_get_order_currency( $order ) !== 'GBP' && defined( 'ePDQ_PSPID' ) && defined( 'ePDQ_REFID' ) ) {
			$PSPID = ePDQ_PSPID;
			$refund_settings['REFID'] = ePDQ_REFID;
		} else {
			$PSPID = $settings['pspid'];
		}

		$data_post = array();
		$data_post['AMOUNT'] = $refund_amount;
		$data_post['PAYID'] = $transaction_id;
		$data_post['OPERATION'] = 'RFD';
		$data_post['ORDERID'] = $order_id;
		$data_post['PSPID'] = $PSPID;
		$data_post['PSWD'] = $refund_settings['PSWD'];
		$data_post['REFID'] = $refund_settings['REFID'];
		$data_post['USERID'] = $refund_settings['USERID'];

		$shasign_arg = array();

		ksort( $data_post );
		foreach( $data_post as $key => $value ) {
			if( $value == '' ) {
				continue;
			}
			$shasign_arg[] = $key . '=' . $value;
		}

		$SHAsig = hash( ePDQ_crypt::get_sha_method(), implode( $settings['shain'], $shasign_arg ) . $settings['shain'] );
		$data_post['SHASIGN'] = $SHAsig;

		// Enable deeper debugging, useful for when the ePDQ team require data to debug.
		if( defined( 'ag_support_debug' ) ) {
			AG_ePDQ_Helpers::ag_log( print_r( $data_post, TRUE ), 'debug', $this->debug );
		}

		// Post
		$result = AG_ePDQ_Helpers::remote_post( $environment_url, $data_post );

		$accepted = array( 8, 81, 85 ); // OK
		$string = implode( '|', $result );
		if( in_array( $result['STATUS'], $accepted ) ) {
			/* @phpstan-ignore-next-line */
			$order->add_order_note( __( 'Refund request successful', 'ag_epdq_server' ) . '<br />' . __( 'Refund Amount: ', 'ag_epdq_server' ) . $amount . '<br />' . __( 'Refund Reason: ', 'ag_epdq_server' ) . $reason . '<br />' . __( 'ePDQ Status: ', 'ag_epdq_server' ) . AG_errors::get_epdq_status_code( $result['STATUS'] ) . ' ' );

			return TRUE;
		}
		/* @phpstan-ignore-next-line */
		$order->add_order_note( __( 'Refund failed', 'ag_epdq_server' ) . '<br />' . __( 'ePDQ Status: ', 'ag_epdq_server' ) . AG_errors::get_epdq_status_code( $result['STATUS'] ) . '<br />' );
		$order->add_order_note( __( 'Refund Note', 'ag_epdq_server' ) . '<br /><strong>' . __( 'Error: ', 'ag_epdq_server' ) . $result['NCERRORPLUS'] . '</strong><br />' );
		// Log refund error
		AG_ePDQ_Helpers::ag_log( $string, 'debug', $this->debug );

		return new WP_Error( 'error', __( 'Refund failed: Please refresh this page and check the order notes, or the debug log.', 'ag_epdq_server' ) );
	}

}