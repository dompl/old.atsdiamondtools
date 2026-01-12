<?php namespace TierPricingTable\Managers;

use TierPricingTable\Core\ServiceContainer;
use TierPricingTable\PriceManager;
use TierPricingTable\TierPricingTablePlugin;
use WC_Product;
use WC_Product_Variable;

/**
 * Class FormatPriceManager
 *
 * @package TierPricingTable\Managers
 */
class FormatPriceManager {
	
	public static function getFormattedPrice( WC_Product $product, array $args = array() ): ?string {
		
		$args = wp_parse_args( $args, array(
			'html'               => true,
			'display_type'       => null,
			'use_cache'          => true,
			'for_display'        => true,
			'with_suffix'        => true,
			'with_lowest_prefix' => true,
			'with_default_price' => true,
		) );
		
		
		$cache     = ServiceContainer::getInstance()->getCache();
		$priceHTML = false;
		
		$isProductVariable = TierPricingTablePlugin::isVariableProductSupported( $product );
		$displayType       = $args['display_type'] ? $args['display_type'] : self::getDisplayType();
		
		if ( $isProductVariable && $args['use_cache'] ) {
			$priceHTML = $cache->getProductData( $product, 'price_html' );
		}
		
		if ( false === $priceHTML ) {
			
			if ( 'range' === $displayType ) {
				$priceHTML = self::getPriceRange( $product, array(
					'for_display'        => $args['for_display'],
					'with_suffix'        => $args['with_suffix'],
					'with_default_price' => false,
				) );
			} else {
				$priceHTML = self::getLowestPrice( $product, array(
					'html'               => $args['html'],
					'for_display'        => $args['for_display'],
					'with_suffix'        => $args['with_suffix'],
					'with_lowest_prefix' => $args['with_lowest_prefix'],
					'with_default_price' => false,
				) );
			}
			
			if ( is_null( $priceHTML ) ) {
				$priceHTML = 'default';
			}
			
			if ( $isProductVariable && $args['use_cache'] ) {
				$cache->setProductData( $product, 'price_html', $priceHTML );
			}
		}
		
		if ( 'default' === $priceHTML ) {
			if ( $args['with_default_price'] ) {
				$priceHTML = $args['html'] ? $product->get_price_html() : $product->get_price();
			} else {
				return null;
			}
		}
		
		return $priceHTML;
	}
	
	public static function getLowestPrice(
		WC_Product $product,
		$args = array()
	): ?string {
		
		$args = wp_parse_args( $args, array(
			'html'               => true,
			'for_display'        => true,
			'with_suffix'        => true,
			'with_lowest_prefix' => true,
			'with_default_price' => true,
		) );
		
		$lowestPrice  = null;
		$regularPrice = null;
		
		// Loop through all product variations and get the lowest price
		if ( TierPricingTablePlugin::isVariableProductSupported( $product ) ) {
			/**
			 * Variable type declaration for PHPStorm
			 *
			 * @var WC_Product_Variable $product
			 */
			
			$minimalPrices = array( (float) $product->get_variation_price( 'min', $args['for_display'] ) );
			$regularPrice  = (float) $product->get_variation_price( 'max', $args['for_display'] );
			
			foreach ( $product->get_available_variations() as $variation ) {
				
				$pricingRule = PriceManager::getPricingRule( (int) $variation['variation_id'] );
				
				if ( ! empty( $pricingRule->getRules() ) ) {
					$minimalPrices[] = $args['for_display'] ? wc_get_price_to_display( $product, array(
						'price' => $pricingRule->getTierPrice( PHP_INT_MAX, false ),
					) ) : $pricingRule->getTierPrice( PHP_INT_MAX, false );
				}
			}
			
			// If product has more than 1 min price - that means that some variation has a tiered pricing rule.
			if ( ! empty( $minimalPrices ) && count( $minimalPrices ) > 1 ) {
				$lowestPrice = min( $minimalPrices );
			}
			
		} else {
			$pricingRule  = PriceManager::getPricingRule( $product->get_id() );
			$regularPrice = (float) $product->get_price();
			
			if ( ! empty( $pricingRule->getRules() ) ) {
				$lowestPrice = $args['for_display'] ? wc_get_price_to_display( $product, array(
					'price' => $pricingRule->getTierPrice( PHP_INT_MAX, false ),
				) ) : $pricingRule->getTierPrice( PHP_INT_MAX, false );
			}
		}
		
		// Handle a case when there are no pricing rules
		if ( ! is_numeric( $lowestPrice ) || $lowestPrice < 0 || $lowestPrice >= $regularPrice ) {
			
			if ( ! $args['with_default_price'] ) {
				return null;
			}
			
			$lowest = $product->get_price();
			
			$lowestPrice = $args['for_display'] ? wc_get_price_to_display( $product, array(
				'price' => $lowest,
			) ) : $lowest;
			
			return $args['html'] ? $product->get_price_html() : $lowestPrice;
		}
		
		if ( $args['for_display'] ) {
			$lowestPrice = wc_get_price_to_display( $product, array(
				'price' => $lowestPrice,
			) );
		}
		
		if ( $args['html'] ) {
			$lowestPrice = wc_price( $lowestPrice );
		}
		
		if ( $args['with_suffix'] ) {
			$lowestPrice .= $product->get_price_suffix();
		}
		
		if ( $args['with_lowest_prefix'] ) {
			$lowestPrice = self::getLowestPrefix() . ' ' . $lowestPrice;
		}
		
		return $lowestPrice;
	}
	
