<?php namespace TierPricingTable\Addons\GlobalTieredPricing\CPT\Columns;

use TierPricingTable\Addons\GlobalTieredPricing\GlobalPricingRule;

class AppliedQuantityRules {

	public function getName(): string {
		return __( 'Quantity limits', 'tier-pricing-table' );
	}

	public function render( GlobalPricingRule $rule ) {

		$notSetLabel = __( 'N/A', 'tier-pricing-table' );

		$minimum = $rule->getMinimum() ? $rule->getMinimum() : $notSetLabel;

		?>
		<p>
			<?php esc_html_e( 'Minimum', 'tier-pricing-table' ); ?>:
			<b><?php echo esc_html( $minimum ? esc_html( $minimum ) : $notSetLabel ); ?></b>
		</p>
		<?php
	}
}
