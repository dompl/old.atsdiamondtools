<?php namespace TierPricingTable\Integrations\Plugins\Elementor;

use Elementor\Plugin;
use TierPricingTable\Integrations\Plugins\PluginIntegrationAbstract;

class ElementorIntegration extends PluginIntegrationAbstract {
	
	public function getTitle(): string {
		return __( 'Elementor', 'tier-pricing-table' );
	}
	
	public function getDescription(): string {
		return __( 'Provides the tiered pricing widget with a bunch of settings, such as: display type, active tier color, etc.',
			'tier-pricing-table' );
	}
	
	public function getSlug(): string {
		return 'elementor';
	}
	
	public function getIconURL(): string {
		return $this->getContainer()->getFileManager()->locateAsset( 'admin/integrations/elementor-icon.svg' );
	}
	
	public function getAuthorURL(): string {
		return 'https://wordpress.org/plugins/elementor/';
	}
	
	public function run() {
		add_action( 'elementor/widgets/widgets_registered', function () {
			Plugin::instance()->widgets_manager->register( new ElementorWidget() );
		} );
	}
}
