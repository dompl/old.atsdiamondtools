<?php
/*-----------------------------------------------------------------------------------*/
/*	AG ePDQ order status check
    Fallback for when ePDQ Direct HTTP server-to-server request fails due to website host issues.
/*-----------------------------------------------------------------------------------*/
defined('ABSPATH') || die("No script kiddies please!");


if (class_exists('AG_ePDQ_order_status_check')) {
        return;
}

class AG_ePDQ_order_status_check {


        public static $single_instance = null;
        public static $args = array();

        
        /**
         * run
         */
        public static function run_instance( $args = array() ) {
            if ( self::$single_instance === null ) {
                self::$args            = $args;
                self::$single_instance = new self();
            }

            return self::$single_instance;
        }

        public function __construct(){


            add_action('admin_enqueue_scripts', array( $this, 'status_check_js' ) );
            add_action('woocommerce_order_status_cancelled', array( $this,'check'));
            add_action('woocommerce_order_item_add_action_buttons', array( $this, 'callback_button'));
            add_action( 'wp_ajax_ag_manually_check_status_call', array( $this, 'ag_manually_check_status_call' ) );
            add_action( 'manage_shop_order_posts_custom_column', array( $this, 'show_status_check_order_screen' ) );
            add_filter( 'manage_edit-shop_order_columns', array( $this, 'ag_change_order_column' ) );
            add_filter( 'manage_edit-shop_order_sortable_columns', array( $this, 'ag_set_sortable_columns') );


        }



