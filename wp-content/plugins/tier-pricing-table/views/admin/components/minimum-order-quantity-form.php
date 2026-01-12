<?php

	use TierPricingTable\Forms\Form;

	if ( ! defined( 'WPINC' ) ) {
		die;
	}

	/**
	 * Available variables
	 *
	 * @var string $role
	 * @var string $loop
	 * @var string $minimum_order_quantity
	 */

	$placeholder = __( 'Leave empty, so don\'t add any restrictions', 'tier-pricing-table' );
?>

<p class="form-field tpt_minimum_order_quantity <?php echo esc_attr( ! is_null( $loop ) ? 'form-row' : '' ); ?>">
	<label for="<?php echo esc_attr( Form::getFieldName( 'minimum_order_quantity', $role, $loop ) ); ?>">
		<?php esc_html_e( 'Minimum order quantity', 'tier-pricing-table' ); ?>
	</label>

	<?php
		echo wp_kses_post( wc_help_tip( __( 'A minimum amount of a product a customer must order to fulfill the order.',
				'tier-pricing-table' ) ) );
	?>

	<input type="number"
		   class="short"
		   min="1"
		   step="1"
		   placeholder="<?php echo esc_attr__( $placeholder ); ?>"
		   name="<?php echo esc_attr( Form::getFieldName( 'minimum_order_quantity', $role, $loop ) ); ?>"
		   id="<?php echo esc_attr( Form::getFieldName( 'minimum_order_quantity', $role, $loop ) ); ?>"
		   value="<?php echo esc_attr( $minimum_order_quantity ); ?>">

</p>
