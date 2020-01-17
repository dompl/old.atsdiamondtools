<?php
/**
 * EU VAT tax rates
 *
 * @author  Your Inspiration Themes
 * @package YITH WooCommerce EU VAT
 * @version 1.1.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'YITH_Tax_Rates' ) ) {
	/**
	 * Admin class.
	 * The class manage all the Frontend behaviors.
	 *
	 * @since 1.0.0
	 */
	class YITH_Tax_Rates {

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
		 * Retrieve preset tax rates
		 * @return mixed|void
		 */
		public function get_default_tax_rates() {

			$tax_rates_data = array(
				"AT" => array(
					"standard_rate" => 20.00,
					"reduced_rate"  => 13.00
				),
				"BE" => array(
					"standard_rate" => 21.00,
					"reduced_rate"  => 12.00
				),
				"BG" => array(
					"standard_rate" => 20.00,
					"reduced_rate"  => 9.00
				),
				"CY" => array(
					"standard_rate" => 19.00,
					"reduced_rate"  => 5.00
				),
				"CZ" => array(
					"standard_rate" => 21.00,
					"reduced_rate"  => 15.00
				),
				"DE" => array(
					"standard_rate" => 19.00,
					"reduced_rate"  => 7.00
				),
				"DK" => array(
					"standard_rate" => 25.00,
					"reduced_rate"  => 25.00
				),

				"EE" => array(
					"standard_rate" => 20.00,
					"reduced_rate"  => 9.00
				),
				"ES" => array(
					"standard_rate" => 21.00,
					"reduced_rate"  => 10.00
				),
				"FI" => array(
					"standard_rate" => 24.00,
					"reduced_rate"  => 14.00
				),
				"FR" => array(
					"standard_rate" => 20.00,
					"reduced_rate"  => 10.00
				),
				"GB" => array(
					"standard_rate" => 20.00,
					"reduced_rate"  => 5.00
				),
				"GR" => array(
					"standard_rate" => 24.00,
					"reduced_rate"  => 13.00
				),
				"HU" => array(
					"standard_rate" => 27.00,
					"reduced_rate"  => 18.00
				),
				"HR" => array(
					"standard_rate" => 25.00,
					"reduced_rate"  => 13.00
				),
				"IE" => array(
					"standard_rate" => 23.00,
					"reduced_rate"  => 13.50
				),
				"IT" => array(
					"standard_rate" => 22.00,
					"reduced_rate"  => 10.00
				),
				"LT" => array(
					"standard_rate" => 21.00,
					"reduced_rate"  => 9.00
				),
				"LU" => array(
					"standard_rate" => 17.00,
					"reduced_rate"  => 14.00
				),
				"LV" => array(
					"standard_rate" => 21.00,
					"reduced_rate"  => 12.00
				),
				"MT" => array(
					"standard_rate" => 18.00,
					"reduced_rate"  => 7.00
				),
				"NL" => array(
					"standard_rate" => 21.00,
					"reduced_rate"  => 6.00
				),

				"PL" => array(
					"standard_rate" => 23.00,
					"reduced_rate"  => 8.00
				),
				"PT" => array(
					"standard_rate" => 23.00,
					"reduced_rate"  => 13.00
				),
				"RO" => array(
					"standard_rate" => 20.00,
					"reduced_rate"  => 9.00
				),
				"SE" => array(
					"standard_rate" => 25.00,
					"reduced_rate"  => 12.00
				),
				"SI" => array(
					"standard_rate" => 22.00,
					"reduced_rate"  => 9.50
				),
				"SK" => array(
					"standard_rate" => 20.00,
					"reduced_rate"  => 10.00
				),
			);

			return apply_filters( 'yith_ywev_default_tax_rates', $tax_rates_data );
		}

		public function get_tax_rates() {
			global $wpdb;

			$tax_classes = ywev_get_tax_classes();
			foreach ( $tax_classes as &$class ) {
				$class = sanitize_title( $class );
			}

			$tax_classes[] = '';

			$query = sprintf( "SELECT * FROM {$wpdb->prefix}woocommerce_tax_rates
						WHERE tax_rate_class IN ('%s')
						ORDER BY tax_rate_order", implode( "','", array_values( $tax_classes ) )
			);

			return $wpdb->get_results( $query );
		}

		public function install_standard_rates() {
			//  delete previous inserted standard rates
            $tax_rates = $this->get_tax_rates();

            foreach ( $tax_rates as $tax_rate ) {
                foreach ($this->get_default_tax_rates() as $key => $value) {
                    $tax_rate_code = $tax_rate->tax_rate_country;

                    if ($tax_rate_code == $key) {
                        WC_Tax::_delete_tax_rate($tax_rate->tax_rate_id);
                    }
                }
			}

			foreach ( $this->get_default_tax_rates() as $key => $value ) {

				$tax_rate = array(
					'tax_rate_country'  => $key,
					'tax_rate_state'    => '*',
					'tax_rate'          => $value["standard_rate"],
					'tax_rate_name'     => sprintf( "EU VAT (%s) %s%%", $key, $value["standard_rate"] ),
					'tax_rate_priority' => 1,
					'tax_rate_compound' => 1,
					'tax_rate_shipping' => 1,
					'tax_rate_class'    => ''
				);

				$tax_rate_id = WC_Tax::_insert_tax_rate( $tax_rate );
				WC_Tax::_update_tax_rate_postcodes( $tax_rate_id, wc_clean( '*' ) );
				WC_Tax::_update_tax_rate_cities( $tax_rate_id, wc_clean( '*' ) );
			}
		}
	}
}