    	/**
		 * Check order status when order is cancelled...
		 *
		 * @param $order_id
		 */
		public static function check( $order_id ){
			
			$order 		    = new WC_Order($order_id);
            $ePDQ_settings  = new epdq_checkout();
            $settings 	    = ePDQ_crypt::refund_settings();
            $key_settings 	= ePDQ_crypt::key_settings();
            $environment_url = AG_ePDQ_Helpers::get_enviroment_url('querydirect');
            $accepted = array(4, 5, 9);

            // Is auto check enabled?
			if($ePDQ_settings->statusCheck === 'yes') {
				return;
			}

            // Check order is using our plugin
            if($order->get_payment_method() !== 'epdq_checkout') {
                return;
            }

            // If customer cancels order.
            if( get_post_meta( $order->get_id(), 'customer_canceled_order' ) ) {
                $order->add_order_note('The customer canceled the order. Status check did not run.');
                return;
            }

            // if manual change.
            if ( current_user_can('administrator') || current_user_can('shop_manager')) {
                $order->add_order_note('Order was manually set to cancelled. Status check did not run.');
                return;
            }




            if (empty($settings['USERID']) || empty($settings['PSWD'])) {
                AG_ePDQ_Helpers::ag_log('AG Status check failed: API username has not been set.', 'debug', $ePDQ_settings->debug);
                $order->add_order_note('AG Status check failed: API details not set. <br />Check the guide on hoe to set up <a href="https://weareag.co.uk/docs/barclays-epdq-payment-gateway/setup-barclays-epdq-payment-gateway/how-to-setup-epdq-status-check/" target="_blank">here</a>.');
            }

            if (empty($settings['PSWD']) || empty($settings['USERID'])) {
                return;
            }

            // Status check has run log
            $auto_status_ran = array(
                'ag_auto_check_ran'  =>	'Yes',       
            );
            AG_ePDQ_Helpers::update_order_meta_data($order->get_id(), $auto_status_ran);

            // Data to send
			$data_post = array();
			$data_post['ORDERID'] = $order->get_order_number();
			if (get_woocommerce_currency() !== 'GBP' && defined('ePDQ_PSPID')) {
				$data_post['PSPID'] = ePDQ_PSPID;
            } else {
				$data_post['PSPID'] = $key_settings['pspid'];
                $data_post['PSWD'] = $settings['PSWD'];
                $data_post['USERID'] = $settings['USERID'];

            }

            // Post
            $result = AG_ePDQ_Helpers::remote_post( $environment_url, $data_post );

            $lines = preg_split('/\r\n|\r|\n/', $result['body']);
            $response = array();
            foreach ($lines as $line) {
                $key_value = preg_split('/=/', $line, 2);
                if (count($key_value) > 1) {
                    $response[trim($key_value[0])] = trim($key_value[1]);
                }
            }


    
            //$STATUS = $response['STATUS'];
            $NCERROR = preg_replace('/[^a-zA-Z0-9\s]/', '', $response['NCERROR']);
            $NCERRORPLUS = preg_replace('/[^a-zA-Z0-9\s]/', '', $response['NCERRORPLUS']);
            $status = preg_replace('/[^a-zA-Z0-9\s]/', '', $response['STATUS']);
    
            $status_check = '<p><strong>'. __('AG ePDQ order status check has checked the status of this order. This was due to time limit reached in WooCommerce', 'ag_epdq_direct');
            $note = '<p>'. __('ePDQ Status:', 'ag_epdq_direct') .' - ' . AG_errors::get_epdq_status_code($status) . '</p>';
            $errornote = '<p>ePDQ NCERROR: - ' . AG_errors::get_epdq_ncerror($NCERROR) . '</p>';
            $error_note = '<p>ePDQ NCERROR: - ' . $NCERRORPLUS . '</p>';
            $note .= '<p>'. __('Order ID:', 'ag_epdq_direct') .' - ' . $order->get_id() . '</p>';
            $note .= '<p>'. __('Payment Reference In ePDQ System:', 'ag_epdq_direct') .' - ' . $response['PAYID'] . '</p>';   

            if (in_array($status, $accepted)) {

                // Wait one second before processing.
                flush();
                sleep(1);
			
                // 3DS v2 Frictionless flow
                $noteTitle = __(' and Barclays ePDQ has confirmed the transaction.', 'ag_epdq_direct') .'</strong></p>';
                AG_ePDQ_Helpers::ag_log('Barclays ePDQ transaction is confirmed (The transaction was a 3DS v2 Frictionless transaction)', 'debug', $ePDQ_settings->debug);
                $note .= '<p><strong>'. __('Barclays ePDQ transaction is confirmed', 'ag_epdq_direct') .'</strong></p>';
                $order->add_order_note($note);
                $order->payment_complete($response['PAYID']);
                delete_post_meta($order->get_id(), 'HTML_ANSWER');
                $order->add_order_note($status_check . $noteTitle);

    
                $orderdata = array(
                    'Status'         	=> 	AG_errors::get_epdq_status_code($status),
                    'PAYID'				=>	$response['PAYID'] = $response['PAYID'] ?? '',
            
                );
                AG_ePDQ_Helpers::update_order_meta_data($order->get_id(), $orderdata);
    
                
            } elseif (in_array($status, array(41, 51, 91))) {
                
                $noteTitle = __(' and the authorisation will be processed offline. Please confirm the payment in the ePDQ back office.', 'ag_epdq_direct') .'</strong></p>';
                AG_ePDQ_Helpers::ag_log('The data capture will be processed offline. This is the standard response if you have selected offline processing in your account configuration. Check the  the "Global transaction parameters" tab in the ePDQ back office.', 'debug', $ePDQ_settings->debug);
                $order->update_status('on-hold');
                delete_post_meta($order->get_id(), 'HTML_ANSWER');
                $order->add_order_note($status_check . $noteTitle);    
    
            } elseif ($status === 2 || $status === 93) {
                
                $noteTitle = __(' and Barclays ePDQ has refused the transaction.', 'ag_epdq_direct').'</strong></p>';
                $order->add_order_note($error_note);
                AG_ePDQ_Helpers::ag_log('The authorisation has been refused by the financial institution. The customer can retry the authorisation process after selecting another card or another payment method.', 'debug', $ePDQ_settings->debug);
                $order->update_status('failed');
                delete_post_meta($order->get_id(), 'HTML_ANSWER');
                $order->add_order_note($status_check . $noteTitle);
    
            } elseif ($status === 52 || $status === 92) {
                
                $noteTitle = __(' and Barclays ePDQ has reported the payment is uncertain.', 'ag_epdq_direct') .'</strong></p>';
                $order->add_order_note($errornote);
                $order->add_order_note($error_note);
                AG_ePDQ_Helpers::ag_log('A technical problem arose during the authorisation/payment process, giving an unpredictable result.', 'debug', $ePDQ_settings->debug);
                $order->update_status('failed');
                $order->add_order_note($status_check . $noteTitle);
    
            } elseif ($status === 1) {
                
                $noteTitle = __(' and ePDQ has confirmed the customer has cancelled the transaction', 'ag_epdq_direct') .'</strong></p>';
                $order->add_order_note($errornote);
                $order->add_order_note($error_note);
                $order->update_status('failed');
                AG_ePDQ_Helpers::ag_log('The customer has cancelled the transaction', 'debug', $ePDQ_settings->debug);
                $order->add_order_note($status_check . $noteTitle);
            
            } elseif ($status === 0 || $status === NULL) {
                
                $noteTitle = __(' and has come back as Incomplete or invalid', 'ag_epdq_direct') .'</strong></p>';
                $order->add_order_note($errornote);
                $order->add_order_note($error_note);
                $order->add_order_note($note);
                $order->update_status('failed');
                AG_ePDQ_Helpers::ag_log('Incomplete or invalid payment', 'debug', $ePDQ_settings->debug);
                $order->add_order_note($status_check . $noteTitle);
            
            }

		}

