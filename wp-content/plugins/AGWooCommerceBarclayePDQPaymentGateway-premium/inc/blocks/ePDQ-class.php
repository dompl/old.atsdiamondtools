<?php
/**
 * AG ePDQ payment gateway implementation for Gutenberg Blocks
 *
 * @package WooCommerce/Blocks
 * @since 3.0.0
 */

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

defined( 'ABSPATH' ) || die( "No script kiddies please!" );

final class epdq_checkout_block extends AbstractPaymentMethodType {

	protected $name = 'epdq_checkout';

	public function initialize() {
		$this->settings = get_option( 'woocommerce_epdq_checkout_settings', [] );
	}

	public function is_active() {
		return filter_var( $this->get_setting( 'enabled', false ), FILTER_VALIDATE_BOOLEAN );
	}

	public function get_payment_method_script_handles() {

		$version      = "2.0.0";
		$script_url   = AG_ePDQ_server::plugin_url() . '/inc/blocks/js/wc-payment-method-epdq.js';
		$handle       = 'wc-payment-method-epdq';
		$dependencies = array();
		wp_register_script( $handle, $script_url, $dependencies, $version, true );
		wp_set_script_translations( 'wc-payment-method-epdq', 'ag_tyl_checkout' );

		return [ 'wc-payment-method-epdq' ];
	}

	public function get_payment_method_data() {
		return [
			'title'       => $this->get_setting( 'title' ),
			'description' => $this->get_setting( 'description' ),
			'iconsrc'     => AG_ePDQ_server::plugin_url() . '/inc/assets/img/ag-barclaycard.png',
		];
	}
}






