<?php namespace TierPricingTable\Addons\GlobalTieredPricing\CPT\Form\Tabs;

use TierPricingTable\Addons\GlobalTieredPricing\CPT\Form\FormTab;
use TierPricingTable\Addons\GlobalTieredPricing\GlobalPricingRule;
use TierPricingTable\Forms\MinimumOrderQuantityForm;

class Quantity extends FormTab {
	
	public function getId(): string {
		return 'quantity';
	}
	
	public function getTitle(): string {
		return __( 'Quantity limits', 'tier-pricing-table' );
	}
	
	public function getDescription(): string {
		return __( 'Specify the minimum, maximum, and quantity step for products.', 'tier-pricing-table' );
	}
	
	public function render( GlobalPricingRule $pricingRule ) {
		
		$this->renderSectionTitle( __( 'Quantity Limits', 'tier-pricing-table' ) );
		
		$this->renderHint( __( 'Quantity rules are applied to each product individually.', 'tier-pricing-table' ) );
		
		MinimumOrderQuantityForm::render( null, null, $pricingRule->getMinimum() );
		
		do_action( 'tiered_pricing_table/global_pricing/after_minimum_order_quantity_field', $pricingRule->getId(),
			$pricingRule );
		?>
		<?php
	}
	
	public function getIcon(): string {
		return 'dashicons-database';
	}
}
