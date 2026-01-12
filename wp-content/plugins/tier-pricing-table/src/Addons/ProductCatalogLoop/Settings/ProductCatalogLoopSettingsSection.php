<?php namespace TierPricingTable\Addons\ProductCatalogLoop\Settings;

use TierPricingTable\Settings\CustomOptions\TPTDisplayType;
use TierPricingTable\Settings\CustomOptions\TPTQuantityMeasurementField;
use TierPricingTable\Settings\CustomOptions\TPTSwitchOption;
use TierPricingTable\Settings\CustomOptions\TPTTextTemplate;
use TierPricingTable\Settings\Sections\SectionAbstract;
use TierPricingTable\Settings\Settings;
use TierPricingTable\TierPricingTablePlugin;

class ProductCatalogLoopSettingsSection extends SectionAbstract {

	public static function getOptionID( $option ): string {
		return self::getSettingsPrefix() . $option;
	}

	public static function getSettingsPrefix(): string {
		return Settings::SETTINGS_PREFIX . 'shop_loop_display_';
	}

	public function getName(): string {
		return __( 'Product Catalog', 'tier-pricing-table' );
	}

	public function getSlug(): string {
		return 'shop-loop-display';
	}

	public function getSettings(): array {

		$availableLayouts = TierPricingTablePlugin::getAvailablePricingLayouts();
		unset( $availableLayouts['tooltip'] );

		return array(
				array(
						'title' => __( 'Tiered Pricing on the shop page', 'tier-pricing-table' ),
						'desc'  => __( 'This section controls how tiered pricing works for product catalog.',
								'tier-pricing-table' ),
						'type'  => 'title',
				),
				array(
						'title'                => __( 'Enabled', 'tier-pricing-table' ),
						'id'                   => self::getOptionID( 'enabled' ),
						'type'                 => TPTSwitchOption::FIELD_TYPE,
						'default'              => 'no',
						'extended_description' => function () {
							?>
							<p>
								<?php
									esc_html_e( 'Enable this option to show tiered pricing in the products catalog.',
											'tier-pricing-table' );
								?>
								<br>
								<br>
								<b><?php esc_html_e( 'Note:', 'tier-pricing-table' ); ?></b>
								<br>
								<?php 
								esc_html_e( 'Some themes could display it incorrectly.',
										'tier-pricing-table' ); 
										?>
							</p>
							<?php
						},
						'desc'                 => __( 'Show tiered pricing in the product loop (product catalog page)',
								'tier-pricing-table' ),
						'desc_tip'             => true,
				),
				array(
						'title'    => __( 'Position on a product item', 'tier-pricing-table' ),
						'id'       => self::getOptionID( 'position' ),
						'type'     => 'select',
						'default'  => 'woocommerce_after_shop_loop_item__6',
						'options'  => array(
								'woocommerce_after_shop_loop_item__6'  => __( 'Above add-to-cart button',
										'tier-pricing-table' ),
								'woocommerce_after_shop_loop_item__15' => __( 'Below add-to-cart button',
										'tier-pricing-table' ),
								'woocommerce_shop_loop_item_title__5'  => __( 'Above product title',
										'tier-pricing-table' ),
								'woocommerce_shop_loop_item_title__15' => __( 'Below product title',
										'tier-pricing-table' ),
						),
						'desc'     => __( 'Where tiered pricing should be displayed on the product page.',
								'tier-pricing-table' ),
						'desc_tip' => true,
				),
				array(
						'title'                => __( 'Show quantity field', 'tier-pricing-table' ),
						'id'                   => self::getOptionID( 'show_quantity_field' ),
						'type'                 => TPTSwitchOption::FIELD_TYPE,
						'default'              => 'no',
						'extended_description' => function () {
							?>
							<p>
								<?php
									esc_html_e( 'Show a quantity field on the product catalog items.',
											'tier-pricing-table' );
								?>
								<br>
								<br>
								<b>
									<?php
										esc_html_e( 'Enable this only if your theme does not show the quantity field already.',
												'tier-pricing-table' );
									?>
								</b>
							</p>
							<?php
						},
				),
				array(
						'title'                => __( 'Dynamic price', 'tier-pricing-table' ),
						'id'                   => self::getOptionID( 'dynamic_price' ),
						'type'                 => TPTSwitchOption::FIELD_TYPE,
						'default'              => 'yes',
						'extended_description' => function () {
							?>
							<p>
								<?php
									esc_html_e( 'Similar to the product page, the price will be updated dynamically when the user changes the quantity of a specific item.',
											'tier-pricing-table' );
								?>
							</p>
							<?php
						},
						'desc_tip'             => true,
				),
				array(
						'title'    => __( 'Use reduced styles', 'tier-pricing-table' ),
						'id'       => self::getOptionID( 'use_reduced_styles' ),
						'type'     => TPTSwitchOption::FIELD_TYPE,
						'default'  => 'yes',
						'desc'     => __( 'The plugin will apply additional CSS styles to the pricing layouts displayed in the product catalog to make them compact.',
								'tier-pricing-table' ),
						'desc_tip' => true,
				),
				array(
						'type' => 'sectionend',
				),

				array(
						'title' => __( 'Template options', 'tier-pricing-table' ),
						'desc'  => __( 'Choose a tiered pricing template and customize its look and behavior.',
								'tier-pricing-table' ),
						'id'    => self::getOptionId( 'layout_settings' ),
						'type'  => 'title',
				),

				array(
						'title'    => __( 'Layout settings', 'tier-pricing-table' ),
						'id'       => self::getOptionID( 'layout_settings' ),
						'type'     => TPTDisplayType::FIELD_TYPE,
						'options'  => array(
								'default' => __( 'Same as for product page', 'tier-pricing-table' ),
								'custom'  => __( 'Custom', 'tier-pricing-table' ),
						),
						'desc'     => __( 'The template for tiered pricing for the catalog page. You can use the same template settings as for the product page or choose a different one.',
								'tier-pricing-table' ),
						'desc_tip' => true,
						'default'  => 'default',
				),
				array(
						'title'    => __( 'Layout', 'tier-pricing-table' ),
						'id'       => self::getOptionID( 'layout' ),
						'type'     => TPTDisplayType::FIELD_TYPE,
						'options'  => $availableLayouts,
						'desc'     => __( 'Template for tiered pricing on the catalog page.', 'tier-pricing-table' ),
						'desc_tip' => true,
						'default'  => 'table',
				),
				array(
						'title'   => __( 'Pricing title', 'tier-pricing-table' ),
						'id'      => self::getOptionID( 'title' ),
						'type'    => 'text',
						'default' => '',
						'desc'    => __( 'The pricing title that is displayed above the tiered pricing.',
								'tier-pricing-table' ),
				),
				array(
						'title'   => __( 'Quantity display type', 'tier-pricing-table' ),
						'id'      => self::getOptionID( 'quantity_type' ),
						'type'    => TPTDisplayType::FIELD_TYPE,
						'options' => array(
								'range'  => __( 'Range', 'tier-pricing-table' ),
								'static' => __( 'Static values', 'tier-pricing-table' ),
						),
						'desc'    => __( '"Range" displays the quantity range a tier applies to. "Static" displays only the minimum quantity required.',
								'tier-pricing-table' ),
						'default' => 'range',
				),
				array(
						'title'   => __( 'Active pricing tier color', 'tier-pricing-table' ),
						'id'      => self::getOptionID( 'selected_quantity_color' ),
						'type'    => 'color',
						'css'     => 'width:6em;',
						'default' => '#96598A',
				),
				array(
						'title'   => __( 'Unit label', 'tier-pricing-table' ),
						'id'      => self::getOptionID( 'table_quantity_measurement' ),
						'type'    => TPTQuantityMeasurementField::FIELD_TYPE,
						'default' => array(
								'singular' => '',
								'plural'   => '',
						),
						'desc'    => __( 'For example: pieces, boxes, bottles, packs, etc. This will be shown next to quantity values. Leave blank to skip adding a unit label.',
								'tier-pricing-table' ),
				),
				array(
						'title'   => __( 'Unit label', 'tier-pricing-table' ),
						'id'      => self::getOptionID( 'blocks_quantity_measurement' ),
						'type'    => TPTQuantityMeasurementField::FIELD_TYPE,
						'default' => array(
								'singular' => _n( 'piece', 'pieces', 1, 'tier-pricing-table' ),
								'plural'   => _n( 'piece', 'pieces', 2, 'tier-pricing-table' ),
						),
						'desc'    => __( 'For example: pieces, boxes, bottles, packs, etc. This will be shown next to quantity values. Leave blank to skip adding a unit label.',
								'tier-pricing-table' ),
				),
				array(
						'title'   => __( 'Quantity column title', 'tier-pricing-table' ),
						'default' => __( 'Quantity', 'tier-pricing-table' ),
						'id'      => self::getOptionID( 'quantity_column_title' ),
						'desc'    => __( 'Leave empty to not show this column.', 'tier-pricing-table' ),
						'type'    => 'text',
				),
				array(
						'title'   => __( 'Discount column title', 'tier-pricing-table' ),
						'default' => __( 'Discount', 'tier-pricing-table' ),
						'id'      => self::getOptionID( 'discount_column_title' ),
						'desc'    => __( 'Leave empty to not show this column.', 'tier-pricing-table' ),
						'type'    => 'text',
				),
				array(
						'title'   => __( 'Price column title', 'tier-pricing-table' ),
						'default' => __( 'Price', 'tier-pricing-table' ),
						'id'      => self::getOptionID( 'price_column_title' ),
						'desc'    => __( 'Leave empty to not show this column.', 'tier-pricing-table' ),
						'type'    => 'text',
				),
				array(
						'title'   => __( 'Show percentage discount', 'tier-pricing-table' ),
						'id'      => self::getOptionID( 'blocks_show_discount' ),
						'type'    => TPTSwitchOption::FIELD_TYPE,
						'default' => 'yes',
				),
				array(
						'title'   => __( 'Show regular product price', 'tier-pricing-table' ),
						'id'      => self::getOptionID( 'options_show_original_product_price' ),
						'type'    => TPTSwitchOption::FIELD_TYPE,
						'default' => 'yes',
						'desc'    => __( 'Show the crossed out regular price in options.', 'tier-pricing-table' ),
				),
				array(
						'title'   => __( 'Show total pricing in option', 'tier-pricing-table' ),
						'id'      => self::getOptionID( 'options_show_total' ),
						'type'    => TPTSwitchOption::FIELD_TYPE,
						'default' => 'yes',
						'desc'    => __( 'Show the total price in an active option.', 'tier-pricing-table' ),
				),
				array(
						'title'        => __( 'Option template', 'tier-pricing-table' ),
						'id'           => self::getOptionID( 'options_option_text' ),
						'default'      => __( '<strong>Buy {tp_quantity} pieces and save {tp_rounded_discount}%</strong>',
								'tier-pricing-table' ),
						'placeholders' => array(
								'tp_quantity',
								'tp_discount',
								'tp_rounded_discount',
						),
						'type'         => TPTTextTemplate::FIELD_TYPE,
				),
				array(
						'title'   => __( 'Show the "no discount" option', 'tier-pricing-table' ),
						'id'      => self::getOptionID( 'options_show_default_option' ),
						'type'    => TPTSwitchOption::FIELD_TYPE,
						'default' => 'yes',
						'desc'    => __( 'Show the option with a regular product price.', 'tier-pricing-table' ),
				),
				array(
						'title'        => __( '"No discount" option template', 'tier-pricing-table' ),
						'id'           => self::getOptionID( 'options_default_option_text' ),
						'default'      => __( '<strong>Buy {tp_quantity} pieces</strong>', 'tier-pricing-table' ),
						'placeholders' => array(
								'tp_quantity',
						),
						'type'         => TPTTextTemplate::FIELD_TYPE,
				),
				array(
						'title'        => __( 'Template', 'tier-pricing-table' ),
						'id'           => self::getOptionID( 'plain_text_template' ),
						'default'      => __( '<strong>Buy {tp_quantity} pieces for {tp_price} each and save {tp_rounded_discount}%</strong>',
								'tier-pricing-table' ),
						'placeholders' => array(
								'tp_quantity',
								'tp_discount',
								'tp_price',
								'tp_rounded_discount',
						),
						'type'         => TPTTextTemplate::FIELD_TYPE,
				),

				array(
						'title'   => __( 'Show the "no discount" tier', 'tier-pricing-table' ),
						'id'      => self::getOptionID( 'plain_text_show_first_tier' ),
						'type'    => TPTSwitchOption::FIELD_TYPE,
						'default' => 'yes',
						'desc'    => __( 'Show the tier with a regular product price.', 'tier-pricing-table' ),
				),

				array(
						'title'        => __( '"No discount" template', 'tier-pricing-table' ),
						'id'           => self::getOptionID( 'plain_text_first_tier_template' ),
						'default'      => __( '<strong>Buy {tp_quantity} pieces for {tp_price} each</strong>',
								'tier-pricing-table' ),
						'placeholders' => array(
								'tp_quantity',
								'tp_price',
						),
						'type'         => TPTTextTemplate::FIELD_TYPE,
				),
				array(
						'title'   => __( 'Clickable tiered pricing', 'tier-pricing-table' ),
						'id'      => self::getOptionID( 'clickable_table_rows' ),
						'type'    => TPTSwitchOption::FIELD_TYPE,
						'default' => 'yes',
						'desc'    => __( 'Makes tiered pricing (table rows, blocks, options, etc) clickable.',
								'tier-pricing-table' ),
				),
				array(
						'type' => 'sectionend',
				),
		);
	}

