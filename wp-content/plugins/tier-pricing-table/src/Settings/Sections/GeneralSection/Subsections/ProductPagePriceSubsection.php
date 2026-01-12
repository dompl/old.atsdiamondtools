<?php namespace TierPricingTable\Settings\Sections\GeneralSection\Subsections;

use TierPricingTable\Core\ServiceContainer;
use TierPricingTable\Settings\CustomOptions\TPTDisplayType;
use TierPricingTable\Settings\CustomOptions\TPTSwitchOption;
use TierPricingTable\Settings\Sections\SubsectionAbstract;
use TierPricingTable\Settings\Settings;

class ProductPagePriceSubsection extends SubsectionAbstract {
	
	public function getTitle(): string {
		return __( 'Price on the product page', 'tier-pricing-table' );
	}
	
	public function getDescription(): string {
		return __( 'Control how the product price with tiered pricing will look and behave on the product page.',
			'tier-pricing-table' );
	}
	
	public function getSlug(): string {
		return 'product_page_price';
	}
	
	public function getSettings(): array {
		return array(
			array(
				'title'   => __( 'Price behaviour', 'tier-pricing-table' ),
				'id'      => Settings::SETTINGS_PREFIX . 'product_page_price_format',
				'type'    => TPTDisplayType::FIELD_TYPE,
				'options' => array(
					'same_as_catalog' => __( 'Match catalog display (price range or lowest price)', 'tier-pricing-table' ),
					'custom'          => __( 'Dynamic', 'tier-pricing-table' ),
				),
				'default' => ServiceContainer::getInstance()->getSettings()->get( 'tiered_price_at_product_page',
					'no' ) === 'yes' ? 'same_as_catalog' : 'custom',
			),
			array(
				'title'   => __( 'Show actual price', 'tier-pricing-table' ),
				'id'      => Settings::SETTINGS_PREFIX . 'update_price_on_product_page',
				'type'    => TPTSwitchOption::FIELD_TYPE,
				'default' => 'yes',
				'desc'    => __( 'The product price is automatically updated when a new price tier is reached.',
					'tier-pricing-table' ),
			),
			array(
				'title'   => __( 'Show tiered price as a discount', 'tier-pricing-table' ),
				'id'      => Settings::SETTINGS_PREFIX . 'show_tiered_price_as_discount',
				'type'    => TPTSwitchOption::FIELD_TYPE,
				'default' => 'yes',
				'desc'    => __( 'When a tiered price is applied, the original price will be crossed out and the discounted price shown.',
					'tier-pricing-table' ),
			),
			array(
				'title'             => __( 'Show total price', 'tier-pricing-table' ),
				'id'                => Settings::SETTINGS_PREFIX . 'show_total_price',
				'type'              => TPTSwitchOption::FIELD_TYPE,
				'default'           => 'no',
				'desc'              => __( 'The product price will be shown as a total price based on the selected pricing tier and quantity.',
					'tier-pricing-table' ),
			),
		);
	}
	
	public static function getFormatPriceType() {
		
		$settings = ServiceContainer::getInstance()->getSettings();
		
		$default = $settings->get( 'tiered_price_at_product_page', 'no' ) === 'yes' ? 'same_as_catalog' : 'custom';
		
		return $settings->get( 'product_page_price_format', $default );
	}
}
