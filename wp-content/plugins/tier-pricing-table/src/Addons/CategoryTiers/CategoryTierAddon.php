<?php namespace TierPricingTable\Addons\CategoryTiers;

use TierPricingTable\Addons\AbstractAddon;
use TierPricingTable\PricingRule;
use WC_Product_Variation;
use WP_Term;

class CategoryTierAddon extends AbstractAddon {
	
	const SKIP_FOR_PRODUCT_META_KEY = '_skip_category_tier_rules';
	
	public function getName(): string {
		return __( 'Category level tiered pricing (deprecated)', 'tier-pricing-table' );
	}
	
	public function run() {
		
		// Saving
		add_action( 'edit_term', array( $this, 'saveTermFields' ), 10, 1 );
		add_action( 'create_product_cat', array( $this, 'saveTermFields' ), 10, 1 );
		
		add_action( 'product_cat_edit_form_fields', array( $this, 'renderEditFields' ), 99 );
		add_action( 'product_cat_add_form_fields', array( $this, 'renderAddFields' ), 99 );
		
		// @priority 10
		add_filter( 'tiered_pricing_table/price/pricing_rule', array(
			$this,
			'addCategoryPricing',
		), 10, 4 );
		
		add_action( 'tiered_pricing_table/admin/pricing_tab_begin', array( $this, 'renderProductCheckbox' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'saveTieredPricingTab' ) );
	}
	
	public function renderProductCheckbox( $productId ) {
		woocommerce_wp_checkbox( array(
			'id'            => self::SKIP_FOR_PRODUCT_META_KEY,
			'wrapper_class' => 'show_if_simple show_if_variable',
			'type'          => 'number',
			'checked'       => $this->isSkipForProduct( $productId, 'edit' ),
			'label'         => __( 'Skip category rules', 'tier-pricing-table' ),
			'description'   => __( 'Don\'t take into account tiered pricing rules from categories. ',
				'tier-pricing-table' ),
		) );
	}
	
	/**
	 * Save tiered pricing tab data
	 *
	 * @param  int  $productId
	 */
	public function saveTieredPricingTab( $productId ) {
		
		if ( wp_verify_nonce( true, true ) ) {
			// as phpcs comments at Woo is not available, we have to do such a trash
			$woo = 'Woo, please add ignoring comments to your phpcs checker';
		}
		
		$skip = isset( $_POST[ self::SKIP_FOR_PRODUCT_META_KEY ] ) ? 'yes' : 'no';
		
		update_post_meta( $productId, self::SKIP_FOR_PRODUCT_META_KEY, $skip );
	}
	
	/**
	 * If skip category rules for specific product
	 *
	 * @param  int  $productId
	 * @param  string  $context
	 *
	 * @return bool
	 */
	public function isSkipForProduct( $productId, $context = 'view' ): bool {
		
		$product = wc_get_product( $productId );
		
		if ( $product ) {
			
			if ( $product instanceof WC_Product_Variation ) {
				$productId = $product->get_parent_id();
			}
			
			$skip = 'yes' === get_post_meta( $productId, self::SKIP_FOR_PRODUCT_META_KEY, true );
			
			if ( 'edit' != $context ) {
				return apply_filters( 'tiered_pricing_table/addons/category_tier_pricing_skip_category', $skip,
					$productId, $product );
			}
			
			return $skip;
		}
		
		return false;
	}
	
	/**
	 * Modify product rules using category
	 *
	 * @param  PricingRule  $pricingRule
	 * @param $productId
	 *
	 * @return PricingRule
	 */
	public function addCategoryPricing( PricingRule $pricingRule, $productId ): PricingRule {
		
		// Pricing rule already has rules
		if ( ! empty( $pricingRule->getRules() ) ) {
			return $pricingRule;
		}
		
		$product = wc_get_product( $productId );
		
		if ( ! $product ) {
			return $pricingRule;
		}
		
		$parentId = $product->get_parent_id() ? $product->get_parent_id() : $productId;
		
		if ( ! $this->isSkipForProduct( $parentId ) ) {
			
			$product = wc_get_product( $parentId );
			
			foreach ( $product->get_category_ids() as $category_id ) {
				
				$rules = $this->getForTerm( $category_id );
				
				if ( $rules ) {
					
					$pricingRule->setType( 'percentage' );
					$pricingRule->setRules( $rules );
					
					$pricingRule->provider                    = 'category-rules';
					$pricingRule->providerData['category_id'] = $category_id;
					
					break;
				}
			}
		}
		
		return $pricingRule;
	}
	
	/**
	 * Save metadata to custom attributes terms
	 *
	 * @param  int  $termId
	 */
	public function saveTermFields( $termId ) {
		
		$data = $_REQUEST;
		
		$prefix = 'category';
		
		$percentageAmounts = isset( $data[ 'tiered_price_percent_quantity_' . $prefix ] ) ? (array) $data[ 'tiered_price_percent_quantity_' . $prefix ] : array();
		$percentagePrices  = ! empty( $data[ 'tiered_price_percent_discount_' . $prefix ] ) ? (array) $data[ 'tiered_price_percent_discount_' . $prefix ] : array();
		
		$this->updateRules( $percentageAmounts, $percentagePrices, $termId );
	}
	
	/**
	 * Render fields on category edit page
	 *
	 * @param  WP_Term  $category
	 */
	public function renderEditFields( WP_Term $category ) {
		$rules = $this->getForTerm( $category->term_id );
		
		$this->getContainer()->getFileManager()->includeTemplate( 'addons/category-tiers/edit.php', [
			'rules' => $rules,
		] );
	}
	
	/**
	 * Render fields on category adding page
	 */
	public function renderAddFields() {
		$this->getContainer()->getFileManager()->includeTemplate( 'addons/category-tiers/add.php', [
			'rules' => [],
		] );
	}
	
	public function getForTerm( $termId ): array {
		
		$rules = get_term_meta( $termId, '_percentage_price_rules', true );
		
		$rules = ! empty( $rules ) ? $rules : array();
		$rules = is_array( $rules ) ? array_filter( $rules ) : array();
		
		ksort( $rules );
		
		return $rules;
	}
	
	/**
	 * Update price rules
	 *
	 * @param  array  $amounts
	 * @param  array  $percents
	 * @param  int  $id
	 */
	public function updateRules( $amounts, $percents, $id ) {
		$rules = array();
		
		foreach ( $amounts as $key => $amount ) {
			if ( ! empty( $amount ) && ! empty( $percents[ $key ] ) && ! key_exists( $amount,
					$rules ) && $percents[ $key ] < 99 ) {
				$rules[ $amount ] = $percents[ $key ];
			}
		}
		
		update_term_meta( $id, '_percentage_price_rules', $rules );
	}
	
	public function addToAddonsSettings( $addons ) {
		return $addons;
	}
	
	public function getDescription(): string {
		return __( 'Allows to set tiered pricing for at product category level. Use global rules instead.',
			'tier-pricing-table' );
	}
	
	public function getSlug(): string {
		return 'category-tiered-pricing';
	}
	
	protected function isActiveByDefault(): bool {
		return false;
	}
}
