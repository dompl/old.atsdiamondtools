<?php namespace TierPricingTable\Addons\GlobalTieredPricing\CPT\Form\Tabs;

use TierPricingTable\Addons\GlobalTieredPricing\CPT\Form\FormTab;
use TierPricingTable\Addons\GlobalTieredPricing\GlobalPricingRule;

class Settings extends FormTab {
	
	public function getId(): string {
		return 'settings';
	}
	
	public function getTitle(): string {
		return __( 'Priority Settings', 'tier-pricing-table' );
	}
	
	public function getDescription(): string {
		return __( 'Handle pricing collisions with product-level prices.', 'tier-pricing-table' );
	}
	
	public function render( GlobalPricingRule $pricingRule ) {
		
		$this->renderSectionTitle( __( 'Priority Settings', 'tier-pricing-table' ) );
		
		$this->renderHint( __( 'Control how this global pricing rule behaves in case of collisions with product-level rules.',
			'tier-pricing-table' ) );
		
		$this->renderRadioOptions( array(
			'id'      => '_tpt_settings_priority_type',
			'title'   => __( 'Pricing rule priority', 'tier-pricing-table' ),
			'options' => array(
				'default'        => __( 'Use global settings', 'tier-pricing-table' ),
				'prefer-product' => __( 'Product-level rules take priority over this global rule.',
					'tier-pricing-table' ),
				'override'       => __( 'This rule takes priority over any product-level pricing rules',
					'tier-pricing-table' ),
				'flexible'       => __( 'Flexible: set priorities for the pricing rule parts individually.',
					'tier-pricing-table' ),
			),
			'value'   => $pricingRule->getSettings()->getPriorityType(),
		) );
		
		?>

		<div class="tpt_settings_advanced_priority_settings hidden">
			<?php
				$this->renderSectionTitle( __( 'Regular prices priority', 'tier-pricing-table' ) );
			?>
			<div class="tpt_settings_regular_pricing">
				<?php
					$this->renderRadioOptions( array(
						'id'      => '_tpt_settings_regular_pricing_priority_type',
						'title'   => __( 'Regular prices priority', 'tier-pricing-table' ),
						'options' => array(
							'prefer-role-based-product' => __( 'Prefer role-based product-level regular prices if they exist.',
								'tier-pricing-table' ),
							'override'                  => __( 'Override any product-level regular prices with the prices set in this rule.',
								'tier-pricing-table' ),
						),
						'value'   => $pricingRule->getSettings()->getRegularPricingPriority(),
					) );
					
					$this->renderHint( __( 'If a product has role-based pricing set at the product level, you can prefer those regular prices over the ones set in this global rule.',
						'tier-pricing-table' ) );
				?>
			</div>
			
			<?php
				$this->renderSectionTitle( __( 'Tiered pricing priority', 'tier-pricing-table' ) );
			?>

			<div class="tpt_settings_tiered_pricing">
				<?php
					$this->renderRadioOptions( array(
						'id'      => '_tpt_settings_tiered_pricing_priority_type',
						'title'   => __( 'Tiered pricing priority', 'tier-pricing-table' ),
						'options' => array(
							'prefer-product'            => __( 'Prefer any product-level tiered prices if they exist.',
								'tier-pricing-table' ),
							'prefer-role-based-product' => __( 'Prefer only role-based product-level tiered pricing if they exist.',
								'tier-pricing-table' ),
							'override'                  => __( 'Override any product-level tiered prices with the prices set in this rule.',
								'tier-pricing-table' ),
						),
						'value'   => $pricingRule->getSettings()->getTieredPricingPriority(),
					) );
					
					$this->renderCheckbox( array(
						'title' => __( 'Mix and Match', 'tier-pricing-table' ),
						'id'    => '_tpt_settings_tiered_pricing_allow_mix_and_match',
						'value' => $pricingRule->getSettings()->isAllowTieredPricingMixAndMatch(),
						'label' => __( 'Allow the "Mix and Match" pricing strategy (if selected) for tiered prices inherited from the product level.',
							'tier-pricing-table' ),
					) );
					
					$this->renderHint( __( 'If a product has tiered pricing set at the product level, you can prefer those tiered prices over the ones set in this global rule. You can also enable the "Mix and Match" pricing strategy for tiered prices inherited from the product level.',
						'tier-pricing-table' ) );
				?>
			</div>
			
			<?php
				$this->renderSectionTitle( __( 'Quantity limits priority', 'tier-pricing-table' ) );
			?>

			<div class="tpt_settings_quantity_limits">
				<?php
					$this->renderRadioOptions( array(
						'id'      => '_tpt_settings_quantity_limits_priority_type',
						'title'   => __( 'Quantity limits priority', 'tier-pricing-table' ),
						'options' => array(
							'prefer-product'            => __( 'Prefer any product-level quantity limits if they exist.',
								'tier-pricing-table' ),
							'prefer-role-based-product' => __( 'Prefer only role-based product-level quantity limits if they exist.',
								'tier-pricing-table' ),
							'override'                  => __( 'Override any product-level quantity limits with the limits set in this rule.',
								'tier-pricing-table' ),
						),
						'value'   => $pricingRule->getSettings()->getQuantityLimitsPriority(),
					) );
					
					$this->renderHint( __( 'If a product has quantity limits set at the product level, you can prefer those quantity limits over the ones set in this global rule.',
						'tier-pricing-table' ) );
				?>
			</div>
		</div>

		<script>
			jQuery(document).ready(function (jQuery) {
				jQuery('[name=_tpt_settings_priority_type]').on('change', function () {

					if (jQuery(this).val() === 'flexible') {
						jQuery('.tpt_settings_advanced_priority_settings').removeClass('hidden');
					} else {
						jQuery('.tpt_settings_advanced_priority_settings').addClass('hidden');
					}
				}).filter(':checked').trigger('change');
			});
		</script>
		
		<?php
	}
	
	public function getIcon(): string {
		return 'dashicons-admin-settings';
	}
}