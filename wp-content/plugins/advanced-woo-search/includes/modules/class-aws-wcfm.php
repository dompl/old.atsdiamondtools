<?php
/**
 * WCFM - WooCommerce Multivendor Marketplace  plugin support
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if ( ! class_exists( 'AWS_WCFM' ) ) :

    /**
     * Class
     */
    class AWS_WCFM {

        /**
         * Main AWS_WCFM Instance
         *
         * Ensures only one instance of AWS_WCFM is loaded or can be loaded.
         *
         * @static
         * @return AWS_WCFM - Main instance
         */
        protected static $_instance = null;

        /**
         * Main AWS_WCFM Instance
         *
         * Ensures only one instance of AWS_WCFM is loaded or can be loaded.
         *
         * @static
         * @return AWS_WCFM - Main instance
         */
        public static function instance() {
            if ( is_null( self::$_instance ) ) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }

        /**
         * Constructor
         */
        public function __construct() {
            add_filter( 'aws_searchbox_markup', array( $this, 'wcfm_searchbox_markup' ), 1, 2 );
            add_filter( 'aws_front_data_parameters', array( $this, 'wcfm_front_data_parameters' ), 1 );
            add_filter( 'aws_search_query_array', array( $this, 'wcfm_search_query_array' ), 1 );
        }

        /*
         * WCFM - WooCommerce Multivendor Marketplace update search page url for vendors shops
         */
        public function wcfm_searchbox_markup( $markup, $params ) {

            $store = $this->get_current_store();

            if ( $store ) {
                $markup = preg_replace( '/action="(.+?)"/i', 'action="' . $store->get_shop_url() . '"', $markup );
            }

            return $markup;

        }

        /*
         * WCFM - WooCommerce Multivendor Marketplace limit search inside vendors shop
         */
        public function wcfm_front_data_parameters( $params ) {

            $store = $this->get_current_store();

            if ( $store ) {
                $params['data-tax'] = 'store:' . $store->get_id();
            }

            return $params;

        }

        /*
         * WCFM - WooCommerce Multivendor Marketplace limit search inside vendoes shop
         */
        public function wcfm_search_query_array( $query ) {

            $vendor_id = false;

            if ( isset( $_REQUEST['aws_tax'] ) && $_REQUEST['aws_tax'] && strpos( $_REQUEST['aws_tax'], 'store:' ) !== false ) {
                $vendor_id = intval( str_replace( 'store:', '', $_REQUEST['aws_tax'] ) );
            } else {
                $store = $this->get_current_store();
                if ( $store ) {
                    $vendor_id = $store->get_id();
                }
            }

            if ( $vendor_id ) {

                $store_products = get_posts( array(
                    'posts_per_page'      => -1,
                    'fields'              => 'ids',
                    'post_type'           => 'product',
                    'post_status'         => 'publish',
                    'ignore_sticky_posts' => true,
                    'suppress_filters'    => true,
                    'no_found_rows'       => 1,
                    'orderby'             => 'ID',
                    'order'               => 'DESC',
                    'lang'                => '',
                    'author'              => $vendor_id
                ) );

                if ( $store_products ) {
                    $query['search'] .= " AND ( id IN ( " . implode( ',', $store_products ) . " ) )";
                }

            }

            return $query;

        }

        /*
         * Get current store object
         */
        private function get_current_store() {

            $store = false;

            if ( function_exists('wcfmmp_is_store_page') && function_exists('wcfm_get_option') && wcfmmp_is_store_page() ) {

                $wcfm_store_url  = wcfm_get_option( 'wcfm_store_url', 'store' );
                $wcfm_store_name = apply_filters( 'wcfmmp_store_query_var', get_query_var( $wcfm_store_url ) );

                if ( $wcfm_store_name ) {
                    $seller_info = get_user_by( 'slug', $wcfm_store_name );
                    if ( $seller_info && function_exists( 'wcfmmp_get_store' ) ) {
                        $store_user = wcfmmp_get_store( $seller_info->ID );
                        if ( $store_user ) {
                            $store = $store_user;
                        }
                    }
                }

            }

            return $store;

        }

    }

endif;