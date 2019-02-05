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
 * WC tested up to: 3.5.4
 * License: GPL3
 */

defined('ABSPATH') or die("No script kiddies please!");

class epdq_checkout extends WC_Payment_Gateway {


	public function __construct() {
		global $woocommerce;

		$this->id 			= 'epdq_checkout';
		$this->method_title = 'AG EPDQ Checkout';
		$this->icon			= apply_filters( 'woocommerce_epdq_checkout_icon', '' );
		$this->has_fields 	= false;

		if (AG_licence::valid_licence() ) {
			if($this->get_option('refund') == 'yes') {
				$this->init_form_fields_refund();
			} else {
				$this->init_form_fields();
			}
			$this->init_settings();
		}

		$this->title 		= $this->get_option( 'title' );
		$this->title 		= (isset($this->title) and $this->title !='') ? $this->title : 'EPDQ Checkout';
		$this->access_key 	= $this->get_option( 'access_key' );
		$this->api_user 	= $this->get_option( 'api_user' );
		$this->api_password = $this->get_option( 'api_password' );
		$this->test_url 	= 'https://mdepayments.epdq.co.uk/ncol/test/orderstandard.asp';
		$this->live_url 	= 'https://payments.epdq.co.uk/ncol/prod/orderstandard.asp';
		$this->status 		= $this->get_option('status');
		$this->error_notice = $this->get_option('error_notice');
		$this->sha_in 		= $this->get_option('sha_in');
		$this->sha_out 		= $this->get_option('sha_out');
		$this->sha_method 	= $this->get_option('sha_method');
		$this->template 	= $this->get_option('template');
		$this->logo	 		= $this->get_option('logo');
		$this->cardtypes  	= $this->get_option('cardtypes');
		$this->TITLE 		= $this->get_option('TITLE');
		$this->sha_method 	= ($this->sha_method !='') ? $this->sha_method : 0;

		if ( $this->status == 'test' ) {
			$this->description 	= $this->get_option( 'description' );
			$this->description .= '<br /><br />' . sprintf( __( '<br /><br /><strong>TEST MODE ACTIVE.</strong><br />In test mode, you can use Visa card number 4444 3333 2222 1111 with any CVC and a valid expiration date or check the documentation <a target="_blank" href="%s">here</a> for more card numbers, steps on setting up and troubleshooting.' ), 'https://www.weareag.co.uk/product/ag-barclays-epdq-payment-gateway-woocommerce/' );
			$this->description  = trim( $this->description );
		} else {
			$this->description 	= $this->get_option( 'description' );
		}

		//
		// TODO -- 'subscription_date_changes'
		//
		$this->supports = array( 'refunds' );
		//$this->supports = array( 'refunds', 'subscription_date_changes', 'subscriptions'  );

		$this->aavscheck = $this->get_option('aavcheck');
		$this->cvccheck = $this->get_option('cvccheck');

		// Save options
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

		// Payment listener/API hook
		add_action( 'woocommerce_receipt_epdq_checkout', array( $this, 'receipt_page' ) );
		add_action('woocommerce_api_epdq_checkout', array($this, 'check_response'));

	}

	function get_icon() {

		$icon = '';
		if ( !$this->cardtypes ) {
			// default behavior
			$icon = '<img src="' . AG_ePDQ_server_path .'/img/cards.gif" alt="' . $this->title . '" />';
		} elseif ( $this->cardtypes ) {
			// display icons for the selected card types
			$icon = '';
			foreach ( $this->cardtypes as $cardtype ) {
					$icon .= '<img style="margin-left:5px; margin-right: 5px;" src="' . AG_ePDQ_server_path .'img/new-card/' . strtolower( $cardtype ) . '.png" alt="' . strtolower( $cardtype ) . '" />';
			}
		}

		return apply_filters( 'woocommerce_gateway_icon', $icon, $this->id );
	}

	public function admin_options() {
		global $woocommerce; ?>

		<h3><?php _e( 'AG ePDQ Checkout Settings', 'ag_epdq_checkout' ); ?></h3>
		<p><?php _e('This gateway will redirect the customers to the secured Barclay payment server and process the order there, Once payment is made Barclays will send them back to the provided links based on the status of their transaction.','ag_epdq_checkout')?></p>

		<table class="form-table">
			<?php $this->generate_settings_html(); ?>
		</table> <!--/.form-table-->

		<p><strong>Need some help setting up this plugin?</strong> <a href="<?php echo admin_url(); ?>admin.php?page=admin.php?page=AGWooCommerceBarclayePDQPaymentGateway">Click here</a></p>

	<?php

    }

