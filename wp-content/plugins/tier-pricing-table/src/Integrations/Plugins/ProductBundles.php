<?php namespace TierPricingTable\Integrations\Plugins;

use WC_Bundled_Item_Data;
use WC_Product_Bundle;

class ProductBundles extends PluginIntegrationAbstract {

	public function run() {
		add_action( 'init', function () {
			// Bundle plugin does not exist
			if ( ! class_exists( '\WC_Product_Bundle' ) ) {
				return;
			}

			$this->hooks();
		} );
	}

	protected function hooks() {

		add_filter( 'tiered_pricing_table/frontend/should_wrap_price', function ( $wrap, \WC_Product $product ) {
			return ! $product->is_type( 'bundle' );
		}, 10, 2 );

		add_filter( 'tiered_pricing_table/catalog_pricing/price_html',
			function ( $priceHTML, $originalPriceHTML, \WC_Product $product ) {

				// Do not modify pricing for bundle products
				if ( 'bundle' === $product->get_type() ) {
					return $originalPriceHTML;
				}

				$currentProductId = get_queried_object_id();
				$currentProduct   = wc_get_product( $currentProductId );

				if ( $currentProduct instanceof WC_Product_Bundle ) {
					foreach ( $currentProduct->get_bundled_data_items() as $dataItem ) {
						// Do not modify prices for bundle items
						if ( $dataItem instanceof WC_Bundled_Item_Data && $dataItem->get_product_id() === $product->get_id() ) {
							return $originalPriceHTML;
						}
					}
				}

				return $priceHTML;
			}, 10, 3 );

		add_filter( 'tiered_pricing_table/supported_simple_product_types', function ( $types ) {
			$types[] = 'bundle';

			return $types;
		}, 10, 1 );

		add_action( 'wp_head', function () {
			if ( is_product() ) {

				$currentProductId = get_queried_object_id();
				$product          = wc_get_product( $currentProductId );

				if ( $product->get_type() === 'bundle' ) {
					?>
					<script>

						var TieredPricingBundlesIntegration = function () {
							this.bundle = null;

							jQuery(document).on('woocommerce-product-bundle-initializing', (function (event, bundle) {
								this.bundle = bundle;
							}).bind(this));

							jQuery('.tpt__tiered-pricing').on('tiered_price_update', (function (event, data) {

								this.bundle.price_data.base_regular_price = data.price;
								this.bundle.price_data.base_price = data.price;

								if (this.bundle.is_initialized) {
									this.bundle.dirty_subtotals = true;
									this.bundle.update_totals();
								}
							}).bind(this));
						}

						document.tieredPricingBundlesIntegration = new TieredPricingBundlesIntegration();

					</script>

					<?php
				}
			}
		} );

		add_filter( 'tiered_pricing_table/services/pricing/override_zero_prices', '__return_false' );
	}

	public function getAuthorURL(): string {
		return 'https://woocommerce.com/products/product-bundles/';
	}

	public function getIconURL(): string {
		return $this->getContainer()->getFileManager()->locateAsset( 'admin/integrations/woocommerce-develop.jpeg' );
	}

	public function getTitle(): string {
		return __( 'Product Bundles (by WooCommerce)', 'tier-pricing-table' );
	}

	public function getDescription(): string {
		return __( 'Integration provides compatibility with Product Bundles for WooCommerce to support bundle product type.',
			'tier-pricing-table' );
	}

	public function getSlug(): string {
		return 'product-bundles-for-woocommerce';
	}

	public function getIntegrationCategory(): string {
		return 'custom_product_types';
	}
}
