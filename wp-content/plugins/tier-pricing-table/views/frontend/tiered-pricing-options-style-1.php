<?php

	use TierPricingTable\CalculationLogic;
	use TierPricingTable\PriceManager;
	use TierPricingTable\PricingRule;
	use TierPricingTable\Settings\Settings;

	if ( ! defined( 'WPINC' ) ) {
		die;
	}
	/**
	 * Available variables
	 *
	 * @var array $price_rules
	 * @var PricingRule $pricing_rule
	 * @var string $real_price
	 * @var string $product_name
	 * @var string $pricing_type
	 * @var WC_Product $product
	 * @var string $id
	 * @var int $product_id
	 * @var int $minimum
	 * @var array $settings
	 */

	$sale_price = $product->get_sale_price();

	if ( $sale_price ) {
		$sale_price = wc_get_price_to_display( $product, array(
				'price' => $sale_price,
		) );
	}

	$regular_price = wc_get_price_to_display( $product, array(
			'price' => $product->get_regular_price(),
	) );

	$price = wc_get_price_to_display( $product, array(
			'price' => $product->get_price(),
	) );

	if ( ! function_exists( 'tptParseOptionText' ) ) {
		function tptParseOptionText( $text, $quantity, $discount = null, $base_unit_name = null ) {
			return strtr( $text, array(
					'{tp_quantity}'         => $quantity,
					'{tp_discount}'         => $discount,
					'{tp_rounded_discount}' => ! is_null( $discount ) ? round( $discount ) : 0,
					'{tp_base_unit_name}'   => $base_unit_name,
			) );
		}
	}
