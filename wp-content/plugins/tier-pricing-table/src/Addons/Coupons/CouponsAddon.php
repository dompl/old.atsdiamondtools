<?php namespace TierPricingTable\Addons\Coupons;

use TierPricingTable\Addons\AbstractAddon;
use WC_Cart;

class CouponsAddon extends AbstractAddon {
	
	public function getName(): string {
		return __( 'Coupons management', 'tier-pricing-table' );
	}
	
	public function run() {
		add_action( 'woocommerce_coupon_options', array( $this, 'addTieredPricingOption' ), 10, 2 );
		add_action( 'woocommerce_coupon_options_save', array( $this, 'saveTieredPricingOption' ), 10, 2 );
		
		add_action( 'tiered_pricing_table/cart/need_price_recalculation', array(
			$this,
			'checkAppliedCoupons',
		), 10, 3 );
		
		add_action( 'tiered_pricing_table/cart/need_price_recalculation/item', array(
			$this,
			'checkAppliedCoupons',
		), 10, 3 );
	}
	
	public function checkAppliedCoupons( $recalculate, $item, $cart = null ) {
		
		$cart = $cart instanceof WC_Cart ? $cart : wc()->cart;
		
		if ( ! $cart ) {
			return $recalculate;
		}
		
		$coupons = $cart->get_coupons();
		
		if ( empty( $coupons ) ) {
			return $recalculate;
		}
		
		foreach ( $coupons as $coupon ) {
			if ( get_post_meta( $coupon->get_id(), '_disable_tiered_pricing', true ) === 'yes' ) {
				return false;
			}
		}
		
		return $recalculate;
	}
	
	public function saveTieredPricingOption( $couponId ) {
		update_post_meta( $couponId, '_disable_tiered_pricing',
			isset( $_REQUEST['_disable_tiered_pricing'] ) ? 'yes' : 'no' );
	}
	
	public function addTieredPricingOption( $couponId, $coupon ) {
		
		woocommerce_wp_checkbox( array(
			'id'          => '_disable_tiered_pricing',
			'label'       => __( 'Disable tiered pricing when the coupon is applied', 'woocommerce' ),
			'description' => __( 'Check this option to don\'t  apply tiered pricing in the cart when users have applied this coupon.',
				'tier-pricing-table' ),
			'value'       => get_post_meta( $couponId, '_disable_tiered_pricing', true ),
		) );
	}
	
	public function getDescription(): string {
		return __( 'It allows you to manage which coupons will allow tiered pricing and which will not.',
			'tier-pricing-table' );
	}
	
	public function getSlug(): string {
		return 'coupons';
	}
}