		public function init_form_fields() {
			$this->epdqcardtypes = apply_filters( 'wc_epdq_cardtypes', array(
				'mastercard'		=> __( 'MasterCard', 'ag_epdq_checkout' ),
				'amex' 	=> __( 'American Express', 'ag_epdq_checkout' ),
				'maestro'			=> __( 'Maestro', 'ag_epdq_checkout' ),
				'visa'				=> __( 'Visa', 'ag_epdq_checkout' ),
				//'​MCDEBIT'	=> __( 'MasterCard Debit', 'ag_epdq_checkout' ),
				//'DELTA'		=> __( 'Visa Debit', 'ag_epdq_checkout' ),
				//'Discover'			=> __( 'Discover', 'ag_epdq_checkout' ),
				'jcb'				=> __( 'JCB', 'ag_epdq_checkout' ),
				//'Laser'				=> __( 'Laser', 'ag_epdq_checkout' ),
			) );

			$this->form_fields = array(
					'enabled' => array(
							'title' => __( 'Enable/Disable', 'ag_epdq_checkout' ),
							'type' => 'checkbox',
							'label' => __( 'Enable EPDQ Checkout', 'ag_epdq_checkout' ),
							'default' => 'no'
					),
					'logo' => array(
						'title' => __( 'Logo for payment page', 'ag_epdq_checkout' ),
						'type' => 'text',
						'label' => __( 'Upload image for payment page', 'ag_epdq_checkout' ),
						'default' => '',
						'description'=>'Add an image to display on the payment page - paste in the full image url <strong>You must have an SSL for this to work correctly.</strong>',
						'desc_tip'=>false
					),
					'title' => array(
							'title' => __( 'Title', 'ag_epdq_checkout' ),
							'type' => 'text',
							'description' => __( 'Title of the payment process. This name will be visible throughout the site and the payment page.', 'ag_epdq_checkout' ),
							'default' => 'EPDQ Checkout',
							'desc_tip'      => true
					),
					'description' => array(
							'title' => __( 'Description', 'ag_epdq_checkout' ),
							'type' => 'textarea',
							'description' => __( 'Description of the payment process. This description will be visible throuhout the site and the payment page.', 'ag_epdq_checkout' ),
							'default' => 'Use the <strong>secure payment processor of Barclays Bank Plc</strong> and checkout with your debit/credit card.',
							'desc_tip'      => true
					),
					'access_key' => array(
							'title' => __( 'PSPID', 'ag_epdq_checkout' ),
							'type' => 'text',
							'description' => __( 'The PSPID for your barclays account. This is the id which you use to login the admin panel of the barclays bank.', 'ag_epdq_checkout' ),
							'default' => '',
							'desc_tip'      => true
					),
					'status' => array(
							'title' => __( 'Store Status', 'ag_epdq_checkout' ),
							'type' => 'select',
							'options'=> array('test'=>'Test Environment','live'=>'Live Store'),
							'description' => __( 'The status of your store tells that are you actually ready to run your shop or its still a test environment. If the test is selected then no payments will be processed. For details please refer to the user guide provided by the Barclays EPDQ servise.', 'ag_epdq_checkout' ),
							'default' => '',
							'css'  => 'height: 35px;',
							'desc_tip'      => true,
					),
					'sha_in' => array(
							'title' => __( 'SHA-IN Passphrase', 'ag_epdq_checkout' ),
							'type' => 'password',
							'description' => __( 'The SHA-IN signature will encode the parameter passed to the payment processor via the hidden fields to ensure better security.', 'ag_epdq_checkout' ),
							'default' => '',
							'desc_tip'      => true
					),
					'sha_out' => array(
							'title' => __( 'SHA-OUT Passphrase', 'ag_epdq_checkout' ),
							'type' => 'password',
							'description' => __( 'The SHA-OUT signature will encode the parameter passed to the redirection url from the payment processor to ensure better security.', 'ag_epdq_checkout' ),
							'desc_tip'      => true
					),
					'sha_method' => array(
							'title' => __( 'SHA encryption method', 'ag_epdq_checkout' ),
							'type' => 'select',
							'css'  => 'height: 35px;',
							'options'=> array(0=>'SHA-1',1=>'SHA-256',2=>'SHA-512'),
							'description' => __( 'Sha encryption method - this needs to be similar waht you have set in the epdq backoffice.', 'ag_epdq_checkout' ),
							'default' => '',
							'desc_tip'      => true,
					),
					'template' => array(
						'title' => __( 'Dynamic template URL', 'ag_epdq_checkout' ),
						'type' => 'text',
						'description' => __( 'The dynamic template page allows you to customise the design of the payment pages in a more advanced way than the static template does.

						When you use a dynamic template page, you fully design your own template page, leaving just one area in that page to be completed by our system. The URL of your template page needs to be sent to us in the hidden fields for each transaction.

						Please bear in mind that using a dynamic template page involves an additional request from our system to look up your template page. This increases the time needed for the payment process.', 'ag_epdq_checkout' ),
						'desc_tip'      => true
					),
					'cardtypes'	=> array(
						'title' 		=> __( 'Accepted Cards', 'ag_epdq_checkout' ),
						'type' 			=> 'multiselect',
						'class'			=> 'chosen_select',
						'css'         	=> 'width: 350px;',
						'description' 	=> __( 'Select which card types to accept. This is to show card icons on the checkout only.', 'ag_epdq_checkout' ),
						'default' 		=> '',
						'options' 		=> $this->epdqcardtypes,
					),
					'refund' => array(
						'title' => __( 'Refunds', 'ag_epdq_checkout' ),
						'type' => 'checkbox',
						'label' => __( 'Process refunds in order screen', 'ag_epdq_checkout' ),
						'default' => '',
						'description'=>'Once the save changes button has been clicked two new fields will show which will need to be set to process refunds on the website.',
						'desc_tip'=>true
					),

			);

		}

	public function init_form_fields_refund() {
		$this->epdqcardtypes = apply_filters( 'wc_epdq_cardtypes', array(
			'mastercard'		=> __( 'MasterCard', 'ag_epdq_checkout' ),
			'amex' 	=> __( 'American Express', 'ag_epdq_checkout' ),
			'maestro'			=> __( 'Maestro', 'ag_epdq_checkout' ),
			'visa'				=> __( 'Visa', 'ag_epdq_checkout' ),
			//'​MCDEBIT'	=> __( 'MasterCard Debit', 'ag_epdq_checkout' ),
			//'DELTA'		=> __( 'Visa Debit', 'ag_epdq_checkout' ),
			//'Discover'			=> __( 'Discover', 'ag_epdq_checkout' ),
			'jcb'				=> __( 'JCB', 'ag_epdq_checkout' ),
			//'Laser'				=> __( 'Laser', 'ag_epdq_checkout' ),
		) );

		$this->form_fields = array(
				'enabled' => array(
						'title' => __( 'Enable/Disable', 'ag_epdq_checkout' ),
						'type' => 'checkbox',
						'label' => __( 'Enable EPDQ Checkout', 'ag_epdq_checkout' ),
						'default' => 'no'
				),
				'logo' => array(
					'title' => __( 'Logo for payment page', 'ag_epdq_checkout' ),
					'type' => 'text',
					'label' => __( 'Upload image for payment page', 'ag_epdq_checkout' ),
					'default' => '',
					'description'=>'Add an image to display on the payment page - paste in the full image url <strong>You must have an SSL for this to work correctly.</strong>',
					'desc_tip'=>false
				),
				'title' => array(
						'title' => __( 'Title', 'ag_epdq_checkout' ),
						'type' => 'text',
						'description' => __( 'Title of the payment process. This name will be visible throughout the site and the payment page.', 'ag_epdq_checkout' ),
						'default' => 'EPDQ Checkout',
						'desc_tip'      => true
				),
				'description' => array(
						'title' => __( 'Description', 'ag_epdq_checkout' ),
						'type' => 'textarea',
						'description' => __( 'Description of the payment process. This description will be visible throuhout the site and the payment page.', 'ag_epdq_checkout' ),
						'default' => 'Use the <strong>secure payment processor of Barclays Bank Plc</strong> and checkout with your debit/credit card.',
						'desc_tip'      => true
				),
				'access_key' => array(
						'title' => __( 'PSPID', 'ag_epdq_checkout' ),
						'type' => 'text',
						'description' => __( 'The PSPID for your barclays account. This is the id which you use to login the admin panel of the barclays bank.', 'ag_epdq_checkout' ),
						'default' => '',
						'desc_tip'      => true
				),
				'status' => array(
						'title' => __( 'Store Status', 'ag_epdq_checkout' ),
						'type' => 'select',
						'css'  => 'height: 35px;',
						'options'=> array('test'=>'Test Environment','live'=>'Live Store'),
						'description' => __( 'The status of your store tells that are you actually ready to run your shop or its still a test environment. If the test is selected then no payments will be processed. For details please refer to the user guide provided by the Barclays EPDQ servise.', 'ag_epdq_checkout' ),
						'default' => '',
						'desc_tip'      => true,
				),
				'sha_in' => array(
						'title' => __( 'SHA-IN Passphrase', 'ag_epdq_checkout' ),
						'type' => 'password',
						'description' => __( 'The SHA-IN signature will encode the parameter passed to the payment processor via the hidden fields to ensure better security.', 'ag_epdq_checkout' ),
						'default' => '',
						'desc_tip'      => true
				),
				'sha_out' => array(
						'title' => __( 'SHA-OUT Passphrase', 'ag_epdq_checkout' ),
						'type' => 'password',
						'description' => __( 'The SHA-OUT signature will encode the parameter passed to the redirection url from the payment processor to ensure better security.', 'ag_epdq_checkout' ),
						'default' => 0,
						'desc_tip'      => true
				),
				'sha_method' => array(
						'title' => __( 'SHA encryption method', 'ag_epdq_checkout' ),
						'type' => 'select',
						'css'  => 'height: 35px;',
						'options'=> array(0=>'SHA-1',1=>'SHA-256',2=>'SHA-512'),
						'description' => __( 'Sha encryption method - this needs to be similar waht you have set in the epdq backoffice.', 'ag_epdq_checkout' ),
						'default' => '',
						'desc_tip'      => true,
				),
				'template' => array(
					'title' => __( 'Dynamic template URL', 'ag_epdq_checkout' ),
					'type' => 'text',
					'description' => __( 'The dynamic template page allows you to customise the design of the payment pages in a more advanced way than the static template does.

					When you use a dynamic template page, you fully design your own template page, leaving just one area in that page to be completed by our system. The URL of your template page needs to be sent to us in the hidden fields for each transaction.

					Please bear in mind that using a dynamic template page involves an additional request from our system to look up your template page. This increases the time needed for the payment process.', 'ag_epdq_checkout' ),
					'desc_tip'      => true
				),
				'cardtypes'	=> array(
					'title' 		=> __( 'Accepted Cards', 'ag_epdq_checkout' ),
					'type' 			=> 'multiselect',
					'class'			=> 'chosen_select',
					'css'         	=> 'width: 350px;',
					'description' 	=> __( 'Select which card types to accept. This is to show card icons on the checkout only.', 'ag_epdq_checkout' ),
					'default' 		=> '',
					'options' 		=> $this->epdqcardtypes,
				),
				'refund' => array(
					'title' => __( 'Refunds', 'ag_epdq_checkout' ),
					'type' => 'checkbox',
					'label' => __( 'Process refunds in order screen', 'ag_epdq_checkout' ),
					'default' => '',
					'description'=>'Once the save changes button has been clicked two new fields will show which will need to be set to process refunds on the website.',
					'desc_tip'=>true
				),
				'api_user' => array(
					'title'		=> __( 'API User ID', 'ag_epdq_checkout' ),
					'type'		=> 'text',
					'desc_tip'	=> __( 'This is to enable you to process refunds via the order screen', 'ag_epdq_checkout' ),
				),
				'api_password' => array(
					'title'		=> __( 'API User Password', 'ag_epdq_checkout' ),
					'type'		=> 'password',
					'desc_tip'	=> __( 'This is to enable you to process refunds via the order screen', 'ag_epdq_checkout' ),
				),
				'api_REFID' => array(
					'title'		=> __( 'API REFID', 'ag_epdq_checkout' ),
					'type'		=> 'text',
					'desc_tip'	=> __( 'you will fine this under the user ID in edit user on the ePDQ back office.', 'ag_epdq_checkout' ),
				),

		);

	}




	/*-----------------------------------------------------------------------------------*/
	/*	Process the payment and return the result
	/*-----------------------------------------------------------------------------------*/
	public function process_payment($order_id){

		$order = new WC_Order( $order_id );

		return array(
			'result' 	=> 'success',
			'redirect'	=> $order->get_checkout_payment_url( true )
		);

	}


	/*-----------------------------------------------------------------------------------*/
	/*	receipt_page
	/*-----------------------------------------------------------------------------------*/
	public function receipt_page($order_id){
		global $woocommerce;
		$order = new WC_Order( $order_id );

		$order_received_url = WC()->api_request_url( 'epdq_checkout' );
		$cancel_order_url = $order->get_cancel_order_url_raw();

		$fields = array(
			'PSPID'=>$this->access_key,
			'ORDERID'=>$order->get_id(),
			'AMOUNT'=>$order->get_total() * 100,
			'CURRENCY'=>get_woocommerce_currency(),
			'LANGUAGE'=>get_bloginfo('language'),
			'CN'=>$order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
			'EMAIL'=>$order->get_billing_email(),
			'OWNERZIP'=>preg_replace('/[^A-Za-z0-9\. -]/', '', $order->get_billing_postcode()),
			'OWNERADDRESS'=>preg_replace('/[^A-Za-z0-9\. -]/', '', $order->get_billing_address_1()),
			'OWNERADDRESS2'=>preg_replace('/[^A-Za-z0-9\. -]/', '', $order->get_billing_address_2()),
			'OWNERCTY'=>preg_replace('/[^A-Za-z0-9\. -]/', '', $woocommerce->countries->countries[$order->get_billing_country()]),
			'OWNERTOWN'=>preg_replace('/[^A-Za-z0-9\. -]/', '', $order->get_billing_city()),
			'OWNERTELNO'=>$order->get_billing_phone(),
			'ACCEPTURL'=>$order_received_url,
			'DECLINEURL'=>$cancel_order_url,
			'TP'=>$this->template,
			'LOGO'=>$this->logo,
			'TITLE'=> '',
		);

		$shasign ='';
		$shasign_arg = array();
		ksort($fields);
		foreach($fields as $key => $value){
			if($value=='') continue;
			$shasign_arg[] =  $key .'='. $value;
		}

		if( $this->sha_method == 0 )
			$shasign = sha1(implode($this->sha_in, $shasign_arg).$this->sha_in);
		elseif ( $this->sha_method == 1 )
			$shasign = hash('sha256',implode($this->sha_in, $shasign_arg).$this->sha_in);
		elseif ( $this->sha_method == 2 )
			$shasign = hash('sha512',implode($this->sha_in, $shasign_arg).$this->sha_in);

		$epdq_args = array();
		foreach($fields as $key => $value){
			if($value=='') continue;
			$epdq_args[] = "<input type='hidden' name='$key' value='$value'/>";
		}

		if( isset($this->status) and ($this->status=='test' or $this->status =='live') ):
			if($this->status=='test')	$url = $this->test_url;
			if($this->status=='live')	$url = $this->live_url;

			echo '<form action="'.$url.'" method="post" id="epdq_payment_form">';
			echo implode('', $epdq_args);
			echo '<input type="hidden" name="SHASIGN" value="'.$shasign.'"/>';
			echo '<input type="hidden" id="register_nonce" name="register_nonce" value="'.wp_create_nonce('generate-nonce').'" />';
			wp_nonce_field('register_nonce');
			echo '<input type="submit" class="button alt" id="submit_epdq_payment_form" value="'.__('Pay securely', 'ag_epdq_checkout').'" />';
			echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
			echo '<a class="button cancel" href="'.$order->get_cancel_order_url().'">'.__('Cancel order &amp; restore cart', 'ag_epdq_checkout').'</a></form>';
		else:
			echo '<p class="error">'.$this->error_notice.'</p>';
		endif;

		wc_enqueue_js('
			jQuery("body").block({
					message: "'.__('You are now being redirected to Barclaycard to make payment securely.', 'ag-epdq').'",
					overlayCSS:
					{
						background: "#fff",
						opacity: 0.6
					},
					css: {
								padding:        20,
								textAlign:      "center",
								color:          "#555",
								border:         "3px solid #aaa",
								backgroundColor:"#fff",
								cursor:         "wait",
								lineHeight:		"32px"
						}
				});
			jQuery("#submit_epdq_payment_form").click();
		');
	}


	function successful_request() {
	}

	function check_response(){


		$check_data = array(
			'ORDERID'	=>	isset($_REQUEST['orderID']) ? $_REQUEST['orderID'] : '',
			'CURRENCY'	=>	isset($_REQUEST['currency']) ? $_REQUEST['currency'] : '',
			'AMOUNT'	=>	isset($_REQUEST['amount']) ? $_REQUEST['amount'] : '',
			'PM'		=>	isset($_REQUEST['PM']) ? $_REQUEST['PM'] : '',
			'STATUS'	=>	isset($_REQUEST['STATUS']) ? $_REQUEST['STATUS'] : '',
			'CARDNO'	=>	isset($_REQUEST['CARDNO']) ? $_REQUEST['CARDNO'] : '',
			'ED'		=>	isset($_REQUEST['ED']) ? $_REQUEST['ED'] : '',
			'CN'		=>	isset($_REQUEST['CN']) ? $_REQUEST['CN'] : '',
			'TRXDATE'	=>	isset($_REQUEST['TRXDATE']) ? $_REQUEST['TRXDATE'] : '',
			'PAYID'		=>	isset($_REQUEST['PAYID']) ? $_REQUEST['PAYID'] : '',
			'NCERROR'	=>	isset($_REQUEST['NCERROR']) ? $_REQUEST['NCERROR'] : '',
			'BRAND'		=>	isset($_REQUEST['BRAND']) ? $_REQUEST['BRAND'] : '',
			'IP'		=>	isset($_REQUEST['IP']) ? $_REQUEST['IP'] : '',
			'AAVADDRESS'    =>	isset($_REQUEST['AAVADDRESS']) ? $_REQUEST['AAVADDRESS'] : '',
			'AAVCHECK'	=>	isset($_REQUEST['AAVCheck']) ? $_REQUEST['AAVCheck'] : ($this->aavscheck == 'yes')? 'NO' : '',
			'AAVZIP'	=>	isset($_REQUEST['AAVZIP']) ? $_REQUEST['AAVZIP'] : '',
			'AAVMAIL'	=>	isset($_REQUEST['AAVMAIL']) ? $_REQUEST['AAVMAIL'] : '',
			'AAVNAME'	=>	isset($_REQUEST['AAVNAME']) ? $_REQUEST['AAVNAME'] : '',
			'AAVPHONE'	=>	isset($_REQUEST['AAVPHONE']) ? $_REQUEST['AAVPHONE'] : '',
			'ACCEPTANCE'    =>	isset($_REQUEST['ACCEPTANCE']) ? $_REQUEST['ACCEPTANCE'] : '',
			'BIN'		=>	isset($_REQUEST['BIN']) ? $_REQUEST['BIN'] : '',
			'CCCTY'		=>	isset($_REQUEST['CCCTY']) ? $_REQUEST['CCCTY'] : '',
			'COMPLUS'	=>	isset($_REQUEST['COMPLUS']) ? $_REQUEST['COMPLUS'] : '',
			'CVCCHECK'	=>	isset($_REQUEST['CVCCheck']) ? $_REQUEST['CVCCheck'] : ($this->cvccheck == 'yes')? 'NO' : '',
			'ECI'		=>	isset($_REQUEST['ECI']) ? $_REQUEST['ECI'] : '',
			'FXAMOUNT'	=>	isset($_REQUEST['FXAMOUNT']) ? $_REQUEST['FXAMOUNT'] : '',
			'FXCURRENCY'    =>	isset($_REQUEST['FXCURRENCY']) ? $_REQUEST['FXCURRENCY'] : '',
			'IPCTY'		=>	isset($_REQUEST['IPCTY']) ? $_REQUEST['IPCTY'] : '',
			'SUBBRAND'	=>	isset($_REQUEST['SUBBRAND']) ? $_REQUEST['SUBBRAND'] : '',
			'VC'		=>	isset($_REQUEST['VC']) ? $_REQUEST['VC'] : '',
		);

		$SHASIGN = isset($_REQUEST['SHASIGN']) ? $_REQUEST['SHASIGN'] : '';
		ksort($check_data);
		$xy = '';

		foreach($check_data as $k=>$v){
			if( $v=='' )	continue;
			$xy.=strtoupper($k).'='.$v.$this->sha_out;
		}

		if( $this->sha_method == 0 )
			$shasignxy = sha1($xy);
		elseif ( $this->sha_method == 1 )
			$shasignxy = hash('sha256',$xy);
		elseif ( $this->sha_method == 2 )
			$shasignxy = hash('sha512',$xy);


		if( strtolower($shasignxy) == strtolower($SHASIGN) ){
			$this->successfull_transaction( $check_data );
		} else {
			$this->successfull_transaction( $check_data );
			//wp_die('Transaction verification error!');
			// TODO: Some kind of notice to check SHA details.
		}

}

function successfull_transaction($args){


    	global $woocommerce;


    	extract($args);

			$order = new WC_Order( $args['ORDERID'] );


				$accepted = array(4, 5, 9, 41, 51, 91);
				$STATUS = $args['STATUS'];
				$ncerror = $args['NCERROR'];

				$dienote = '<p>Transection result is uncertain.<p>';
				$dienote .= '<p>Status Code: ' . $STATUS . ' - ' . AG_errors::get_epdq_status_code($STATUS) . '';
				$dienote .= '<br>Error Code: ' . $ncerror . ' - ' . AG_errors::get_epdq_ncerror($ncerror) . '</p>';
				$died = '';
				$died .= $dienote;
				$died .= '<p>Your order is cancelled and your cart is emptied.';
				$died .= '<br>Go to your <a href="' . get_permalink(get_option('woocommerce_myaccount_page_id')) . '">account</a> to process your order again or ';
				$died .= 'go to <a href="' . home_url() . '">homepage</a></p>';

				if (in_array($STATUS, $accepted)) {

						if (!empty($args['ORDERID']))
								$note = 'Order ID: ' . $args['ORDERID'] . '.<br><br>'; //	order id
						if (!empty($args['AMOUNT']))
								$note .= 'Amount: ' . $args['AMOUNT'] . '.<br><br>'; //	amount
						if (!empty($args['CURRENCY']))
								$note .= 'Order currency: ' . $args['CURRENCY'] . '.<br><br>'; //	order currency
						if (!empty($args['PM']))
								$note .= 'Payment Method: ' . $args['PM'] . '.<br><br>'; //	payment method
						if (!empty($args['ACCEPTANCE']))
								$note .= 'Acceptance code returned by acquirer: ' . $args['ACCEPTANCE'] . '.<br><br>';    //	acceptance
						if (!empty($args['STATUS']))
								$note .= 'Transaction status : ' . $args['STATUS'] . '.<br><br>'; //	status code
						if (!empty($args['PAYID']))
								$note .= 'Payment reference in EPDQ system: ' . $args['PAYID'] . '.<br><br>'; //	pay id
						if (!empty($args['NCERROR']))
								$note .= 'Error Code: ' . $args['NCERROR'] . '.<br><br>'; //	ncerror
						if (!empty($args['BRAND']))
								$note .= 'Card brand (EPDQ system derives this from the card number) : ' . $args['BRAND'] . '.<br><br>'; //	brand

						if (!empty($args['TRXDATE']))
								$note .= 'Transaction Date: ' . $args['TRXDATE'] . '.<br><br>'; //	date
						if (!empty($args['CN']))
								$note .= 'Cardholder/customer name: ' . $args['CN'] . '.<br><br>'; //	payer's name
						if (!empty($args['IP']))
								$note .= 'Customer\'s IP: ' . $args['IP'] . '.<br><br>'; //	payer's ip

						if (!empty($args['AAVADDRESS']))
								$note .= 'AAV result for the address: ' . $args['AAVADDRESS'] . ' . <br><br>'; //	aav address
						if (!empty($args['AAVCHECK']))
								$note .= 'Result of the automatic address verification: ' . $args['AAVCHECK'] . ' . <br><br>'; //	aav check
						if (!empty($args['AAVZIP']))
								$note .= 'AAV result for the zip code: ' . $args['AAVZIP'] . ' . <br><br>'; // aav zip

						if (!empty($args['CCCTY']))
								$note .= 'Country where the card was issued: ' . $args['CCCTY'] . ' . <br><br>';
						if (!empty($args['COMPLUS']))
								$note .= 'Custom value passed: ' . $args['COMPLUS'] . ' . <br><br>';

						if (!empty($args['CVCCHECK']))
								$note .= 'Result of the card verification code check: ' . $args['CVCCHECK'] . ' . <br><br>';
						if (!empty($args['ECI']))
								$note .= 'Electronic Commerce Indicator: ' . $args['ECI'] . ' . <br><br>';
						if (!empty($args['FXAMOUNT']))
								$note .= 'FXAMOUNT: ' . $args['FXAMOUNT'] . ' . <br><br>';
						if (!empty($args['FXCURRENCY']))
								$note .= 'FXCURRENCY: ' . $args['FXCURRENCY'] . ' . <br><br>';
						if (!empty($args['IPCTY']))
								$note .= 'Originating country of the IP address: ' . $args['IPCTY'] . ' . <br><br>';
						if (!empty($args['SUBBRAND']))
								$note .= 'SUBBRAND: ' . $args['SUBBRAND'] . ' . <br><br>';
						if (!empty($args['VC']))
								$note .= 'Virtual Card type: ' . $args['VC'] . ' . <br><br>';


								if (isset($args['SHASIGN'])) {
									update_post_meta($order->id, 'SHASIGN', $args['SHASIGN']);
								}
								// Store these for later
								if (isset($args['ORDERID'])) {
									update_post_meta($order->id, 'OrderID', $args['ORDERID']);
								}
								if (isset($args['AMOUNT'])) {
									update_post_meta($order->id, 'OrderAmount', $args['AMOUNT']);
								}
								if (isset($args['CURRENCY'])) {
									update_post_meta($order->id, 'OrderCurrency', $args['CURRENCY']);
								}
								if (isset($args['PM'])) {
									update_post_meta($order->id, 'PaymentMethod', $args['PM']);
								}
								if (isset($args['ACCEPTANCE'])) {
									update_post_meta($order->id, 'Acceptance', $args['ACCEPTANCE']);
								}
								if (isset($args['STATUS'])) {
									update_post_meta($order->id, 'Status', $args['STATUS']);
								}
								if (isset($args['PAYID'])) {
									update_post_meta($order->id, 'PAYID', $args['PAYID']);
								}
								if (isset($args['NCERROR'])) {
									update_post_meta($order->id, 'NCERROR', $args['NCERROR']);
								}
								if (isset($args['BRAND'])) {
									update_post_meta($order->id, 'BRAND', $args['BRAND']);
								}
								if (isset($args['TRXDATE'])) {
									update_post_meta($order->id, 'TRXDATE', $args['TRXDATE']);
								}
								if (isset($args['IP'])) {
									update_post_meta($order->id, 'IP', $args['IP']);
								}
								update_post_meta( $order->id, '_transaction_id', $args['PAYID'] );


						$woocommerce->cart->empty_cart();

						if (in_array($STATUS, array(4, 5, 9))) {
							$noteTitle = 'Barclay ePDQ transaction is confirmed.';
							$order->add_order_note($note);
							$order->add_order_note($noteTitle);
							$order->payment_complete();

						}

						if (in_array($STATUS, array(41, 51, 91))) {
								$note = 'Barclay ePDQ transaction is awaiting for confirmation.<br>';
								$note .= $note;
								$order->update_status('on-hold', $note);
						}

				} elseif ($STATUS == 2 || $STATUS == 93) {
						$dienote .= '<br>Order has failed.';
						$order->update_status('failed', $dienote);
						$woocommerce->cart->empty_cart();
				} elseif ($STATUS == 52 || $STATUS == 92) {
						$dienote .= '<br>Order has failed.';
						$order->update_status('failed', $dienote);
						$woocommerce->cart->empty_cart();
				} elseif ($STATUS == 1) {
						$dienote .= '<br>Order has been cancelled.';
						$order->update_status('cancelled', $dienote);
						$woocommerce->cart->empty_cart();
				} else {
						$dienote .= '<br>Order has failed.';
						$order->update_status('failed', $dienote);
						$woocommerce->cart->empty_cart();
				}


				wp_redirect($order->get_checkout_order_received_url());
        exit;

	}


	public function process_refund( $order_id, $amount = NULL, $reason = '' ) {

		$order 			   = new WC_Order( $order_id );
		$refund_amount  = $amount * 100;
		$transaction_id = get_post_meta( $order_id, 'PAYID', true );
		$SHASIGN = get_post_meta( $order_id, 'SHASIGN', true );


		if($this->status=='test')	$environment_url = 'https://mdepayments.epdq.co.uk/ncol/test/maintenancedirect.asp';
		if($this->status=='live')	$environment_url = 'https://payments.epdq.co.uk/ncol/prod/maintenancedirect.asp';

		if ( ! $transaction_id ) {
			return new WP_Error( 'error', __( 'Refund failed: Transaction ID not found.', 'ag_epdq_checkout' ) );
		}
		if ( ! $refund_amount ) {
			return new WP_Error( 'error', __( 'Refund failed: Amount invalid.', 'ag_epdq_checkout' ) );
		}

		$data_post;
		$data_post['AMOUNT'] = $refund_amount;
		$data_post['OPERATION'] = 'RFD';
		$data_post['ORDERID'] = $order_id;
		$data_post['PSPID'] = $this->access_key;
		$data_post['PSWD'] = $this->api_password;
		$data_post['REFID'] = $this->api_REFID;
		$data_post['USERID'] = $this->api_user;


		$post_string = '';
		foreach ($data_post as $key => $value) {
	        $post_string[] = $key. '=' .$value;
		}
		$actual_string = '';
		$actual_string = implode ('&', $post_string);
		$result = wp_remote_post($environment_url, array(
			'method' => 'POST',
			'timeout'     => 6,
			'redirection' => 5,
			'body' => $actual_string,
		));
		$lines = preg_split('/\r\n|\r|\n/', $result['body']);
		$response = array();
		foreach ($lines as $line) {
		    $key_value = preg_split('/=/', $line, 2);
		    if (count($key_value) > 1) {
		        $response[trim($key_value[0])] = trim($key_value[1]);
		    }
		}

		$accepted = array(8, 81, 85); // OK
		$status = preg_replace('/[^a-zA-Z0-9\s]/', '', $response['STATUS']);
		$error = preg_replace('/[^a-zA-Z0-9\s]/', '', $response['NCERROR']);
		$fullError = preg_replace('/[^a-zA-Z0-9\s]/', '', $response['NCERRORPLUS']);


		$string = implode('|',$response);
		if (!is_wp_error($result) && $result['response']['code'] >= 200 && $result['response']['code'] < 300) {
		    if (in_array($status, $accepted)) {
				$order->add_order_note( __('Refund successful', 'ag_epdq_checkout') . '<br />' .
				__('Refund Amount: ', 'ag_epdq_checkout') . $amount . '<br />' .
				__('Refund Reason: ', 'ag_epdq_checkout') . $reason . '<br />' .
				__('ePDQ Status: ', 'ag_epdq_checkout') . AG_errors::get_epdq_status_code($status) . ' '
				);
				return true;

      	} else {

					$order->add_order_note( __('Refund failed', 'ag_epdq_checkout') . '<br />' . __('ePDQ Status: ', 'ag_epdq_checkout') . AG_errors::get_epdq_status_code($status) . '<br />' );
					$order->add_order_note( __('Refund Note', 'ag_epdq_checkout') . '<br /><strong>' . __('Error: ', 'ag_epdq_checkout') . $fullError . '</strong><br />' );

			}

		}

	}

}
