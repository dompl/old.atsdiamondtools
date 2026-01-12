<?php namespace TierPricingTable\Integrations\Plugins;

class MixMatch extends PluginIntegrationAbstract {
	
	public function run() {
		add_filter( 'tiered_pricing_table/cart/need_price_recalculation', function ( $bool, $cart_item ) {
			
			if ( isset( $cart_item['mnm_container'] ) ) {
				return false;
			}
			
			return $bool;
			
		}, 10, 2 );
	}
	
	public function getIconURL(): string {
		return $this->getContainer()->getFileManager()->locateAsset( 'admin/integrations/mix-match-icon.png' );
	}
	
	public function getAuthorURL(): string {
		return 'https://woocommerce.com/products/woocommerce-mix-and-match-products/';
	}
	
	public function getTitle(): string {
		return __( 'Mix&Match for WooCommerce', 'tier-pricing-table' );
	}
	
	public function getDescription(): string {
		return __( 'Make tiered pricing properly work with this type of product.', 'tier-pricing-table' );
	}
	
	public function getSlug(): string {
		return 'mix-match-for-woocommerce';
	}
	
	public function getIntegrationCategory(): string {
		return 'custom_product_types';
	}
}
