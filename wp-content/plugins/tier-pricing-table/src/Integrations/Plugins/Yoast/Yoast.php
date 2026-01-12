<?php namespace TierPricingTable\Integrations\Plugins\Yoast;

use TierPricingTable\Integrations\Plugins\PluginIntegrationAbstract;
use TierPricingTable\Managers\FormatPriceManager;
use TierPricingTable\PriceManager;
use TierPricingTable\TierPricingTablePlugin;
use WC_Product;

class Yoast extends PluginIntegrationAbstract {
	
	protected $product = null;
	
	public function getTitle(): string {
		return 'Yoast SEO';
	}
	
	public function getDescription(): string {
		return __( 'Adds <b>%%lowest_price%%</b> and <b>%%price_range%%</b> variables to display the lowest and price range of products with tiered pricing.',
			'tier-pricing-table' );
	}
	
	public function getSlug(): string {
		return 'yoast-seo';
	}
	
	public function run() {

		add_action( 'plugins_loaded', function () {
			
			if ( ! class_exists( 'WPSEO_Options' ) ) {
				return;
			}
			
			add_filter( 'tiered_pricing_table/settings/sections', function ( $sections ) {
				
				$sections[] = new Settings();
				
				return $sections;
			} );
			
		} );
		
		add_filter( 'wpseo_replacements', function ( $replacements ) {
			
			if ( ! $this->isVariablesEnabled() ) {
				return $replacements;
			}
			
			$product = $this->get_product();
			
			if ( ! $product ) {
				return $replacements;
			}
			
			$replacements['%%lowest_price%%'] = FormatPriceManager::getFormattedPrice( $product, array(
				'for_display'        => true,
				'with_suffix'        => false,
				'with_default_price' => true,
				'with_lowest_prefix' => false,
				'html'               => true,
				'display_type'       => 'lowest_price',
			) );
			
			$replacements['%%price_range%%'] = FormatPriceManager::getFormattedPrice( $product, array(
				'for_display'        => true,
				'with_suffix'        => false,
				'with_default_price' => true,
				'with_lowest_prefix' => false,
				'html'               => true,
				'display_type'       => 'range',
			) );
			
			return $replacements;
		}, 10, 2 );

		add_filter( 'wpseo_schema_product', function ( $data ) {
	
			if ( ! is_product() || ! is_array( $data ) ) {
				return $data;
			}
			
			global $product;
			
			if ( ! $this->isEnhancedSchemaEnabled() || ! $product instanceof WC_Product ) {
				return $data;
			}
			
			// Update only low price for variable products.
			if ( TierPricingTablePlugin::isVariableProductSupported( $product ) ) {
				
				$variableProductsSupported = apply_filters( 'tiered_pricing_table/integrations/yoast_seo/variable_products_supported',
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
			
			if ( ! TierPricingTablePlugin::isSimpleProductSupported( $product ) ) {
				return $data;
			}
			
			$pricingRule = PriceManager::getPricingRule( $product->get_id() );
			
			if ( empty( $pricingRule->getRules() ) ) {
				return $data;
			}
			
			$data['offers']['@type']      = 'AggregateOffer';
			$data['offers']['offerCount'] = count( $pricingRule->getRules() ) + 1;
			$data['offers']['lowPrice']   = FormatPriceManager::getFormattedPrice( $product, array(
				'for_display'        => true,
				'with_suffix'        => false,
				'with_default_price' => true,
				'with_lowest_prefix' => false,
				'html'               => false,
				'display_type'       => 'lowest_price',
			) );
			$data['offers']['highPrice']  = $product->get_price();
			
			// Create offers for each tier
			$offers = [];
			
			$iterator = new \ArrayIterator( $pricingRule->getRules() );
			
			while ( $iterator->valid() ) {
				$quantity = $iterator->key();
				$iterator->next();
				
				$offer = [
					'@type'            => 'Offer',
					'price'            => $pricingRule->getTierPrice( $quantity ),
					'seller'           => [
						'@type' => 'Organization',
						'name'  => get_bloginfo( 'name', 'display' ),
					],
					'eligibleQuantity' => [
						'@type'    => 'QuantitativeValue',
						'minValue' => $quantity,
					],
				];
				
				if ( $iterator->valid() ) {
					$offer['eligibleQuantity']['maxValue'] = $iterator->key() - 1;
				}
				
				if ( $product->get_sku() ) {
					$offer['sku'] = $product->get_sku();
				}
				
				$offer['availability'] = $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock';
				
				$offers[] = $offer;
			}
			
			$data['offers']['offers'] = $offers;
			
			return $data;
		} );
	}
	
	public function getIntegrationCategory(): string {
		return 'seo';
	}
	
	public function getIconURL(): ?string {
		return $this->getContainer()->getFileManager()->locateAsset( 'admin/integrations/yoast-icon.gif' );
	}
	
	public function get_product() {
		
		if ( ! is_null( $this->product ) ) {
			return $this->product;
		}
		
		$product_id = get_queried_object_id();
		
		$this->product = ( ! function_exists( 'wc_get_product' ) || ! $product_id || ( ! is_admin() && ! is_singular( 'product' ) ) ) ? null : wc_get_product( $product_id );
		
		return $this->product;
	}
	
	public function isVariablesEnabled(): bool {
		return $this->getContainer()->getSettings()->get( 'yoast_enable_variables', 'yes' ) === 'yes';
	}
	
	public function isEnhancedSchemaEnabled(): bool {
		return $this->getContainer()->getSettings()->get( 'yoast_enhance_schema', 'no' ) === 'yes';
	}
}