	public static function getPriceRange( WC_Product $product, array $args = array() ): ?string {
		
		$args = wp_parse_args( $args, array(
			'for_display'        => true,
			'with_suffix'        => true,
			'with_default_price' => true,
		) );
		
		/**
		 * Variable type declaration for PHPStorm
		 *
		 * @var WC_Product_Variable $product
		 */
		$lowestPrice = self::getLowestPrice( $product, array(
			'html'               => false,
			'for_display'        => false,
			'with_lowest_prefix' => false,
			'with_default_price' => false,
			'with_suffix'        => false,
		) );
		
		if ( is_null( $lowestPrice ) ) {
			if ( $args['with_default_price'] ) {
				return $args['html'] ? $product->get_price_html() : $product->get_price();
			} else {
				return null;
			}
		}
		
		if ( TierPricingTablePlugin::isVariableProductSupported( $product ) ) {
			$highestPrice = $product->get_variation_price( 'max' );
		} elseif ( TierPricingTablePlugin::isSimpleProductSupported( $product ) ) {
			$highestPrice = $product->get_price();
		} else {
			return $args['with_default_price'] ? $product->get_price() : null;
		}
		
		if ( is_null( $highestPrice ) || $highestPrice === $lowestPrice ) {
			return $args['with_default_price'] ? $product->get_price() : null;
		}
		
		if ( $args['for_display'] ) {
			$lowestPrice = wc_get_price_to_display( $product, array(
				'price' => $lowestPrice,
			) );
			
			$highestPrice = wc_get_price_to_display( $product, array(
				'price' => $highestPrice,
			) );
		}
		
		$lowestPrice  = wc_price( $lowestPrice );
		$highestPrice = wc_price( $highestPrice );
		
		if ( $lowestPrice === $highestPrice ) {
			return $args['with_default_price'] ? $product->get_price() : null;
		}
		
		$range = $lowestPrice . ' - ' . $highestPrice;
		
		if ( $args['with_suffix'] ) {
			$range .= $product->get_price_suffix();
		}
		
		return $range;
	}
	
	public static function getLowestPrefix(): string {
		$settings = ServiceContainer::getInstance()->getSettings();
		
		return (string) $settings->get( 'lowest_prefix', __( 'From', 'tier-pricing-table' ) );
	}
	
	public static function getDisplayType(): string {
		$settings = ServiceContainer::getInstance()->getSettings();
		
		return $settings->get( 'tiered_price_at_catalog_type', 'range' ) === 'range' ? 'range' : 'lowest';
	}
}