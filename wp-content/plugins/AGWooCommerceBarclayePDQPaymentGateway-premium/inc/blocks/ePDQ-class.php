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

		//$this->gateway = new epdq_checkout;
		$this->settings = get_option( 'woocommerce_epdq_checkout_settings', [] );
	}

	public function is_active() {

		return filter_var( $this->get_setting( 'enabled', false ), FILTER_VALIDATE_BOOLEAN );
	}

	public function get_payment_method_script_handles() {

		$version      = AG_ePDQ_server::$AGversion;
		$script_url   = AG_ePDQ_server::plugin_url() . '/inc/blocks/js/wc-payment-method-epdq.js';
		$handle       = 'wc-payment-method-epdq';
		$dependencies = array();
		wp_register_script( $handle, $script_url, $dependencies, $version, true );
		wp_set_script_translations( 'wc-payment-method-epdq', 'ag_epdq_server' );

		return [ 'wc-payment-method-epdq' ];

	}


	public function get_payment_method_data() {

		$epdq_settings = new epdq_checkout();

		return [
			'title'       => $epdq_settings->title,
			'description' => $this->get_setting( 'description' ),
			'iconsrc'     => AG_ePDQ_server::plugin_url() . '/inc/assets/img/new_cards.png',
			'supports'    => $this->get_supported_features(),
			'cardIcons'   => $this->get_icons(),
			'testmode'    => $epdq_settings->status,

		];
	}


	public function get_supported_features() {

		$gateways = WC()->payment_gateways->get_available_payment_gateways();
		if ( isset( $gateways['epdq_checkout'] ) ) {
			$gateway = $gateways['epdq_checkout'];

			return array_filter( $gateway->supports, [ $gateway, 'supports' ] );
		}

		return [];
	}


	private function get_icons() {

		$icons_src = [
			'visa'       => [
				'src' => AG_ePDQ_server_path . 'inc/assets/img/new-card/visa.png',
				'alt' => __( 'Visa', 'ag_epdq_server' ),
			],
			'maestro'    => [
				'src' => AG_ePDQ_server_path . 'inc/assets/img/new-card/maestro.png',
				'alt' => __( 'Maestro', 'ag_epdq_server' ),
			],
			'amex'       => [
				'src' => AG_ePDQ_server_path . 'inc/assets/img/new-card/amex.png',
				'alt' => __( 'American Express', 'ag_epdq_server' ),
			],
			'mastercard' => [
				'src' => AG_ePDQ_server_path . 'inc/assets/img/new-card/mastercard.png',
				'alt' => __( 'Mastercard', 'ag_epdq_server' ),
			]
		];

		return $icons_src;
	}


}






