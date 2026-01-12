<?php use TierPricingTable\Forms\Form;
	
if ( ! defined( 'WPINC' ) ) {
	die;
}
	
	/**
	 * Available variables
	 *
	 * @var string $tiered_pricing_type
	 * @var array $fixed_rules
	 * @var array $percentage_rules
	 *
	 * @var string $role
	 * @var string $loop
	 * @var string $custom_prefix
	 *
	 * @var int $entity_id
	 */
	$percentage_rules = is_array( $percentage_rules ) ? $percentage_rules : array();
	$fixed_rules      = is_array( $fixed_rules ) ? $fixed_rules : array();
	
	// Used to adjust when there is more fields
	$fieldsWidth = apply_filters( 'tiered_pricing_table/admin/tiered_pricing_rules_form/inputs_width', 50 );
	
	// Add one empty row
	$fixed_rules[ null ]      = '';
	$percentage_rules[ null ] = '';
?>
<div class="tiered-pricing-rules-form">
	
	<?php
		do_action( 'tiered_pricing_table/admin/tiered_pricing_rules_form/form_begin', $entity_id, $role, $loop,
			$custom_prefix );
		?>
	<div
		class="<?php echo esc_attr( is_null( $loop ) ? 'tiered-pricing-form-block' : 'tiered-pricing-form-variation-block' ); ?> tiered-pricing-rules-form__type">
		
		<label for="<?php echo esc_attr( Form::getFieldName( 'type', $role, $loop, $custom_prefix ) ); ?>">
			<?php esc_html_e( 'Tiered pricing type', 'tier-pricing-table' ); ?>
		</label>
		
		<select name="<?php echo esc_attr( Form::getFieldName( 'type', $role, $loop, $custom_prefix ) ); ?>"
				id="<?php echo esc_attr( Form::getFieldName( 'type', $role, $loop, $custom_prefix ) ); ?>"
		>
			
			<option value="fixed" <?php selected( 'fixed', $tiered_pricing_type ); ?> >
				<?php esc_html_e( 'Fixed prices', 'tier-pricing-table' ); ?>
			</option>

			<option value="percentage" <?php selected( 'percentage', $tiered_pricing_type ); ?> >
				<?php esc_html_e( 'Percentage discounts', 'tier-pricing-table' ); ?>
			</option>
		</select>
	</div>
	
	<?php
		do_action( 'tiered_pricing_table/admin/tiered_pricing_rules_form/after_pricing_type', $entity_id, $role, $loop,
			$custom_prefix );
		?>
	
	<div
		class="<?php echo esc_attr( is_null( $loop ) ? 'tiered-pricing-form-block' : 'tiered-pricing-form-variation-block' ); ?> tiered-pricing-rules-form__percentage <?php echo 'fixed' === $tiered_pricing_type ? 'hidden' : ''; ?>">
		
		<label>
			<?php esc_html_e( 'Tiered price', 'tier-pricing-table' ); ?>
		</label>
		
		<div class="tiered-pricing-pricing-rules-form" role="form">
			<div class="tiered-pricing-pricing-rules-form__rules">
				<?php foreach ( $percentage_rules as $amount => $discount ) : ?>
					<div class="tiered-pricing-pricing-rules-form-row">
						<div class="tiered-pricing-pricing-rules-form-row__inputs"
							 style="width: <?php echo esc_attr( $fieldsWidth ); ?>%;">
							
							<?php
								$quantityFieldName  = Form::getFieldName( 'percentage_quantities', $role, $loop,
										$custom_prefix ) . '[]';
								$discountsFieldName = Form::getFieldName( 'percentage_discounts', $role, $loop,
										$custom_prefix ) . '[]';
							?>
							
							<input type="number"
								   min="2"
								   value="<?php echo esc_attr( $amount ); ?>"
								   placeholder="<?php esc_attr_e( 'Quantity', 'tier-pricing-table' ); ?>"
								   name="<?php echo esc_attr( $quantityFieldName ); ?>">
							
							<input type="number"
								   step="any"
								   max="99"
								   value="<?php echo esc_attr( $discount ); ?>"
								   placeholder="<?php esc_attr_e( 'Percentage discount', 'tier-pricing-table' ); ?>"
								   name="<?php echo esc_attr( $discountsFieldName ); ?>"
							>
							<?php
								do_action( 'tiered_pricing_table/admin/tiered_pricing_rules_form/inputs', $entity_id,
									$amount, $role, $loop, $custom_prefix, 'percentage' );
							?>
						</div>
						<div class="tiered-pricing-pricing-rules-form-row__actions">
							<a class="notice-dismiss tiered-pricing-pricing-rules-form__remove"></a>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
			
			<div class="tiered-pricing-pricing-rules-form__buttons">
				<button class="button tiered-pricing-pricing-rules-form__add-new">
					<?php esc_html_e( 'New tier', 'tier-pricing-table' ); ?>
				</button>
			</div>
		</div>
	</div>
	
	<div
		class="<?php echo esc_attr( is_null( $loop ) ? 'tiered-pricing-form-block' : 'tiered-pricing-form-variation-block' ); ?> tiered-pricing-rules-form__fixed <?php echo 'percentage' === $tiered_pricing_type ? 'hidden' : ''; ?>">
		
		<label>
			<?php esc_html_e( 'Tiered price', 'tier-pricing-table' ); ?>
		</label>
		
		<div class="tiered-pricing-pricing-rules-form">
			<div class="tiered-pricing-pricing-rules-form__rules">
				<?php foreach ( $fixed_rules as $amount => $price ) : ?>
					<div class="tiered-pricing-pricing-rules-form-row">
						<div class="tiered-pricing-pricing-rules-form-row__inputs"
							 style="width: <?php echo esc_attr( $fieldsWidth ); ?>%;">
							
							<?php
								$quantityFieldName = Form::getFieldName( 'fixed_quantities', $role, $loop,
										$custom_prefix ) . '[]';
								$pricesFieldName   = Form::getFieldName( 'fixed_prices', $role, $loop,
										$custom_prefix ) . '[]';
							?>
							
							<input type="number"
								   min="2"
								   value="<?php echo esc_attr( $amount ); ?>"
								   placeholder="<?php esc_attr_e( 'Quantity', 'tier-pricing-table' ); ?>"
								   name=" <?php echo esc_attr( $quantityFieldName ); ?>">
							
							<input type="text"
								   step="any"
								   class="wc_input_price"
								   value="<?php echo esc_attr( wc_format_localized_price( $price ) ); ?>"
								   placeholder="<?php esc_attr_e( 'Price', 'tier-pricing-table' ); ?>"
								   name="<?php echo esc_attr( $pricesFieldName ); ?>"
							>
							<?php
								do_action( 'tiered_pricing_table/admin/tiered_pricing_rules_form/inputs', $entity_id,
									$amount, $role, $loop, $custom_prefix, 'fixed' );
							?>
						</div>
						<div class="tiered-pricing-pricing-rules-form-row__actions">
							<button class="notice-dismiss tiered-pricing-pricing-rules-form__remove"></button>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
			
			<div class="tiered-pricing-pricing-rules-form__buttons">
				<button class="button tiered-pricing-pricing-rules-form__add-new">
					<?php esc_html_e( 'New tier', 'tier-pricing-table' ); ?>
				</button>
			</div>
		</div>
	</div>

</div>
