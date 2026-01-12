<?php namespace TierPricingTable\Settings\Sections\GeneralSection\Subsections;

use TierPricingTable\Settings\CustomOptions\TPTDisplayType;
use TierPricingTable\Settings\CustomOptions\TPTSwitchOption;
use TierPricingTable\Settings\Sections\SubsectionAbstract;
use TierPricingTable\Settings\Settings;

class CatalogPricesSubsection extends SubsectionAbstract {
	
	public function getTitle(): string {
		return __( 'Price format for products with tiered pricing (in the catalog, widgets, etc.)',
			'tier-pricing-table' );
	}
	
	public function getDescription(): string {
		return __( 'Control how products with tiered pricing will show their prices outside their product pages.',
			'tier-pricing-table' );
	}
	
	public function getSlug(): string {
		return 'catalog_prices';
	}
	
	public function getSettings(): array {
		return array(
			array(
				'title'    => __( 'Format price', 'tier-pricing-table' ),
				'id'       => Settings::SETTINGS_PREFIX . 'tiered_price_at_catalog',
				'type'     => TPTSwitchOption::FIELD_TYPE,
				'default'  => 'yes',
				'desc'     => __( 'Format price based on tiered pricing (show pricing range or minimal price).',
					'tier-pricing-table' ),
				'desc_tip' => true,
			),
			array(
				'title'    => __( 'Format price for variable products', 'tier-pricing-table' ),
				'id'       => Settings::SETTINGS_PREFIX . 'tiered_price_at_catalog_for_variable',
				'type'     => TPTSwitchOption::FIELD_TYPE,
				'default'  => 'no',
				'desc'     => __( 'Format price based on tiered pricing (show pricing range or minimal price) for variable products. Uses the lowest and the highest prices from all variations.',
					'tier-pricing-table' ),
				'desc_tip' => true,
			),
			array(
				'title'    => __( 'Display price as', 'tier-pricing-table' ),
				'id'       => Settings::SETTINGS_PREFIX . 'tiered_price_at_catalog_type',
				'type'     => TPTDisplayType::FIELD_TYPE,
				'options'  => [
					'lowest' => __( 'Show lowest price', 'tier-pricing-table' ),
					'range'  => __( 'Show range (lowest to highest)', 'tier-pricing-table' ),
				],
				'default'  => 'lowest',
				'desc'     => __( 'Choose how to display tiered pricing: show only the lowest price or the full price range.', 'tier-pricing-table' ),
				'desc_tip' => true,
			),
			array(
				'title'   => __( 'The lowest price prefix', 'tier-pricing-table' ),
				'id'      => Settings::SETTINGS_PREFIX . 'lowest_prefix',
				'type'    => 'text',
				'default' => __( 'From', 'tier-pricing-table' ),
				'desc'    => __( 'Enter a prefix that will be shown before the lowest price. For example, it can look like this: <b>From $10.00</b>',
					'tier-pricing-table' ),
			),
		);
	}
}
