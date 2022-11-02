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
 * WC tested up to: 4.2
 * License: GPL3
 */

defined( 'ABSPATH' ) || die( "No script kiddies please!" );

add_action( 'plugins_loaded', 'init_ag_epdq' );

function init_ag_epdq() {

	class epdq_checkout extends WC_Payment_Gateway {

		/**
		 * Plugin Doc link
		 *
		 * @var string
		 */
		private static $AG_ePDQ_doc = "https://weareag.co.uk/docs/";
		public $settings;
		public $form_fields;
		private static $test_url = 'https://mdepayments.epdq.co.uk/ncol/test/orderstandard.asp';
		private static $live_url = 'https://payments.epdq.co.uk/ncol/prod/orderstandard.asp';
		private static $refund_test = 'https://mdepayments.epdq.co.uk/ncol/test/maintenancedirect.asp';
		private static $refund_live = 'https://payments.epdq.co.uk/ncol/prod/maintenancedirect.asp';


		public function __construct() {

			$this->id           = 'epdq_checkout';
			$this->method_title = 'AG ePDQ Checkout';
			$this->icon         = apply_filters( 'woocommerce_epdq_checkout_icon', '' );
			$this->has_fields   = false;
			$this->notice       = 'no';
			$this->status       = 'test';


			if ( ! AG_licence::valid_licence() ) {
				return;
			}

			$this->init_form_fields();
			$this->init_settings();


			// Turn settings into variables we can use
			foreach ( $this->settings as $setting_key => $value ) {
				$this->$setting_key = $value;
			}

			//$this->sha_method 	= ($this->sha_method != '') ? $this->sha_method : 2;
			$this->notice      = ( $this->notice !== '' ) ? $this->notice : 'no';
			$this->threeds     = $this->get_option( 'threeds' ) ?: 'no';
			$this->description = $this->display_checkout_description();
			$this->fraudCheck  = $this->get_option( 'fraudCheck' ) ?: 'no';

			$this->supports = array(
				'products',
				'refunds',
				'subscriptions',
				'gateway_scheduled_payments',
				'tokenization'
			);

			// Save options
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array(
				$this,
				'process_admin_options'
			) );

			// Payment listener/API hook
			add_action( 'woocommerce_receipt_epdq_checkout', array( $this, 'receipt_page' ) );
			add_action( 'woocommerce_api_epdq_checkout', array( $this, 'check_response' ) );

			add_action( 'admin_head', array( 'AG_ePDQ_Helpers', 'add_disable_to_input' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_script' ) );

		}

		/**
		 * Validate customer billing address, there is a character limit of 34 for each, this is set by the ePDQ platform.
		 */
		public function validate_fields() {

			//if ( strlen( $_POST['billing_address_1'] ) > 35 ) {
			//wc_add_notice( 'Your street address is too long, please also use the second address field.', 'error' );
			//}
			//if ( strlen( $_POST['billing_address_2'] ) > 35 ) {
			//wc_add_notice( 'Your second street address is too long. Please shorten your billing address.', 'error' );
			//}

		}

		/**
		 * Plugin settings
		 *
		 * @return void
		 */
		public function init_form_fields() {
			$this->form_fields = AG_ePDQ_Settings::form_fields();
		}

		public function admin_script() {

			$screen = get_current_screen();

			if ( 'woocommerce_page_wc-settings' !== $screen->base ) {
				return;
			}

			wp_enqueue_script( 'ePDQ_settings_script', AG_ePDQ_server_path . 'inc/assets/js/admin-script.js' );
			wp_enqueue_script( 'ePDQ_alert', 'https://unpkg.com/sweetalert/dist/sweetalert.min.js' );
		}

		/**
		 * Plugin settings with refund option
		 *
		 * @return void
		 */
		public function init_form_fields_refund() {
			//$this->form_fields = AG_ePDQ_Settings::form_fields_refund();
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

			$input = sprintf( __( '<span class="AG-redirect-icon"><img src="%s" /></span>', 'ag_epdq_server' ), AG_ePDQ_server_path . 'inc/assets/img/AG-ePDQ-redirect.png' );
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

			if ( $this->notice === 'yes' ) {
				$description .= $this->display_redirect_message();
			} else {
				$description .= $this->get_option( 'description' );
			}

			/** @noinspection PhpUndefinedFieldInspection */
			if ( $this->status === 'test' ) {
				$description .= $this->display_test_message();
			}

			// Display token section
			if ( isset( $this->token ) && $this->token === 'yes' ) {
				$description .= AG_ePDQ_Token::selectSavedCards( get_current_user_id(), is_user_logged_in() );
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
			$icon      = '';
			if ( ! $cardTypes ) {
				// default behavior
				$icon = '<img src="' . AG_ePDQ_server_path . 'inc/assets/img/cards.gif" alt="' . $this->title . '" />';
			} elseif ( $cardTypes ) {
				// display icons for the selected card types
				$icon = '';
				foreach ( $cardTypes as $cardtype ) {
					$icon .= '<img class="ePDQ-card-icons" src="' . AG_ePDQ_server_path . 'inc/assets/img/new-card/' . strtolower( $cardtype ) . '.png" alt="' . strtolower( $cardtype ) . '" />';
				}
			}

			return apply_filters( 'woocommerce_gateway_icon', $icon, $this->id );
		}

		/**
		 * Display settings
		 *
		 * @return void
		 */
		public function admin_options() {
			?>

            <h3><?php echo __( 'AG ePDQ Checkout Settings', 'ag_epdq_server' ); ?></h3>
            <p><?php echo __( 'This gateway will redirect the customers to the secured Barclays payment server and process the order there, Once payment is made Barclays will send them back to website.', 'ag_epdq_server' ) ?></p>
            <p>
                <i><?php echo __( 'Having issues setting up the plugin? Why not try the setup wizard <a href="' . admin_url( '?page=AG_ePDQ-wizard' ) . '">here</a>.', 'ag_epdq_server' ) ?></i>
            </p>
            <table class="form-table">
				<?php $this->generate_settings_html(); ?>
            </table>
            <!--/.form-table-->

            <p><strong>Need some help setting up this plugin?</strong> <a
                        href="<?php echo admin_url( 'admin.php?page=AGWooCommerceBarclayePDQPaymentGateway' ); ?>">Click
                    here</a></p>

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

			AG_ePDQ_Helpers::update_order_meta_data( $order_id, $orderdata );

			if ( isset( $this->token ) && $this->token === 'yes' && isset( $_POST['saved_cards'] ) ) {
				$saved_cards = (int) $_POST['saved_cards'];
				update_post_meta( $order_id, 'use_saved_card', $saved_cards ?? null );
			}

			// This is for debugging customer device type.
			if ( defined( 'ePDQ_support_debug' ) ) {
				AG_ePDQ_Helpers::ag_log( $_SERVER['HTTP_USER_AGENT'], 'debug', $this->debug );
			}

			return array(
				'result'   => 'success',
				'redirect' => $order->get_checkout_payment_url( true )
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

			$order              = new WC_Order( $order_id );
			$settings           = ePDQ_crypt::key_settings();
			$order_received_url = WC()->api_request_url( 'epdq_checkout' ) . '?idOrder=' . $order->get_id();
			$cancel_order_url   = $order->get_cancel_order_url_raw();

			$hash_fields      = array(
				$settings['pspid'],
				date( 'Y:m:d' ),
				$order->get_order_number(),
				$settings['shain']
			);
			$encrypted_string = ePDQ_crypt::ripemd_crypt( implode( $hash_fields ), $settings['shain'] );

			$fullName = remove_accents( $order->get_billing_first_name() . ' ' . str_replace( "'", "", $order->get_billing_last_name() ) );

			// Currency
			if ( get_woocommerce_currency() !== 'GBP' && defined( 'ePDQ_PSPID' ) ) {
				$PSPID = ePDQ_PSPID;
			} else {
				$PSPID = $settings['pspid'];
			}

			// Use different PSPID (This is useful for stores that are franchisees)
			$ePDQ_PSPID  = null;
			$multi_PSPID = apply_filters( 'ePDQ_PSPID', $ePDQ_PSPID );
			if ( ! empty( $multi_PSPID ) ) {
				$PSPID = $multi_PSPID;
			}

			// Products
			$order_item = $order->get_items();
			foreach ( $order_item as $product ) {
				$product_name[] = preg_replace( "/[^a-zA-Z0-9\s]/", "", str_replace( array(
						"-",
						" "
					), "", $product['name'] ) ) . " x" . $product['qty'];
				$product_ids[]  = str_replace( '&', 'and', $product['product_id'] ) . " x" . $product['qty'];
			}
			$product_list_string = implode( ',', $product_name );
			$product_id_string   = implode( ',', $product_ids );

			// If the items in the cart add to more than the character limit set by ePDQ then switch to product id.
			if ( strlen( $product_list_string ) < 99 && get_locale() !== 'ar' ) {
				$product_list = $product_list_string;
			} elseif ( strlen( $product_id_string ) < 99 ) {
				$product_list = $product_id_string;
			} else {
				// Fallback if both products name/id is more than character limit.
				$product_list = 'Please check order #' . $order_id . ' on website for information.';
				AG_ePDQ_Helpers::ag_log( 'Order #' . $order_id . ' had more products than allowed to pass over to ePDQ, default message was passed instead of product\'s name/id.', 'debug', $this->debug );
			}

			// Custom product data - this could be for custom meta data
			if ( defined( 'ePDQ_custom_product_data' ) ) {
				$com = apply_filters( 'ePDQ_custom_product_data', $order );
			} else {
				$com = $product_list;
			}

			// Custom Merchant Ref - this could be for custom meta data
			if ( defined( 'ePDQ_custom_order_id' ) ) {
				$orderID = apply_filters( 'ePDQ_custom_order_id', $order );
			} else {
				$orderID = $order->get_order_number();
			}

			// Get customer token
			$savedCard     = get_post_meta( $orderID, 'use_saved_card', true );
			$customerToken = AG_ePDQ_Token::get( get_current_user_id(), is_user_logged_in(), $savedCard );
			// END

			$fields = array(
				'PSPID'         => $PSPID,
				'ORDERID'       => $orderID,
				'AMOUNT'        => $order->get_total() * 100,
				'COMPLUS'       => $encrypted_string,
				'CURRENCY'      => get_woocommerce_currency(),
				'LANGUAGE'      => get_locale(),
				'CN'            => $fullName,
				'COM'           => $com,
				'EMAIL'         => $order->get_billing_email(),
				'OWNERZIP'      => preg_replace( '/[^A-Za-z0-9\. -]/', '', $order->get_billing_postcode() ),
				'OWNERADDRESS'  => substr( preg_replace( '/[^A-Za-z0-9\. -]/', '', $order->get_billing_address_1() ), 0, 34 ),
				'OWNERADDRESS2' => substr( preg_replace( '/[^A-Za-z0-9\. -]/', '', $order->get_billing_address_2() ), 0, 34 ),
				'OWNERCTY'      => substr( preg_replace( '/[^A-Za-z0-9\. -]/', '', $order->get_billing_country() ), 0, 34 ),
				'OWNERTOWN'     => substr( preg_replace( '/[^A-Za-z0-9\. -]/', '', $order->get_billing_city() ), 0, 34 ),
				'OWNERTELNO'    => $order->get_billing_phone(),
				'ACCEPTURL'     => $order_received_url,
				'DECLINEURL'    => $cancel_order_url,
				'HOMEURL'       => $cancel_order_url,
				'TP'            => ( $this->template ?? '' ),
				'LOGO'          => ( $this->logo ?? '' ),
				'TITLE'         => '',
			);

			if ( isset( $this->token ) && $this->token === 'yes' ) {
				$fields['ALIAS']          = $customerToken['token'] ?? '';
				$fields['ALIASOPERATION'] = 'BYPSP';
				$fields['ALIASUSAGE']     = get_bloginfo( 'name' );
				$fields['COF_INITIATOR']  = 'CIT';
				$fields['BRAND']          = $customerToken['brand'] ?? '';
				$fields['PM']             = $customerToken['brand'] = 'CreditCard' ?? '';
			}

			if ( class_exists( 'WC_Subscriptions_Order' ) && AG_ePDQ_Helpers::order_contains_subscription( $order ) ) {
				//$price_per_period = WC_Subscription::get_total();
				$billing_period = WC_Subscriptions_Order::get_subscription_period( $order );
				switch ( strtolower( $billing_period ) ) {
					case 'day':
						$billing_period        = 'd';
						$subscription_interval = WC_Subscriptions_Order::get_subscription_interval( $order );
						break;
					case 'week':
						$billing_period        = 'ww';
						$subscription_interval = WC_Subscriptions_Order::get_subscription_interval( $order );
						break;
					case 'year':
						$billing_period        = 'm';
						$subscription_interval = WC_Subscriptions_Order::get_subscription_interval( $order );;
						//$adv = strtotime("+12 Months");
						break;
					case 'month':
					default:
						$billing_period        = 'm';
						$subscription_interval = WC_Subscriptions_Order::get_subscription_interval( $order );
						//$adv = strtotime("+1 Months");
						break;
				}

				// todo $adv is a hot fix from Ross Davidson, Will need to work out the strtotime for day, week and year and test.


				// Recurring payment
				$fields['SUBSCRIPTION_ID']   = $order->get_order_number();
				$fields['SUB_AMOUNT']        = $order->get_total() * 100;
				$fields['SUB_COM']           = 'order description';
				$fields['SUB_COMMENT']       = 'Recurring payment';
				$fields['SUB_ORDERID']       = $order->get_order_number();
				$fields['SUB_PERIOD_MOMENT'] = date( 'd' );
				$fields['SUB_PERIOD_NUMBER'] = $subscription_interval;
				$fields['SUB_PERIOD_UNIT']   = $billing_period;
				$fields['SUB_STARTDATE']     = date( "Y-m-d", $adv );
				$fields['SUB_STATUS']        = '1';
			}

			//Server-to-server parameter
			$fields['PARAMVAR'] = $order->get_id();

			// Order card icons on ePDQ side
			$fields['PMLISTTYPE'] = ( $this->pmlisttype ?? '0' );

			// Hook to add extra para
			do_action( 'AG_ePDQ_extra_parameters' );

			AG_ePDQ_Helpers::ag_log( 'Debug URL ' . $order_received_url, 'debug', $this->debug );

			$shasign_arg = array();
			ksort( $fields );
			foreach ( $fields as $key => $value ) {
				if ( $value == '' ) {
					continue;
				}
				$shasign_arg[] = $key . '=' . $value;
			}

			$shasign = hash( ePDQ_crypt::get_sha_method(), implode( $settings['shain'], $shasign_arg ) . $settings['shain'] );

			// Enable deeper debugging, useful for when the ePDQ team require data to debug.
			if ( defined( 'ePDQ_support_debug' ) ) {
				AG_ePDQ_Helpers::ag_log( print_r( $fields, true ) . ' ' . print_r( $shasign, true ), 'debug', $this->debug );
			}

			$epdq_args = array();
			foreach ( $fields as $key => $value ) {
				if ( $value === '' ) {
					continue;
				}
				$epdq_args[] = '<input type="hidden" name="' . sanitize_text_field( $key ) . '" value="' . $value . '"/>';
			}

			if ( empty( $this->access_key ) || empty( $this->sha_in ) ) {

				AG_ePDQ_Helpers::ag_log( 'You are missing your PSPID and or SHA-IN', 'debug', $this->debug );
				wc_add_notice( 'ePDQ Bad Setup: You are missing your PSPID and or SHA-IN', 'error' );

				return;
			}

			if ( isset( $this->status ) && ( $this->status === 'test' || $this->status === 'live' ) ) {
				if ( $this->status === 'test' ) {
					$url = self::$test_url;
				}
				if ( $this->status === 'live' ) {
					$url = self::$live_url;
				}

				echo '<form action="' . esc_url_raw( $url ) . '" method="post" id="epdq_payment_form">';
				echo implode( '', $epdq_args );
				echo '<input type="hidden" name="SHASIGN" value="' . sanitize_text_field( $shasign ) . '"/>';
				echo '<input type="hidden" id="register_nonce" name="register_nonce" value="' . wp_create_nonce( 'generate-nonce' ) . '" />';
				echo '<input type="submit" class="button alt" id="submit_epdq_payment_form" value="' . __( 'Pay securely', 'ag_epdq_server' ) . '" />';
				echo '<a class="button cancel" href="' . $order->get_cancel_order_url() . '">' . __( 'Cancel order &amp; restore cart', 'ag_epdq_server' ) . '</a></form>';

				wc_enqueue_js( '
				$("body").block({
						message: "' . __( 'You are now being redirected to Barclaycard to make payment securely.', 'ag_epdq_server' ) . '",
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
				$("#submit_epdq_payment_form").click();
			' );

				return;

			} else {

				AG_ePDQ_Helpers::ag_log( 'Please double check the ePDQ plugin settings, something is not quite right...', 'debug', $this->debug );
				wc_add_notice( 'ePDQ Bad Setup: Please double check the ePDQ plugin settings, something is not quite right...', 'error' );

				return;

			}

		}

		/**
		 * Check payment response
		 *
		 * @return void
		 */
		public function check_response() {

			ob_clean();
			header( 'HTTP/1.1 200 OK' );

			$nonce = AG_ePDQ_Helpers::AG_escape( $_REQUEST['COMPLUS'] );

			// Store 3D secure data
			//ePDQ_Display_Score::AG_sore_PSP_returned_data($_REQUEST);

			$check_data = array();
			$data       = $_REQUEST;
			$settings   = ePDQ_crypt::key_settings();

			foreach ( $data as $key => $value ) {
				if ( $value == "" ) {
					continue;
				}
				$check_data[ $key ]              = AG_ePDQ_Helpers::AG_escape( $value );
				$datacheck[ strtoupper( $key ) ] = AG_ePDQ_Helpers::AG_escape( strtoupper( $value ) );
			}

			// Server-to-server API callback
			if ( null !== AG_ePDQ_Helpers::AG_get_request( 'callback' ) ) {
				AG_ePDQ_Helpers::ag_log( 'API call back happened', 'warning', $this->debug );

				// Passing id
				if ( ! isset( $datacheck['PARAMVAR'] ) ) {
					AG_ePDQ_Helpers::ag_log( 'PARAMVAR parameter is missing, please read the docs ' . self::$AG_ePDQ_doc . 'barclays-epdq-payment-gateway/troubleshooting-barclays-epdq-payment-gateway/pending-failed-transactions/', 'warning', $this->debug );
				} else {
					$check_data['idOrder'] = AG_ePDQ_Helpers::AG_get_request( 'PARAMVAR' );
				}

			}


			//if (class_exists('WC_Subscriptions_Order') && AG_ePDQ_Helpers::order_contains_subscription($order) ) {
			//$check_data['SUBSCRIPTION_ID'] = isset($check_data['subscription_id']) ? $check_data['subscription_id'] : '';
			//$check_data['CREATION_STATUS'] = isset($check_data['creation_status']) ? $check_data['creation_status'] : '';
			//}


			// Hash
			if ( defined( 'ePDQ_custom_order_id' ) ) {
				$hash_fields = array(
					$settings['pspid'],
					date( 'Y:m:d' ),
					AG_ePDQ_Helpers::AG_get_request( 'idOrder' ),
					$settings['shain']
				);
			} else {
				$hash_fields = array(
					$settings['pspid'],
					date( 'Y:m:d' ),
					AG_ePDQ_Helpers::AG_get_request( 'orderID' ),
					$settings['shain']
				);
			}
			$encrypted_string = ePDQ_crypt::ripemd_crypt( implode( $hash_fields ), $settings['shain'] );


			if ( null !== AG_ePDQ_Helpers::AG_get_request( 'STATUS' ) ) {

				if ( hash_equals( $encrypted_string, $nonce ) ) {
					if ( ! empty( $this->sha_out ) ) {
						$SHA_check = $this->SHA_check( $check_data );
						if ( $SHA_check ) {
							$this->successful_transaction( $check_data );
						} else {
							if ( $this->threeds === 'yes' ) {
								AG_ePDQ_Helpers::ag_log( 'Extra parameters are required to be sent back when using the AG 3D secure score system, please check through the trouble shooting in the plugin docs.', 'warning', $this->debug );
							} else {
								AG_ePDQ_Helpers::ag_log( 'Transaction is unsuccessful due to a SHA-Out issue, please check the docs ' . self::$AG_ePDQ_doc . 'barclays-epdq-payment-gateway/troubleshooting-barclays-epdq-payment-gateway/transaction-is-unsuccessful-due-to-a-sha-out-issue/', 'warning', $this->debug );
							}
							// SHA-Out check fail
							wp_die( 'Transaction is unsuccessful due to a SHA-Out issue' );
						}
					} else {
						// SHA-Out not set
						AG_ePDQ_Helpers::ag_log( 'You dont have SHA-out set, for improved security we recommend you set this. Please check the docs ' . self::$AG_ePDQ_doc . 'barclays-epdq-payment-gateway/troubleshooting-barclays-epdq-payment-gateway/transaction-is-unsuccessful-due-to-a-sha-out-issue/', 'warning', $this->debug );
						$this->successful_transaction( $check_data );
					}
				} else {
					// Nonce check fail
					AG_ePDQ_Helpers::ag_log( 'Security check fail, please check the docs ' . self::$AG_ePDQ_doc . 'barclays-epdq-payment-gateway/troubleshooting-barclays-epdq-payment-gateway/security-check-fail/', 'warning', $this->debug );
					wp_die( 'Security check fail.' );
				}
			} else {

				AG_ePDQ_Helpers::ag_log( 'The transaction failed, ePDQ didn\'t send any data back. Please check you have setup the plugin correctly.', 'warning', $this->debug );
				//wp_die('Transaction fail.');
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
			$SHA_out  = $settings['shaout'];
			$origsig  = $datatocheck['SHASIGN'];

			// Remove parameters before doing decryption
			unset(
				$datatocheck['SHASIGN'],
				$datatocheck['wc-api'],
				$datatocheck['idOrder'],
				$datatocheck['PARAMVAR'],
				$datatocheck['callback'],
				$datatocheck['doing_wp_cron'],
				$datatocheck['woocs_order_emails_is_sending'],
				$datatocheck['q'],
				$datatocheck['somdn_error_logs_export_errors'],
				$datatocheck['inner_section'],
				$datatocheck['woof_parse_query']
			);

			// 3D score check
			if ( $this->threeds !== 'yes' && isset( $datatocheck['SCORING'] ) ) {
				unset(
					$datatocheck['CCCTY'],
					$datatocheck['ECI'],
					$datatocheck['CVCCheck'],
					$datatocheck['AAVCheck'],
					$datatocheck['VC'],
					$datatocheck['AAVZIP'],
					$datatocheck['AAVADDRESS'],
					$datatocheck['AAVNAME'],
					$datatocheck['AAVPHONE'],
					$datatocheck['AAVMAIL'],
					$datatocheck['SCORING']
				);
			}
			// END

			uksort( $datatocheck, 'strcasecmp' );

			// Enable deeper debugging, useful for when the ePDQ team require data to debug.
			if ( defined( 'ePDQ_support_debug' ) ) {
				$args = array(
					'AAVADDRESS' => $datatocheck['AAVADDRESS'] ?? '',
					'ACCEPTANCE' => $datatocheck['ACCEPTANCE'] ?? '',
					'COMPLUS'    => $datatocheck['COMPLUS'] ?? '',
					'NCERROR'    => $datatocheck['NCERROR'] ?? '',
					'orderID'    => $datatocheck['orderID'] ?? '',
					'PAYID'      => $datatocheck['PAYID'] ?? '',
					'STATUS'     => $datatocheck['STATUS'] ?? ''
				);
				AG_ePDQ_Helpers::ag_log( 'Debug data sent back ' . print_r( $args, true ), 'debug', $this->debug );
			}


			$SHAsig = '';
			foreach ( $datatocheck as $key => $value ) {
				$SHAsig .= trim( strtoupper( $key ) ) . '=' . utf8_encode( trim( $value ) ) . $SHA_out;
			}

			$SHAsig = strtoupper( hash( ePDQ_crypt::get_sha_method(), $SHAsig ) );

			if ( hash_equals( $SHAsig, $origsig ) ) {
				return true;
			}

			return false;

		}


		/**
		 * Successful transaction
		 *
		 * @param $args
		 *
		 * @return void
		 */
		public function successful_transaction( $args ) {

			global $woocommerce;

			foreach ( $args as $key => $value ) {
				if ( $value == "" ) {
					continue;
				}
				$args[ $key ] = AG_ePDQ_Helpers::AG_escape( $value );
			}

			extract( $args );

			$order = new WC_Order( $args['idOrder'] );

			// Catch and stop if order is already paid for.
			if ( $order->has_status( array( 'processing', 'completed' ) ) ) {
				AG_ePDQ_Helpers::ag_log( 'Aborting, Order #' . $args['idOrder'] . ' is already paid for.', 'debug', 'yes' );
				wp_redirect( $order->get_checkout_order_received_url() );
				exit;
			}

			// Save payment token to user
			if ( $this->token === 'yes' ) {
				AG_ePDQ_Token::save( $args, get_current_user_id(), is_user_logged_in() );
				// Drop BIN
				unset( $args['BIN'] );
				update_post_meta( $args['idOrder'], 'use_saved_card', '' );
			}
			// END


			$STATUS    = $args['STATUS'];
			$NCERROR   = $args['NCERROR'];
			$note      = 'ePDQ Status: - ' . AG_errors::get_epdq_status_code( $STATUS ) . '</p>';
			$errornote = 'ePDQ NCERROR: - ' . AG_errors::get_epdq_ncerror( $NCERROR ) . '</p>';


			$order_notes = array(
				'Order ID                            : ' => $args['ORDERID'] ?? '',
				'Amount                              : ' => $args['AMOUNT'] = $args['AMOUNT'] ?? '',
				'Order Currency                      : ' => $args['CURRENCY'] = $args['CURRENCY'] ?? '',
				'Payment Method                      : ' => $args['PM'] = $args['PM'] ?? '',
				'Acceptance Code Returned By Acquirer: ' => $args['ACCEPTANCE'] = $args['ACCEPTANCE'] ?? '',
				'Payment Reference In ePDQ System    : ' => $args['PAYID'] = $args['PAYID'] ?? '',
				'Error Code                          : ' => $args['NCERROR'] = $args['NCERROR'] ?? '',
				'Card Brand                          : ' => $args['BRAND'] = $args['BRAND'] ?? '',
				'Transaction Date                    : ' => $args['TRXDATE'] = $args['TRXDATE'] ?? '',
				'Cardholder/Customer Name            : ' => $args['CN'] = $args['CN'] ?? '',
				'Customer IP                         : ' => $args['IP'] = $args['IP'] ?? '',
				'AAV Result For Address              : ' => $args['AAVADDRESS'] = $args['AAVADDRESS'] ?? '',
				'Result for AAV Check                : ' => $args['AAVCHECK'] = $args['AAVCHECK'] ?? '',
				'AAV Result For Postcode             : ' => $args['AAVZIP'] = $args['AAVZIP'] ?? '',
			);

			if ( class_exists( 'WC_Subscriptions_Order' ) && AG_ePDQ_Helpers::order_contains_subscription( $order ) ) {
				$order_notes['Subscription ID: ']     = $args['subscription_id'] ?? '';
				$order_notes['Subscription status: '] = $args['creation_status'] ?? '';
			}


			AG_ePDQ_Helpers::update_order_notes( $order, $order_notes );


			// Time customer took to process through ePDQ
			update_post_meta( $order->get_id(), 'AG_returned_back', date( 'Y-m-d H:i:s', current_time( 'timestamp', 0 ) ) );
			$start  = new DateTime( get_post_meta( $order->get_id(), 'AG_sent_to_ePDQ', true ) );
			$finish = new DateTime( get_post_meta( $order->get_id(), 'AG_returned_back', true ) );

			if ( $start !== null && $finish !== null ) { //if(!empty($start) && !empty($finish)) {

				$interval = date_diff( $start, $finish );
				AG_ePDQ_Helpers::ag_log( 'Customer took ' . $interval->format( '%i Minute %s Seconds' ) . ' to process through ePDQ', 'debug', $this->debug );
				$time = __( 'Customer took ' . $interval->format( '%i Minute %s Seconds' ) . ' to process through ePDQ', 'ag_epdq_server' );
				$order->add_order_note( $time );

			}

			$order_data = array();
			unset( $args['SHASIGN'], $args['COMPLUS'], $args['CARDNO'], $args['ALIAS'] );
			foreach ( $args as $key => $value ) {
				if ( $value == "" ) {
					continue;
				}
				$order_data[ $key ] = $value;
			}

			if ( class_exists( 'WC_Subscriptions_Order' ) && AG_ePDQ_Helpers::order_contains_subscription( $order ) ) {
				$order_data['SUBSCRIPTION_ID'] = $args['subscription_id'] ?? '';
				$order_data['CREATION_STATUS'] = $args['creation_status'] ?? '';
			}

			// Storing meta if customer canceled order.
			$orderCancel = array(
				'customer_canceled_order' => true,
			);

			AG_ePDQ_Helpers::update_order_meta_data( $args['idOrder'], $order_data );

			switch ( $STATUS ): case '4':
				case '5':
				case '9':
					if ( ! $order->has_status( array( 'processing', 'completed' ) ) ) {
						$noteTitle = __( 'Barclays ePDQ transaction is confirmed.', 'ag_epdq_server' );
						AG_ePDQ_Helpers::ag_log( 'Barclays ePDQ transaction is confirmed. No issues to report.', 'debug', $this->debug );
						$order->add_order_note( $noteTitle );
						$order->add_order_note( $note );
						$order->payment_complete( $args['PAYID'] );
					}
					break;

				case '41':
				case '51':
				case '91':
					$noteTitle = __( 'Barclays ePDQ transaction is awaiting for confirmation.', 'ag_epdq_server' );
					AG_ePDQ_Helpers::ag_log( 'Barclays ePDQ transaction is awaiting for confirmation. No issues to report.', 'debug', $this->debug );
					$order->add_order_note( $noteTitle );
					$order->update_status( 'on-hold', $note );
					break;

				case '2':
				case '93':
					$noteTitle = __( 'Barclays ePDQ transaction was refused.', 'ag_epdq_server' );
					$order->add_order_note( $noteTitle );
					$order->add_order_note( $errornote );
					AG_ePDQ_Helpers::ag_log( 'The authorisation has been refused by the financial institution. The customer can retry the authorisation process after selecting another card or another payment method.', 'notice', $this->debug );
					$order->update_status( 'failed', $note );
					$woocommerce->cart->empty_cart();
					break;

				case '52':
				case '92':
					$noteTitle = __( 'Barclays ePDQ payment uncertain.', 'ag_epdq_server' );
					$order->add_order_note( $noteTitle );
					$order->add_order_note( $errornote );
					AG_ePDQ_Helpers::ag_log( 'A technical problem arose during the authorisation/payment process, giving an unpredictable result.', 'notice', $this->debug );
					$order->update_status( 'failed', $note );
					$woocommerce->cart->empty_cart();
					break;

				case '1':
					$noteTitle = __( 'The customer has cancelled the transaction', 'ag_epdq_server' );
					$order->add_order_note( $noteTitle );
					$order->add_order_note( $errornote );
					$order->update_status( 'cancelled', $note );

					AG_ePDQ_Helpers::update_order_meta_data( $args['idOrder'], $orderCancel );

					$woocommerce->cart->empty_cart();
					break;

				case '0':
					$noteTitle = __( 'Incomplete or invalid', 'ag_epdq_server' );
					$order->add_order_note( $noteTitle );
					$order->add_order_note( $errornote );
					$order->update_status( 'failed', $note );
					$woocommerce->cart->empty_cart();
					break;

			endswitch;

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
		function process_refund( $order_id, $amount = null, $reason = '' ) {

			$order           = new WC_Order( $order_id );
			$settings        = ePDQ_crypt::key_settings();
			$refund_settings = ePDQ_crypt::refund_settings();
			$environment_url = AG_ePDQ_Helpers::get_enviroment_url( 'maintenancedirect' );


			$refund_amount  = $amount * 100;
			$transaction_id = get_post_meta( $order_id, 'PAYID', true );

			if ( $this->status === 'test' ) {
				$environment_url = self::$refund_test;
			}
			if ( $this->status === 'live' ) {
				$environment_url = self::$refund_live;
			}

			if ( ! $transaction_id ) {
				AG_ePDQ_Helpers::ag_log( 'Refund failed: Transaction ID not found.', 'debug', $this->debug );

				return new WP_Error( 'error', __( 'Refund failed: Transaction ID not found.', 'ag_epdq_server' ) );
			}
			if ( ! $refund_amount ) {
				AG_ePDQ_Helpers::ag_log( 'Refund failed: Amount invalid.', 'debug', $this->debug );

				return new WP_Error( 'error', __( 'Refund failed: Amount invalid.', 'ag_epdq_server' ) );
			}

			if ( empty( $refund_settings['USERID'] ) ) {
				AG_ePDQ_Helpers::ag_log( 'Refund failed: API username has not been set.', 'debug', $this->debug );

				return new WP_Error( 'error', __( 'Refund failed: API username has not been set.', 'ag_epdq_server' ) );
			}

			if ( empty( $refund_settings['PSWD'] ) ) {
				AG_ePDQ_Helpers::ag_log( 'Refund failed: API password has not been set.', 'debug', $this->debug );

				return new WP_Error( 'error', __( 'Refund failed: API password has not been set.', 'ag_epdq_server' ) );
			}

			if ( AG_ePDQ_Helpers::ag_get_order_currency( $order ) !== 'GBP' && defined( 'ePDQ_PSPID' ) && defined( 'ePDQ_REFID' ) ) {
				$PSPID                    = ePDQ_PSPID;
				$refund_settings['REFID'] = ePDQ_REFID;
			} else {
				$PSPID = $settings['pspid'];
			}

			$data_post              = array();
			$data_post['AMOUNT']    = $refund_amount;
			$data_post['PAYID']     = $transaction_id;
			$data_post['OPERATION'] = 'RFD';
			$data_post['ORDERID']   = $order_id;
			$data_post['PSPID']     = $PSPID;
			$data_post['PSWD']      = $refund_settings['PSWD'];
			$data_post['REFID']     = $refund_settings['REFID'];
			$data_post['USERID']    = $refund_settings['USERID'];

			$shasign_arg = array();
			if ( isset( $settings['shain'] ) ) {

				ksort( $data_post );
				foreach ( $data_post as $key => $value ) {
					if ( $value == '' ) {
						continue;
					}
					$shasign_arg[] = $key . '=' . $value;
				}

				$SHAsig               = hash( ePDQ_crypt::get_sha_method(), implode( $settings['shain'], $shasign_arg ) . $settings['shain'] );
				$data_post['SHASIGN'] = $SHAsig;

			}


			// Enable deeper debugging, useful for when the ePDQ team require data to debug.
			if ( defined( 'ePDQ_support_debug' ) ) {
				AG_ePDQ_Helpers::ag_log( print_r( $data_post, true ), 'debug', $this->debug );
			}

			// Post
			$result = AG_ePDQ_Helpers::remote_post( $environment_url, $data_post );

			$lines    = preg_split( '/\r\n|\r|\n/', $result['body'] );
			$response = array();
			foreach ( $lines as $line ) {
				$key_value = preg_split( '/=/', $line, 2 );
				if ( count( $key_value ) > 1 ) {
					$response[ trim( $key_value[0] ) ] = trim( $key_value[1] );
				}
			}

			$accepted  = array( 8, 81, 85 ); // OK
			$status    = preg_replace( '/[^a-zA-Z0-9\s]/', '', $response['STATUS'] );
			$fullError = preg_replace( '/[^a-zA-Z0-9\s]/', '', $response['NCERRORPLUS'] );


			$string = implode( '|', $response );

			if ( ! is_wp_error( $result ) && $result['response']['code'] >= 200 && $result['response']['code'] < 300 ) {
				if ( in_array( $status, $accepted ) ) {
					$order->add_order_note(
						__( 'Refund successful', 'ag_epdq_server' ) . '<br />' .
						__( 'Refund Amount: ', 'ag_epdq_server' ) . $amount . '<br />' .
						__( 'Refund Reason: ', 'ag_epdq_server' ) . $reason . '<br />' .
						__( 'ePDQ Status: ', 'ag_epdq_server' ) . AG_errors::get_epdq_status_code( $status ) . ' '
					);

					return true;
				} else {

					$order->add_order_note( __( 'Refund failed', 'ag_epdq_server' ) . '<br />' . __( 'ePDQ Status: ', 'ag_epdq_server' ) . AG_errors::get_epdq_status_code( $status ) . '<br />' );
					$order->add_order_note( __( 'Refund Note', 'ag_epdq_server' ) . '<br /><strong>' . __( 'Error: ', 'ag_epdq_server' ) . $fullError . '</strong><br />' );
					// Log refund error
					AG_ePDQ_Helpers::ag_log( $string, 'debug', $this->debug );

					return new WP_Error( 'error', __( 'Refund failed: Please refresh this page and check the order notes, or the debug log.', 'ag_epdq_server' ) );
				}
			} else {
				// Log refund error
				AG_ePDQ_Helpers::ag_log( $string, 'debug', $this->debug );

				return new WP_Error( 'error', __( 'Refund failed: Please refresh this page and check the order notes, or the debug log.', 'ag_epdq_server' ) );
			}
		}
	}
}