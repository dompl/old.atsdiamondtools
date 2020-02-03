<?php
/*-----------------------------------------------------------------------------------*/
/*	AG ePDQ subscription
/*-----------------------------------------------------------------------------------*/
defined('ABSPATH') or die("No script kiddies please!");

class epdq_checkout_subscription extends epdq_checkout
{

    /**
     * Constructor
     */
    public function __construct()
    {

        parent::__construct();

        if (class_exists('WC_Subscriptions_Order')) {

            add_action('wcs_renewal_order_created', array($this, 'link_recurring_child'), 10, 2);

            add_action('woocommerce_scheduled_subscription_payment_' . $this->id, array($this, 'scheduled_subscription_payment'), 10, 2);
        
            add_action('woocommerce_receipt_epdq_checkout_subscription', array($this, 'sub_receipt_page'));

        }
    }

    /**
     * Link recurring to child
     *
     * @param $renewal_order
     * @param $subscription
     * @return void
     */
    public function link_recurring_child($renewal_order, $subscription)
    {
        update_post_meta($renewal_order->id, 'subscription_id', $subscription->id);
        return $renewal_order;
    }


    /**
     * Check if an order contains a subscription
     *
     * @param $order_id
     * @return void
     */
    public function order_contains_subscription($order_id)
    {

        return function_exists('wcs_order_contains_subscription') && (wcs_order_contains_subscription($order_id) || wcs_order_contains_renewal($order_id));
    }

    /**
     * Process trial payment
     *
     * @param $order_id
     * @return void
     */
    public function process_payment($order_id)
    {

        $order = wc_get_order($order_id);

        // Check for trial subscription order with 0 total
        if ($this->order_contains_subscription($order) && $order->get_total() == 0) {

            $order->payment_complete();

            $order->add_order_note('This subscription has a free trial, reason for the 0 amount');

            return array(
                'result'   => 'success',
                'redirect' => $this->get_return_url($order),
            );
            
        } else {

            return parent::process_payment($order_id);
            //return $this->sub_receipt_page($order_id);

            //$order = new WC_Order($order_id);

            //return array(
            //    'result' 	=> 'success',
            //    'redirect'	=> $order->get_checkout_payment_url(true)
            //);


        }
    }

    /**
     * Scheduled subscription payment
     *
     * @param $amount_to_charge
     * @param $renewal_order
     * @return void
     */
    public function scheduled_subscription_payment($amount_to_charge, $renewal_order)
    {

        $response = $this->process_subscription_payment($renewal_order, $amount_to_charge);

        if (is_wp_error($response)) {

            $renewal_order->update_status('failed', sprintf('ePDQ Transaction Failed (%s)', $response->get_error_message()));
        }
    }


    /**
    * receipt_page
    *
    * @param $order_id
    * @return void
    */
    public function sub_receipt_page($order_id)
    {

        $order = new WC_Order($order_id);
        $order_received_url = WC()->api_request_url('epdq_checkout');
        $cancel_order_url = $order->get_cancel_order_url_raw();

        $hash_fields = array($this->access_key, date('Y:m:d'), $order->get_id(), $this->sha_in);
        $encrypted_string = ePDQ_crypt::ripemd_crypt(implode($hash_fields), $this->sha_in);


        $fullName = 	remove_accents($order->get_billing_first_name() . ' ' . str_replace("'", "", $order->get_billing_last_name()));

        if (get_woocommerce_currency() != 'GBP' && defined('ePDQ_PSPID')) {
            $PSPID = ePDQ_PSPID;
        } else {
            $PSPID = $this->access_key;
        }


        $fields = array(
            'PSPID' => $PSPID,
            'ORDERID' => $order->get_id(),
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


        //$price_per_period = WC_Subscription::get_total();
        $billing_period = WC_Subscriptions_Product::get_period($order);

        switch (strtolower($billing_period)) {
            case 'day':
                $billing_period = 'd';
                $subscription_interval = WC_Subscriptions_Product::get_interval($order);
                break;
            case 'week':
                $billing_period = 'ww';
                $subscription_interval = WC_Subscriptions_Product::get_interval($order);
                break;
            case 'year':
                $billing_period = 'm';
                $subscription_interval = '12';
                break;
            case 'month':
            default:
                $billing_period = 'm';
                $subscription_interval = WC_Subscriptions_Product::get_interval($order);
                break;
        }

        // Recurring payment
        $fields['SUBSCRIPTION_ID'] = $order->get_id();
        $fields['SUB_AMOUNT'] = $order->get_total() * 100;
        $fields['SUB_COM'] = 'order description';
        $fields['SUB_COMMENT'] = 'Recurring payment';
        $fields['SUB_ORDERID'] = $order->get_id();
        $fields['SUB_PERIOD_MOMENT'] = date('d');
        $fields['SUB_PERIOD_NUMBER'] = $subscription_interval;
        $fields['SUB_PERIOD_UNIT'] = $billing_period;
        $fields['SUB_STARTDATE'] = date('Y-m-d');
        $fields['SUB_STATUS'] = '1';
        

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
            $epdq_args[] = "<input type='hidden' name='$key' value='$value'/>";
        }


        if (isset($this->status) and ($this->status == 'test' or $this->status == 'live')) :
            if ($this->status == 'test')	$url = $this->test_url;
            if ($this->status == 'live')	$url = $this->live_url;

            echo '<form action="' . $url . '" method="post" id="epdq_payment_form">';
            echo implode('', $epdq_args);
            echo '<input type="hidden" name="SHASIGN" value="' . $shasign . '"/>';
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
                //jQuery("#submit_epdq_payment_form").click();
            ');
    }

