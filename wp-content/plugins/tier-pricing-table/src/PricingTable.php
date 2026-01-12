<?php namespace TierPricingTable;

use TierPricingTable\Admin\ProductPage\AdvanceOptionsForVariableProduct;
use TierPricingTable\Admin\ProductPage\TieredPricingTab;
use TierPricingTable\Core\ServiceContainerTrait;
use TierPricingTable\Settings\Sections\GeneralSection\GeneralSection;
use WC_Product;
use WC_Product_Data_Store_CPT;
use WC_Product_Variable;
use WC_Product_Variation;

class PricingTable {

	use ServiceContainerTrait;

	private static $instance;

	private function __construct() {}

	public static function getInstance(): self {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Main function for rendering pricing table for product
	 *
	 * @param  int  $parentProductID
	 * @param  int  $variationID
	 * @param  array  $settings
	 */
	public function renderPricingTable( $parentProductID = 0, $variationID = null, array $settings = array() ) {

		if ( apply_filters( 'tiered_pricing_table/should_render_pricing_table', true, $parentProductID, $variationID,
						$settings ) === false ) {
			return;
		}

		$parentProduct = wc_get_product( $parentProductID );

		if ( ! $parentProduct ) {
			return;
		}

		if ( ! $this->productHasPricingRules( $parentProduct ) ) {
			return;
		}

		$settings = wp_parse_args( $settings, $this->getDefaultSettings( $parentProductID ) );

		// If the product is variable, but no specific variation is passed - check for default
		if ( ! $variationID && TierPricingTablePlugin::isVariableProductSupported( $parentProduct ) ) {
			$variationID = $this->getDefaultVariation( $parentProduct );
		}

		$ajaxVariationsThreshold = apply_filters( 'tiered_pricing_table/ajax_variations_threshold', 10 );

		// Use variation if exists, otherwise the parent product is used
		$productId = $variationID ? $variationID : $parentProduct->get_id();
		$product   = wc_get_product( $productId );

		$supportedTypes = array_merge( TierPricingTablePlugin::getSupportedSimpleProductTypes(),
				TierPricingTablePlugin::getSupportedVariableProductTypes() );

		// Exit if product is not valid
		if ( ! $product || ! in_array( $parentProduct->get_type(), $supportedTypes ) ) {
			return;
		}

		$settings = apply_filters( 'tiered_pricing_table/display_settings', $settings, $productId );

		if ( 'tooltip' === $settings['display_type'] ) {
			wp_enqueue_script( 'jquery-ui-tooltip' );
		}

		$hidden = ( 'tooltip' === $settings['display_type'] || ! $settings['display'] );

		do_action( 'tiered_pricing_table/before_rendering_tiered_pricing', $parentProduct, $variationID, $settings );

		$loadVariationByAjax = true;

		?>
		<div class="clear"></div>
		<div class="tpt__tiered-pricing <?php echo esc_attr( $hidden ? 'tpt__hidden' : '' ); ?>"
			 data-settings="<?php echo esc_attr( json_encode( $settings ) ); ?>"
			 data-display-context="<?php echo esc_attr( $settings['display_context'] ); ?>"
			 data-display-type="<?php echo esc_attr( $settings['display_type'] ); ?>"
			 data-product-id="<?php echo esc_attr( $parentProduct->get_id() ); ?>"
			 data-product-type="<?php echo esc_attr( $parentProduct->get_type() ); ?>"

				<?php if ( TierPricingTablePlugin::isVariableProductSupported( $parentProduct ) ) : ?>
					<?php
					$loadVariationByAjax       = count( $parentProduct->get_children() ) > $ajaxVariationsThreshold;
					$variableProductSamePrices = AdvanceOptionsForVariableProduct::isVariableProductSamePrices( $parentProduct->get_id() );
					?>
					data-load-variation-by-ajax="<?php echo esc_attr( wc_bool_to_string( $loadVariationByAjax ) ); ?>"
					data-variable-product-same-prices="<?php echo esc_attr( wc_bool_to_string( $variableProductSamePrices ) ); ?>"
				<?php endif; ?>>

			<?php $this->renderPricingTableHTML( $parentProduct, $product, $settings ); ?>
		</div>

		<?php if ( TierPricingTablePlugin::isVariableProductSupported( $parentProduct ) && ! $loadVariationByAjax ) : ?>
			<div class="tpt__tiered-pricing-preloaded-variations" style="display:none">
				<?php foreach ( $parentProduct->get_available_variations( 'objects' ) as $childProduct ) : ?>
					<div class="tiered-pricing-preloaded-variation"
						 data-variation-id="<?php echo esc_attr( $childProduct->get_id() ); ?>"
						 data-display-context="<?php echo esc_attr( $settings['display_context'] ); ?>">
						<?php $this->renderPricingTableHTML( $parentProduct, $childProduct, $settings ); ?>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
		<?php
	}

	/**
	 * Render pricing table without wrapper
	 *
	 * @param  WC_Product  $parentProduct
	 * @param  WC_Product  $product
	 * @param  array  $settings
	 */
	public function renderPricingTableHTML(
			WC_Product $parentProduct,
			WC_Product $product,
			array $settings = array()
	) {

		$settings = wp_parse_args( $settings, $this->getDefaultSettings( $parentProduct->get_id() ) );

		// Don't get rules from variable product when variation is empty
		if ( in_array( $parentProduct->get_type(),
						TierPricingTablePlugin::getSupportedVariableProductTypes() ) && ! ( $product instanceof WC_Product_Variation ) ) {
			// Empty rule
			$priceRule = new PricingRule( $product->get_id() );
		} else {
			$priceRule = PriceManager::getPricingRule( $product->get_id() );
		}

		// If rules are not empty
		if ( ! empty( $priceRule->getRules() ) ) {

			$displayType        = $settings['display_type'];
			$availableTemplates = TierPricingTablePlugin::getAvailablePricingLayouts();

			$templateType = array_key_exists( $settings['display_type'], $availableTemplates ) ? $displayType : 'table';

			if ( 'tooltip' === $displayType ) {
				$templateType = 'table';
			}

			if ( 'blocks' === $displayType ) {
				$style = $settings['blocks_style'];

				if ( 'default' !== $style ) {
					$templateType = 'blocks-' . $style;
				} else {
					$templateType = 'blocks';
				}
			}

			if ( 'options' === $displayType ) {
				$style = $settings['options_style'];

				if ( $style !== 'default' ) {
					$templateType = 'options-' . $style;
				} else {
					$templateType = 'options';
				}
			}

			$template = "tiered-pricing-$templateType.php";

			do_action( 'tiered_pricing_table/before_rendering_tiered_pricing/inner', $priceRule, $product, $settings );

			$this->checkForDuplicateQuantities( $priceRule );

			$this->getContainer()->getFileManager()->includeTemplate( 'frontend/' . $template, array(
					'pricing_rule' => $priceRule,
					'price_rules'  => $priceRule->getRules(),
					'real_price'   => $product->get_price(),
					'product_name' => $product->get_name(),
					'product_id'   => $product->get_id(),
					'product'      => $product,
					'minimum'      => $priceRule->getMinimum( true ),
					'settings'     => $settings,
					'pricing_type' => $priceRule->getType(),
					'id'           => $this->getUniqueTieredPricingId(),
			) );
		}
	}

	protected function getDefaultSettings( $productId = false ): array {
		$settings = array(
				'display_context'       => 'product-page',
				'display'               => $this->getContainer()->getSettings()->get( 'display', 'yes' ) === 'yes',
				'display_type'          => $this->getContainer()->getSettings()->get( 'display_type', 'table' ),
				'title'                 => $this->getContainer()->getSettings()->get( 'table_title', '' ),
				'table_class'           => $this->getContainer()->getSettings()->get( 'table_css_class', '' ),
				'quantity_column_title' => $this->getContainer()->getSettings()->get( 'head_quantity_text',
						__( 'Quantity', 'tier-pricing-table' ) ),
				'price_column_title'    => $this->getContainer()->getSettings()->get( 'head_price_text',
						__( 'Price', 'tier-pricing-table' ) ),
				'discount_column_title' => $this->getContainer()->getSettings()->get( 'head_discount_text',
						__( 'Discount (%)', 'tier-pricing-table' ) ),
				'quantity_type'         => $this->getContainer()->getSettings()->get( 'quantity_type', 'range' ),
				'show_discount_column'  => $this->getContainer()->getSettings()->get( 'show_discount_column',
								'yes' ) === 'yes',
				'clickable_rows'        => $this->getContainer()->getSettings()->get( 'clickable_table_rows',
								'yes' ) === 'yes',
				'active_tier_color'     => $this->getContainer()->getSettings()->get( 'selected_quantity_color',
						'#96598A' ),
				'tooltip_border'        => $this->getContainer()->getSettings()->get( 'tooltip_border',
								'yes' ) === 'yes',

				'blocks_style'  => GeneralSection::getPricingBlocksStyle(),
				'options_style' => GeneralSection::getPricingOptionsStyle(),

				'options_show_total'                  => GeneralSection::isShowOptionTotal(),
				'options_show_original_product_price' => GeneralSection::isShowOriginalProductPrice(),
				'options_show_default_option'         => GeneralSection::isDefaultOptionEnabled(),

				'options_default_option_text' => GeneralSection::getDefaultOptionText(),
				'options_option_text'         => GeneralSection::getOptionText(),

				'plain_text_show_default_option' => GeneralSection::isPlainTextFirstTierEnabled(),
				'plain_text_option_text'         => GeneralSection::getPlainTextTemplate(),
				'plain_text_default_option_text' => GeneralSection::getPlainTextFirstTierTemplate(),

				'update_price_on_product_page'  => $this->getContainer()->getSettings()->get( 'update_price_on_product_page',
								'yes' ) === 'yes',
				'show_tiered_price_as_discount' => $this->getContainer()->getSettings()->get( 'show_tiered_price_as_discount',
								'yes' ) === 'yes',
				'show_total_price'              => $this->getContainer()->getSettings()->get( 'show_total_price',
								'no' ) === 'yes',
		);

		$default_quantity_measurement = array(
				'singular' => '',
				'plural'   => '',
		);

		$quantity_measurement = $default_quantity_measurement;

		if ( in_array( $settings['display_type'], array( 'table', 'horizontal-table', 'tooltip' ) ) ) {
			$quantity_measurement = $this->getContainer()->getSettings()->get( 'table_quantity_measurement',
					$default_quantity_measurement );
		}

		if ( 'blocks' === $settings['display_type'] ) {
			$quantity_measurement = $this->getContainer()->getSettings()->get( 'blocks_quantity_measurement', array(
					'singular' => _n( 'piece', 'pieces', 1, 'tier-pricing-table' ),
					'plural'   => _n( 'piece', 'pieces', 2, 'tier-pricing-table' ),
			) );
		}

		$settings['quantity_measurement_singular'] = $quantity_measurement['singular'];
		$settings['quantity_measurement_plural']   = $quantity_measurement['plural'];

		if ( $productId ) {
			$template = TieredPricingTab::getProductTemplate( $productId );

			if ( 'default' !== $template ) {
				$settings['display_type'] = $template;
			}

			$baseUnitName = TieredPricingTab::getProductBaseUnitName( $productId );

			if ( $baseUnitName['singular'] && $baseUnitName['plural'] ) {
				$settings['quantity_measurement_singular'] = $baseUnitName['singular'];
				$settings['quantity_measurement_plural']   = $baseUnitName['plural'];
			}
		}

		return $settings;
	}

	protected function getUniqueTieredPricingId() {
		return preg_replace( '/[0-9]+/', '', strtolower( wp_generate_password( 20, false ) ) );
	}

	protected function getDefaultVariation( WC_Product_Variable $product ): ?int {

		$defaultVariation = AdvanceOptionsForVariableProduct::getDefaultVariation( $product->get_id() );

		if ( $defaultVariation ) {
			return $defaultVariation->get_id();
		}

		$defaultAttributes = $product->get_default_attributes();

		if ( ! empty( $defaultAttributes ) ) {

			$defaultAttributesKeys = array_map( function ( $attribute ) {
				return 'attribute_' . $attribute;
			}, array_keys( $defaultAttributes ) );

			$defaultAttributes = array_combine( $defaultAttributesKeys, $defaultAttributes );

			return ( new WC_Product_Data_Store_CPT() )->find_matching_product_variation( $product, $defaultAttributes );
		}

		return null;
	}

	public function productHasPricingRules( WC_Product $product ): bool {

		if ( TierPricingTablePlugin::isSimpleProductSupported( $product ) ) {
			$pricingRule = PriceManager::getPricingRule( $product->get_id() );

			return ! empty( $pricingRule->getRules() );
		}

		if ( TierPricingTablePlugin::isVariableProductSupported( $product ) && $product instanceof WC_Product_Variable ) {

			// Do not check if product has tiered pricing for variable product by default.
			// If a variable product has many variations, it can take many resources to check every variation for rules existing.
			// The downside is that the plugin will send an AJAX request for each variant selection on the product page even if a product does not have any tiered pricing
			if ( ! apply_filters( 'tiered_pricing_table/check_if_variable_product_has_rules', false, $product ) ) {
				return true;
			}

			$hasTieredPricing = $this->getContainer()->getCache()->getProductData( $product, 'product_has_rules' );

			if ( 'no' === $hasTieredPricing ) {
				return false;
			}

			if ( 'yes' === $hasTieredPricing ) {
				return true;
			}

			foreach ( $product->get_available_variations() as $productVariation ) {
				$pricingRule = PriceManager::getPricingRule( $productVariation['variation_id'] );

				if ( ! empty( $pricingRule->getRules() ) ) {
					$this->getContainer()->getCache()->setProductData( $product, 'product_has_rules', 'yes' );

					return true;
				}
			}

			$this->getContainer()->getCache()->setProductData( $product, 'product_has_rules', 'no' );
		}

		return false;
	}

	public function checkForDuplicateQuantities( PricingRule $pricingRule ) {
		// Show notice only for admin users
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$minimum           = $pricingRule->getMinimum( true );
		$pricingRules      = $pricingRule->getRules();
		$firstRuleQuantity = ! empty( $pricingRules ) ? array_keys( $pricingRules )[0] : false;

		if ( $firstRuleQuantity && $firstRuleQuantity <= $minimum ) {
			?>

			<div style="font-size: .8em;">
				<div class="woocommerce-error" role="alert" style="margin-bottom: 0">
					Duplicated quantities detected. Minimum order quantity is equal or higher than the first tiered
					pricing.
				</div>
				<div style="color: #555;
	padding: 10px 12px;
	border: 1px solid #b32c2e;
	background: #fff5f5;">
					<p style="padding: 0; margin: 0">
						The minimum order quantity must be less than the first tiered pricing rule quantity.
					</p>
					<p style="padding: 0; margin: 0"> The first tier always uses woocommerce price and is applied to
						quantities between minimum order
						quantity and
						the first tiered pricing rule quantity.
					</p>
					<br>
					<b>This notice shown only for administrators.</b>
				</div>
			</div>
			<?php
		}
	}
}