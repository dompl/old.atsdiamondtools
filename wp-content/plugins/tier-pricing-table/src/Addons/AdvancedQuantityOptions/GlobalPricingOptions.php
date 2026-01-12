<?php namespace TierPricingTable\Addons\AdvancedQuantityOptions;

use TierPricingTable\Addons\GlobalTieredPricing\GlobalPricingRule;
use TierPricingTable\CalculationLogic;
use TierPricingTable\PricingRule;

class GlobalPricingOptions {
	
	/**
	 * Form
	 *
	 * @var AdvancedQuantityOptionsForm
	 */
	protected $form;
	
	public function __construct( AdvancedQuantityOptionsForm $form ) {
		
		$this->form = $form;
		
		add_action( 'tiered_pricing_table/global_pricing/after_minimum_order_quantity_field', function ( $ruleId ) {
			$this->form->render( $ruleId, null, null, true, false );
		} );
		
		add_action( 'tiered_pricing_table/global_pricing/before_updating',
			function ( GlobalPricingRule $rule, $ruleId ) {
				DataProvider::updateFromRequest( 'maximum', $ruleId );
				DataProvider::updateFromRequest( 'group_of', $ruleId );
			}, 10, 2 );
		
		// Add custom data to pricing rule object
		add_filter( 'tiered_pricing_table/global_pricing/after_built_rule', function ( GlobalPricingRule $rule ) {
			
			$rule->data['maximum_quantity']  = DataProvider::getMaximumQuantity( $rule->getId() );
			$rule->data['group_of_quantity'] = DataProvider::getGroupOfQuantity( $rule->getId() );
			
			return $rule;
		} );
		
		// Adjust pricing rule by data from global pricing rule
		add_action( 'tiered_pricing_table/global_pricing/before_adjusting_pricing_rule', function (
			PricingRule $pricingRule,
			GlobalPricingRule $globalPricingRule,
			$productId,
			$priority
		) {
			$globalMaximum = $globalPricingRule->data['maximum_quantity'] ?? null;
			$globalGroupOf = $globalPricingRule->data['group_of_quantity'] ?? null;
			
			$updateQuantityLimits = false;
			
			if ( 'flexible' === $priority ) {
				$priority = $globalPricingRule->getSettings()->getQuantityLimitsPriority();
			}
			
			if ( 'prefer-role-based-product' === $priority ) {
				
				// Update only if there are no quantity limits set in the product or role-based
				if ( 'role-based' !== $pricingRule->provider ) {
					$updateQuantityLimits = true;
				}
				
			} elseif ( 'prefer-product' === $priority ) {
				// Update only if there are no quantity limits set in the product
				if ( empty( $pricingRule->data['maximum_quantity'] ) && empty( $pricingRule->data['group_of_quantity'] ) ) {
					$updateQuantityLimits = true;
				}
			} else {
				$updateQuantityLimits = true;
			}
			
			if ( $updateQuantityLimits ) {
				$pricingRule->data['maximum_quantity']  = $globalMaximum ? intval( $globalMaximum ) : null;
				$pricingRule->data['group_of_quantity'] = $globalGroupOf ? intval( $globalGroupOf ) : null;
				
				$pricingRule->logPricingModification( 'Maximum order quantity and quantity-step were updated by global rule' );
			}
			
			return $pricingRule;
			
		}, 10, 4 );
		
		add_action( 'tiered_pricing_table/global_pricing/table/after_tab_render',
			function ( $column, GlobalPricingRule $rule ) {
				if ( 'applied_quantity_rules' === $column ) {
					$maximum = $rule->data['maximum_quantity'];
					$groupOf = $rule->data['group_of_quantity'];
					$notSet  = __( 'N/A', 'tier-pricing-table' );
					
					if ( $maximum || $groupOf ) {
						?>
						<p>
							<?php esc_html_e( 'Maximum', 'tier-pricing-table' ); ?>:
							<b><?php echo esc_html( $maximum ? esc_html( $maximum ) : $notSet ); ?></b>
						</p>

						<p>
							<?php esc_html_e( 'Quantity step', 'tier-pricing-table' ); ?>:
							<b><?php echo esc_html( $groupOf ? esc_html( $groupOf ) : $notSet ); ?></b>
						</p>
						<?php
					}
				}
			}, 10, 2 );
		
		add_filter( 'tiered_pricing_table/global_pricing/validation',
			function ( $valid, GlobalPricingRule $pricingRule ) {
				
				if ( $pricingRule->data['maximum_quantity'] || $pricingRule->data['group_of_quantity'] ) {
					return true;
				}
				
				return $valid;
			}, 10, 2 );
	}
}