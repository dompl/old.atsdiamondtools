<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Implements features of PREMIUM version of YWEV plugin
 *
 * @class   YITH_WooCommerce_EU_VAT_Privacy
 * @package Yithemes
 * @since   1.3.3
 * @author  Daniel Sanchez Saez
 */
if ( ! class_exists( 'YITH_WooCommerce_EU_VAT_Privacy' ) ) {

    class YITH_WooCommerce_EU_VAT_Privacy extends YITH_Privacy_Plugin_Abstract
    {

        /**
         * Init - hook into events.
         */
        public function __construct()
        {

            /**
             * GDRP privacy policy content
             */
            parent::__construct( _x( 'YITH EU VAT for WooCommerce premium', 'Privacy Policy Content', 'yith-woocommerce-advanced-reviews' ) );

            /**
             * GDRP to export customer data
             */

            add_filter( 'woocommerce_privacy_export_customer_personal_data_props', array( $this, 'woocommerce_privacy_export_customer_personal_data_props_call_back' ), 10, 1 );

            add_filter( 'woocommerce_privacy_export_customer_personal_data_prop_value', array( $this, 'woocommerce_privacy_export_customer_personal_data_prop_value_call_back' ), 10, 3 );

            /**
             * GDRP to erase customer data
             */

            add_filter( 'woocommerce_privacy_erase_customer_personal_data_props', array( $this, 'woocommerce_privacy_erase_customer_personal_data_props_call_back' ), 10, 1 );

            add_filter( 'woocommerce_privacy_erase_customer_personal_data_prop', array( $this, 'woocommerce_privacy_erase_customer_personal_data_prop_call_back' ), 10, 3 );

            /**
             * GDRP to export order personal data
             */

            add_filter( 'woocommerce_privacy_export_order_personal_data_props', array( $this, 'woocommerce_privacy_export_order_personal_data_props_call_back' ), 10, 1 );

            add_filter( 'woocommerce_privacy_export_order_personal_data_prop', array( $this, 'woocommerce_privacy_export_order_personal_data_prop_call_back' ), 10, 3 );


            /**
             * GDRP to erase order personal data
             */

            add_filter( 'woocommerce_privacy_erase_order_personal_data', array( $this, 'woocommerce_privacy_erase_order_personal_data_call_back' ), 10, 2 );

        }

        /**
         * Add privacy policy content for the privacy policy page.
         *
         * @since 1.3.3
         */
        public function get_privacy_message( $section ) {

            $privacy_content_path = YITH_YWEV_VIEWS_PATH . '/privacy/html-policy-content-' . $section . '.php';

            if ( file_exists( $privacy_content_path ) ) {

                ob_start();

                include $privacy_content_path;

                return ob_get_clean();

            }

            return '';

        }

        /**
         * GDPR erase order_metas to the filter hook of WooCommerce to erase personal order data associated with an email address.
         *
         * @since 1.3.3
         *
         * @param  boolean $erasure_enabled.
         * @param  object $order.
         * @return boolean
         */
        function woocommerce_privacy_erase_order_personal_data_call_back( $erasure_enabled, $order )
        {

            if ( $erasure_enabled ){

                yit_save_prop( $order, 'yweu_billing_vat', wp_privacy_anonymize_data( 'text', yit_get_prop( $order, 'yweu_billing_vat', true ) ) );
                yit_save_prop( $order, 'ywev_vat_exemption_amount', wp_privacy_anonymize_data( 'text', yit_get_prop( $order, 'ywev_vat_exemption_amount', true ) ) );

                yit_save_prop( $order, 'ywev_COUNTRY', wp_privacy_anonymize_data( 'address_country', yit_get_prop( $order, 'ywev_COUNTRY', true ) ) );
                yit_save_prop( $order, 'ywev_STATE', wp_privacy_anonymize_data( 'address_state', yit_get_prop( $order, 'ywev_STATE', true ) ) );
                yit_save_prop( $order, 'ywev_CITY', wp_privacy_anonymize_data( 'address_city', yit_get_prop( $order, 'ywev_CITY', true ) ) );
                yit_save_prop( $order, 'ywev_POST_CODE', wp_privacy_anonymize_data( 'text', yit_get_prop( $order, 'ywev_POST_CODE', true ) ) );
                yit_save_prop( $order, 'ywev_GEO_COUNTRY', wp_privacy_anonymize_data( 'address_country', yit_get_prop( $order, 'ywev_GEO_COUNTRY', true ) ) );
                yit_save_prop( $order, 'ywev_IP_ADDRESS', wp_privacy_anonymize_data( 'ip', yit_get_prop( $order, 'ywev_IP_ADDRESS', true ) ) );

                yit_save_prop( $order, 'ywev_country_confirmed', wp_privacy_anonymize_data( 'text', yit_get_prop( $order, 'ywev_country_confirmed', true ) ) );

            }

            return $erasure_enabled;

        }

        /**
         * GDPR add user_meta to the filter hook of WooCommerce to erase personal order data associated with an email address.
         *
         * @since 1.3.3
         *
         * @param  array $array_meta_to_export user_meta.
         * @return array
         */
        function woocommerce_privacy_erase_customer_personal_data_props_call_back( $array_meta_to_export )
        {

            $array_meta_to_export[ 'billing_yweu_vat' ] = __( 'VAT number', 'yith-woocommerce-eu-vat' );

            return $array_meta_to_export;

        }

        /**
         * GDPR erase the value user_meta to add to the filer hook of WooCommerce to erase personal order data associated with an email address.
         *
         * @since 1.3.3
         *
         * @param  boolean $erased
         * @param  string $prop meta_user
         * @param  object $customer
         * @return string
         */
        function woocommerce_privacy_erase_customer_personal_data_prop_call_back( $erased, $prop, $customer )
        {

            $array_props = array(
                'billing_yweu_vat',
            );

            if ( in_array( $prop, $array_props ) ){

                if ( get_user_meta( $customer->get_id(), $prop, true ) ){
                    update_user_meta( $customer->get_id(), $prop, '' );
                    $erased = true;
                }
                else
                    $erased = false;

            }

            return $erased;

        }


        /**
         * GDPR add user_meta to the filter hook of WooCommerce to export personal order data associated with an email address.
         *
         * @since 1.3.3
         *
         * @param  array $array_meta_to_export user_meta.
         * @return array
         */
        function woocommerce_privacy_export_customer_personal_data_props_call_back( $array_meta_to_export )
        {

            $array_meta_to_export[ 'billing_yweu_vat' ] = __( 'VAT number', 'yith-woocommerce-eu-vat' );

            return $array_meta_to_export;

        }

        /**
         * GDPR retrieve the value user_meta to add to the filer hook of WooCommerce to export personal order data associated with an email address.
         *
         * @since 1.3.3
         *
         * @param  string $value value of meta_user.
         * @param  string $prop meta_user
         * @param  object $customer
         * @return string
         */
        function woocommerce_privacy_export_customer_personal_data_prop_value_call_back( $value, $prop, $customer )
        {

            $array_props = array(
                'billing_yweu_vat',
            );

            return ( in_array( $prop, $array_props ) ? get_user_meta( $customer->get_id(), $prop, true ) : $value );

        }

        /**
         * GDPR add order_meta to the filter hook of WooCommerce to export personal order data associated with an email address.
         *
         * @since 1.3.3
         *
         * @param  array $array_meta_to_export meta_orders.
         * @return array
         */
        function woocommerce_privacy_export_order_personal_data_props_call_back( $array_meta_to_export )
        {

            $array_meta_to_export[ 'yweu_billing_vat' ] = __( 'VAT number', 'yith-woocommerce-eu-vat' );
            $array_meta_to_export[ 'ywev_vat_exemption_amount' ] = __( 'Reverse charge amount', 'yith-woocommerce-eu-vat' );

            $array_meta_to_export[ 'ywev_COUNTRY' ] = __( 'Customer country', 'yith-woocommerce-eu-vat' );
            $array_meta_to_export[ 'ywev_STATE' ] = __( 'Customer state', 'yith-woocommerce-eu-vat' );
            $array_meta_to_export[ 'ywev_CITY' ] = __( 'Customer city', 'yith-woocommerce-eu-vat' );
            $array_meta_to_export[ 'ywev_POST_CODE' ] = __( 'Customer post code', 'yith-woocommerce-eu-vat' );
            $array_meta_to_export[ 'ywev_GEO_COUNTRY' ] = __( 'Geolocalized country:', 'yith-woocommerce-eu-vat' );
            $array_meta_to_export[ 'ywev_IP_ADDRESS' ] = __( 'Customer ip address', 'yith-woocommerce-eu-vat' );

            $array_meta_to_export[ 'ywev_country_confirmed' ] = __( 'Country confirmation', 'yith-woocommerce-eu-vat' );

            return $array_meta_to_export;

        }

        /**
         * GDPR retrieve the value order_meta to add to the filer hook of WooCommerce to export personal order data associated with an email address.
         *
         * @since 1.3.3
         *
         * @param  string $value value of meta_order.
         * @param  string $prop meta_order
         * @param  object $order
         * @return string
         */
        function woocommerce_privacy_export_order_personal_data_prop_call_back( $value, $prop, $order )
        {

            $array_props = array(
                'yweu_billing_vat',
                'ywev_vat_exemption_amount',
                'ywev_COUNTRY',
                'ywev_STATE',
                'ywev_CITY',
                'ywev_POST_CODE',
                'ywev_GEO_COUNTRY',
                'ywev_IP_ADDRESS',
                'ywev_country_confirmed',
            );

            return ( in_array( $prop, $array_props ) ? yit_get_prop( $order, $prop, true ) : $value );

        }

    }

}

new YITH_WooCommerce_EU_VAT_Privacy();
