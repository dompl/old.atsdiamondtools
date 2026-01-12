<?php namespace TierPricingTable\Settings\Sections\CalculationLogic;

use TierPricingTable\Settings\CustomOptions\TPTSwitchOption;
use TierPricingTable\Settings\Sections\SectionAbstract;
use TierPricingTable\Settings\Settings;

class CalculationLogic extends SectionAbstract {

	public function getSettings() {

		$settings = array();
		$advanced = apply_filters( 'tiered_pricing_table/settings/calculation_logic', $this->getMainSettings() );

		$sectionTitle = array(
				'title' => __( 'Calculation logic', 'tier-pricing-table' ),
				'desc'  => __( 'This section controls how tiered pricing does calculations.', 'tier-pricing-table' ),
				'id'    => Settings::SETTINGS_PREFIX . 'calculation_logic',
				'type'  => 'title',
		);

		$sectionEnd = array(
				'type' => 'sectionend',
		);

		$settings[] = $sectionTitle;
		$settings   = array_merge( $settings, $advanced );
		$settings[] = $sectionEnd;

		return array_merge( $settings, $this->getGlobalPricingRulesOptions() );
	}

	public function getMainSettings(): array {
		return array(
				array(
						'title'    => __( 'Consider product variations as one product', 'tier-pricing-table' ),
						'id'       => Settings::SETTINGS_PREFIX . 'summarize_variations',
						'type'     => TPTSwitchOption::FIELD_TYPE,
						'default'  => 'no',
						'desc'     => __( 'For the same variable product, the plugin will consider all its variations as the same product when calculating tiered pricing.',
								'tier-pricing-table' ),
						'desc_tip' => true,
				),
				array(
						'title'                => __( 'Always use regular price to calculate percentage discounts',
								'tier-pricing-table' ),
						'id'                   => Settings::SETTINGS_PREFIX . 'calculate_discount_based_on_regular_price',
						'type'                 => TPTSwitchOption::FIELD_TYPE,
						'extended_description' => array( $this, 'getCalculateDiscountDescription' ),
						'default'              => 'no',
				),
				array(
						'title'                => __( 'Round price', 'tier-pricing-table' ),
						'id'                   => Settings::SETTINGS_PREFIX . 'round_price',
						'type'                 => TPTSwitchOption::FIELD_TYPE,
						'default'              => 'yes',
						'extended_description' => __( 'WooCommerce rounds prices when showing them to users. To avoid possible rounding errors with displaying and calculation, the plugin rounds prices when calculating percentage discounts.',
								'tier-pricing-table' ),
						'desc_tip'             => true,
				),
		);
	}

	public function getCalculateDiscountDescription() {
		?>
		<p>
			<?php
				esc_html_e( 'If enabled, the plugin will always use the regular price to calculate percentage discounts. By default, the plugin uses the sale price if it exists.',
						'tier-pricing-table' );
			?>
		</p>
		<br>
		<p>
			<?php
				esc_html_e( 'For example: if the regular product price is $100.00, the sale price is $90.00, and there is a tiered
			pricing rule', 'tier-pricing-table' );
			?>
			<br>
			<b>
				<?php esc_html_e( '20 pieces → 20% off', 'tier-pricing-table' ); ?>:
			</b>
			<br>
			<?php
				esc_html_e( 'When the option is disabled, 20% will be calculated based on the sale price, in this case — ',
						'tier-pricing-table' );
			?>
			<b>$90.00 - 20% = $72.00</b>.
			<br>
			<?php
				esc_html_e( 'When the option is enabled, 20% will be calculated based on the regular price, in this case — ',
						'tier-pricing-table' );
			?>
			<b>$100.00 - 20% = $80.00</b>.
			<br>
			<br>
			<?php
				esc_html_e( 'This option is also affecting discount calculation in role-based and global pricing rules.',
						'tier-pricing-table' );
			?>
		</p>
		<?php
	}

	public function getSlug(): string {
		return 'calculation_logic';
	}

	public function getName(): string {
		return __( 'Calculation Logic', 'tier-pricing-table' );
	}

	protected function getGlobalPricingRulesOptions(): array {
		return array(
				array(
						'title' => __( 'Global pricing rules', 'tier-pricing-table' ),
						'desc'  => __( 'How global pricing rules behave.', 'tier-pricing-table' ),
						'type'  => 'title',
				),
				array(
						'title'                => __( 'Override product level rules', 'tier-pricing-table' ),
						'id'                   => Settings::SETTINGS_PREFIX . 'override_prices_by_global_rules',
						'extended_description' => __( 'Make global rule override product level and role-based rules. When this option is disabled, product level rules will have a higher priority.',
								'tier-pricing-table' ),
						'type'                 => TPTSwitchOption::FIELD_TYPE,
						'default'              => 'no',
				),
				array(
						'type' => 'sectionend',
				),
		);
	}

}