	public static function isEnabled(): bool {
		return 'yes' === get_option( self::getOptionID( 'enabled' ), 'no' );
	}

	public static function getPosition(): array {
		$hook = get_option( self::getOptionID( 'position' ), 'woocommerce_after_shop_loop_item__6' );

		$hook = explode( '__', $hook );

		return array(
				'hook'     => ! empty( $hook[0] ) ? $hook[0] : '__none__',
				'priority' => ! empty( $hook[1] ) ? $hook[1] : 15,
		);
	}

	public static function isCustomLayoutSettings(): bool {
		return 'custom' === get_option( self::getOptionID( 'layout_settings' ), 'default' );
	}

	public static function getLayoutType(): string {
		return get_option( self::getOptionID( 'layout' ), 'table' );
	}

	public static function getTitle(): string {
		return get_option( self::getOptionID( 'title' ), '' );
	}

	public static function getQuantityType(): string {
		return get_option( self::getOptionID( 'quantity_type' ), 'range' );
	}

	public static function getSelectedQuantityColor(): string {
		return get_option( self::getOptionID( 'selected_quantity_color' ), '#96598A' );
	}

	public static function getTableQuantityMeasurement(): array {
		return get_option( self::getOptionID( 'table_quantity_measurement' ), array(
				'singular' => '',
				'plural'   => '',
		) );
	}

