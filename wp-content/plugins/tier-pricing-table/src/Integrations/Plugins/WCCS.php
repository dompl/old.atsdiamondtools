<?php namespace TierPricingTable\Integrations\Plugins;

class WCCS extends PluginIntegrationAbstract {
	
	public function run() {
		
		add_filter( 'tiered_pricing_table/price/price_by_rules',
			function ( $product_price, $quantity, $product_id, $context ) {
				
				global $WCCS;
				
				if ( $WCCS ) {
					
					$product = wc_get_product( $product_id );
					
					if ( 'view' === $context && $product ) {
						return $WCCS->wccs_custom_price( $product_price, $product );
					}
				}
				
				return $product_price;
				
			}, 10, 10 );
	}
	
	public function getTitle(): string {
		return __( 'WooCommerce Currency Switcher by WP Experts (WCCS)', 'tier-pricing-table' );
	}
	
	public function getDescription(): string {
		return __( 'Make the tiered pricing properly work with multiple currencies.', 'tier-pricing-table' );
	}
	
	public function getIconURL(): string {
		return $this->getContainer()->getFileManager()->locateAsset( 'admin/integrations/wccs-icon.png' );
	}
	
	public function getAuthorURL(): string {
		return 'https://woocommerce.com/products/currency-switcher-for-woocommerce/';
	}
	
	public function getSlug(): string {
		return 'wccs';
	}
	
	public function getIntegrationCategory(): string {
		return 'multicurrency';
	}
}
