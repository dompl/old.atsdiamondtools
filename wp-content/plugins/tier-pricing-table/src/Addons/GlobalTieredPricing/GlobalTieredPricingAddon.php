<?php namespace TierPricingTable\Addons\GlobalTieredPricing;

use TierPricingTable\Addons\AbstractAddon;
use TierPricingTable\Addons\GlobalTieredPricing\CPT\GlobalTieredPricingCPT;

class GlobalTieredPricingAddon extends AbstractAddon {
	
	public function getName(): string {
		return __( 'Global pricing rules', 'tier-pricing-table' );
	}
	
	public function run() {
		
		// Enable pricing service
		add_filter( 'tiered_pricing_table/services/pricing_service_enabled', '__return_true' );
		
		new LookupService();
		new GlobalTieredPricingCPT();
		new GlobalTieredPricingCartManager();
		new PricingService();
		
		GlobalPricingRulesRepository::getInstance();
		
		add_action( 'tiered_pricing_table/admin/pricing_tab_end', array(
			$this,
			'showMessageOnProductsTieredPricingTab',
		), 999 );
	}
	
	public function showMessageOnProductsTieredPricingTab() {
		$globalRules = GlobalTieredPricingCPT::getGlobalRules( false );
		
		if ( empty( $globalRules ) ) {
			$this->getContainer()->getFileManager()->includeTemplate( 'addons/global-rules/tiered-pricing-tab.php' );
		}
	}
	
	public function getDescription(): string {
		return __( 'Create pricing rules for user roles or specific users, and apply them to selected products or product categories.',
			'tier-pricing-table' );
	}
	
	public function getSlug(): string {
		return 'global-tier-pricing';
	}
}
