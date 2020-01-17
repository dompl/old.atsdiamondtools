<?php
/**
 * EU VAT base class
 *
 * @author  Your Inspiration Themes
 * @package YITH WooCommerce EU VAT
 * @version 1.1.2
 */

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (!class_exists('YITH_WooCommerce_EU_VAT')) {
    /**
     * Admin class.
     * The class manage all the Frontend behaviors.
     *
     * @since 1.0.0
     */
    class YITH_WooCommerce_EU_VAT
    {

        public $is_checkout_forbidden = false;

        public $show_forbid_warning = false;

        public $forbid_warning_message = '';

        public $forbid_error_message = '';

        /**
         * @var YITH_YWEV_Backend
         */
        public $admin = null;

        /**
         * @var YITH_YWEV_Frontend
         */
        public $public = null;

        /**
         * Single instance of the class
         *
         * @since 1.0.0
         */
        protected static $instance;

        /**
         * @var string Premium version landing link
         */
        protected $_premium_landing = 'https://yithemes.com/themes/plugins/yith-woocommerce-eu-vat/';

        /**
         * @var string Plugin official documentation
         */
        protected $_official_documentation = 'https://docs.yithemes.com/yith-woocommerce-eu-vat/';

        /**
         * @var string Official plugin landing page
         */
        protected $_premium_live = 'https://plugins.yithemes.com/yith-woocommerce-eu-vat/';

        /**
         * @var string Official plugin support page
         */
        protected $_support = 'https://yithemes.com/my-account/support/dashboard/';

        /**
         * @var string Plugin panel page
         */
        protected $_panel_page = 'yith_woocommerce_eu_vat';

        /**
         * Returns single instance of the class
         *
         * @since 1.0.0
         */
        public static function get_instance()
        {
            if (is_null(self::$instance)) {
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
        public function __construct()
        {
            $this->includes();

            add_action('init', array($this, 'init'));

            /* === Show Plugin Information === */

            add_filter( 'plugin_action_links_' . plugin_basename( YITH_YWEV_DIR . '/' . basename( YITH_YWEV_FILE ) ), array(
                $this,
                'action_links',
            ) );

            add_filter( 'yith_show_plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 5 );

        }

        public function init_plugin_options()
        {
            //  if it shouldn't warning a eu customer, nothing should be done
            $this->is_checkout_forbidden = "yes" == get_option('ywev_forbid_checkout', 'no');
            $this->show_forbid_warning = 'yes' == get_option('ywev_show_forbid_warning', 'no');
            $this->forbid_warning_message = get_option('ywev_forbid_warning_message', '');
            $this->forbid_error_message = get_option('ywev_forbid_error_message', '');
        }

        public function includes()
        {
            require_once(YITH_YWEV_DIR . '/functions.php');

            if (is_admin()) {

                require_once(YITH_YWEV_LIB_DIR . '/class.yith-tax-rates.php');
                require_once(YITH_YWEV_LIB_DIR . '/class.yith-ywev-custom-types.php');
                require_once(YITH_YWEV_LIB_DIR . '/class.yith-ywev-backend.php');
            }

            if (!is_admin() || (defined('DOING_AJAX') && DOING_AJAX)) {
                require_once(YITH_YWEV_LIB_DIR . '/class.yith-ywev-frontend.php');
            }
        }

        public function init()
        {
            $this->init_plugin_options();

            if (is_admin()) {
                $this->load_backend();
            }

            if (!is_admin() || (defined('DOING_AJAX') && DOING_AJAX)) {
                $this->load_frontend();
            }

            add_filter('woocommerce_billing_fields', array($this, 'add_eu_vat_field'), 10, 2);
        }

        /**
         * Start backend instance
         */
        public function load_backend()
        {
            $this->admin = YITH_YWEV_Backend::get_instance();
        }

        /**
         * Start frontend instance
         */
        public function load_frontend()
        {
            $this->public = YITH_YWEV_Frontend::get_instance();
        }


        public function add_eu_vat_field($address_fields, $country)
        {

            $user = wp_get_current_user();

            if ( is_object($user) && $user->ID ){
                $user_stored_vat_number  = get_user_meta( $user->ID, 'yweu_vat_number', true );
            }
            else{
                $user_stored_vat_number = '';
            }

            $address_fields['billing_yweu_vat'] = array(
                'type' => 'text',
                'description' => get_option('ywev_eu_vat_field_description', __('European companies with valid EU VAT number will be exempt of VAT.', 'yith-woocommerce-eu-vat')),
                'class' => array('form-row-wide update_totals_on_change'),
                'label' => get_option('ywev_eu_vat_field_label', __('VAT number', 'yith-woocommerce-eu-vat')),
                'placeholder' => get_option('ywev_eu_vat_field_placeholder', __('Enter your VAT number', 'yith-woocommerce-eu-vat')),
                'required' => YITH_YWEV()->mandatory_vat_number,
                'default' => apply_filters('yith_eu_vat_default_number', $user_stored_vat_number)
            );
            return $address_fields;
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

            $links = yith_add_action_links( $links, $this->_panel_page, false );
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
        public function plugin_row_meta( $new_row_meta_args, $plugin_meta, $plugin_file, $plugin_data, $status, $init_file = 'YITH_YWEV_FREE_INIT' )
        {

            if (defined( $init_file ) && constant( $init_file ) == $plugin_file) {
                $new_row_meta_args['slug'] = YITH_YWEV_SLUG;
            }

            return $new_row_meta_args;

        }

    }
}