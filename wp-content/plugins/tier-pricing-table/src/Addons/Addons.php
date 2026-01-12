<?php namespace TierPricingTable\Addons;

use TierPricingTable\Addons\AdvancedQuantityOptions\AdvancedQuantityOptionsAddon;
use TierPricingTable\Addons\CategoryTiers\CategoryTierAddon;
use TierPricingTable\Addons\Coupons\CouponsAddon;
use TierPricingTable\Addons\CustomColumns\CustomColumnsAddon;
use TierPricingTable\Addons\GlobalTieredPricing\GlobalTieredPricingAddon;
use TierPricingTable\Addons\ManualOrders\ManualOrdersAddon;
use TierPricingTable\Addons\MinQuantity\MinQuantity;
use TierPricingTable\Addons\PluginsRecommendations\PluginsRecommendationsAddon;
use TierPricingTable\Addons\ProductCatalogLoop\ProductCatalogLoop;
use TierPricingTable\Addons\ReactProductEditorAddon\ReactProductEditorAddon;
use TierPricingTable\Addons\RoleBasedPricing\RoleBasedPricingAddon;
use TierPricingTable\Core\ServiceContainerTrait;

class Addons {
	
	use ServiceContainerTrait;
	
	/**
	 * Addons constructor.
	 */
	public function __construct() {
		$this->init();
	}
	
	public function init() {
		
		$addons = array(
			ManualOrdersAddon::class            => new ManualOrdersAddon(),
			GlobalTieredPricingAddon::class     => new GlobalTieredPricingAddon(),
			CouponsAddon::class                 => new CouponsAddon(),
			RoleBasedPricingAddon::class        => new RoleBasedPricingAddon(),
			CategoryTierAddon::class            => new CategoryTierAddon(),
			AdvancedQuantityOptionsAddon::class => new AdvancedQuantityOptionsAddon(),
			PluginsRecommendationsAddon::class  => new PluginsRecommendationsAddon(),
			CustomColumnsAddon::class           => new CustomColumnsAddon(),
			ProductCatalogLoop::class           => new ProductCatalogLoop(),
			ReactProductEditorAddon::class      => new ReactProductEditorAddon(),
			MinQuantity::class                  => new MinQuantity(),
		);
		
		$addons = apply_filters( 'tiered_pricing_table/addons/list', $addons );
		
		foreach ( $addons as $addon ) {
			
			if ( $addon->isEnabled() ) {
				$addon->run();
			}
		}
	}
}
