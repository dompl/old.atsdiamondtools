<?php namespace TierPricingTable\Settings\Sections\GeneralSection\Subsections;

use TierPricingTable\Settings\CustomOptions\TPTSwitchOption;
use TierPricingTable\Settings\CustomOptions\TPTTextTemplate;
use TierPricingTable\Settings\Sections\SubsectionAbstract;
use TierPricingTable\Settings\Settings;

class UpsellsSubsection extends SubsectionAbstract {
	
	public function getTitle(): string {
		return __( 'Cart upsells', 'tier-pricing-table' );
	}
	
	public function getDescription(): string {
		return __( 'Use cart upsell messages to encourage customers to buy more by displaying available discounts.',
			'tier-pricing-table' );
	}
	
	public function getSlug(): string {
		return 'cart-upsells';
	}
	
	public function getSettings(): array {
		return array(
			array(
				'title'    => __( 'Enabled', 'tier-pricing-table' ),
				'id'       => Settings::SETTINGS_PREFIX . 'cart_upsell_enabled',
				'type'     => TPTSwitchOption::FIELD_TYPE,
				'default'  => 'no',
				'desc_tip' => false,
			),
			
			array(
				'title'        => __( 'Template', 'tier-pricing-table' ),
				'id'           => Settings::SETTINGS_PREFIX . 'cart_upsell_template',
				'type'         => TPTTextTemplate::FIELD_TYPE,
				'placeholders' => array(
					'tp_required_quantity',
					'tp_next_price',
					'tp_next_discount',
					'tp_actual_discount',
				),
				'default'      => __( 'Buy <b>{tp_required_quantity}</b> more to get <b>{tp_next_price}</b> each',
					'tier-pricing-table' ),
				'desc_tip'     => false,
				'desc'         => __( 'Note: This feature is not compatible with the Gutenberg Cart/Checkout blocks.',
					'tier-pricing-table' ),
			),
			
			array(
				'title'    => __( 'Upsell text color', 'tier-pricing-table' ),
				'id'       => Settings::SETTINGS_PREFIX . 'cart_upsell_color',
				'type'     => 'color',
				'default'  => '#96598A',
				'css'      => 'width:6em;',
				'desc_tip' => false,
			),
		);
	}
}
