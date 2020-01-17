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

if ( ! class_exists( 'YITH_YWEV_Backend_Premium' ) ) {
	/**
	 * Admin class.
	 * The class manage all the admin behaviors.
	 *
	 * @since 1.0.0
	 */
	class YITH_YWEV_Backend_Premium extends YITH_YWEV_Backend {
		
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
			parent::__construct();
		}
		
		public function init_hooks() {
			parent::init_hooks();
			
			/**
			 * Add metabox on order, to let vendor add order tracking code and carrier
			 */
			add_action( 'add_meta_boxes', array(
				$this,
				'add_invoice_metabox'
			) );
			
			/**
			 * Force the download of Maxmind DB for geolocation
			 */
			add_filter( 'woocommerce_geolocation_update_database_periodically', array(
				$this,
				'__return_true'
			) );

            add_filter( 'woocommerce_customer_meta_fields', array( $this,'customize_admin_user_fields' ) );
            add_filter( 'woocommerce_ajax_get_customer_details', array( $this,'get_eu_vat_admin_user_fields' ),10,3 );

        }
		
		
		/**
		 *  Add a metabox on backend order page, to be filled with order tracking information
		 *
		 * @since  1.0
		 * @author Lorenzo giuffrida
		 * @access public
		 * @return void
		 */
		function add_invoice_metabox() {
			add_meta_box( 'ywev-order-data',
				__( 'EU VAT', 'yith-woocommerce-eu-vat' ), array(
					$this,
					'show_eu_vat_metabox',
				), 'shop_order', 'side', 'high' );
		}
		
		/**
		 * Show data stored during che checkout process
		 *
		 * @param WP_Post $post the order object that is currently shown
		 *
		 * @since  1.0
		 * @author Lorenzo giuffrida
		 * @access public
		 * @return void
		 */
		function show_eu_vat_metabox( $post ) {

            //  retrieve stored data for current order
            $order       = wc_get_order( $post->ID );
            $eu_vat_data = yit_get_prop( $order, '_ywev_order_vat_paid', true );

            $user = $order->get_user();

            if( is_array( $eu_vat_data ) && $eu_vat_data ) {

                $eu_vat_data["eu_cart_with_digital_goods"] = yit_get_prop($order, 'ywev_eu_cart_with_digital_goods', true);
                $eu_vat_data["eu_vat_amount"] = yit_get_prop($order, 'ywev_vat_exemption_amount', true);
                $eu_vat_data["vat_number"] = yit_get_prop($order, 'yweu_billing_vat', true);
                $eu_vat_data["country_confirmed"] = yit_get_prop($order, 'ywev_country_confirmed', true);

                $eu_vat_data["Localization"] = array();
                $eu_vat_data["Localization"]["COUNTRY"] = yit_get_prop($order, 'ywev_COUNTRY', true);
                $eu_vat_data["Localization"]["GEO_COUNTRY"] = yit_get_prop($order, 'ywev_GEO_COUNTRY', true);
                $eu_vat_data["Localization"]["IP_ADDRESS"] = yit_get_prop($order, 'ywev_IP_ADDRESS', true);
                $eu_vat_data["Localization"]["STATE"] = yit_get_prop($order, 'ywev_STATE', true);
                $eu_vat_data["Localization"]["POST_CODE"] = yit_get_prop($order, 'ywev_POST_CODE', true);
                $eu_vat_data["Localization"]["IP_ADDRESS"] = yit_get_prop($order, 'ywev_IP_ADDRESS', true);


                $has_digital_goods = !empty($eu_vat_data["eu_cart_with_digital_goods"]) && (1 == $eu_vat_data["eu_cart_with_digital_goods"]) ? true : false;


                if ( $has_digital_goods || get_option('ywev_apply_to_physical') == 'yes' ) {
                    $reverse_charge_amount = ( ! empty( $eu_vat_data["eu_vat_amount"] ) && ( $eu_vat_data["eu_vat_amount"] > 0 ) ) ? $eu_vat_data["eu_vat_amount"] : 0;
                    $vat_number            = $eu_vat_data["vat_number"] ? $eu_vat_data["vat_number"] : '' ;

                    $customer_country = $eu_vat_data["Localization"]["COUNTRY"] ? $eu_vat_data["Localization"]["COUNTRY"] : '';
                    $geo_country      = $eu_vat_data["Localization"]["GEO_COUNTRY"] ? $eu_vat_data["Localization"]["GEO_COUNTRY"] : '' ;
                    $ip_address       = $eu_vat_data["Localization"]["IP_ADDRESS"] ? $eu_vat_data["Localization"]["IP_ADDRESS"] : '';

                    $country_confirmed = $eu_vat_data["country_confirmed"] ? $eu_vat_data["country_confirmed"] : '';


                    //Store the vat number in the user account
                    if ( is_object($user) && $user->ID ){

                        $user_stored_vat_number  = get_user_meta( $user->ID, 'yweu_vat_number', true );

                        if ( ! $user_stored_vat_number){
                            update_user_meta( $user->ID, 'yweu_vat_number', $vat_number );
                        }
                    }

                    
                    ?>

                    <div class="eu-vat-information">
                        <div style="overflow: hidden; padding: 5px 0">
                            <span style="float:left"><?php _e( 'VAT number: ', 'yith-woocommerce-eu-vat' ); ?></span>
                            <span style="float:right"><?php echo $vat_number; ?></span>
                        </div>
                        <div style="overflow: hidden; padding: 5px 0">
                            <span style="float:left"><?php _e( 'Reverse charge amount: ', 'yith-woocommerce-eu-vat' ); ?></span>
                            <span style="float:right"><?php echo wc_price( $reverse_charge_amount ); ?></span>
                        </div>
                        <div style="overflow: hidden; padding: 5px 0">
                            <span style="float:left"><?php _e( 'Customer country: ', 'yith-woocommerce-eu-vat' ); ?></span>
                            <span style="float:right"><?php echo ( isset( WC()->countries->countries[ $customer_country ] ) ? WC()->countries->countries[ $customer_country ] : '' ); ?></span>
                        </div>
                        <div style="overflow: hidden; padding: 5px 0">
                            <span style="float:left"><?php _e( 'Geolocalized country: ', 'yith-woocommerce-eu-vat' ); ?></span>
                            <span style="float:right"><?php if ( isset( WC()->countries->countries[ $geo_country ] ) ) {
                                    echo WC()->countries->countries[ $geo_country ];
                                } ?></span>
                        </div>
                        <div style="overflow: hidden; padding: 5px 0">
                            <span style="float:left"><?php _e( 'Customer IP address: ', 'yith-woocommerce-eu-vat' ); ?></span>
                            <span style="float:right"><?php echo $ip_address; ?></span>
                        </div>
                        <?php if ( $country_confirmed ) : ?>
                            <div style="overflow: hidden; padding: 5px 0">
							<span
                                    style="float:left"><?php _e( 'The customer manually confirmed his/her country', 'yith-woocommerce-eu-vat' ); ?></span>
                            </div>
                        <?php else: ?>
                            <div style="overflow: hidden; padding: 5px 0">
							<span
                                    style="float:left"><?php _e( 'The customer did NOT confirm his/her country', 'yith-woocommerce-eu-vat' ); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php
                }
            }

             else {
				?>
				<div class="eu-vat-information">
					<div style="overflow: hidden; padding: 5px 0">
						<span style="float:left"><?php _e( 'Reverse charge not applied.', 'yith-woocommerce-eu-vat' ); ?></span>
					</div>
				</div>
				<?php
			}
		}


        public function customize_admin_user_fields( $meta_fields ){
            $meta_fields['billing']['fields']['yweu_vat_number'] = array(
                'label'       => __( 'VAT Number', 'yith-woocommerce-eu-vat' ),
                'description' => '',
            ) ;
            return $meta_fields;
        }

        public function get_eu_vat_admin_user_fields( $data, $customer, $user_id ){

            $data['billing']['fields']['yweu_vat_number'] =  get_user_meta( $user_id,'yweu_vat_number',true );

            return $data;

        }




    }
}