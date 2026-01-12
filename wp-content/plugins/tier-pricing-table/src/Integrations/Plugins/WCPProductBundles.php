<?php namespace TierPricingTable\Integrations\Plugins;

class WCPProductBundles extends PluginIntegrationAbstract {
	
	public function run() {
		add_filter( 'tiered_pricing_table/cart/need_price_recalculation', function ( $need, $cart_item ) {
			
			if ( ! empty( $cart_item['woosb_parent_id'] ) || ! empty( $cart_item['woosb_ids'] ) ) {
				return false;
			}
			
			return $need;
			
		}, 10, 2 );
	}
	
	public function addToIntegrationsSettings( $integrations ) {
		return $integrations;
	}
	
	public function getIconURL(): string {
		return '';
	}
	
	public function getAuthorURL(): string {
		return '';
	}
	
	public function getTitle(): string {
		return __( 'WPC Product Bundles for WooCommerce', 'tier-pricing-table' );
	}
	
	public function getDescription(): string {
		return '';
	}
	
	public function getSlug(): string {
		return 'wcp-product-bundles-for-woocommerce';
	}
	
	public function getIntegrationCategory(): string {
		return 'custom_product_types';
	}
}
