<?php namespace TierPricingTable\Addons\PluginsRecommendations;

use TierPricingTable\Addons\AbstractAddon;

class PluginsRecommendationsAddon extends AbstractAddon {
	
	public function getName(): string {
		return __( 'Plugins recommendations', 'tier-pricing-table' );
	}
	
	public function getDescription(): string {
		return __( 'The plugin will recommend some addons or compatible plugins.', 'tier-pricing-table' );
	}
	
	public function getSlug(): string {
		return 'plugins-recommendations';
	}
	
	public function run() {
		new ConditionalLogicForProductAddons();
		new CancellationSurveysPlugin();
		new BulkPriceEditorPlugin();
	}
}
