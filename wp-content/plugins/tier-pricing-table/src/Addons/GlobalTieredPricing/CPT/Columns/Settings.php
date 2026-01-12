<?php namespace TierPricingTable\Addons\GlobalTieredPricing\CPT\Columns;

use TierPricingTable\Addons\GlobalTieredPricing\GlobalPricingRule;
use TierPricingTable\CalculationLogic;

class Settings {
	
	public function getName(): string {
		return __( 'Priority Settings', 'tier-pricing-table' );
	}
	
	public function render( GlobalPricingRule $rule ) {
		
		$prioritySlug = $rule->getSettings()->getPriorityType();
		
		if ( 'default' === $prioritySlug ) {
			$realPriority = CalculationLogic::globalRulesOverrideProductLevelRules() ? 'override' : 'prefer-product';
		} else {
			$realPriority = $prioritySlug;
		}
		
		$priorities = array(
			'default'                   => __( 'Global', 'tier-pricing-table' ),
			'prefer-product'            => __( 'Prefer Product', 'tier-pricing-table' ),
			'prefer-role-based-product' => __( 'Prefer Product', 'tier-pricing-table' ),
			'override'                  => __( 'Override', 'tier-pricing-table' ),
			'flexible'                  => __( 'Flexible', 'tier-pricing-table' ),
		);
		
		
		if ( ! array_key_exists( $prioritySlug, $priorities ) ) {
			return;
		}
		
		?>
		<div>
			<p>
				<?php esc_html_e( 'Priority type', 'tier-pricing-table' ); ?>:
				<b><?php echo esc_html( $priorities[ $realPriority ] ); ?></b>
				
				<?php if ( 'flexible' === $prioritySlug ) : ?>
					<br>
					<br>
					<?php esc_html_e( 'Regular prices priority' ); ?>:
					<br>
					<b>
						<?php echo esc_html( $priorities[ $rule->getSettings()->getRegularPricingPriority() ] ); ?>
					</b>
					<br>
					<br>
					<?php esc_html_e( 'Tiered pricing priority' ); ?>:
					<b>
						<?php echo esc_html( $priorities[ $rule->getSettings()->getTieredPricingPriority() ] ); ?>
					</b>
					<br>
					<br>
					<?php esc_html_e( 'Quantity Limits Priority' ); ?>:
					<b>
						<?php echo esc_html( $priorities[ $rule->getSettings()->getQuantityLimitsPriority() ] ); ?>
					</b>
				<?php endif; ?>
			</p>
		</div>
		
		<?php
	}
}
