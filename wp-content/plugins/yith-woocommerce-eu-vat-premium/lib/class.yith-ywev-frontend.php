<?php
/**
 * Premium version class
 *
 * @author  Your Inspiration Themes
 * @package YITH WooCommerce EU VAT
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'YITH_YWEV_Frontend' ) ) {
	/**
	 * Admin class.
	 * The class manage all the admin behaviors.
	 *
	 * @since 1.0.0
	 */
	class YITH_YWEV_Frontend {

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
			if ( is_null( self::$instance ) ) {
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
			$this->includes();
			$this->init_hooks();
		}

		public function includes() {

		}

		public function init_hooks() {

			add_action( 'woocommerce_checkout_order_processed', array(
				$this,
				'store_checkout_order_data'
			),10,3 );

			add_action( 'woocommerce_check_cart_items', array(
				$this,
				'check_eu_customer_cart'
			) );

			add_action( 'woocommerce_checkout_process', array(
				$this,
				'check_eu_customer_checkout'
			) );
		}

		/**
		 * Show a warning for eu customer when they go to cart page
		 */
		public function check_eu_customer_cart() {

			if ( $this->cart_contains_product_not_purchasable() && YITH_YWEV()->show_forbid_warning ) {
				wc_add_notice( YITH_YWEV()->forbid_warning_message, 'notice' );

				return;
			}
		}

		public function check_eu_customer_checkout() {

			if ( $this->cart_contains_product_not_purchasable() && YITH_YWEV()->is_checkout_forbidden ) {

				wc_add_notice( YITH_YWEV()->forbid_error_message, 'error' );

				return;
			}
		}

		public function store_checkout_order_data( $order_id, $posted_data, $order ) {

		    if( $posted_data['billing_yweu_vat'] == '' )
		        return;

			$order_storing_data          = array();
			$order_storing_data["taxes"] = array();
			$order_storing_data_taxes    = &$order_storing_data["taxes"];

			$eu_vat_tax_used_list = get_option( 'ywev_eu_vat_tax_list', array() );

			//  Collect information about the order taxes...
			$order   = wc_get_order( $order_id );

			$line_items     = $order->get_items( 'line_item' );
			$allow_physical = 'yes' == get_option( 'ywev_apply_to_physical', 'no' );

			foreach ( $line_items as $item_id => $item ) {

				$_product  = is_object ( $item ) ? $item->get_product () : $order->get_product_from_item( $item );

				//  Only for products set as virtual
				if ( ! isset( $_product ) ) {
					continue;
				}

				if ( ! ( $allow_physical || $_product->is_virtual() ) ) {
					continue;
				}

				$line_tax_data = isset( $item['line_tax_data'] ) ? $item['line_tax_data'] : '';


                $tax_data      = maybe_unserialize( $line_tax_data );

                if ( isset( $tax_data['total'] ) ) {
					$tax_data_total = $tax_data['total'];


                    foreach ( $tax_data_total as $key => $value ) {

						//  check if tax rate is on the list of selected tax rate to be recorded for EU VAT reporting
						if ( isset( $eu_vat_tax_used_list[ $key ] ) ) {
							if ( isset( $order_storing_data_taxes[ $key ] ) ) {
								$order_storing_data_taxes[ $key ] += $value;
							} else {
								$order_storing_data_taxes[ $key ] = $value;
							}
						}
					}
				}
			}

			//... add data about customer location
			$location = WC()->customer->get_taxable_address();
			//  the result should be an array as follow :
			//  of array( $country, $state, $postcode, $city ) );
			if ( is_array( $location ) && ( 4 == count( $location ) ) ) {
				$taxable_location["COUNTRY"]   = $location[0];
				$taxable_location["STATE"]     = $location[1];
				$taxable_location["POST_CODE"] = $location[2];
				$taxable_location["CITY"]      = $location[3];

				$order_storing_data['Localization'] = $taxable_location;
			}

            // storing the taxes of the old behaviour of the plugin before version 1.3.3
            // this meta '_ywev_order_vat_paid' will store only the taxes
            yit_save_prop( $order, '_ywev_order_vat_paid', apply_filters( 'yith_ywev_storing_data_taxes', $order_storing_data["taxes"] ) );

            $order_storing_data = apply_filters( 'yith_ywev_storing_data', $order_storing_data );

            foreach ( $order_storing_data as $key => $value ){

                yit_save_prop( $order, $key, $value );

            }

            // Fix to change the shipping tax value to 0
            foreach( $order->get_items( 'shipping' ) as $shipping_item_id => $shipping_item_obj ){

                if ( $order_storing_data['ywev_vat_exemption_amount'] > 0){
                    wc_update_order_item_meta($shipping_item_id, 'taxes', wc_price( 0 ));
                }
            }

		}

		/**
		 * Tell if the customer is from an EU country and the cart contains a digital goods
		 * @return bool
		 */
		public function cart_contains_product_not_purchasable() {

			$taxable_address = WC()->customer->get_taxable_address();
			$country_code    = $taxable_address[0];

			if ( ! YITH_YWEV()->is_eu_country_code( $country_code ) ) {
				return false;
			}

			return $this->cart_contains_digital_goods();
		}


		/**
		 * Check if the current cart should be checked for tax usage
		 *
		 * @return bool
		 */
		public function should_check_eu_taxes() {
			//  if it shouldn't warning a eu customer, nothing should be done
			if ( YITH_YWEV()->is_checkout_forbidden ) {
				return false;
			}

			//  If woocommerce will not calculate taxes, nothing should be done for eu VAT compliance
			$is_woocommerce_calc_taxes = get_option( 'woocommerce_calc_taxes' );
			if ( 'yes' != $is_woocommerce_calc_taxes ) {
				return false;
			}

			return true;
		}

		/**
		 * Check if current cart contains digital goods to be treated with EU VAT laws
		 *
		 * @return bool
		 */
		public function cart_contains_digital_goods() {
			//  Check if the cart contains product that fall into EU VAT compliance and stop checkout if the customer if from EU area.
			$items_on_cart = WC()->cart->get_cart();

			foreach ( $items_on_cart as $cart_item ) {
				if ( empty( $cart_item['data'] ) ) {
					continue;
				}

				$_product   = $cart_item['data'];
				$tax_status = $_product->get_tax_status();

				//  Warning the customer if the product is subjected to EU VAT laws
				if ( $_product->is_virtual() && 'taxable' == $tax_status ) {
					return true;
				}
			}

			return false;
		}
	}
}