	public static function getBlocksQuantityMeasurement(): array {
		return get_option( self::getOptionID( 'blocks_quantity_measurement' ), array(
				'singular' => _n( 'piece', 'pieces', 1, 'tier-pricing-table' ),
				'plural'   => _n( 'piece', 'pieces', 2, 'tier-pricing-table' ),
		) );
	}

	public static function getTableColumnsTitles(): array {
		return array(
				'head_quantity_text' => get_option( self::getOptionID( 'quantity_column_title' ),
						__( 'Quantity', 'tier-pricing-table' ) ),
				'head_discount_text' => get_option( self::getOptionID( 'discount_column_title' ),
						__( 'Discount', 'tier-pricing-table' ) ),
				'head_price_text'    => get_option( self::getOptionID( 'price_column_title' ),
						__( 'Price', 'tier-pricing-table' ) ),
		);
	}

	public static function blocksShowDiscount(): bool {
		return 'yes' === get_option( self::getOptionID( 'blocks_show_discount' ), 'yes' );
	}

	public static function isShowOriginalProductPriceInOptions(): bool {
		return 'yes' === get_option( self::getOptionID( 'options_show_original_product_price' ), 'yes' );
	}

	public static function isShowTotalInOptions(): bool {
		return 'yes' === get_option( self::getOptionID( 'options_show_total' ), 'yes' );
	}

