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

if ( ! class_exists( 'YITH_WooCommerce_EU_VAT_Premium' ) ) {
	/**
	 * Admin class.
	 * The class manage all the admin behaviors.
	 *
	 * @since 1.0.0
	 */
	class YITH_WooCommerce_EU_VAT_Premium extends YITH_WooCommerce_EU_VAT {
		/**
		 * Set if vat number is a mandatory field
		 *
		 * @var bool
		 */
		public $mandatory_vat_number = false;

		/**
		 * Verify EU VAT number
		 *
		 * @var bool
		 */
		public $check_eu_vat_number = false;

		/**
		 * Allow invalid EU VAT number to go to checkout
		 *
		 * @var bool
		 */
		public $stop_invalid_eu_vat_number = false;

		/**
		 * @var string the message to prompt to the customer when the billing country do not match the geolocated country
		 */
		public $country_confirmation_message = '';

		/**
		 * @var string the message to show to the customer when the mandatory VAT number is not submitted at checkout
		 */
		public $missing_mandatory_vat_number_message = '';

		/**
		 * @var bool enable VAT number field for customer of the same shop country
		 */
		public $enable_vat_number_same_country = false;

		/**
		 * @var string|array base country/countries code
		 */
		public $base_country = '';

        /**
         * @var bool disable Vat exception for same country
         */
		public $disable_exception_in_same_country = false;

		/**
		 * Single instance of the class
		 *
		 * @since 1.0.0
		 */
		protected static $instance;

		/**
		 * @var YITH_YWEV_Backend_Premium
		 */
		public $admin = null;

		/**
		 * @var YITH_YWEV_Frontend_Premium
		 */
		public $public = null;

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

			parent::__construct();

		}

		public function includes() {
			parent::includes();

			if ( is_admin() ) {

                /**
                 * Including the GDRP
                 */
                add_action( 'plugins_loaded', array( $this, 'load_privacy' ), 20 );

				require_once( YITH_YWEV_LIB_DIR . '/class.yith-ywev-backend-premium.php' );

			}

			if ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
				require_once( YITH_YWEV_LIB_DIR . '/class.yith-ywev-frontend-premium.php' );
			}
		}

        /**
         * Including the GDRP
         */
        public function load_privacy() {

            if ( class_exists( 'YITH_Privacy_Plugin_Abstract' ) )
                require_once( YITH_YWEV_LIB_DIR . '/class.yith-woocommerce-eu-vat-privacy.php' );

        }

		/**
		 * Start backend instance
		 */
		public function load_backend() {
			$this->admin = YITH_YWEV_Backend_Premium::get_instance();
		}

		/**
		 * Start frontend instance
		 */
		public function load_frontend() {
			$this->public = YITH_YWEV_Frontend_Premium::get_instance();
		}

		/**
		 * Init plugin options
		 */
		public function init_plugin_options() {
			parent::init_plugin_options();
			$this->mandatory_vat_number                 = ( "yes" === get_option( 'ywev_mandatory_vat_number' ) ) ? true : false;
			$this->country_confirmation_message         = get_option( 'ywev_country_confirmation_message', '' );
			$this->missing_mandatory_vat_number_message = get_option( 'ywev_missing_vat_number_message', '' );
			$this->enable_vat_number_same_country       = "yes" == get_option( 'ywev_enable_vat_number_same_country', 'no' );
            $this->disable_exception_in_same_country       = "yes" == get_option( 'ywev_disable_vat_exception_same_country', 'no' );

			$custom_base_locations = get_option( "ywev_custom_base_location", '' );
			$this->base_country    = $custom_base_locations ?
				explode( ",", $custom_base_locations ) :
				array( WC()->countries->get_base_country() );

			// Set the default customer address to be geolocation
			update_option('woocommerce_default_customer_address', 'geolocation');
		}

		/**
		 * Use an external free service to check if a VAT number is a recognized EU
		 * VAT number for a specific country.
		 *
		 * @param string $vat_country the country of the customer
		 * @param string $vat_number  the VAT number of the customer
		 *
		 * @return string return a response between :
		 * "valid" for VAT number recognized as EU VAT number,
		 * "busy" if the server who provide the response is currently busy
		 * "invalid" for VAT number not recognized as EU VAT number for the specific country
		 * "error" for general error during the verification request.
		 */
		public function check_eu_vat_number_validity( $vat_country, $vat_number ) {

			//  before doing a request to an external service, see if a positive response was
			//  received during a previous request
			if ( WC()->session->get( $vat_country . $vat_number ) != null ) {

				return "valid";
			}

			$client = new SoapClient( "http://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl" );

			if ( $client ) {
				try {
					$resp = $client->checkVat( array(
						'countryCode' => $vat_country,
						'vatNumber'   => $vat_number,
					) );

					if ( $resp->valid == true ) {
						WC()->session->set( $vat_country . $vat_number, 1 );

						return "valid";
					}

					//  VAT number not valid

					return "invalid";
				} catch ( SoapFault $e ) {

					return "error";
				}
			}

			// Connection to host not possible
			return "busy";

		}

		/**
		 * Check if the current customer is from the EU area
		 *
		 * @param string $country_code the customer country code
		 *
		 * @return bool
		 */
		public function is_eu_country_code( $country_code ) {
			if ( ! isset( $country_code ) ) {
				return false;
			}

			//  ...and check if it's part of european countries
			$eu_vat_countries = WC()->countries->get_european_union_countries( 'eu_vat' );

			//  If it's not a european customer, nothing should be done
			if ( in_array( $country_code, $eu_vat_countries ) ) {
				return true;
			}

			return false;
		}

        /**
         * Action links
         *
         *
         * @return void
         * @since    1.3.5
         * @author   Daniel Sanchez <daniel.sanchez@yithemes.com>
         */
        public function action_links( $links ) {

            $links = yith_add_action_links( $links, $this->_panel_page, true );
            return $links;

        }

        /**
         * Plugin Row Meta
         *
         *
         * @return void
         * @since    1.3.5
         * @author   Daniel Sanchez <daniel.sanchez@yithemes.com>
         */
        public function plugin_row_meta( $new_row_meta_args, $plugin_meta, $plugin_file, $plugin_data, $status, $init_file = 'YITH_YWEV_INIT' ) {

            $new_row_meta_args = parent::plugin_row_meta( $new_row_meta_args, $plugin_meta, $plugin_file, $plugin_data, $status, $init_file );

            if ( defined( $init_file ) && constant( $init_file ) == $plugin_file ){
                $new_row_meta_args[ 'is_premium' ] = true;
            }

            return $new_row_meta_args;
        }

	}
}