        public static function callback_button( $order ) {

            $settings = ePDQ_crypt::refund_settings();

            // Check order is using our plugin
            if($order->get_payment_method() !== 'epdq_checkout') {
                return;
            }

            if (empty($settings['PSWD']) || empty($settings['USERID'])) {
                return;
            }
			
			if ( 0 < $order->get_total() - $order->get_total_refunded() || 0 < absint( $order->get_item_count() - $order->get_item_count_refunded() ) ) {
			
				echo '<button id="ag-check-status"  type="button" class="button" data-order_url="'. esc_attr(get_edit_post_link($order->get_id()))  .'" data-order_id="'. esc_attr($order->get_id())  .'" data-plugin="'. AG_path .'">AG ePDQ Order Status Check</button>';
				return;
			
			}
			
		}

        public function status_check_js() {

            wp_enqueue_script( self::$args['plugin_name'].'-status-check', AG_ePDQ_server_path . "inc/assets/js/ag-status-check-script.js", array('jquery'), NULL, true );
            wp_localize_script( self::$args['plugin_name'].'-status-check', 'ag_status_var', array( 
                'ajaxurl' => admin_url( 'admin-ajax.php' ),
                'msg'    => __( 'Are you sure you wish to check the status of this order?', 'ag_epdq_direct' ),
                'nonce'  => wp_create_nonce( self::$args['plugin_name'].'-status-check' ),
                'error'  => __( 'Something went wrong, and the order status check could not be completed. Please try again.', 'ag_epdq_direct' ),                
            ) );

        }


