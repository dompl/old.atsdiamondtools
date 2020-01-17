<?php
/**
 * Premium version class
 *
 * @author  Your Inspiration Themes
 * @package YITH WooCommerce EU VAT
 * @version 1.0.0
 */

if ( ! defined ( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists ( 'YITH_YWEV_Frontend_Premium' ) ) {
	/**
	 * Admin class.
	 * The class manage all the admin behaviors.
	 *
	 * @since 1.0.0
	 */
	class YITH_YWEV_Frontend_Premium extends YITH_YWEV_Frontend {
		
		/**
		 * Single instance of the class
		 *
		 * @since 1.0.0
		 */
		protected static $instance;
		
		/**
		 * Returns single instance of the class
		 *
		 * @since 1.0.0
		 */
		public static function get_instance() {
			if ( is_null ( self::$instance ) ) {
				self::$instance = new self();
			}
			
			return self::$instance;
		}
		
		/**
		 * Constructor
		 *
		 * @access public
		 * @since  1.0.0
		 */
		public function __construct() {
			parent::__construct ();
		}
		
		public function includes() {
			parent::includes ();
		}
		
		public function init_hooks() {
			parent::init_hooks ();
			
			/**
			 * Enqueue plugin scripts
			 */
			add_action ( 'wp_footer', array(
				$this,
				'enqueue_scripts'
			) );
            			
			/**
			 * Check EU VAT numbers validity during checkout
			 */
			add_action ( 'wp_ajax_check_eu_vat_number', array(
				$this,
				'check_eu_vat_number_callback'
			) );
			add_action ( 'wp_ajax_nopriv_check_eu_vat_number', array(
				$this,
				'check_eu_vat_number_callback'
			) );
			
			/**
			 * Check for digital goods on current cart that should be VAT exempt
			 */
			add_filter ( 'woocommerce_after_calculate_totals', array(
				$this,
				'calculate_taxes_exemption'
			) );
			
			/**
			 * Store additional data on checkout
			 */
			add_filter ( 'yith_ywev_storing_data', array(
				$this,
				'store_geo_data'
			) );
			
			/**
			 * Show the amount of VAT tax that is removed from checkout when a valid EU VAT number is entered by a european customer
			 */
			add_action ( 'woocommerce_review_order_before_order_total', array(
				$this,
				'show_eu_vat_amount_on_checkout',
			) );
			
			add_action ( 'woocommerce_after_checkout_validation', array(
				$this,
				'check_country_confirmation'
			) );
			
			add_action ( 'woocommerce_after_checkout_validation', array(
				$this,
				'check_mandatory_vat_number'
			) );
			
			add_action ( 'woocommerce_review_order_before_submit', array(
				$this,
				'show_country_confirmation_field'
			) );

            add_action ( 'ywcsb_after_calculate_totals', array(
               $this,
               'ywcsb_after_calculate_totals_call_back_yith_ywev'
            ), 10, 1 );



            //Store the vat number in the user account fields
            add_action( 'woocommerce_save_account_details', array(
                $this,
                'woocommerce_save_account_details_eu_vat_field'
            ), 12, 1 );

            add_action( 'woocommerce_edit_account_form', array(
                $this,
                'woocommerce_edit_account_form_eu_vat_field'
            ), 10 );

		}

        public function ywcsb_after_calculate_totals_call_back_yith_ywev( $order )
        {
            $post_data_array = array();
            //  Need $_POST['post_data'] to be set in order to extract checkout data
            if (isset($_POST['post_data'])) {

                parse_str ( $_POST['post_data'], $post_data_array );
                $billing_country = isset( $post_data_array['billing_country'] ) ? $post_data_array['billing_country'] : '';
                $ywev_vat_number = isset( $post_data_array['billing_yweu_vat'] ) ? $post_data_array['billing_yweu_vat'] : '';

            } else {
                $billing_country = isset($_POST['billing_country']) ? sanitize_text_field($_POST['billing_country']) : '';
                $ywev_vat_number = isset($_POST['billing_yweu_vat']) ? sanitize_text_field($_POST['billing_yweu_vat']) : '';
            }

            $ywev_vat_number = str_replace(' ', '', $ywev_vat_number);

            //  Without billing country and VAT number, no exemption can be calculated
            if ($billing_country && $ywev_vat_number) {

                //  If the billing country is not an EU country, nothing has to be calculated
                if (YITH_YWEV()->is_eu_country_code($billing_country)) {

                    //  Check if there is a VAT number and if it was validated, if it is affirmative, remove the taxes for digital goods
                    if (WC()->session->get("ywev_validated") == $this->get_full_vat_number($ywev_vat_number, $billing_country)) {

                        $geo_country = $this->get_geolocated_country_code();

                        $remove_tax = apply_filters('yith_ywev_customer_taxes_exemption', true, $geo_country, $billing_country);

                        if ($remove_tax) {

                            $allow_physical = 'yes' == get_option('ywev_apply_to_physical', 'no');

                            if ($order instanceof WC_Order) {
                                $save_order = false;
                                $tax_total = 0;
                                $tax_total_array = array();
                                $tax_data = array(
                                    'total' => 0,
                                    'subtotal' => 0,
                                );

                                foreach ($order->get_items() as $order_item_id => $order_item_data) {

                                    $product_id = $order_item_data["product_id"];
                                    $product = wc_get_product($product_id);

                                    if (!$product->is_taxable()) {
                                        continue;
                                    }

                                    if (!$product->is_virtual() && !$allow_physical) {
                                        continue;
                                    }

                                    $item_taxes = $order_item_data->get_taxes();
                                    if (!empty($item_taxes['total'])) {
                                        $tax_total += array_sum($item_taxes['total']);
                                        foreach ($item_taxes['total'] as $tax_rate_id => $tax_amount) {
                                            if (!isset($tax_total_array[$tax_rate_id])) {
                                                $tax_total_array[$tax_rate_id] = 0;
                                            }

                                            $tax_total_array[$tax_rate_id] += $tax_amount;
                                        }
                                    }
                                    $order_item_data->set_taxes($tax_data);
                                    $order_item_data->save();
                                    $save_order = true;


                                }

                                if ($save_order) {
                                    $order_total_tax = $order->get_tax_totals();


                                    if (!empty($order_total_tax)) {
                                        $order_total_tax_temp = $order_total_tax;
                                        foreach ($order_total_tax_temp as $tax_id => $tax_object) {
                                            if (!empty($tax_total_array[$tax_object->rate_id])) {
                                                $order_total_tax[$tax_object->rate_id] = ($order_total_tax[$tax_id]->amount - $tax_total_array[$tax_object->rate_id]);
                                            }
                                        }

                                        foreach ($order->get_taxes() as $tax_item_id => $tax) {
                                            /** @var WC_Order_Item_Tax $tax */
                                            if (isset($order_total_tax[$tax->get_rate_id()])) {
                                                $tax->set_tax_total($order_total_tax[$tax->get_rate_id()]);
                                                $tax->save();
                                            }
                                        }
                                    }

                                    $order->set_total($order->get_total() - $tax_total);
                                    $order->save();

                                }

                            }

                        }

                    }

                }

            }

        }
		
		/**
		 * Show a field for manual confirmation of the customer base location if the billing country does not match the (EU) geolocated country
		 */
		public function show_country_confirmation_field() {
			
			$customer_country = version_compare ( WC ()->version, '2.7.0', '<' ) ? WC ()->customer->get_country () : WC ()->customer->get_billing_country ();
			
			if ( $this->need_country_confirmation ( $customer_country ) ) {
				$countries = WC ()->countries->get_countries ();
				$country   = $countries[ $customer_country ];
				wc_get_template ( 'yith-eu-vat/country-confirmation.php',
					array(
						'country' => $country,
					),
					'',
					trailingslashit ( YITH_YWEV_TEMPLATE_DIR ) );
			}
		}
		
		/**
		 * Add scripts for dealing with checkout page elements
		 */
		public function enqueue_scripts() {
			if ( ! is_checkout () ) {
				return;
			}

            //  register and enqueue ajax calls related script file
            wp_register_script ( "ywev-checkout",
                apply_filters( 'yith_ywev_script_checkout_register_url' ,	YITH_YWEV_SCRIPT_URL . yit_load_js_file ( 'ywev-checkout.js' ) ),
                array( 'jquery' ),
                YITH_YWEV_VERSION,
                true );
			
			wp_localize_script ( 'ywev-checkout',
				'ywev',
				array(
					'eu_vat_field_enabled_countries'        => '["' . implode ( '","', $this->get_country_for_vat_number_field () ) . '"]',
					'base_countries'                        => YITH_YWEV ()->base_country,
					'geo_country'                           => $this->get_geolocated_country_code (),
					'mandatory_vat_number'                  => YITH_YWEV ()->mandatory_vat_number,
                    'disable_vat_exception_same_country'    =>  YITH_YWEV()->disable_exception_in_same_country,
					'loader'                                => apply_filters ( 'yith_ywev_loader_gif', YITH_YWEV_ASSETS_URL . '/images/loading.gif' ),
					'message_country_conflicted'            => __ ( "I confirm I reside in {user_country}", 'yith-woocommerce-eu-vat' ),
					'message_confirm_option1'               => __ ( "Calculate normal tax rates.", 'yith-woocommerce-eu-vat' ),
					'message_confirm_option2'               => __ ( "I'm from", 'yith-woocommerce-eu-vat' ),
					'message_confirm_option2b'              => __ ( "apply the EU VAT reverse charge.", 'yith-woocommerce-eu-vat' ),
				) );
			
			wp_enqueue_script ( 'ywev-checkout' );
		}
		
		/**
		 * Get EU countries for whose the EU VAT field should be shown
		 *
		 * @return array
		 */
		public function get_country_for_vat_number_field() {
			
			$enabled_countries = array_values ( WC ()->countries->get_european_union_countries ( 'eu_vat' ) );
			
			if ( ! YITH_YWEV ()->enable_vat_number_same_country ) {
				
				$shop_base_location = YITH_YWEV ()->base_country;
				
				foreach ( $shop_base_location as $location ) {
					if ( ( $key = array_search ( $location, $enabled_countries ) ) !== false ) {
						unset( $enabled_countries[ $key ] );
					}
				}
			}
			
			return $enabled_countries;
		}
		
		/**
		 * Show additional fields base don plugin settings.
		 *
		 * @param WC_Checkout $checkout
		 */
		public function show_eu_vat_field_on_checkout( $checkout ) {
			?>
			<?php
			// Add VAT number field on checkout form
			woocommerce_form_field ( 'ywev_vat_number', array(
				'type'        => 'text',
				'description' => get_option('ywev_eu_vat_field_description',__ ( 'European companies with valid EU VAT number will be exempt of VAT.', 'yith-woocommerce-eu-vat' )),
				'class'       => array( 'form-row-wide update_totals_on_change' ),
				'label'       => get_option('ywev_eu_vat_field_label',__ ( 'VAT number', 'yith-woocommerce-eu-vat' )),
				'placeholder' => get_option('ywev_eu_vat_field_placeholder',__ ( 'Enter your VAT number', 'yith-woocommerce-eu-vat' )),
				'required'    => YITH_YWEV ()->mandatory_vat_number,
				'default'	  => apply_filters('yith_eu_vat_default_number',''),
			), $checkout->get_value ( 'ywev_vat_number' ) );
			?>
			<?php
		}
		
		/**
		 * Retrieve a vat number in format [country_code][vat_number]. Additional country code used in VAT number will be removed
		 *
		 * @param string $vat_number the VAT number
		 * @param string $country    the country code
		 *
		 * @return string
		 */
		public function get_full_vat_number( $vat_number, $country ) {

            //Fix a problem with the Greece country code and VAT code
            if( $country === 'GR') {
                $country = 'EL';
            }

			if ( 0 === strpos ( $vat_number, $country ) ) {
				$vat_number = substr ( $vat_number, strlen ( $country ) );
			}
			
			return $country . $vat_number;
		}
		
		/**
		 * Verify the if a VAT number is a valid EU VAT number
		 *
		 * @since  1.0
		 * @author Lorenzo giuffrida
		 */
		public function check_eu_vat_number_callback() {


            $vat_number = isset($_POST['vat_number']) ? str_replace ( ' ', '', sanitize_text_field ( $_POST['vat_number'] ) ) : '';

            $selected_country = isset($_POST['selected_country']) ? sanitize_text_field($_POST['selected_country']) : '';
            
            $shipping_country = isset($_POST['shipping_country']) ? sanitize_text_field($_POST['shipping_country']) : '';

            $billing_company = isset($_POST['billing_company']) ? sanitize_text_field($_POST['billing_company']) : '';

            if ( isset( $vat_number ) && isset( $selected_country ) ) {
				
				//  check if current country is on EU country list or return
				if ( ! YITH_YWEV ()->is_eu_country_code ( $selected_country ) ) {
					wp_send_json ( array( "code" => 0 ) );
				}

                //Fix a problem with the Greece country code and VAT code
                if( $selected_country === 'GR') {
                    $selected_country = 'EL';
                }
				
				if ( 0 === strpos ( $vat_number, $selected_country ) ) {
					$vat_number = substr ( $vat_number, strlen ( $selected_country ) );
				}
				$vat_number_merged = $selected_country . $vat_number;
				
				//  Do not do another call to VIES server if the EU VAT was previously validated
				$last_valid_vat_number = WC ()->session->get ( "ywev_validated" );

                $matching_vat = $last_valid_vat_number == $vat_number_merged;
                $already_validated_and_valid = apply_filters( 'ywev_vat_already_validated_and_valid', $matching_vat, $shipping_country, $billing_company );

                if ( $already_validated_and_valid ) {
					wp_send_json ( array(
						"code"  => 1,
						"value" => __ ( 'Valid EU VAT number.', 'yith-woocommerce-eu-vat' )
					) );
				}
				
				//  Call VIES system
				$response = apply_filters( 'ywev_vat_validation_response', YITH_YWEV ()->check_eu_vat_number_validity ( $selected_country, $vat_number ), $selected_country, $vat_number, $shipping_country, $billing_company );
				
				if ( "valid" == $response ) {
					WC ()->session->set ( "ywev_validated", $vat_number_merged );
					wp_send_json ( array(
						"code"  => 1,
						"value" => __ ( "Valid EU VAT number.", 'yith-woocommerce-eu-vat' )
					) );
					
				} else {
					wp_send_json ( array(
						"code"  => - 1,
						"value" => sprintf ( __ ( "%s is not a valid EU VAT number or the VAT number can't be validated at the moment.", 'yith-woocommerce-eu-vat' ), $vat_number_merged ),
					) );
				}
			}
			
			wp_send_json ( array( "code" => 0 ) );
		}
		
		
		/**
		 * Get the customer country code.
		 *
		 * @return bool|array the country code or false if something goes wrong
		 */
		public function get_geolocated_country_code() {
			$data = WC_Geolocation::geolocate_ip ( $this->get_client_ip_address () );
			if ( is_array ( $data ) && isset( $data['country'] ) ) {
				return $data['country'];
			}
			
			return false;
		}
		
		
		/**
		 * Extract client ip address
		 *
		 * @return string   ip address
		 */
		private function get_client_ip_address() {
			return getenv ( 'HTTP_CLIENT_IP' ) ?:
				getenv ( 'HTTP_X_FORWARDED_FOR' ) ?:
					getenv ( 'HTTP_X_FORWARDED' ) ?:
						getenv ( 'HTTP_FORWARDED_FOR' ) ?:
							getenv ( 'HTTP_FORWARDED' ) ?:
								getenv ( 'REMOTE_ADDR' );
		}
		
		/**
		 * Calculate the tax exemption for digital goods on current cart.
		 *
		 * @param WC_Cart $cart current cart object
		 *
		 * @return object cart
		 */
		public function calculate_taxes_exemption( $cart ) {

			$eu_vat_exempt_amount = 0;
            $post_data_array = array();

			//  Need $_POST['post_data'] to be set in order to extract checkout data
			if ( isset( $_POST['post_data'] ) ) {
                parse_str ( $_POST['post_data'], $post_data_array );
                $billing_country = isset( $post_data_array['billing_country'] ) ? $post_data_array['billing_country'] : '';
                $billing_yweu_vat = isset( $post_data_array['billing_yweu_vat'] ) ? $post_data_array['billing_yweu_vat'] : '';
			} else {
				$billing_country = isset( $_POST['billing_country'] ) ? sanitize_text_field($_POST['billing_country']) : '';
                $billing_yweu_vat = isset($_POST['billing_yweu_vat']) ? sanitize_text_field($_POST['billing_yweu_vat']) : '';
			}


            if (isset($billing_yweu_vat) && $billing_yweu_vat != '') {
                $billing_yweu_vat = str_replace(' ', '', $billing_yweu_vat);
            } else {
                $billing_yweu_vat = '';
            }

        
            $cart_shipping_taxes = $cart->get_shipping_taxes();
            $cart_shipping_tax = $cart->get_shipping_tax();
            $cart_fees_array = $cart->get_fees();
            $cart_total_ex_tax = $cart->get_total_ex_tax();

            //  Without billing country and VAT number, no exemption can be calculated
			if ( $billing_country && $billing_yweu_vat ) {

				//  If the billing country is not an EU country, nothing has to be calculated
				if ( YITH_YWEV ()->is_eu_country_code ( $billing_country ) ) {

					//  Check if there is a VAT number and if it was validated, if it is affirmative, remove the taxes for digital goods
					if ( WC ()->session->get ( "ywev_validated" ) == $this->get_full_vat_number ( $billing_yweu_vat, $billing_country ) ) {

						$geo_country = $this->get_geolocated_country_code ();

						$remove_tax = apply_filters ( 'yith_ywev_customer_taxes_exemption', true, $geo_country, $billing_country );

						if ( $remove_tax ) {

							$allow_physical = 'yes' == get_option ( 'ywev_apply_to_physical', 'no' );

							foreach ( $cart->cart_contents as $cart_item_key => $values ) {
								/** @var WC_Product $_product */
								$_product = $values['data'];

								if ( ! $_product->is_taxable () ) {
									continue;
								}

								if ( ! $_product->is_virtual () && ! $allow_physical ) {
									continue;
								}

								//  if the customer in from an european country and he inserted a valid EU VAT number, remove tax if the tax class is on the list of tax class to be monitored from the plugin option page.
								$eu_vat_tax_used_list = get_option ( 'ywev_eu_vat_tax_list', array() );

								$line_tax_data = $values["line_tax_data"];

								$line_tax_data_total = $line_tax_data["total"];

                                foreach ( $line_tax_data_total as $line_tax_data_key => $line_tax_data_values ) {
                                    $line_tax_data_values = round($line_tax_data_values , wc_get_price_decimals());
                                    //  Check if the tax rate is on the list of tax rate monitored by the plugin
									if ( isset( $eu_vat_tax_used_list[ $line_tax_data_key ] ) ) {

										$eu_vat_exempt_amount += $line_tax_data_values;

										$cart->set_cart_contents_tax(0);
                                        $cart->set_cart_contents_taxes(array($line_tax_data_key => 0));

                                        $cart->set_total_tax(0);
                                        $cart->set_subtotal_tax(0);

                                        //Necessary to display a zero tax in the order page
                                        $cart->cart_contents[ $cart_item_key ]["line_tax_data"]["total"][ $line_tax_data_key ] = 0;
                                        $cart->cart_contents[ $cart_item_key ]["line_tax_data"]["subtotal"][ $line_tax_data_key ] = 0;
                                    }
								}

                                foreach ( $cart_shipping_taxes as $cart_shipping_taxes_key => $cart_shipping_taxes_value) {
                                    if ( isset( $eu_vat_tax_used_list[ $cart_shipping_taxes_key ] ) ) {

                                        $eu_vat_exempt_amount += $cart_shipping_tax;

                                        $cart->set_shipping_tax(0);
                                        $cart->set_shipping_taxes(array($cart_shipping_taxes_key => 0));
                                    }
                                }

                                if ( isset( $cart_fees_array ) ){

                                    foreach ( $cart_fees_array as $key_fee => $value_fee ){

                                        $fee_obj = $value_fee->data_source;
                                        $fee_tax = $fee_obj->tax_amount;
                                        $eu_vat_exempt_amount += $fee_tax;
                                        
                                        $cart->set_fee_taxes( array($key_fee => 0));
                                    }
                                    
                                    $cart->set_fee_tax(0);
                                }
							}

                            $cart->set_total( $cart_total_ex_tax );

                            $cart->set_session ();
                        }
					}
				}
			}

            //  Update the VAT exemption amount for the current cart
			WC ()->session->set ( "ywev_vat_exemption_amount", $eu_vat_exempt_amount);
			
			return $cart;
		}
		
		/**
		 * Check if there is a need for asking a country confirmation
		 *
		 * @param string $customer_country_code the country code selected by the customer
		 *
		 * @return bool
		 */
		public function need_country_confirmation( $customer_country_code ) {
			
			$ask_confirmation = false;
			
			if ( $this->cart_contains_digital_goods () ) {
				$geo_located_country = $this->get_geolocated_country_code ();
				
				if ( ( $geo_located_country != $customer_country_code ) &&
				     ( YITH_YWEV ()->is_eu_country_code ( $customer_country_code ) ||
				       YITH_YWEV ()->is_eu_country_code ( $geo_located_country ) )
				) {
					$ask_confirmation = true;
				}
			}
			
			return $ask_confirmation;
		}
		
		/**
		 * Prevent the checkout processing if there are conflicts between the country geo located and the country the user set as billing country and one
		 * of them in an EU country
		 *
		 */
		public function check_country_confirmation() {
			
			if ( ! isset( $_POST['billing_country'] ) ) {
				return;
			}
			
			$billing_country     = $_POST['billing_country'];
			$manual_confirmation = false;
			$country_conflict    = $this->need_country_confirmation ( $billing_country );
			
			if ( $country_conflict ) {
				//  Check for manual confirmation input
				if ( isset( $_POST['ywev-country-confirmation'] ) && ( 'on' == $_POST['ywev-country-confirmation'] ) ) {
					$manual_confirmation = true;
				} else {
					wc_add_notice ( apply_filters ( 'yith_ywev_country_not_match_notification', YITH_YWEV ()->country_confirmation_message ), 'error' );
					
					return;
				}
			}
			
			WC ()->session->set ( "ywev_country_conflicted", $country_conflict );
            WC ()->session->set ( "billing_yweu_vat", isset( $_POST['billing_yweu_vat'] ) ? $_POST['billing_yweu_vat'] : '' );
			WC ()->session->set ( "ywev_country_confirmed", $manual_confirmation );
		}
		
		/**
		 * Check if a mandatory VAT number was submitted by the user at checkout, or stop it.
		 */
		public
		function check_mandatory_vat_number() {
			
			if ( ! YITH_YWEV ()->mandatory_vat_number ) {
				return;
			}

            if ( ! isset( $_POST['billing_yweu_vat'] ) || ! $_POST['billing_yweu_vat'] ) {
				wc_add_notice ( apply_filters ( 'yith_ywev_missing_mandatory_vat_number_notification', YITH_YWEV ()->missing_mandatory_vat_number_message ), 'error' );
				
				return;
			}
		}
		
		/**
		 * Store additional data related to EU VAT laws on checkout
		 *
		 * @param $order_storing_data
		 */
		public
		function store_geo_data($order_storing_data) {

			$order_storing_data["eu_vat_amount"]              = WC ()->session->get ( "ywev_vat_exemption_amount" );
            $order_storing_data["vat_number"]                 = WC ()->session->get ( "billing_yweu_vat" );
			$order_storing_data["country_confirmed"]          = WC ()->session->get ( "ywev_country_confirmed" );
			$order_storing_data["eu_cart_with_digital_goods"] = $this->cart_contains_digital_goods ();
			
			$taxable_location = &$order_storing_data['Localization'];
			
			if ( ! isset( $taxable_location ) ) {
				return;
			}
			
			$taxable_location["GEO_COUNTRY"] = $this->get_geolocated_country_code ();
			$taxable_location["IP_ADDRESS"]  = $this->get_client_ip_address ();

			return $this->unserialize_order_storing_data( $order_storing_data );
		}

        /**
         * unserualize $order_storing_data
         *
         * @param $order_storing_data_serialized
         *
         * @return array
         */
        public function unserialize_order_storing_data( $order_storing_data_serialized ) {

            $order_storing_data[ "ywev_vat_exemption_amount" ] = $order_storing_data_serialized[ "eu_vat_amount" ];
            $order_storing_data[ "yweu_billing_vat" ] = $order_storing_data_serialized["vat_number"];
            $order_storing_data[ "ywev_country_confirmed" ] = $order_storing_data_serialized["country_confirmed"];
            $order_storing_data[ "ywev_eu_cart_with_digital_goods" ] = $order_storing_data_serialized["eu_cart_with_digital_goods"];

            $taxable_location = $order_storing_data_serialized['Localization'];

            $order_storing_data[ "ywev_COUNTRY" ] = $taxable_location[ "COUNTRY" ];
            $order_storing_data[ "ywev_STATE" ] = $taxable_location[ "STATE" ];
            $order_storing_data[ "ywev_POST_CODE" ] = $taxable_location[ "POST_CODE" ];
            $order_storing_data[ "ywev_CITY" ] = $taxable_location[ "CITY" ];

            $order_storing_data[ "ywev_GEO_COUNTRY" ] = $taxable_location[ "GEO_COUNTRY" ];
            $order_storing_data[ "ywev_IP_ADDRESS" ] = $taxable_location[ "IP_ADDRESS" ];

            return $order_storing_data;
        }
		
		/**
		 * Show the amount of VAT tax that is removed from checkout when a valid EU VAT number is entered by a european customer
		 */
		public function show_eu_vat_amount_on_checkout() {
			
			$eu_vat_exempt_amount = WC ()->session->get ( "ywev_vat_exemption_amount" );
			
			if ( $eu_vat_exempt_amount > 0 ) :
				?>
				
				<tr class="eu-vat-amount">
					<th><?php _e ( 'EU VAT exempted', 'yith-woocommerce-eu-vat' ); ?></th>
					<td>
						<del><span class="amount"><?php echo wc_price ( $eu_vat_exempt_amount ); ?></span></del>
					</td>
				</tr>
				<?php
			endif;
		}

        //Store the vat number in the user account
        function woocommerce_edit_account_form_eu_vat_field() {
            $user = wp_get_current_user();

            $eu_vat_number = get_user_meta( $user->ID , 'yweu_vat_number', true );

            ?>
            <p class="woocommerce-form-row woocommerce-form-row--first form-row form-row-first">
                <label for="yweu_vat_number"><?php _e( 'VAT Number', 'woocommerce' ); ?>
                    <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="yweu_vat_number" id="yweu_vat_number" value="<?php echo $eu_vat_number; ?>" />
            </p>
            <div class="clear"></div>
            <br>
            <?php
        }

        function woocommerce_save_account_details_eu_vat_field( $user_id ) {

            if( isset( $_POST['yweu_vat_number'] ) )
                update_user_meta( $user_id, 'yweu_vat_number', sanitize_text_field( $_POST['yweu_vat_number'] ) );
        }


	}
}