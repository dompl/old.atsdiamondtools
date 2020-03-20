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
 * WC tested up to: 3.7
 * License: GPL3
 */

defined('ABSPATH') or die("No script kiddies please!");

class epdq_checkout extends WC_Payment_Gateway
{


	/**
	 * Plugin Doc link
	 *
	 * @var string
	 */
	public static $AG_ePDQ_doc = "https://we-are-ag.helpscoutdocs.com/";


	
	public function __construct()
	{

		$this->id 			= 'epdq_checkout';
		$this->method_title = 'AG ePDQ Checkout';
		$this->icon			= apply_filters('woocommerce_epdq_checkout_icon', '');
		$this->has_fields 	= false;
		if (!AG_licence::valid_licence()) {
			return;
		}

		if ($this->get_option('refund') == 'yes') {
			$this->init_form_fields_refund();
		} else {
			$this->init_form_fields();
		}
		$this->init_settings();

		$this->test_url 	= 'https://mdepayments.epdq.co.uk/ncol/test/orderstandard.asp';
		$this->live_url 	= 'https://payments.epdq.co.uk/ncol/prod/orderstandard.asp';

		// Turn settings into variables we can use
		foreach ($this->settings as $setting_key => $value) {
			$this->$setting_key = $value;
		}

		$this->sha_method 	= ($this->sha_method != '') ? $this->sha_method : 2;
		$this->notice 	= ($this->notice != '') ? $this->notice : 'no';
		$this->threeds = $this->get_option( 'threeds' ) ?: 'no';
		update_option( 'threeds', $this->threeds );
		$this->description = $this->display_checkout_description();

		$this->supports = array(
			'products',
			'refunds',
			'subscriptions',
			'gateway_scheduled_payments'
		);

		// Save options
		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

		// Payment listener/API hook
		add_action('woocommerce_receipt_epdq_checkout', array($this, 'receipt_page'));
		add_action('woocommerce_api_epdq_checkout', array($this, 'check_response'));

		add_action('admin_head', array('AG_ePDQ_Helpers', 'add_disable_to_input'));
	}

	/**
	 * Plugin settings
	 *
	 * @return void
	 */
	public function init_form_fields()
	{
		$this->form_fields = AG_ePDQ_Settings::form_fields();
	}

	/**
	 * Plugin settings with refund option
	 *
	 * @return void
	 */
	public function init_form_fields_refund()
	{
		$this->form_fields = AG_ePDQ_Settings::form_fields_refund();
	}

	/**
	 * Test mode message added to checkout when test mode enabled
	 *
	 * @return $input
	 */
	public function display_test_message() {

		$input = sprintf(__('<br /><strong>TEST MODE ACTIVE.</strong><br />In test mode, you can use Visa card number 4444 3333 2222 1111 with any CVC and a valid expiration date or check the documentation <a target="_blank" href="%s">here</a> for more card numbers, steps on setting up and troubleshooting.'), 'https://www.weareag.co.uk/product/ag-barclays-epdq-payment-gateway-woocommerce/');
		return $input;

	}

	/**
	 * Display notice to customer of redirect to ePDQ server
	 *
	 * @return $input
	 */
	public function display_redirect_message() {
		
		$input = sprintf(__('<span class="AG-redirect-icon"><img src="%s" /></span>' ), AG_ePDQ_server_path . 'img/AG-ePDQ-redirect.png' );
		$input .= '<p class="AG-redirect-notice">After clicking "Place order", you will be redirected to Barclays to complete your purchase securely.</p>';
		return $input;

	}

	/**
	 * Logic for displaying notices
	 *
	 * @return $description
	 */
	public function display_checkout_description() {

		$description = '';

		if($this->notice == 'yes') {
			$description	.= $this->display_redirect_message();
		} else {
			$description	.= $this->get_option('description');
		}

		if ($this->status == 'test') {
			$description	.= $this->display_test_message();
		} 

		return $description;

	}