        /**
         * Ajax
         */
        function ag_manually_check_status_call() { 
            
            check_ajax_referer( self::$args['plugin_name'].'-status-check', 'nonce' );

            // the data from the ajax call
            $order_id   = (int)$_POST['order_id'];
            $order      = new WC_Order($order_id);

            // Check order is using our plugin
            if($order->get_payment_method() !== 'epdq_checkout') {
                return;
            }

            $order 		    = new WC_Order($order_id);
            $ePDQ_settings  = new epdq_checkout();
            $settings 	    = ePDQ_crypt::refund_settings();
            $key_settings 	= ePDQ_crypt::key_settings();
            $environment_url = AG_ePDQ_Helpers::get_enviroment_url('querydirect');
            $accepted = array(4, 5, 9); 

            if (empty($settings['USERID'])) {
                AG_ePDQ_Helpers::ag_log('AG Status check failed: API username has not been set.', 'debug', $ePDQ_settings->debug);
                $order->add_order_note('AG Status check failed: API username has not been set.');
            }
    
            if (empty($settings['PSWD'])) {
                AG_ePDQ_Helpers::ag_log('AG Status check failed: API password has not been set.', 'debug', $ePDQ_settings->debug);
                $order->add_order_note('AG Status check failed: API password has not been set.');
            }

            if (empty($settings['PSWD']) && empty($settings['USERID'])) {
                return;
            }

            // Status check has ran log
            $manual_status_ran = array(
                'ag_manual_check_ran'  =>	'Yes',       
            );
            AG_ePDQ_Helpers::update_order_meta_data($order->get_id(), $manual_status_ran);

	        // Data to send
			$data_post = array();
			$data_post['ORDERID'] = $order->get_order_number();

			if (get_woocommerce_currency() !== 'GBP' && defined('ePDQ_PSPID')) {
				$data_post['PSPID'] = ePDQ_PSPID;
            } else {
				$data_post['PSPID'] = $key_settings['pspid'];
                $data_post['PSWD'] = $settings['PSWD'];
                $data_post['USERID'] = $settings['USERID'];

            }

            // Post
            $result = AG_ePDQ_Helpers::remote_post( $environment_url, $data_post );

            $lines = preg_split('/\r\n|\r|\n/', $result['body']);
            $response = array();
            foreach ($lines as $line) {
                $key_value = preg_split('/=/', $line, 2);
                if (count($key_value) > 1) {
                    $response[trim($key_value[0])] = trim($key_value[1]);
                }
            }

            AG_ePDQ_Helpers::ag_log(print_r($response, TRUE), 'debug', $ePDQ_settings->debug);
    
            //$STATUS = preg_replace('/[^a-zA-Z0-9\s]/', '', $response['STATUS']);
            $NCERROR = preg_replace('/[^a-zA-Z0-9\s]/', '', $response['NCERROR']);
            $NCERRORPLUS = preg_replace('/[^a-zA-Z0-9\s]/', '', $response['NCERRORPLUS']);
            $status = preg_replace('/[^a-zA-Z0-9\s]/', '', $response['STATUS']);
            $PAYID = preg_replace('/[^a-zA-Z0-9\s]/', '', $response['PAYID']);

    
            $status_check = '<p><strong>'. __('AG ePDQ order status check was manually checked', 'ag_epdq_direct');
            $note = '<p>'. __('ePDQ Status:', 'ag_epdq_direct') .' - ' . AG_errors::get_epdq_status_code($status) . '</p>';
            $errornote = '<p>ePDQ NCERROR: - ' . AG_errors::get_epdq_ncerror($NCERROR) . '</p>';
            $error_note = '<p>ePDQ NCERROR: - ' . $NCERRORPLUS . '</p>';
            $note .= '<p>'. __('Order ID:', 'ag_epdq_direct') .' - ' . $order->get_id() . '</p>';
            $note .= '<p>'. __('Payment Reference In ePDQ System:', 'ag_epdq_direct') .' - ' . $PAYID . '</p>';   

            if (in_array($status, $accepted)) {
			
                // 3DS v2 Frictionless flow
                $noteTitle = __(' and Barclays ePDQ has confirmed the transaction.', 'ag_epdq_direct') .'</strong></p>';
                AG_ePDQ_Helpers::ag_log('Barclays ePDQ transaction is confirmed (The transaction was a 3DS v2 Frictionless transaction)', 'debug', $ePDQ_settings->debug);
                $note .= '<p><strong>'. __('Barclays ePDQ transaction is confirmed', 'ag_epdq_direct') .'</strong></p>';
                $order->add_order_note($note);
                $order->payment_complete($PAYID);
                delete_post_meta($order->get_id(), 'HTML_ANSWER');
                $order->add_order_note($status_check . $noteTitle);

    
                $orderdata = array(
                    'Status'         	=> 	AG_errors::get_epdq_status_code($status),
                    'PAYID'				=>	$PAYID = $PAYID ?? '',
            
                );
                AG_ePDQ_Helpers::update_order_meta_data($order->get_id(), $orderdata);
                
            } elseif (in_array($status, array(41, 51, 91))) {
                
                $noteTitle = __(' and the authorisation will be processed offline. Please confirm the payment in the ePDQ back office.', 'ag_epdq_direct') .'</strong></p>';
                AG_ePDQ_Helpers::ag_log('The data capture will be processed offline. This is the standard response if you have selected offline processing in your account configuration. Check the  the "Global transaction parameters" tab in the ePDQ back office.', 'debug', $ePDQ_settings->debug);
                $order->update_status('on-hold');
                delete_post_meta($order->get_id(), 'HTML_ANSWER');
                $order->add_order_note($status_check . $noteTitle);
   
            } elseif ($status === 2 || $status === 93) {
                
                $noteTitle = __(' and Barclays ePDQ has refused the transaction.', 'ag_epdq_direct').'</strong></p>';
                $order->add_order_note($error_note);
                AG_ePDQ_Helpers::ag_log('The authorisation has been refused by the financial institution. The customer can retry the authorisation process after selecting another card or another payment method.', 'debug', $ePDQ_settings->debug);
                $order->update_status('failed');
                delete_post_meta($order->get_id(), 'HTML_ANSWER');
                $order->add_order_note($status_check . $noteTitle);
    
            } elseif ($status === 52 || $status === 92) {
                
                $noteTitle = __(' and Barclays ePDQ has reported the payment is uncertain.', 'ag_epdq_direct') .'</strong></p>';
                $order->add_order_note($errornote);
                $order->add_order_note($error_note);
                AG_ePDQ_Helpers::ag_log('A technical problem arose during the authorisation/payment process, giving an unpredictable result.', 'debug', $ePDQ_settings->debug);
                $order->update_status('failed');
                $order->add_order_note($status_check . $noteTitle);

            } elseif ($status === 1) {
                
                $noteTitle = __(' and ePDQ has confirmed the customer has cancelled the transaction', 'ag_epdq_direct') .'</strong></p>';
                $order->add_order_note($errornote);
                $order->add_order_note($error_note);
                $order->update_status('failed');
                AG_ePDQ_Helpers::ag_log('The customer has cancelled the transaction', 'debug', $ePDQ_settings->debug);
                $order->add_order_note($status_check . $noteTitle);
            
            } elseif ($status === 0 || $status === NULL) {
                
                $noteTitle = __(' and has come back as Incomplete or invalid', 'ag_epdq_direct') .'</strong></p>';
                $order->add_order_note($errornote);
                $order->add_order_note($error_note);
                $order->add_order_note($note);
                $order->update_status('failed');
                AG_ePDQ_Helpers::ag_log('Incomplete or invalid payment', 'debug', $ePDQ_settings->debug);
                $order->add_order_note($status_check . $noteTitle);
            
            }


        }

