<?php namespace TierPricingTable\Integrations\Plugins\RankMath;

use RankMath\Helpers\Param;
use TierPricingTable\Integrations\Plugins\PluginIntegrationAbstract;
use TierPricingTable\Managers\FormatPriceManager;
use TierPricingTable\PriceManager;
use TierPricingTable\TierPricingTablePlugin;
use WC_Product;

class RankMath extends PluginIntegrationAbstract {
	
	protected $product = null;
	
	public function getTitle(): string {
		return 'Rank Math SEO';
	}
	
	public function getDescription(): string {
		return __( 'Enhance the product schema with tiered pricing offers and adds <b>%lowest_price%</b> and <b>%price_range%</b> variables to display the lowest price and price range of products with tiered pricing.',
			'tier-pricing-table' );
	}
	
	public function getSlug(): string {
		return 'rank-math';
	}
	
	public function run() {
		
		add_action( 'plugins_loaded', function () {
			if ( ! class_exists( 'RankMath\Helpers\Param' ) || ! function_exists( 'rank_math_register_var_replacement' ) ) {
				return;
			}
			
			add_filter( 'tiered_pricing_table/settings/sections', function ( $sections ) {
				
				$sections[] = new Settings();
				
				return $sections;
			} );
			
		} );
		
		add_action( 'rank_math/vars/register_extra_replacements', function () {
			
			if ( ! function_exists( 'rank_math_register_var_replacement' ) ) {
				return;
			}
			
			if ( ! $this->isVariablesEnabled() ) {
				return;
			}
			
			rank_math_register_var_replacement( 'lowest_price', [
				'name'        => esc_html__( 'Lowest Price', 'rank-math' ),
				'description' => esc_html__( 'Tiered Pricing: The lowest price of a product.', 'rank-math' ),
				'variable'    => 'lowest_price',
				'example'     => $this->getLowestPrice(),
			], array( $this, 'getLowestPrice' ) );
			
			rank_math_register_var_replacement( 'price_range', [
				'name'        => esc_html__( 'Price range', 'rank-math' ),
				'description' => esc_html__( 'Tiered Pricing: A price range from the lowest to the highest.',
					'rank-math' ),
				'variable'    => 'price_range',
				'example'     => $this->getPriceRange(),
			], array( $this, 'getPriceRange' ) );
		} );
		
		add_filter( 'rank_math/snippet/rich_snippet_product_entity', function ( $data ) {
			global $product;
			
			if ( ! $this->isEnhancedSchemaEnabled() || ! $product instanceof WC_Product ) {
				return $data;
			}
			
			// Update only low price for variable products.
			if ( TierPricingTablePlugin::isVariableProductSupported( $product ) ) {
				
				$variableProductsSupported = apply_filters( 'tiered_pricing_table/integrations/rank_math/variable_products_supported',
					true, $product );
				
				if ( ! $variableProductsSupported ) {
					return $data;
				}
				
				$data['offers']['lowPrice'] = FormatPriceManager::getFormattedPrice( $product, array(
					'for_display'        => true,
					'with_suffix'        => false,
					'with_default_price' => true,
					'with_lowest_prefix' => false,
					'html'               => false,
					'display_type'       => 'lowest_price',
					'use_cache'          => false,
				) );
				
				return $data;
			}
			
			// Then it works only for simple products
			if ( ! TierPricingTablePlugin::isSimpleProductSupported( $product ) ) {
				return $data;
			}
			
			$pricingRule = PriceManager::getPricingRule( $product->get_id() );
			
			if ( empty( $pricingRule->getRules() ) ) {
				return $data;
			}
			
			// Count the number of rules, add 1 for the default price.
			$data['offers']['offerCount'] = count( $pricingRule->getRules() ) + 1;
			$data['offers']['@type']      = 'AggregateOffer';
			$data['offers']['lowPrice']   = FormatPriceManager::getFormattedPrice( $product, array(
				'for_display'        => true,
				'with_suffix'        => false,
				'with_default_price' => true,
				'with_lowest_prefix' => false,
				'html'               => false,
				'display_type'       => 'lowest_price',
			) );
			$data['offers']['highPrice']  = $product->get_price();
			
			// Add the default offer for the product price.
			$offers[] = array(
				'@type'            => 'Offer',
				'price'            => $product->get_price(),
				'seller'           => array(
					'@type' => 'Organization',
					'name'  => get_bloginfo( 'name', 'display' ),
				),
				'eligibleQuantity' => array(
					'@type'    => 'QuantitativeValue',
					'minValue' => $pricingRule->getMinimum(),
					'maxValue' => array_keys( $pricingRule->getRules() )[0] - 1,
				),
				'sku'              => $product->get_sku(),
				'offeredBy'        => $product->get_name(),
			);
			
			$iterator = new \ArrayIterator( $pricingRule->getRules() );
			
			// Iterate through the pricing rules and add them to the offers.
			$offers = array();
			
			while ( $iterator->valid() ) {
				$quantity = $iterator->key();
				
				$iterator->next();
				
				$offer = array(
					'@type'            => 'Offer',
					'price'            => $pricingRule->getTierPrice( $quantity ),
					'seller'           => array(
						'@type' => 'Organization',
						'name'  => get_bloginfo( 'name', 'display' ),
					),
					'eligibleQuantity' => array(
						'@type'    => 'QuantitativeValue',
						'minValue' => $quantity,
						'maxValue' => $iterator->valid() ? $iterator->key() - 1 : null,
						'sku'      => $product->get_sku(),
					),
				);
				
				if ( $product->is_in_stock() ) {
					$offer['availability'] = 'https://schema.org/InStock';
				} else {
					$offer['availability'] = 'https://schema.org/OutOfStock';
				}
				
				$offers[] = $offer;
			}
			
			$data['offers']['offers'] = $offers;
			
			return $data;
		}, 10 );
	}
	