	/**
	 * Display card icons
	 *
	 * @return void
	 */
	function get_icon()
	{

		$icon = '';
		if (!$this->cardtypes) {
			// default behavior
			$icon = '<img src="' . AG_ePDQ_server_path . '/img/cards.gif" alt="' . $this->title . '" />';
		} elseif ($this->cardtypes) {
			// display icons for the selected card types
			$icon = '';
			foreach ($this->cardtypes as $cardtype) {
				$icon .= '<img class="ePDQ-card-icons" src="' . AG_ePDQ_server_path . 'img/new-card/' . strtolower($cardtype) . '.png" alt="' . strtolower($cardtype) . '" />';
			}
		}

		return apply_filters('woocommerce_gateway_icon', $icon, $this->id);
	}

	/**
	 * Display settings
	 *
	 * @return void
	 */
	public function admin_options()
	{
		?>

	<h3><?php _e('AG ePDQ Checkout Settings', 'ag_epdq_checkout'); ?></h3>
	<p><?php _e('This gateway will redirect the customers to the secured Barclays payment server and process the order there, Once payment is made Barclays will send them back to the provided links based on the status of their transaction.', 'ag_epdq_checkout') ?></p>

	<table class="form-table">
		<?php $this->generate_settings_html(); ?>
	</table>
	<!--/.form-table-->

	<p><strong>Need some help setting up this plugin?</strong> <a href="<?php echo admin_url(); ?>admin.php?page=AGWooCommerceBarclayePDQPaymentGateway">Click here</a></p>

<?php

}

/**
 * Process the payment and return the result
 *
 * @param $order_id
 * @return void
 */
public function process_payment($order_id)
{

	$order = new WC_Order($order_id);

	return array(
		'result' 	=> 'success',
		'redirect'	=> $order->get_checkout_payment_url(true)
	);
}


/**
 * receipt_page
 *
 * @param $order_id
 * @return void
 */
public function receipt_page($order_id)
{

	$order = new WC_Order($order_id);
	$order_received_url = WC()->api_request_url('epdq_checkout'). '?idOrder='. $order->get_id();
	$cancel_order_url = $order->get_cancel_order_url_raw();

	$hash_fields = array($this->access_key, date('Y:m:d'), $order->get_order_number(), $this->sha_in);
	$encrypted_string = ePDQ_crypt::ripemd_crypt(implode($hash_fields), $this->sha_in);

	$fullName = remove_accents($order->get_billing_first_name() . ' ' . str_replace("'", "", $order->get_billing_last_name()));

	if (get_woocommerce_currency() != 'GBP' && defined('ePDQ_PSPID')) {
		$PSPID = ePDQ_PSPID;
	} else {
		$PSPID = $this->access_key;
	}

	$fields = array(
		'PSPID' => $PSPID,
		'ORDERID' => $order->get_order_number(),
		'AMOUNT' => $order->get_total() * 100,
		'COMPLUS' => $encrypted_string,
		'CURRENCY' => get_woocommerce_currency(),
		'LANGUAGE' => get_bloginfo('language'),
		'CN' => $fullName,
		'EMAIL' => $order->get_billing_email(),
		'OWNERZIP' => preg_replace('/[^A-Za-z0-9\. -]/', '', $order->get_billing_postcode()),
		'OWNERADDRESS' => substr(preg_replace('/[^A-Za-z0-9\. -]/', '', $order->get_billing_address_1()), 0, 34),
		'OWNERADDRESS2' => substr(preg_replace('/[^A-Za-z0-9\. -]/', '', $order->get_billing_address_2()), 0, 34),
		'OWNERCTY' => substr(preg_replace('/[^A-Za-z0-9\. -]/', '', $order->get_billing_country()), 0, 34),
		'OWNERTOWN' => substr(preg_replace('/[^A-Za-z0-9\. -]/', '', $order->get_billing_city()), 0, 34),
		'OWNERTELNO' => $order->get_billing_phone(),
		'ACCEPTURL' => $order_received_url,
		'DECLINEURL' => $cancel_order_url,
		'TP' => $this->template,
		'LOGO' => $this->logo,
		'TITLE' => '',
	);

	if (class_exists('WC_Subscriptions_Order') && epdq_checkout_subscription::order_contains_subscription($order)) {
		//$price_per_period = WC_Subscription::get_total();
		$billing_period = WC_Subscriptions_Order::get_subscription_period($order);
		switch (strtolower($billing_period)) {
			case 'day':
				$billing_period = 'd';
				$subscription_interval = WC_Subscriptions_Order::get_subscription_interval($order);
				break;
			case 'week':
				$billing_period = 'ww';
				$subscription_interval = WC_Subscriptions_Order::get_subscription_interval($order);
				break;
			case 'year':
				$billing_period = 'm';
				$subscription_interval = '12';
				break;
			case 'month':
			default:
				$billing_period = 'm';
				$subscription_interval = WC_Subscriptions_Order::get_subscription_interval($order);
				break;
		}
		// Recurring payment
		$fields['SUBSCRIPTION_ID'] = $order->get_order_number();
		$fields['SUB_AMOUNT'] = $order->get_total() * 100;
		$fields['SUB_COM'] = 'order description';
		$fields['SUB_COMMENT'] = 'Recurring payment';
		$fields['SUB_ORDERID'] = $order->get_order_number();
		$fields['SUB_PERIOD_MOMENT'] = date('d');
		$fields['SUB_PERIOD_NUMBER'] = $subscription_interval;
		$fields['SUB_PERIOD_UNIT'] = $billing_period;
		$fields['SUB_STARTDATE'] = date('Y-m-d');
		$fields['SUB_STATUS'] = '1';
	}

	//Server-to-server parameter
	$fields['PARAMVAR']	 =	$order->get_id();

	// Order card icons on ePDQ side
	$fields['PMLISTTYPE'] = $this->pmlisttype;

	// Hook to add extra para
	do_action( 'AG_ePDQ_extra_parameters' );

	$shasign = '';
	$shasign_arg = array();
	ksort($fields);
	foreach ($fields as $key => $value) {
		if ($value == '') continue;
		$shasign_arg[] =  $key . '=' . utf8_encode($value);
	}

	if ($this->sha_method == 0)
		$shasign = sha1(implode($this->sha_in, $shasign_arg) . $this->sha_in);
	elseif ($this->sha_method == 1)
		$shasign = hash('sha256', implode($this->sha_in, $shasign_arg) . $this->sha_in);
	elseif ($this->sha_method == 2)
		$shasign = hash('sha512', implode($this->sha_in, $shasign_arg) . $this->sha_in);

	$epdq_args = array();
	foreach ($fields as $key => $value) {
		if ($value == '') continue;
		$epdq_args[] = '<input type="hidden" name="' . sanitize_text_field( $key ) .'" value="' . sanitize_text_field( $value ) . '"/>';
	}


	if (isset($this->status) and ($this->status == 'test' or $this->status == 'live')) :
		if ($this->status == 'test')	$url = $this->test_url;
		if ($this->status == 'live')	$url = $this->live_url;

		echo '<form action="' . esc_url_raw( $url ) . '" method="post" id="epdq_payment_form">';
		echo implode('', $epdq_args);
		echo '<input type="hidden" name="SHASIGN" value="' . sanitize_text_field( $shasign ) . '"/>';
		echo '<input type="hidden" id="register_nonce" name="register_nonce" value="' . wp_create_nonce('generate-nonce') . '" />';
		echo '<input type="submit" class="button alt" id="submit_epdq_payment_form" value="' . __('Pay securely', 'ag_epdq_checkout') . '" />';
		echo '<a class="button cancel" href="' . $order->get_cancel_order_url() . '">' . __('Cancel order &amp; restore cart', 'ag_epdq_checkout') . '</a></form>';

	endif;

	wc_enqueue_js('
			jQuery("body").block({
					message: "' . __('You are now being redirected to Barclaycard to make payment securely.', 'ag-epdq') . '",
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

/**
 * Check payment response
 *
 * @return void
 */
 function check_response()
 {
 
	 $nonce = sanitize_text_field( $_REQUEST['COMPLUS'] );

	 // Store 3D secure data
	 //ePDQ_Display_Score::AG_sore_PSP_returned_data($_REQUEST);
 
	 $check_data = array();
	 $data = $_REQUEST;
	 foreach ($data as $key => $value) {
		 if ($value == "") {
			 continue;
		 }
		 $check_data[$key] = sanitize_text_field($value);
		 $datacheck[strtoupper($key)] = sanitize_text_field(strtoupper($value));
	 }
 
	 // Server-to-server API callback
	 if( isset( $datacheck['callback'] ) ) {
		 AG_ePDQ_Helpers::ag_log('API call back happened', 'warning', $this->debug);
	  
		 // Passing id 
		 if( ! isset( $datacheck['PARAMVAR'] ) ) {
			 AG_ePDQ_Helpers::ag_log('PARAMVAR parameter is missing, please read the docs ' . self::$AG_ePDQ_doc . 'article/106-pending-failed-transactions', 'warning', $this->debug);
		 } else {
			 $check_data['idOrder'] = $datacheck['PARAMVAR'];
		 }
	  
	 }
 
 
	 if (class_exists('WC_Subscriptions_Order') && epdq_checkout_subscription::order_contains_subscription( $check_data['orderID'] ) ) {
		 //$check_data['SUBSCRIPTION_ID'] = isset($check_data['subscription_id']) ? $check_data['subscription_id'] : '';
		 //$check_data['CREATION_STATUS'] = isset($check_data['creation_status']) ? $check_data['creation_status'] : '';
	 }
	  
 
	 // Hash
	 $hash_fields = array($this->access_key, date('Y:m:d'), $check_data['orderID'], $this->sha_in);
	 $encrypted_string = ePDQ_crypt::ripemd_crypt(implode($hash_fields), $this->sha_in);

 
	 if (isset($check_data['STATUS'])) {
 
		 if (hash_equals($encrypted_string, $nonce)) {
			 if (!empty($this->sha_out)) {
				 $SHA_check = $this->SHA_check($check_data);
				 if ($SHA_check) {
					 $this->successful_transaction($check_data);
				 } else {
					if($this->threeds == 'yes'){
						AG_ePDQ_Helpers::ag_log('Extra parameters are required to be sent back when using the AG 3D secure score system, please check through the trouble shooting in the plugin docs.', 'warning', $this->debug);
					} else {
						AG_ePDQ_Helpers::ag_log('Transaction is unsuccessful due to a SHA-Out issue, please check the docs ' . self::$AG_ePDQ_doc . 'article/88-transaction-is-unsuccessful-due-to-a-sha-out-issue', 'warning', $this->debug);
					}
					// SHA-Out check fail
					wp_die('Transaction is unsuccessful due to a SHA-Out issue');
				 }
			 } else {
				 // SHA-Out not set
				 AG_ePDQ_Helpers::ag_log('You dont have SHA-out set, for improved security we recommend you set this. Please check the docs ' . self::$AG_ePDQ_doc . 'article/88-transaction-is-unsuccessful-due-to-a-sha-out-issue', 'warning', $this->debug);
				 $this->transaction_successfull($check_data);
			 }
		 } else {
			 // Nonce check fail
			 AG_ePDQ_Helpers::ag_log('Security check fail, please check the docs ' . self::$AG_ePDQ_doc . 'article/86-security-check-fail', 'warning', $this->debug);
			 wp_die('Security check fail.');
		 }
	 } else {
 
		 AG_ePDQ_Helpers::ag_log('The transaction failed, ePDQ didn\'t send any data back. Please check you have setup the plugin correctly.', 'warning', $this->debug);
		 wp_die('Transaction fail.');
	 }
 }
 
 
 /**
  * Check SHA data
  *
  * @param $data
  * @return void
  */
 protected function SHA_check($datatocheck)
 {
	 $SHA_out = $this->sha_out;
	 $origsig = $datatocheck['SHASIGN'];
	 unset($datatocheck['SHASIGN']);
	 unset($datatocheck['wc-api']);
	 unset($datatocheck['idOrder']);
	 unset($datatocheck['PARAMVAR']);
	 unset($datatocheck['callback']);
	 unset($datatocheck['doing_wp_cron']);

	 // 3D score check
	if($this->threeds != 'yes' && isset($datatocheck['SCORING'])){
		unset($datatocheck['CCCTY']);
		unset($datatocheck['ECI']);
		unset($datatocheck['CVCCheck']);
		unset($datatocheck['AAVCheck']);
		unset($datatocheck['VC']);
		unset($datatocheck['AAVZIP']);
		unset($datatocheck['AAVADDRESS']);
		unset($datatocheck['AAVNAME']);
		unset($datatocheck['AAVPHONE']);
		unset($datatocheck['AAVMAIL']);
		unset($datatocheck['SCORING']);
	}
	 // END

	 uksort($datatocheck, 'strcasecmp');
   
	 $SHAsig = '';
	 foreach ($datatocheck as $key => $value) {
		 $SHAsig .= trim(strtoupper($key)) . '=' . utf8_encode(trim($value)) . $SHA_out;
	 }
  
	 if ($this->sha_method == 0) {
		 $shasign_method = 'sha1';
	 } elseif ($this->sha_method == 1) {
		 $shasign_method = 'sha256';
	 } elseif ($this->sha_method == 2) {
		 $shasign_method = 'sha512';
	 }
  
	 $SHAsig = strtoupper(hash($shasign_method, $SHAsig));

	 if (hash_equals($SHAsig, $origsig)) {
		 return true;
	 } else {
		 return false;
	 }
 }
 


/**
 * Successful transaction
 *
 * @param $args
 * @return void
 */
function successful_transaction($args)
{

	global $woocommerce;
	extract($args);

	$order = new WC_Order($args['idOrder']);
		
	$STATUS = $args['STATUS'];
	$note .= 'ePDQ Status: - ' . AG_errors::get_epdq_status_code($STATUS) . '</p>';
	$errornote = 'ePDQ NCERROR: - ' . AG_errors::get_epdq_ncerror($NCERROR) . '</p>';



	$order_notes                          = array(
		'Order ID                            : ' => $args['ORDERID']    = $args['ORDERID'] ?? '',
		'Amount                              : ' => $args['AMOUNT']     = $args['AMOUNT'] ?? '',
		'Order Currency                      : ' => $args['CURRENCY']   = $args['CURRENCY'] ?? '',
		'Payment Method                      : ' => $args['PM']         = $args['PM'] ?? '',
		'Acceptance Code Returned By Acquirer: ' => $args['ACCEPTANCE'] = $args['ACCEPTANCE'] ?? '',
		'Payment Reference In ePDQ System    : ' => $args['PAYID']      = $args['PAYID'] ?? '',
		'Error Code                          : ' => $args['NCERROR']    = $args['NCERROR'] ?? '',
		'Card Brand                          : ' => $args['BRAND']      = $args['BRAND'] ?? '',
		'Transaction Date                    : ' => $args['TRXDATE']    = $args['TRXDATE'] ?? '',
		'Cardholder/Customer Name            : ' => $args['CN']         = $args['CN'] ?? '',
		'Customer IP                         : ' => $args['IP']         = $args['IP'] ?? '',
		'AAV Result For Address              : ' => $args['AAVADDRESS'] = $args['AAVADDRESS'] ?? '',
		'Result for AAV Check                : ' => $args['AAVCHECK']   = $args['AAVCHECK'] ?? '',
		'AAV Result For Postcode             : ' => $args['AAVZIP']     = $args['AAVZIP'] ?? '',
	);

	if (class_exists('WC_Subscriptions_Order') && epdq_checkout_subscription::order_contains_subscription($order)) {
		$order_notes['Subscription ID: '] = isset($args['subscription_id']) ? $args['subscription_id'] : '';
		$order_notes['Subscription status: '] = isset($args['creation_status']) ? $args['creation_status'] : '';
	}

	AG_ePDQ_Helpers::update_order_notes($order, $order_notes);

	$order_data = array();
	unset($args['SHASIGN']);
	unset($args['COMPLUS']);
	unset($args['CARDNO']);
	foreach ($args as $key => $value) {
		if ($value == "") {
			continue;
		}
		$order_data[$key] = $value;
	}

	if (class_exists('WC_Subscriptions_Order') && epdq_checkout_subscription::order_contains_subscription($order)) {
		$order_data['SUBSCRIPTION_ID'] = isset($args['subscription_id']) ? $args['subscription_id'] : '';
		$order_data['CREATION_STATUS'] = isset($args['creation_status']) ? $args['creation_status'] : '';
	}

	AG_ePDQ_Helpers::update_order_meta_data($args['idOrder'], $order_data);

	switch ($STATUS): case '4':
		case '5':
		case '9':
			$noteTitle = 'Barclays ePDQ transaction is confirmed.';
			AG_ePDQ_Helpers::ag_log('Barclays ePDQ transaction is confirmed. No issues to report.', 'debug', $this->debug);
			$order->add_order_note($noteTitle);
			$order->add_order_note($note);
			$order->payment_complete();
			break;

		case '41':
		case '51':
		case '91':
			$noteTitle = 'Barclays ePDQ transaction is awaiting for confirmation.';
			AG_ePDQ_Helpers::ag_log('Barclays ePDQ transaction is awaiting for confirmation. No issues to report.', 'debug', $this->debug);
			$order->add_order_note($noteTitle);
			$order->update_status('on-hold', $note);
			break;

		case '2':
		case '93':
			$noteTitle = 'Barclays ePDQ transaction was refused.';
			$order->add_order_note($noteTitle);
			$order->add_order_note($errornote);
			AG_ePDQ_Helpers::ag_log('The authorisation has been refused by the financial institution. The customer can retry the authorisation process after selecting another card or another payment method.', 'notice', $this->debug);
			$order->update_status('failed', $note);
			$woocommerce->cart->empty_cart();
			break;

		case '52':
		case '92':
			$noteTitle = 'Barclays ePDQ payment uncertain.';
			$order->add_order_note($noteTitle);
			$order->add_order_note($errornote);
			AG_ePDQ_Helpers::ag_log('A technical problem arose during the authorisation/payment process, giving an unpredictable result.', 'notice', $this->debug);
			$order->update_status('failed', $note);
			$woocommerce->cart->empty_cart();
			break;

		case '1':
			$noteTitle = 'The customer has cancelled the transaction';
			$order->add_order_note($noteTitle);
			$order->add_order_note($errornote);
			$order->update_status('failed', $note);
			$woocommerce->cart->empty_cart();
			break;

		case '0':
			$noteTitle = 'Incomplete or invalid';
			$order->add_order_note($noteTitle);
			$order->add_order_note($errornote);
			$order->update_status('failed', $note);
			$woocommerce->cart->empty_cart();
			break;

	endswitch;

	wp_redirect($order->get_checkout_order_received_url());
}

/**
 * Process refund
 *
 * @param $order_id
 * @param $amount
 * @param string $reason
 * @return void
 */
public function process_refund($order_id, $amount = NULL, $reason = '')
{

	$order 			   = new WC_Order($order_id);
	$refund_amount  = $amount * 100;
	$transaction_id = get_post_meta($order_id, 'PAYID', true);

	if ($this->status == 'test')	$environment_url = 'https://mdepayments.epdq.co.uk/ncol/test/maintenancedirect.asp';
	if ($this->status == 'live')	$environment_url = 'https://payments.epdq.co.uk/ncol/prod/maintenancedirect.asp';

	if (!$transaction_id) {
		AG_ePDQ_Helpers::ag_log('Refund failed: Transaction ID not found.', 'debug', $this->debug);
		return new WP_Error('error', __('Refund failed: Transaction ID not found.', 'ag_epdq_checkout'));
	}
	if (!$refund_amount) {
		AG_ePDQ_Helpers::ag_log('Refund failed: Amount invalid.', 'debug', $this->debug);
		return new WP_Error('error', __('Refund failed: Amount invalid.', 'ag_epdq_checkout'));
	}

	$data_post = array();
	$data_post['AMOUNT'] = $refund_amount;
	$data_post['PAYID'] = $transaction_id;
	$data_post['OPERATION'] = 'RFD';
	$data_post['ORDERID'] = $order_id;
	$data_post['PSPID'] = $this->access_key;
	$data_post['PSWD'] = $this->api_password;
	$data_post['REFID'] = $this->api_REFID;
	$data_post['USERID'] = $this->api_user;

	$shasign = '';
	$shasign_arg = array();
	ksort($data_post);
	foreach ($data_post as $key => $value) {
		if ($value == '') continue;
		$shasign_arg[] =  $key . '=' . utf8_encode($value);
	}

	if ($this->sha_method == 0)
		$shasign = sha1(implode($this->sha_in, $shasign_arg) . $this->sha_in);
	elseif ($this->sha_method == 1)
		$shasign = hash('sha256', implode($this->sha_in, $shasign_arg) . $this->sha_in);
	elseif ($this->sha_method == 2)
		$shasign = hash('sha512', implode($this->sha_in, $shasign_arg) . $this->sha_in);

	$data_post['SHASIGN'] = $shasign;


	$post_string = array();
	foreach ($data_post as $key => $value) {
		$post_string[] = $key . '=' . $value;
	}
	$actual_string = '';
	$actual_string = implode('&', $post_string);
	$result = wp_safe_remote_post($environment_url, array(
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
	$fullError = preg_replace('/[^a-zA-Z0-9\s]/', '', $response['NCERRORPLUS']);


	$string = implode('|', $response);
	if (!is_wp_error($result) && $result['response']['code'] >= 200 && $result['response']['code'] < 300) {
		if (in_array($status, $accepted)) {
			$order->add_order_note(
				__('Refund successful', 'ag_epdq_checkout') . '<br />' .
					__('Refund Amount: ', 'ag_epdq_checkout') . $amount . '<br />' .
					__('Refund Reason: ', 'ag_epdq_checkout') . $reason . '<br />' .
					__('ePDQ Status: ', 'ag_epdq_checkout') . AG_errors::get_epdq_status_code($status) . ' '
			);
			return true;
		} else {

			$order->add_order_note(__('Refund failed', 'ag_epdq_checkout') . '<br />' . __('ePDQ Status: ', 'ag_epdq_checkout') . AG_errors::get_epdq_status_code($status) . '<br />');
			$order->add_order_note(__('Refund Note', 'ag_epdq_checkout') . '<br /><strong>' . __('Error: ', 'ag_epdq_checkout') . $fullError . '</strong><br />');
			// Log refund error
			AG_ePDQ_Helpers::ag_log($string, 'debug', $this->debug);
		}
	} else {
		// Log refund error
		AG_ePDQ_Helpers::ag_log($string, 'debug', $this->debug);
	}
}
}
