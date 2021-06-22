<?php 
/**
 * AG ePDQ payment gateway implementation for Gutenberg Blocks
 *
 * @package WooCommerce/Blocks
 * @since 3.0.0
 */

namespace Automattic\WooCommerce\Blocks\Payments\Integrations;

defined('ABSPATH') || die("No script kiddies please!");

final class epdq_checkout extends AbstractPaymentMethodType {
 
	protected $name = 'epdq_checkout';

	public function initialize() {
		$this->settings = get_option( 'woocommerce_epdq_checkout_settings', [] );
	}

	public function is_active() {
		return filter_var( $this->get_setting( 'enabled', false ), FILTER_VALIDATE_BOOLEAN );
	}

	public function get_payment_method_script_handles() {
                $version = "1.0.0";
                $path = plugins_url('js/wc-payment-method-epdq.js', __FILE__);
                $handle = 'wc-payment-method-epdq';
                $dependencies = array('wc-blocks' );
                wp_register_script($handle, $path, $dependencies,$version,true);
                wp_set_script_translations( 'wc-payment-method-epdq', 'ag_epdq_server' );
		return [ 'wc-payment-method-epdq' ];
	}

	public function get_payment_method_data() {
		return [
			'title'                    => $this->get_setting( 'title' ),
			'description'              => $this->get_setting( 'description' ),
            'iconsrc'                  => AG_ePDQ_server_path . 'inc/assets/img/ag-barclaycard.png',
		];
	}
}