    /**
     * Process subscription payment
     *
     * @param string $order
     * @param integer $amount
     * @return void
     */
    public function process_subscription_payment($order = '', $amount = 0)
    {

        $order_id = method_exists($order, 'get_id') ? $order->get_id() : $order->id;
        $transaction_id = get_post_meta($order_id, 'PAYID', true);

        WC_Subscriptions_Manager::process_subscription_payments_on_order( $order_id );
			        $order->payment_complete();

        if ($this->status == 'test')    $environment_url = 'https://mdepayments.epdq.co.uk/ncol/test/querydirect.asp';
        if ($this->status == 'live')    $environment_url = 'https://payments.epdq.co.uk/ncol/prod/querydirect.asp';

        if ($transaction_id) {


            $data_post = array();
            //$data_post['ORDERID'] = $order->get_id();
            $data_post['PAYID'] = $transaction_id;
            $data_post['PAYIDSUB'] = '';
            $data_post['PSPID'] = $this->access_key;
            $data_post['PSWD'] = $this->api_password;
            $data_post['USERID'] = $this->api_user;


            $post_string = array();
            foreach ($data_post as $key => $value) {
                $post_string[] = $key . '=' . $value;
            }
            $actual_string = '';
            $actual_string = implode('&', $post_string);
            $result = wp_remote_post($environment_url, array(
                'method' => 'POST',
                'timeout'     => 6,
                'redirection' => 5,
                'body' => $actual_string,
            ));

            if (!is_wp_error($result) && 200 == wp_remote_retrieve_response_code($result)) {

                $lines = preg_split('/\r\n|\r|\n/', $result['body']);
                $response = array();
                foreach ($lines as $line) {
                    $key_value = preg_split('/=/', $line, 2);
                    if (count($key_value) > 1) {
                        $response[trim($key_value[0])] = trim($key_value[1]);
                    }
                }

                $accepted = array(4, 5, 9); // OK
                $status = preg_replace('/[^a-zA-Z0-9\s]/', '', $response['STATUS']);

                if (in_array($status, $accepted)) {


                    update_post_meta($order_id, '_epdq_status', $response['STATUS']);
                    update_post_meta($order_id, '_epdq_acceptance', $response['ACCEPTANCE']);
                    update_post_meta($order_id, '_epdq_amount', $response['amount']);
                    update_post_meta($order_id, '_epdq_PAYID', $response['PAYID']);
                    update_post_meta($order_id, '_epdq_PAYIDSUB', $response['PAYIDSUB']);
                    update_post_meta($order_id, '_epdq_NCERROR', $response['NCERROR']);


                    $order->payment_complete($response['PAYID']);
                    $message = sprintf('Subscription payment via ePDQ successful (<strong>Transaction Reference:</strong> %s)', $response['PAYID']);
                    $order->add_order_note($message);

                    return true;


                } else {
                    $order_note = 'Subscription payment via ePDQ fail.';
                    $order->add_order_note($order_note);
                    $note = 'ePDQ Status Code: ' . $status . ' - ' . AG_errors::get_epdq_status_code($status) . '</p>';
                    $order->update_status('on-hold', $note);

                    return new WP_Error( 'epdq_error', 'ePDQ payment failed. ' . AG_errors::get_epdq_status_code($status) );

                }
            }
        }
        AG_ePDQ_Helpers::ag_log('This subscription can\'t be renewed automatically.', 'warning', 'yes');
        return new WP_Error('epdq_error', 'This subscription can\'t be renewed automatically.');
    }
}
