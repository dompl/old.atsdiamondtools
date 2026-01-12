<?php namespace TierPricingTable\Addons\GlobalTieredPricing\CPT\Columns;

use TierPricingTable\Addons\GlobalTieredPricing\GlobalPricingRule;

class Status {
	
	public function getName(): string {
		return '<span class="dashicons dashicons-post-status"></span>';
	}
	
	public function render( GlobalPricingRule $rule ) {
		if ( $rule->isSuspended() ) {
			?>
			<mark class="tpt-rule-suspend-status tpt-rule-suspend-status--suspended"
				  title="<?php esc_attr_e( 'The rule is suspended', 'tier-pricing-table' ); ?>">
			</mark>
			<?php
		} else {
			?>
			<mark class="tpt-rule-suspend-status tpt-rule-suspend-status--active"
				  title="<?php esc_attr_e( 'The rule is active', 'tier-pricing-table' ); ?>">
			</mark>
			<?php
		}
	}
}
