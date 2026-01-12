<?php namespace TierPricingTable\Addons\GlobalTieredPricing\CPT\Columns;

use ArrayIterator;
use Exception;
use TierPricingTable\Addons\GlobalTieredPricing\GlobalPricingRule;
use TierPricingTable\Core\ServiceContainerTrait;

class Pricing {
	
	use ServiceContainerTrait;
	
	public function getName(): string {
		return __( 'Pricing', 'tier-pricing-table' );
	}
	
	public function render( GlobalPricingRule $rule ) {
		try {
			$rule->validatePricing();
			
			$pricingType = $rule->getTieredPricingType();
			$rules       = 'percentage' === $pricingType ? $rule->getPercentageTieredPricingRules() : $rule->getFixedTieredPricingRules();
			$minimum     = $rule->getMinimum() ? intval( $rule->getMinimum() ) : 1;
			
			$regularProductPriceString = __( 'Regular price', 'tier-pricing-table' );
			
			if ( $rule->getPricingType() === 'flat' ) {
				if ( $rule->getSalePrice() ) {
					$regularProductPriceString = wc_price( $rule->getSalePrice() );
				} elseif ( $rule->getRegularPrice() ) {
					$regularProductPriceString = wc_price( $rule->getRegularPrice() );
				}
			} elseif ( $rule->getDiscount() ) {
				$regularProductPriceString = $rule->getDiscount() . '% off';
			}
			
			?>
			
			<?php if ( $rule->getPricingType() === 'flat' ) : ?>
				<?php if ( $rule->getRegularPrice() ) : ?>
					<p>
						<?php
							echo wp_kses_post( __( 'Regular Price',
									'tier-pricing-table' ) . ': <b>' . wc_price( $rule->getRegularPrice() ) . '</b>' );
						?>
					</p>
				<?php endif; ?>
				
				<?php if ( $rule->getSalePrice() ) : ?>
					<p>
						<?php
							echo wp_kses_post( __( 'Sale Price',
									'tier-pricing-table' ) . ': <b>' . wc_price( $rule->getSalePrice() ) . '</b>' );
						?>
					</p>
				<?php endif; ?>
			<?php else : ?>
				<?php if ( $rule->getDiscount() ) : ?>
					<p>
						<?php esc_html_e( 'Discount', 'tier-pricing-table' ); ?>
						:
						<b><?php echo esc_html( $rule->getDiscount() ); ?>%</b>
						<small>(
							<?php
								$rule->getDiscountType() === 'sale_price' ? esc_html_e( 'Sale price',
									'tier-pricing-table' ) : esc_html_e( 'Regular price', 'tier-pricing-table' );
							?>
							)</small>
					</p>
				<?php endif; ?>
			<?php endif; ?>
			
			<?php
			if ( empty( $rules ) ) {
				return;
			}
			?>
			<table class="wp-list-table widefat fixed striped table-view-list tpt-global-rule-pricing-table">
				<thead>
				<tr>
					<th>
						<b><?php esc_html_e( 'Quantity', 'tier-pricing-table' ); ?></b></th>
					<th>
						<b>
							<?php if ( 'percentage' === $pricingType ) : ?>
								<?php esc_html_e( 'Discount', 'tier-pricing-table' ); ?>
							<?php else : ?>
								<?php esc_html_e( 'Price', 'tier-pricing-table' ); ?>
							<?php endif; ?>
						</b>
					</th>
				</tr>
				</thead>
				<tbody>
				<tr>
					<td>
						<?php if ( 1 >= array_keys( $rules )[0] - $minimum ) : ?>
							<span><?php echo esc_attr( number_format_i18n( $minimum ) ); ?></span>
						<?php else : ?>
							<span><?php echo esc_attr( number_format_i18n( $minimum ) ); ?> - <?php echo esc_attr( number_format_i18n( array_keys( $rules )[0] - 1 ) ); ?></span>
						<?php endif; ?>
					</td>

					<td>
						<?php if ( 'percentage' === $pricingType ) : ?>
							<?php echo wp_kses_post( $regularProductPriceString ); ?>
						<?php else : ?>
							<?php echo wp_kses_post( $regularProductPriceString ); ?>
						<?php endif; ?>
					</td>
				</tr>
				
				<?php $iterator = new ArrayIterator( $rules ); ?>
				
				<?php while ( $iterator->valid() ) : ?>
					<?php
					$currentPrice    = $iterator->current();
					$currentQuantity = $iterator->key();
					
					$iterator->next();
					
					if ( $iterator->valid() ) {
						$quantity = $currentQuantity;
						
						if ( intval( $iterator->key() - 1 != $currentQuantity ) ) {
							
							$quantity = number_format_i18n( $quantity );
							
							if ( $this->getContainer()->getSettings()->get( 'quantity_type', 'range' ) === 'range' ) {
								$quantity .= ' - ' . number_format_i18n( intval( $iterator->key() - 1 ) );
							}
						}
					} else {
						$quantity = number_format_i18n( $currentQuantity ) . '+';
					}
					?>
					<tr>
						<td>
							<?php echo esc_attr( $quantity ); ?>
						</td>

						<td>
							
							<?php if ( 'percentage' === $pricingType ) : ?>
								<?php echo esc_html( $currentPrice . '%' ); ?>
							
							<?php else : ?>
								<?php echo wp_kses_post( wc_price( $currentPrice ) ); ?>
							
							<?php endif; ?>

						</td>
					</tr>
				<?php endwhile; ?>


				</tbody>
			</table>
			<h4 style="margin-top: 10px;">
				<?php
					$applyingType = $rule->getApplyingType() === 'individual' ? __( 'Applied individually per product',
						'tier-pricing-table' ) : __( 'Applied as Mix and Match', 'tier-pricing-table' );
					echo esc_html( $applyingType );
				?>
			</h4>
			<?php
			
		} catch ( Exception $e ) {
			echo wp_kses_post( '<div class="help_tip tpt-rule-status tpt-rule-status--invalid" data-tip="' . $e->getMessage() . '">!</div>' );
		}
	}
}
