<?php namespace TierPricingTable\Addons\RoleBasedPricing;

use TierPricingTable\Addons\AbstractAddon;

class RoleBasedPricingAddon extends AbstractAddon {
	
	const SETTING_ENABLE_KEY = 'enable_role_based_pricing_addon';
	
	public function getName(): string {
		return __( 'Product level role-based pricing rules', 'tier-pricing-table' );
	}
	
	public function isActive(): bool {
		return $this->getContainer()->getSettings()->get( self::SETTING_ENABLE_KEY, 'yes' ) === 'yes';
	}
	
	public function getDescription(): string {
		return __( 'Enable role-based pricing rules at the product level. Disabling this will not affect role-based functionality for global pricing rules.',
			'tier-pricing-table' );
	}
	
	public function getSlug(): string {
		return 'role-based-rules';
	}
	
	public function run() {
		// Enable pricing service
		add_filter( 'tiered_pricing_table/services/pricing_service_enabled', '__return_true' );
		
		new PricingService();
		new ProductManager();
	}
}