?>
<?php if ( ! empty( $price_rules ) ) : ?>

	<div class="tiered-pricing-wrapper">
		<?php if ( ! empty( $settings['title'] ) ) : ?>
			<h3 style="clear:both; margin: 20px 0;"><?php echo esc_attr( $settings['title'] ); ?></h3>
		<?php endif; ?>

		<div class="tiered-pricing-options tiered-pricing-options--styled tiered-pricing-options--style-1"
			 id="<?php echo esc_attr( $id ); ?>"
			 data-product-id="<?php echo esc_attr( $product_id ); ?>"
			 data-price-rules="<?php echo esc_attr( htmlspecialchars( json_encode( $price_rules ) ) ); ?>"
			 data-minimum="<?php echo esc_attr( $minimum ); ?>"
			 data-product-name="<?php echo esc_attr( $product_name ); ?>"
			 data-regular-price="<?php echo esc_attr( $regular_price ); ?>"
			 data-sale-price="<?php echo esc_attr( $sale_price ); ?>"
			 data-price="<?php echo esc_attr( $price ); ?>"
			 data-product-price-suffix="<?php echo esc_attr( $product->get_price_suffix() ); ?>"
		>
			<div class="tiered-pricing-option tiered-pricing--active tiered-pricing-option--default"
				 data-tiered-quantity="<?php echo esc_attr( $minimum ); ?>"
				 data-tiered-price="<?php echo esc_attr( $price ); ?>"
				 data-tiered-price-exclude-taxes="
				<?php
					 echo esc_attr( wc_get_price_excluding_tax( wc_get_product( $product_id ), array(
							 'price' => $real_price,
					 ) ) );
				 ?>
				 "
				 data-tiered-price-include-taxes="
				<?php
					 echo esc_attr( wc_get_price_including_tax( wc_get_product( $product_id ), array(
							 'price' => $real_price,
					 ) ) );
				 ?>
				 "
			>
				<div class="tiered-pricing-option__checkbox">
					<div class="tiered-pricing-option-checkbox tiered-pricing-option-checkbox--active"></div>
				</div>

				<?php
					$discountAmount = 0;
					if ( CalculationLogic::calculateDiscountBasedOnRegularPrice() && $product->is_on_sale() ) {
						$discountAmount = PriceManager::calculateDiscount( $product->get_regular_price(),
								$product->get_sale_price() );
					}
				?>

				<div class="tiered-pricing-option__quantity">
					<?php if ( 1 >= array_keys( $price_rules )[0] - $minimum || 'static' === $settings['quantity_type'] ) : ?>
						<?php
						$quantity     = esc_attr( number_format_i18n( $minimum ) . ' ' );
						$baseUnitName = $settings['quantity_measurement_singular'];
						?>
					<?php else : ?>
						<?php
						$quantity     = esc_attr( number_format_i18n( $minimum ) . ' - ' . number_format_i18n( array_keys( $price_rules )[0] - 1 ) . ' ' );
						$baseUnitName = $settings['quantity_measurement_plural'];
						?>
					<?php endif; ?>

					<?php if ( $discountAmount > 0 ) : ?>
						<?php
						echo wp_kses_post( tptParseOptionText( $settings['options_option_text'], $quantity,
								$discountAmount, $baseUnitName ) );
						?>
					<?php else : ?>
						<?php
						echo wp_kses_post( tptParseOptionText( $settings['options_default_option_text'], $quantity,
								null, $baseUnitName ) );
						?>
					<?php endif; ?>

				</div>

				<div class="tiered-pricing-option__pricing">
					<div class="tiered-pricing-option-price">

						<?php if ( $discountAmount > 0 ) : ?>
							<div class="tiered-pricing-option-price__original">
								<del>
									<?php echo wp_kses_post( wc_price( $regular_price ) ); ?>
								</del>
							</div>
						<?php endif; ?>

						<div class="tiered-pricing-option-price__discounted">
							<?php
								echo wp_kses_post( wc_price( wc_get_price_to_display( wc_get_product( $product_id ),
										array(
												'price' => $real_price,
										) ) ) );
							?>
						</div>

					</div>


					<div class="tiered-pricing-option-total">

						<div class="tiered-pricing-option-total__label">
							<?php esc_html_e( 'Total:', 'tier-pricing-table' ); ?>
						</div>

						<div class="tiered-pricing-option-total__original_total"></div>

						<div class="tiered-pricing-option-total__discounted_total">
							<?php
								echo wp_kses_post( wc_price( wc_get_price_to_display( wc_get_product( $product_id ),
										array(
												'price' => CalculationLogic::calculateDiscountBasedOnRegularPrice() ? $regular_price : $real_price,
										) ) ) );
							?>
						</div>
					</div>
				</div>
			</div>

			<?php $iterator = new ArrayIterator( $price_rules ); ?>

			<?php while ( $iterator->valid() ) : ?>
				<?php
				$currentPrice    = $iterator->current();
				$currentQuantity = $iterator->key();

				if ( 'percentage' === $pricing_type ) {
					$discountAmount = $currentPrice;
				} else {
					$discountAmount = PriceManager::calculateDiscount( CalculationLogic::calculateDiscountBasedOnRegularPrice() ? $product->get_regular_price() : $product->get_price(),
							$pricing_rule->getTierPrice( $currentQuantity, false ) );
				}

				$iterator->next();

				if ( $iterator->valid() ) {
					$quantity = $currentQuantity;

					if ( intval( $iterator->key() - 1 != $currentQuantity ) ) {

						$quantity = number_format_i18n( $quantity );

						if ( 'range' === $settings['quantity_type'] ) {
							$quantity .= ' - ' . number_format_i18n( intval( $iterator->key() - 1 ) );
						}
					}
				} else {
					$quantity = number_format_i18n( $currentQuantity );

					$quantity .= apply_filters( 'tiered_pricing_table/tiered_pricing/last_tier_postfix', '+',
							$currentQuantity, $pricing_rule, 'blocks' );
				}

				$currentProductPrice = PriceManager::getPriceByRules( $currentQuantity, $product_id );

				$currentProductPriceExcludeTaxes = wc_get_price_excluding_tax( wc_get_product( $product_id ), array(
						'price' => PriceManager::getPriceByRules( $currentQuantity, $product_id, null, null, false ),
				) );

				$currentProductPriceIncludeTaxes = wc_get_price_including_tax( wc_get_product( $product_id ), array(
						'price' => PriceManager::getPriceByRules( $currentQuantity, $product_id, null, null, false ),
				) );

				?>

				<div class="tiered-pricing-option"
					 data-tiered-quantity="<?php echo esc_attr( $currentQuantity ); ?>"
					 data-tiered-price="<?php echo esc_attr( $currentProductPrice ); ?>"
					 data-tiered-price-exclude-taxes="<?php echo esc_attr( $currentProductPriceExcludeTaxes ); ?>"
					 data-tiered-price-include-taxes="<?php echo esc_attr( $currentProductPriceIncludeTaxes ); ?>">

					<div class="tiered-pricing-option__checkbox">
						<div class="tiered-pricing-option-checkbox"></div>
					</div>
					<div class="tiered-pricing-option__quantity">
						<?php
							echo wp_kses_post( tptParseOptionText( $settings['options_option_text'], $quantity,
									round( $discountAmount, 2 ), $settings['quantity_measurement_plural'] ) );
						?>
					</div>
					<div class="tiered-pricing-option__pricing">
						<div class="tiered-pricing-option-price">
							<div class="tiered-pricing-option-price__original">
								<del>
									<?php
										echo wp_kses_post( wc_price( wc_get_price_to_display( $product, array(
												'price' => CalculationLogic::calculateDiscountBasedOnRegularPrice() ? $regular_price : $real_price,
										) ) ) );
									?>
								</del>
							</div>
							<div class="tiered-pricing-option-price__discounted">
								<?php
									echo wp_kses_post( wc_price( PriceManager::getPriceByRules( $currentQuantity,
											$product_id ) ) );
								?>
							</div>
						</div>

						<div class="tiered-pricing-option-total">
							<div class="tiered-pricing-option-total__label">
								<?php esc_html_e( 'Total:', 'tier-pricing-table' ); ?>
							</div>

							<div class="tiered-pricing-option-total__original_total"></div>

							<div class="tiered-pricing-option-total__discounted_total"></div>
						</div>

					</div>
				</div>
			<?php endwhile; ?>

			<?php do_action( 'tiered_pricing_table/options/options', $pricing_rule ); ?>
		</div>

		<?php do_action( 'tiered_pricing_table/options/after_options', $pricing_rule ); ?>
	</div>

	<style>
		<?php

		if ( $settings['clickable_rows'] ) {
			echo esc_html("#{$id} .tiered-pricing-option {cursor: pointer; }");
		}

		if ( $settings['options_show_total'] ) {
		  echo esc_html("#{$id} .tiered-pricing--active .tiered-pricing-option-total { display: flex; }");
		}

		if ( ! $settings['options_show_default_option'] ) {
			echo esc_html("#{$id} .tiered-pricing-option--default { display: none }");
		}

		$backgroundColor = Settings::hex2rgba($settings['active_tier_color'], 0.05);
		$background_20percent = Settings::hex2rgba($settings['active_tier_color'], 0.3);
		$background_50percent = Settings::hex2rgba($settings['active_tier_color'], 0.5);

		echo esc_html( "#{$id} .tiered-pricing--active .tiered-pricing-option-checkbox::after {
			background: {$settings['active_tier_color']};
		}");

		echo esc_html( "#{$id} .tiered-pricing--active .tiered-pricing-option-checkbox {
			border-color:  {$settings['active_tier_color']};
		}");


		echo esc_html( "#{$id} .tiered-pricing-option {
			border-color: {$background_20percent};
		}");


		echo esc_html( "#{$id} {
			border-color: {$background_20percent};
		}");

		if ( ! $settings['options_show_original_product_price']) {
			{echo esc_html( "#{$id} .tiered-pricing-option-price__original {
				display: none
			}");}

			echo esc_html( "#{$id} .tiered-pricing-option-total__original_total {
				display: none
			}");
		}
		?>

	</style>
<?php endif; ?>