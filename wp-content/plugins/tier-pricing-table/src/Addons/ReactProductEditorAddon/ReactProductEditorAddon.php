<?php namespace TierPricingTable\Addons\ReactProductEditorAddon;

use TierPricingTable\Addons\AbstractAddon;

class ReactProductEditorAddon extends AbstractAddon {
	
	public function getName(): string {
		return __( 'WooCommerce Product Editor integration', 'tier-pricing-table' );
	}
	
	public function getDescription(): string {
		return __( 'Enable integration with the new WooCommerce React-based product editor.', 'tier-pricing-table' );
	}
	
	public function getSlug(): string {
		return 'woocommerce-react-product-editor';
	}
	
	public function run() {
		new ProductEditor();
	}
}
