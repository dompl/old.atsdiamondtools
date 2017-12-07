<?php

/**
 * Plugin Name: AJAX Search for WooCommerce
 * Plugin URI: https://wordpress.org/plugins/ajax-search-for-woocommerce/
 * Description: Allows your customers to search products easily and quickly. It will display the results instantly while typing in an inputbox.
 * Version: 1.1.6
 * Author: Damian Góra
 * Author URI: http://damiangora.com
 * Text Domain: ajax-search-for-woocommerce
 * Domain Path: /languages/
 * 
 */
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

if ( !class_exists( 'DGWT_WC_Ajax_Search' ) ) {

	final class DGWT_WC_Ajax_Search {

		private static $instance;
		private $tnow;
		public $settings;
		public $search;
		public $result_details;

		public static function get_instance() {
			if ( !isset( self::$instance ) && !( self::$instance instanceof DGWT_WC_Ajax_Search ) ) {

				self::$instance = new DGWT_WC_Ajax_Search;
				
				self::$instance->constants();
				
				if ( !self::$instance->check_requirements() ) {
					return;
				}

				
				self::$instance->includes();
				self::$instance->hooks();

				// Set up localisation
				self::$instance->load_textdomain();


				self::$instance->settings		 = new DGWT_WCAS_Settings;
				self::$instance->search			 = new DGWT_WCAS_Search;
				self::$instance->result_details	 = new DGWT_WCAS_Result_Details;
			}
			self::$instance->tnow = time();

			return self::$instance;
		}

		/**
		 * Constructor Function
		 */
		private function __construct() {
			self::$instance = $this;
		}

		/**
		 * Check requirements
		 */

		private function check_requirements() {

			if ( version_compare( PHP_VERSION, '5.3.0' ) < 0 ) {
				add_action( 'admin_notices', array( $this, 'admin_notice_php' ) );

				return false;
			}

			if ( !class_exists('WooCommerce')) {
				add_action( 'admin_notices', array( $this, 'admin_notice_no_woocommerce' ) );

				return false;
			}

			return true;
		}
		
		/**
		 * Notice: PHP version less than 5.3
		 */

		public function admin_notice_php() {
			?>
            <div class="error">
                <p>
					<?php
					_e( '<b>Ajax Search for WooCommerce</b>: You need PHP version at least 5.3 to run this plugin. You are currently using PHP version ', 'ajax-search-for-woocommerce' );
					echo PHP_VERSION . '.';
					?>
                </p>
            </div>
			<?php
		}

		/**
		 * Notice: PHP version less than 5.3
		 */

		public function admin_notice_no_woocommerce() {
			?>
            <div class="error">
                <p>
					<?php
					printf(__( '<b>Ajax Search for WooCommerce</b> is enabled but not effective. It requires %s in order to work.', 'ajax-search-for-woocommerce' ),
                        '<a href="https://pl.wordpress.org/plugins/woocommerce/"  target="_blank">WooCommerce</a>');
					?>
                </p>
            </div>
			<?php
		}

		/**
		 * Setup plugin constants
		 */
		private function constants() {

			$this->define( 'DGWT_WCAS_VERSION', '1.1.6' );
			$this->define( 'DGWT_WCAS_NAME', 'Ajax Search for WooCommerce' );
			$this->define( 'DGWT_WCAS_FILE', __FILE__ );
			$this->define( 'DGWT_WCAS_DIR', plugin_dir_path( __FILE__ ) );
			$this->define( 'DGWT_WCAS_URL', plugin_dir_url( __FILE__ ) );

			$this->define( 'DGWT_WCAS_SETTINGS_KEY', 'dgwt_wcas_settings' );

			$this->define( 'DGWT_WCAS_SEARCH_ACTION', 'dgwt_wcas_ajax_search' );
			$this->define( 'DGWT_WCAS_RESULT_DETAILS_ACTION', 'dgwt_wcas_result_details' );

			$this->define( 'DGWT_WCAS_WOO_PRODUCT_POST_TYPE', 'product' );
			$this->define( 'DGWT_WCAS_WOO_PRODUCT_CATEGORY', 'product_cat' );
			$this->define( 'DGWT_WCAS_WOO_PRODUCT_TAG', 'product_tag' );

			$this->define( 'DGWT_WCAS_WC_AJAX_ENDPOINT', true );


			$this->define( 'DGWT_WCAS_DEBUG', false );

		}

		/**
		 * Define constant if not already set
		 * @param  string $name
		 * @param  string|bool $value
		 */
		private function define( $name, $value ) {
			if ( !defined( $name ) ) {
				define( $name, $value );
			}
		}

		/**
		 * Include required core files.
		 */
		public function includes() {

			require_once DGWT_WCAS_DIR . 'includes/functions.php';

			require_once DGWT_WCAS_DIR . 'includes/install.php';

			require_once DGWT_WCAS_DIR . 'includes/admin/settings/class-settings-api.php';
			require_once DGWT_WCAS_DIR . 'includes/admin/settings/class-settings.php';

			require_once DGWT_WCAS_DIR . 'includes/register-scripts.php';

			require_once DGWT_WCAS_DIR . 'includes/admin/admin-menus.php';
			require_once DGWT_WCAS_DIR . 'includes/admin/Promo/FeedbackNotice.php';

			require_once DGWT_WCAS_DIR . 'includes/widget.php';
			require_once DGWT_WCAS_DIR . 'includes/style.php';
			require_once DGWT_WCAS_DIR . 'includes/shortcode.php';
			require_once DGWT_WCAS_DIR . 'includes/class-search.php';
			require_once DGWT_WCAS_DIR . 'includes/class-result-details.php';

			require_once DGWT_WCAS_DIR . 'includes/integrations/wp-tao.php';
		}

		/**
		 * Actions and filters
		 */
		private function hooks() {

			add_action( 'admin_init', array( $this, 'admin_scripts' ) );

			//@todo create_cron_jobs action
			//@todo fire_cron function init
		}

		/*
		 * Create cron if not exists
		 */

		public function create_cron_jobs() {
			//@todo create cron jobs
		}

		/*
		 * Enqueue admin sripts
		 */

		public function admin_scripts() {
			// Register CSS
			wp_register_style( 'dgwt-wcas-admin-style', DGWT_WCAS_URL . 'assets/css/admin-style.css', array(), DGWT_WCAS_VERSION );



			// Enqueue CSS            
			wp_enqueue_style( array(
				'dgwt-wcas-admin-style',
				'wp-color-picker'
			) );


			wp_enqueue_script( 'wp-color-picker' );
		}

		/*
		 * Register text domain
		 */

		private function load_textdomain() {
			$lang_dir = dirname( plugin_basename( DGWT_WCAS_FILE ) ) . '/languages/';
			load_plugin_textdomain( 'ajax-search-for-woocommerce', false, $lang_dir );
		}

	}

}

// Init the plugin
function DGWT_WCAS() {
	return DGWT_WC_Ajax_Search::get_instance();
}

add_action( 'plugins_loaded', 'DGWT_WCAS' );
