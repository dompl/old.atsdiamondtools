<?php namespace TierPricingTable\Addons\GlobalTieredPricing\CPT\Form\Tabs;

use TierPricingTable\Addons\GlobalTieredPricing\CPT\Form\FormTab;
use TierPricingTable\Addons\GlobalTieredPricing\GlobalPricingRule;
use TierPricingTable\Addons\GlobalTieredPricing\LookupService;

class ProductAndCategories extends FormTab {
	
	public function getId(): string {
		return 'products-and-categories';
	}
	
	public function getTitle(): string {
		return __( 'Products', 'tier-pricing-table' );
	}
	
	public function getDescription(): string {
		return __( 'Select products or product categories the rule will work for.', 'tier-pricing-table' );
	}
	
	public function render( GlobalPricingRule $pricingRule ) {
		
		$this->renderSectionTitle( __( 'Included Products', 'tier-pricing-table' ), array(
			'description' => __( 'Select products or product categories that the rule will apply to. The rule will work for all products in the selected categories.',
				'tier-pricing-table' ),
		) );
		
		if ( empty( $pricingRule->getIncludedProductCategories() ) && empty( $pricingRule->getIncludedProducts() ) ) {
			$this->renderHint( __( 'If you do not specify products or product categories, the rule will apply to all products in your store (excluding those selected in the exclusions section).',
				'tier-pricing-table' ) );
		}
		
		$this->renderSelect2( array(
			'id'            => 'tpt_included_categories',
			'label'         => __( 'Apply for categories', 'tier-pricing-table' ),
			'value'         => ( function () use ( $pricingRule ) {
				$options = [];
				
				foreach ( $pricingRule->getIncludedProductCategories() as $categoryId ) {
					$category = get_term_by( 'id', $categoryId, 'product_cat' );
					
					if ( $category ) {
						$options[ $categoryId ] = LookupService::getCategoryLabel( $category );
					}
				}
				
				return $options;
			} )(),
			'placeholder'   => __( 'Search for a category &hellip;', 'tier-pricing-table' ),
			'search_action' => 'woocommerce_json_search_tpt_categories',
		) );
		
		$this->renderSelect2( array(
			'id'            => 'tpt_included_products',
			'label'         => __( 'Apply for specific products', 'tier-pricing-table' ),
			'value'         => ( function () use ( $pricingRule ) {
				$options = [];
				
				foreach ( $pricingRule->getIncludedProducts() as $productId ) {
					$product = wc_get_product( $productId );
					
					if ( $product ) {
						if ( ! $product->get_sku() ) {
							$options[ $productId ] = $product->get_name();
						} else {
							$options[ $productId ] = $product->get_name() . ' (' . $product->get_sku() . ')';
						}
					}
				}
				
				return $options;
			} )(),
			'placeholder'   => __( 'Search for a product &hellip;', 'tier-pricing-table' ),
			'search_action' => 'woocommerce_json_search_products_and_variations',
		) );
		
		$this->renderSectionTitle( __( 'Exclusions', 'tier-pricing-table' ), array(
			'description' => __( 'Select products or product categories the rule will not work for.',
				'tier-pricing-table' ),
		) );
		
		$this->renderSelect2( array(
			'id'            => 'tpt_excluded_categories',
			'label'         => __( 'Exclude for categories', 'tier-pricing-table' ),
			'value'         => ( function () use ( $pricingRule ) {
				$options = [];
				
				foreach ( $pricingRule->getExcludedProductCategories() as $categoryId ) {
					$category = get_term_by( 'id', $categoryId, 'product_cat' );
					
					if ( $category ) {
						$options[ $categoryId ] = LookupService::getCategoryLabel( $category );
					}
				}
				
				return $options;
			} )(),
			'placeholder'   => __( 'Search for a category &hellip;', 'tier-pricing-table' ),
			'search_action' => 'woocommerce_json_search_tpt_categories',
		) );
		
		$this->renderSelect2( array(
			'id'            => 'tpt_excluded_products',
			'label'         => __( 'Exclude for specific products', 'tier-pricing-table' ),
			'value'         => ( function () use ( $pricingRule ) {
				$options = [];
				
				foreach ( $pricingRule->getExcludedProducts() as $productId ) {
					$product = wc_get_product( $productId );
					
					if ( ! $product ) {
						continue;
					}
					
					if ( ! $product->get_sku() ) {
						$options[ $productId ] = $product->get_name();
					} else {
						$options[ $productId ] = $product->get_name() . ' (' . $product->get_sku() . ')';
					}
				}
				
				return $options;
			} )(),
			'placeholder'   => __( 'Search for a product &hellip;', 'tier-pricing-table' ),
			'search_action' => 'woocommerce_json_search_products_and_variations',
		) );
	}
	
	public function getIcon(): string {
		return 'dashicons-archive';
	}
}
