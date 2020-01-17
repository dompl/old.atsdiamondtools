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

if ( ! class_exists ( 'YITH_YWEV_Backend' ) ) {
	/**
	 * Admin class.
	 * The class manage all the admin behaviors.
	 *
	 * @since 1.0.0
	 */
	class YITH_YWEV_Backend {
		
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
			$this->includes ();
			$this->init_hooks ();
		}
		
		public function includes() {
			
		}
		
		public function init_hooks() {
			//Actions
			add_action ( 'admin_enqueue_scripts', array(
				$this,
				'enqueue_style'
			) );
			
			add_action ( 'admin_enqueue_scripts', array(
				$this,
				'enqueue_scripts'
			) );
			
			/**
			 * On plugin init check query vars for commands to import default tax rates
			 */
			add_action ( "admin_init", array(
				$this,
				"check_import_actions"
			) );
			
			/**
			 * Show the EU VAT report
			 */
			add_filter ( 'woocommerce_admin_reports', array( $this, 'show_eu_vat_admin_report' ) );

			add_action( 'yith_ywev_reports_panel',array( $this, 'get_report' ) );
		}
		
		/**
		 * Enqueue admin styles and scripts
		 *
		 * @access public
		 * @return void
		 * @since  1.0.0
		 */
		public function enqueue_style( $hook_suffix ) {
			
			if ( 'yith-plugins_page_yith_woocommerce_eu_vat' != $hook_suffix ) {
				return;
			}
			
			wp_enqueue_style ( 'ywev-admin', YITH_YWEV_ASSETS_URL . '/css/ywev-admin.css' );
		}
		
		public function enqueue_scripts( $hook_suffix ) {
			if ( 'yith-plugins_page_yith_woocommerce_eu_vat' != $hook_suffix ) {
				return;
			}
			
			$path = WC ()->plugin_url () . '/assets/js/admin/' . yit_load_js_file ( 'reports.js' );
			wp_register_script ( 'wc-reports', $path, array( 'jquery', 'jquery-ui-datepicker' ), WC_VERSION, true );
			wp_enqueue_script ( 'wc-reports' );
		}
		
		/**
		 * On plugin init check query vars for commands to import default tax rates
		 */
		function check_import_actions() {
			if ( ! isset( $_GET["install-tax-rates"] ) ) {
				return;
			}
			
			if ( "standard" == $_GET["install-tax-rates"] ) {
				//  import standard tax rates
				YITH_Tax_Rates::get_instance ()->install_standard_rates ();
			} elseif ( "reduced" == $_GET["install-tax-rates"] ) {
				//  import reduced tax rates
			}
			
			wp_redirect ( esc_url_raw ( remove_query_arg ( "install-tax-rates" ) ) );
			exit;
		}
		
		public function show_eu_vat_admin_report( $reports ) {
			if ( isset( $reports['taxes'] ) ) {
				$reports['taxes']['reports']['yith_eu_vat'] = array(
					'title'       => __ ( 'EU VAT Reports', 'yith-woocommerce-eu-vat' ),
					'description' => '',
					'hide_title'  => true,
					'callback'    => array( $this, 'get_report' ),
				);
			}
			
			return $reports;
		}
		
		public function get_report() {
			if ( ! class_exists( 'WC_Admin_Report' ) ) {
				include_once( WC()->plugin_path() . '/includes/admin/reports/class-wc-admin-report.php' );
			}
			include_once ( YITH_YWEV_TEMPLATE_DIR . '/report/class.yith-ywev-report-eu-vat-taxes.php' );
			
			$report = new YITH_YWEV_Report_EU_VAT_Taxes();
			$report->output_report ();
		}
	}
}

