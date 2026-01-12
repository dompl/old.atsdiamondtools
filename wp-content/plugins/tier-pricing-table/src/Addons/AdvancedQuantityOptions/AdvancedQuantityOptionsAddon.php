<?php namespace TierPricingTable\Addons\AdvancedQuantityOptions;

use TierPricingTable\Addons\AbstractAddon;
use TierPricingTable\Addons\AdvancedQuantityOptions\API\MaximumOrderQuantity;
use TierPricingTable\Addons\AdvancedQuantityOptions\API\QuantityStep;
use TierPricingTable\Addons\AdvancedQuantityOptions\ProductEditor\ProductEditor;

class AdvancedQuantityOptionsAddon extends AbstractAddon {
	
	const MAXIMUM_QUANTITY_BASE_META_KEY = 'maximum_quantity';
	const GROUP_OF_QUANTITY_BASE_META_KEY = 'group_of_quantity';
	
	public function getName(): string {
		return __( 'Additional product quantity options', 'tier-pricing-table' );
	}
	
	public function getDescription(): string {
		return __( 'Manage maximum quantity and quantity step for products.', 'tier-pricing-table' );
	}
	
	public function getSlug(): string {
		return 'additional-product-quantity-options';
	}
	
	public function run() {
		$form = new AdvancedQuantityOptionsForm();
		
		new RoleBasedOptions( $form );
		new ProductOptions( $form );
		new GlobalPricingOptions( $form );
		new QuantityManager();
		
		// Extend API
		add_filter( 'tiered_pricing_table/api/product_fields', function ( $productFields ) {
			$productFields[] = MaximumOrderQuantity::class;
			$productFields[] = QuantityStep::class;
			
			return $productFields;
		} );
		
		new ProductEditor();
	}
}
