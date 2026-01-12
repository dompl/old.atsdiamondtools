<?php namespace TierPricingTable\Addons\GlobalTieredPricing\CPT\Columns;

use TierPricingTable\Addons\GlobalTieredPricing\Formatter;
use TierPricingTable\Addons\GlobalTieredPricing\GlobalPricingRule;
use WC_Product;
use WP_Term;

class AppliedProducts {
	
	public function getName(): string {
		return __( 'Products', 'tier-pricing-table' );
	}
	
	public function render( GlobalPricingRule $rule ) {
		
		$hasProducts   = $this->showProducts( $rule->getIncludedProducts() );
		$hasCategories = $this->showCategories( $rule->getIncludedProductCategories() );
		
		if ( ! $hasProducts && ! $hasCategories ) {
			?>
			<mark class="order-status status-processing tips">
				<span>
					<?php esc_html_e( 'Applied to every product', 'tier-pricing-table' ); ?>
				</span>
			</mark>
			<?php
		} else {
			$this->showProducts( $rule->getExcludedProducts(), false );
			$this->showCategories( $rule->getExcludedProductCategories(), false );
		}
	}
	
	public function showProducts( array $productsIds, $included = true ): bool {
		
		$moreThanCanBeShown = count( $productsIds ) > 10;
		
		$productsIds = array_slice( $productsIds, 0, 5 );
		
		$products = array_filter( array_map( function ( $productId ) {
			return wc_get_product( $productId );
		}, $productsIds ) );
		
		if ( ! empty( $products ) ) {
			
			if ( $included ) {
				esc_html_e( 'Products: ', 'tier-pricing-table' );
			} else {
				esc_html_e( 'Excluded products: ', 'tier-pricing-table' );
			}
			
			$productsString = array_map( function ( WC_Product $product ) {
				return sprintf( '<a href="%s" target="_blank">%s</a>',
					get_edit_post_link( $product->get_parent_id() ? $product->get_parent_id() : $product->get_id() ),
					$product->get_name() );
			}, $products );
			
			echo wp_kses_post( implode( ', ', $productsString ) . ( $moreThanCanBeShown ? '<span> ...</span>' : '' ) );
			
			echo '<br><br>';
			
			return true;
		}
		
		return false;
	}
	
	public function showCategories( array $categoriesIds, $included = true ): bool {
		$moreThanCanBeShown = count( $categoriesIds ) > 10;
		$categoriesIds      = array_slice( $categoriesIds, 0, 10 );
		
		$categories = array_filter( array_map( function ( $categoryId ) {
			return get_term( $categoryId );
		}, $categoriesIds ) );
		
		$categories = array_filter( $categories, function ( $category ) {
			return $category instanceof WP_Term;
		} );
		
		if ( ! empty( $categories ) ) {
			
			if ( $included ) {
				esc_html_e( 'Categories: ', 'tier-pricing-table' );
			} else {
				esc_html_e( 'Excluded categories: ', 'tier-pricing-table' );
			}
			
			$categoriesString = array_map( function ( WP_Term $category ) {
				return sprintf( '<a href="%s" target="_blank">%s</a>', get_edit_term_link( $category->term_id ),
					$category->name );
			}, $categories );
			
			echo wp_kses_post( implode( ', ',
					$categoriesString ) . ( $moreThanCanBeShown ? '<span> ...</span>' : '' ) );
			
			echo '<br><br>';
			
			return true;
		}
		
		return false;
	}
}
