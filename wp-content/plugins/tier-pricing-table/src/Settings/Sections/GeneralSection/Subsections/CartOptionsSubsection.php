<?php namespace TierPricingTable\Settings\Sections\GeneralSection\Subsections;

use TierPricingTable\Settings\CustomOptions\TPTSwitchOption;
use TierPricingTable\Settings\Sections\SubsectionAbstract;
use TierPricingTable\Settings\Settings;

class CartOptionsSubsection extends SubsectionAbstract {
	
	public function getTitle(): string {
		return __( 'Tiered pricing in the cart', 'tier-pricing-table' );
	}
	
	public function getDescription(): string {
		return __( 'Control how tiered pricing will be shown in the cart.', 'tier-pricing-table' );
	}
	
	public function getSlug(): string {
		return 'cart';
	}
	
	public function getSettings(): array {
		return array(
			array(
				'title'   => __( 'Show cart item price as a discount', 'tier-pricing-table' ),
				'id'      => Settings::SETTINGS_PREFIX . 'show_discount_in_cart',
				'desc'    => __( 'When a product in the cart has a tiered price, show it as a discount with a crossed-out original price near it. For example: ',
						'tier-pricing-table' ) . ' <b><del>$10.00</del> <ins>$8.00</ins><b>',
				'type'    => TPTSwitchOption::FIELD_TYPE,
				'default' => 'yes',
			),
			array(
				'title'   => __( 'Show cart item subtotal as a discount', 'tier-pricing-table' ),
				'id'      => Settings::SETTINGS_PREFIX . 'show_subtotal_as_discount_in_cart',
				'desc'    => __( 'When a product in the cart has a tiered price, show its subtotal as a discount, similar to the option above.',
					'tier-pricing-table' ),
				'type'    => TPTSwitchOption::FIELD_TYPE,
				'default' => 'yes',
			),
			array(
				'title'   => __( 'Always cross out the regular price', 'tier-pricing-table' ),
				'id'      => Settings::SETTINGS_PREFIX . 'consider_sale_price_as_discount_in_cart',
				'desc'    => __( 'When showing a crossed-out price, always use the regular price.',
					'tier-pricing-table' ),
				'type'    => TPTSwitchOption::FIELD_TYPE,
				'default' => 'no',
			),
		);
	}
	
	
	/**
	 * When a cart item has a tiered price, show its subtotal as a discount with the original subtotal crossed out.
	 *
	 * @return bool
	 */
	public static function showSubtotalInCartAsDiscount(): bool {
		return get_option( Settings::SETTINGS_PREFIX . 'show_subtotal_as_discount_in_cart', 'yes' ) === 'yes';
	}
	
	/**
	 * Do global pricing rules have a higher priority than product level rules?
	 *
	 * @return bool
	 */
	public static function globalRulesOverrideProductLevelRules(): bool {
		return get_option( Settings::SETTINGS_PREFIX . 'override_prices_by_global_rules', 'no' ) === 'yes';
	}
}
