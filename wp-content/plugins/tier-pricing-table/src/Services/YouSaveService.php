<?php namespace TierPricingTable\Services;

use TierPricingTable\Core\ServiceContainerTrait;
use TierPricingTable\Settings\Settings;
use WC_Product;

/**
 * Class YouSaveService
 *
 * Shows saving amount
 *
 * @package TierPricingTable\Services
 */
class YouSaveService {
	
	use ServiceContainerTrait;
	
	/**
	 * CatalogPriceManager constructor.
	 */
	public function __construct() {
		
		if ( $this->isEnabled() ) {
			add_action( 'woocommerce_get_price_html', array( $this, 'addYouSave' ), 150, 2 );
		}
		
		add_shortcode( 'tiered_price_you_save', function ( $tag, $args ) {
			
			$args = wp_parse_args( $args, array(
				'product_id'          => null,
				'color'               => $this->getTextColor(),
				'template'            => $this->getTemplate(),
				'consider_sale_price' => $this->considerSalePrice(),
			) );
			
			$product = wc_get_product( $args['product_id'] );
			
			if ( ! $product ) {
				return '';
			}
			
			return $this->getYouSaveHTML( $args['color'], $args['template'], $args['consider_sale_price'], $product );
		} );
	}
	
	public function addYouSave( $priceHTML, WC_Product $product ) {
		
		if ( false === strpos( $priceHTML, 'tiered-pricing-dynamic-price-wrapper' ) ) {
			return $priceHTML;
		}
		
		$priceHTML .= '<br>' . $this->getYouSaveHTML( $this->getTextColor(), $this->getTemplate(),
				$this->considerSalePrice(), $product );
		
		return $priceHTML;
	}
	
	public function getYouSaveHTML( $color, $template, $considerSalePrice, $product = null ): string {
		$template = $this->parseTemplate( $template );
		
		ob_start();
		?>
		<small data-consider-sale-price="<?php echo esc_attr( wc_bool_to_string( $considerSalePrice ) ); ?>"
			   data-product-id="<?php echo esc_attr( $product->get_id() ); ?>"
			   data-parent-id="<?php echo esc_attr( $product->get_parent_id() ? $product->get_parent_id() : $product->get_id() ); ?>"
			   class="tiered-pricing-you-save tiered-pricing-you-save--hidden"
			   style="color: <?php echo esc_attr( $color ); ?>"><?php echo wp_kses_post( $template ); ?>
		</small>
		<?php
		return ob_get_clean();
	}
	
	public function getTemplate() {
		return get_option( Settings::SETTINGS_PREFIX . 'you_save_template', 'You save: {tp_ys_total_price}' );
	}
	
	public function getTextColor() {
		return get_option( Settings::SETTINGS_PREFIX . 'you_save_text_color', '#FF0000' );
	}
	
	public function considerSalePrice(): bool {
		return get_option( Settings::SETTINGS_PREFIX . 'you_save_consider_sale_price', 'yes' ) === 'yes';
	}
	
	public function isEnabled(): bool {
		return get_option( Settings::SETTINGS_PREFIX . 'you_save_enabled', 'no' ) === 'yes';
	}
	
	protected function parseTemplate( $template ): string {
		return strtr( $template, array(
			'{tp_ys_price}'       => '<span class="tiered-pricing-you-save__price"></span>',
			'{tp_ys_total_price}' => '<span class="tiered-pricing-you-save__total"></span>',
		) );
	}
	
}
