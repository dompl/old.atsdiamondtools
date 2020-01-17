<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'YITH_YWEV_Custom_Types' ) ) {

	/**
	 * custom types fields
	 *
	 * @class   YITH_YWZM_Custom_Types
	 * @package Yithemes
	 * @since   1.0.0
	 * @author  Your Inspiration Themes
	 */
	class YITH_YWEV_Custom_Types {

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

		public function __construct() {
			/**
			 * Register actions and filters for custom types used on the current plugin
			 */

			/** Custom types : ywev_eu_vat_tax_list */
			add_action ( 'woocommerce_update_option', array (
				$this,
				'admin_update_ywev_eu_vat_tax_list',
			) );

			add_action( 'woocommerce_admin_field_ywev_eu_vat_tax_list', array( $this, 'show_eu_vat_tax_list' ) );
		}

		public function show_eu_vat_tax_list( $value ) {

			include( YITH_YWEV_TEMPLATE_DIR . '/admin/eu-vat-tax-list.php' );
		}

		/**
		 * Save the tax classes to use
		 *
		 * @param array $option
		 *
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function admin_update_ywev_eu_vat_tax_list( $option ) {

			if ( ! isset( $option["type"] ) ) {
				return;
			}

			if ( 'ywev_eu_vat_tax_list' != $option["type"] ) {
				return;
			}

			if ( ! isset( $_POST["ywev_eu_vat_tax_list"] ) ) {
				return;
			}

			$options = array(
				$option['id'] => isset( $_POST[ $option['id'] ] ) ? stripslashes_deep( $_POST[ $option['id'] ] ) : false,
			);

			update_option( $option['id'], $options );
		}
	}
}