	public static function getOptionText(): string {
		return get_option( self::getOptionID( 'options_option_text' ),
				'<strong>Buy {tp_quantity} pieces and save {tp_rounded_discount}%</strong>' );
	}

	public static function isShowDefaultOption(): bool {
		return 'yes' === get_option( self::getOptionID( 'options_show_default_option' ), 'yes' );
	}

	public static function getDefaultOptionText(): string {
		return get_option( self::getOptionID( 'options_default_option_text' ),
				'<strong>Buy {tp_quantity} pieces</strong>' );
	}

	public static function getPlainTextTemplate(): string {
		return get_option( self::getOptionID( 'plain_text_template' ),
				'<strong>Buy {tp_quantity} pieces for {tp_price} each and save {tp_rounded_discount}%</strong>' );
	}

	public static function isShowFirstPlainTextTier(): bool {
		return 'yes' === get_option( self::getOptionID( 'plain_text_show_first_tier' ), 'yes' );
	}

	public static function getFirstTierPlainTextTemplate(): string {
		return get_option( self::getOptionID( 'plain_text_first_tier_template' ),
				'<strong>Buy {tp_quantity} pieces for {tp_price} each</strong>' );
	}

	public static function isClickableTableRows(): bool {
		return 'yes' === get_option( self::getOptionID( 'clickable_table_rows' ), 'yes' );
	}

	public static function useReducedStyles(): bool {
		return 'yes' === get_option( self::getOptionID( 'use_reduced_styles' ), 'yes' );
	}

	public static function isDynamicPrice(): bool {
		return 'yes' === get_option( self::getOptionID( 'dynamic_price' ), 'yes' );
	}

	public static function showQuantityField(): bool {
		return 'yes' === get_option( self::getOptionID( 'show_quantity_field' ), 'no' );
	}

}