	public function getIntegrationCategory(): string {
		return 'seo';
	}
	
	public function getIconURL(): ?string {
		return $this->getContainer()->getFileManager()->locateAsset( 'admin/integrations/rank-math-icon.svg' );
	}
	
	public function getPriceRange(): ?string {
		$product = $this->get_product();
		
		if ( ! $product ) {
			return '';
		}
		
		$price = FormatPriceManager::getFormattedPrice( $product, array(
			'for_display'        => true,
			'with_suffix'        => false,
			'with_default_price' => true,
			'with_lowest_prefix' => false,
			'html'               => true,
			'display_type'       => 'range',
		) );
		
		return $price ? $price : '';
	}
	
	public function getLowestPrice(): ?string {
		
		$product = $this->get_product();
		
		if ( ! $product ) {
			return '';
		}
		
		$price = FormatPriceManager::getFormattedPrice( $product, array(
			'for_display'        => true,
			'with_suffix'        => false,
			'with_default_price' => true,
			'with_lowest_prefix' => false,
			'html'               => true,
			'display_type'       => 'lowest_price',
		) );
		
		return $price ? $price : '';
	}
	
	public function get_product() {
		
		if ( ! is_null( $this->product ) ) {
			return $this->product;
		}
		
		if ( ! class_exists( 'RankMath\Helpers\Param' ) ) {
			return null;
		}
		
		$product_id = Param::get( 'post', get_queried_object_id(), FILTER_VALIDATE_INT );
		
		$this->product = ( ! function_exists( 'wc_get_product' ) || ! $product_id || ( ! is_admin() && ! is_singular( 'product' ) ) ) ? null : wc_get_product( $product_id );
		
		return $this->product;
	}
	
	public function isVariablesEnabled(): bool {
		return $this->getContainer()->getSettings()->get( 'rank_math_enable_variables', 'yes' ) === 'yes';
	}
	
	public function isEnhancedSchemaEnabled(): bool {
		return $this->getContainer()->getSettings()->get( 'rank_math_enhance_schema', 'no' ) === 'yes';
	}
}