        public function ag_set_sortable_columns( $columns )
        {
            
            // Disable new column from showing.
            if( defined( 'AG_disable_column' )) {
                return $columns;
            }
            
            $columns['order_number_new'] = 'order_number_new';
            return $columns;
        }



        public function ag_change_order_column( $columns ){
            
            // Disable new column from showing.
            if( defined( 'AG_disable_column' )) {
                return $columns;
            }
            
            $new_columns = [];
        
            foreach ( $columns as $key => $column ) {
                if( $key === 'order_number' ) {
                    $new_columns['order_number_new'] = $column;
                } else {
                    $new_columns[$key] = $column;
                }
            }
            return $new_columns;
        }


        public function show_status_check_order_screen( $column ) {
            
            global $post;

            if ( 'order_number_new' === $column ) {
               $order = wc_get_order( $post->ID );
               
               
               $buyer = '';
           
               if ( $order->get_billing_first_name() || $order->get_billing_last_name() ) {
                   /* translators: 1: first name 2: last name */
                   $buyer = trim( sprintf( _x( '%1$s %2$s', 'full name', 'woocommerce' ), $order->get_billing_first_name(), $order->get_billing_last_name() ) );
               } elseif ( $order->get_billing_company() ) {
                   $buyer = trim( $order->get_billing_company() );
               } elseif ( $order->get_customer_id() ) {
                   $user  = get_user_by( 'id', $order->get_customer_id() );
                   $buyer = ucwords( $user->display_name );
               }
           
               /**
                * Filter buyer name in list table orders.
               *
               * @since 3.7.0
               * @param string   $buyer Buyer name.
               * @param WC_Order $order Order data.
               */
               $buyer = apply_filters( 'woocommerce_admin_order_buyer_name', $buyer, $order );
           
               if ( $order->get_status() === 'trash' ) {
                   echo '<strong>#' . esc_attr( $order->get_order_number() ) . ' ' . esc_html( $buyer ) . '</strong>';
               } else {
                   echo '<a href="#" class="order-preview" data-order-id="' . absint( $order->get_id() ) . '" title="' . esc_attr( __( 'Preview', 'woocommerce' ) ) . '">' . esc_html( __( 'Preview', 'woocommerce' ) ) . '</a>';
                   echo '<a href="' . esc_url( admin_url( 'post.php?post=' . absint( $order->get_id() ) ) . '&action=edit' ) . '" class="order-view"><strong>#' . esc_attr( $order->get_order_number() ) . ' ' . esc_html( $buyer ) . '</strong></a>';
                   
                   // Check order is using our plugin
                   if($order->get_payment_method() !== 'epdq_checkout') {
                       return;
                   } 
           
                   if(get_post_meta( $order->get_id(), 'ag_auto_check_ran', true ) === 'Yes') {
           
                       echo '<p title="The AG auto status check has already ran on this order and updated it status.">AG Auto Status Check &#10004;</p>';
           
                   }
           
                   if(get_post_meta( $order->get_id(), 'ag_manual_check_ran', true ) === 'Yes') {
           
                       echo '<p title="The AG manual status check has already ran on this order and updated it status.">AG Manual Status Check &#10004;</p>';
           
                   }
           
               }
           
           
            }
        }



}




