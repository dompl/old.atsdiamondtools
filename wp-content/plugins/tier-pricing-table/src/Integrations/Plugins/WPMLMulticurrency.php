<?php namespace TierPricingTable\Integrations\Plugins;

use TierPricingTable\PricingRule;
use woocommerce_wpml;

class WPMLMulticurrency extends PluginIntegrationAbstract {

	public function run() {
		
		add_filter( 'tiered_pricing_table/price/price_by_rules', function ( $productPrice, $quantity, $productId,
			$context, $place, PricingRule $pricingRule ) {
		
			/**
			 * Clarifying type
			 *
			 * @var woocommerce_wpml $woocommerce_wpml
			 */
			global $woocommerce_wpml;

			if ( $pricingRule->isPercentage() || ! $productPrice || ! $woocommerce_wpml ) {
				return $productPrice;
			}

			if ( ! $woocommerce_wpml->multi_currency || ! method_exists( $woocommerce_wpml->multi_currency, 'get_client_currency' ) ) {
				return $productPrice;
			}

			$currentCurrency = $woocommerce_wpml->multi_currency->get_client_currency();

			if ( wcml_get_woocommerce_currency_option() !== $currentCurrency ) {

				return $woocommerce_wpml->multi_currency->prices->convert_price_amount( $productPrice,
					$currentCurrency );
			}

			return $productPrice;
		}, 10, 10 );
	}

	public function getTitle(): string {
		return __( 'WPML Multicurrency', 'tier-pricing-table' );
	}

	public function getIconURL(): string {
		return $this->getContainer()->getFileManager()->locateAsset( 'admin/integrations/wpml-multicurrency-icon.png' );
	}

	public function getAuthorURL(): string {
		return 'https://wpml.org/documentation/related-projects/woocommerce-multilingual/';
	}

	public function getDescription(): string {
		return __( 'Make the tiered pricing properly work with multiple currencies.', 'tier-pricing-table' );
	}

	public function getSlug(): string {
		return 'wpml_multicurrency';
	}

	public function getIntegrationCategory(): string {
		return 'multicurrency';
	}
}
