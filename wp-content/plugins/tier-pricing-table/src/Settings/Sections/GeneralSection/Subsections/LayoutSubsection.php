<?php namespace TierPricingTable\Settings\Sections\GeneralSection\Subsections;

use TierPricingTable\Settings\CustomOptions\TPTDisplayType;
use TierPricingTable\Settings\CustomOptions\TPTTableColumnsField;
use TierPricingTable\Settings\CustomOptions\TPTQuantityMeasurementField;
use TierPricingTable\Settings\CustomOptions\TPTSwitchOption;
use TierPricingTable\Settings\CustomOptions\TPTTextTemplate;
use TierPricingTable\Settings\CustomOptions\TPTTwoFields;
use TierPricingTable\Settings\Sections\SubsectionAbstract;
use TierPricingTable\Settings\Settings;
use TierPricingTable\TierPricingTablePlugin;

class LayoutSubsection extends SubsectionAbstract {
	
	public function getTitle(): string {
		return __( 'Template options', 'tier-pricing-table' );
	}
	
	public function getDescription(): string {
		return __( 'Choose a tiered pricing template and customize its look and behavior.', 'tier-pricing-table' );
	}
	
	public function getSlug(): string {
		return 'layout';
	}
	
	public function getSettings(): array {
		return array(
			array(
				'title'    => __( 'Show tiered pricing', 'tier-pricing-table' ),
				'id'       => Settings::SETTINGS_PREFIX . 'display',
				'type'     => TPTSwitchOption::FIELD_TYPE,
				'default'  => 'yes',
				'desc'     => __( 'Automatically display tiered pricing on the product page. Prices remain dynamic even if the tiered pricing is not displayed. You can also display tiered pricing via shortcode, Gutenberg block, or Elementor widget.',
					'tier-pricing-table' ),
				'desc_tip' => true,
			),
			array(
				'title'    => __( 'Default template', 'tier-pricing-table' ),
				'id'       => Settings::SETTINGS_PREFIX . 'display_type',
				'type'     => TPTDisplayType::FIELD_TYPE,
				'options'  => TierPricingTablePlugin::getAvailablePricingLayouts(),
				'desc'     => __( 'Default tiered pricing template. Template can be customized individually per product.',
					'tier-pricing-table' ),
				'desc_tip' => true,
				'default'  => 'table',
			),
			array(
				'title'    => __( 'Pricing options style', 'tier-pricing-table' ),
				'id'       => Settings::SETTINGS_PREFIX . 'pricing_options_style',
				'type'     => TPTDisplayType::FIELD_TYPE,
				'options'  => array(
					'default' => __( 'Default', 'tier-pricing-table' ),
					'style-1' => __( 'Style #1', 'tier-pricing-table' ),
					'style-2' => __( 'Style #2', 'tier-pricing-table' ),
					'style-3' => __( 'Style #3', 'tier-pricing-table' ),
				),
				'desc_tip' => true,
				'default'  => 'default',
			),
			array(
				'title'    => __( 'Pricing blocks style', 'tier-pricing-table' ),
				'id'       => Settings::SETTINGS_PREFIX . 'pricing_blocks_style',
				'type'     => TPTDisplayType::FIELD_TYPE,
				'options'  => array(
					'default' => __( 'Default', 'tier-pricing-table' ),
					'style-1' => __( 'Style #1', 'tier-pricing-table' ),
					'style-2' => __( 'Style #2', 'tier-pricing-table' ),
					'style-3' => __( 'Style #3', 'tier-pricing-table' ),
					'style-4' => __( 'Style #4', 'tier-pricing-table' ),
				),
				'desc_tip' => true,
				'default'  => 'default',
			),
			array(
				'title'    => __( 'Pricing title', 'tier-pricing-table' ),
				'id'       => Settings::SETTINGS_PREFIX . 'table_title',
				'type'     => 'text',
				'default'  => '',
				'desc'     => __( 'The title is shown above the tiered pricing.', 'tier-pricing-table' ),
				'desc_tip' => true,
			),
			array(
				'title'    => __( 'Position on the product page', 'tier-pricing-table' ),
				'id'       => Settings::SETTINGS_PREFIX . 'position_hook',
				'type'     => 'select',
				'options'  => array(
					'woocommerce_before_add_to_cart_button'     => __( 'Above add to cart button', 'tier-pricing-table' ),
					'woocommerce_after_add_to_cart_button'      => __( 'Below add to cart button', 'tier-pricing-table' ),
					'woocommerce_before_add_to_cart_form'       => __( 'Above add to cart form', 'tier-pricing-table' ),
					'woocommerce_after_add_to_cart_form'        => __( 'Below add to cart form', 'tier-pricing-table' ),
					'woocommerce_single_product_summary'        => __( 'Above product title', 'tier-pricing-table' ),
					'woocommerce_before_single_product_summary' => __( 'Before product summary', 'tier-pricing-table' ),
					'woocommerce_after_single_product_summary'  => __( 'After product summary', 'tier-pricing-table' ),
					'____none____'                              => __( 'I display tiered pricing via shortcode/gutenberg/elementor',
						'tier-pricing-table' ),
				),
				'desc'     => __( 'Choose where you what tiered pricing be displayed on the product page.',
					'tier-pricing-table' ),
				'desc_tip' => true,
			),
			array(
				'title'    => __( 'Quantity display type', 'tier-pricing-table' ),
				'id'       => Settings::SETTINGS_PREFIX . 'quantity_type',
				'type'     => TPTDisplayType::FIELD_TYPE,
				'options'  => array(
					'range'  => __( 'Range', 'tier-pricing-table' ),
					'static' => __( 'Static values', 'tier-pricing-table' ),
				),
				'desc'     => __( '"Range" displays the quantity range a tier applies to. "Static" displays only the minimum quantity required.',
					'tier-pricing-table' ),
				'desc_tip' => false,
				'default'  => 'range',
			),
			array(
				'title'   => __( 'Active pricing tier color', 'tier-pricing-table' ),
				'id'      => Settings::SETTINGS_PREFIX . 'selected_quantity_color',
				'type'    => 'color',
				'css'     => 'width:6em;',
				'default' => '#96598A',
			),
			array(
				'title'    => __( 'Tooltip icon color', 'tier-pricing-table' ),
				'id'       => Settings::SETTINGS_PREFIX . 'tooltip_color',
				'type'     => 'color',
				'default'  => '#96598A',
				'css'      => 'width:6em;',
				'desc'     => __( 'Color of the icon.', 'tier-pricing-table' ),
				'desc_tip' => true,
			),
			array(
				'title'    => __( 'Tooltip icon size (px)', 'tier-pricing-table' ),
				'id'       => Settings::SETTINGS_PREFIX . 'tooltip_size',
				'type'     => 'number',
				'default'  => '15',
				'css'      => 'width:120px;',
				'desc'     => __( 'Size of the icon.', 'tier-pricing-table' ),
				'desc_tip' => true,
			),
			array(
				'title'   => __( 'Tooltip border', 'tier-pricing-table' ),
				'id'      => Settings::SETTINGS_PREFIX . 'tooltip_border',
				'type'    => TPTSwitchOption::FIELD_TYPE,
				'default' => 'yes',
			),
			array(
				'title'   => __( 'Unit label', 'tier-pricing-table' ),
				'id'      => Settings::SETTINGS_PREFIX . 'table_quantity_measurement',
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
				'id'      => Settings::SETTINGS_PREFIX . 'blocks_quantity_measurement',
				'type'    => TPTQuantityMeasurementField::FIELD_TYPE,
				'default' => array(
					'singular' => _n( 'piece', 'pieces', 1, 'tier-pricing-table' ),
					'plural'   => _n( 'piece', 'pieces', 2, 'tier-pricing-table' ),
				),
				'desc'    => __( 'For example: pieces, boxes, bottles, packs, etc. This will be shown next to quantity values. Leave blank to skip adding a unit label.',
					'tier-pricing-table' ),
			),
			array(
				'title'   => __( 'Columns titles', 'tier-pricing-table' ),
				'id'      => Settings::SETTINGS_PREFIX . 'table_columns_titles',
				'options' => array(
					array(
						'label'   => __( 'Quantity', 'tier-pricing-table' ),
						'id'      => Settings::SETTINGS_PREFIX . 'head_quantity_text',
						'default' => __( 'Quantity', 'tier-pricing-table' ),
					),
					array(
						'label'   => __( 'Discount', 'tier-pricing-table' ),
						'id'      => Settings::SETTINGS_PREFIX . 'head_discount_text',
						'default' => __( 'Discount (%)', 'tier-pricing-table' ),
					),
					array(
						'label'   => __( 'Price', 'tier-pricing-table' ),
						'id'      => Settings::SETTINGS_PREFIX . 'head_price_text',
						'default' => __( 'Price', 'tier-pricing-table' ),
					),
				),
				'desc'    => __( 'Leave a column title empty so as not to show that column.', 'tier-pricing-table' ),
				'type'    => TPTTableColumnsField::FIELD_TYPE,
			),
			array(
				'title'   => __( 'Show percentage discount', 'tier-pricing-table' ),
				'id'      => Settings::SETTINGS_PREFIX . 'show_discount_column',
				'type'    => TPTSwitchOption::FIELD_TYPE,
				'default' => 'yes',
				'desc'    => __( 'Show the percentage discount in pricing blocks that offer a discount.',
					'tier-pricing-table' ),
			),
			array(
				'title'   => __( 'Show regular product price', 'tier-pricing-table' ),
				'id'      => Settings::SETTINGS_PREFIX . 'options_show_original_product_price',
				'type'    => TPTSwitchOption::FIELD_TYPE,
				'default' => 'yes',
				'desc'    => __( 'Pricing options will show a crossed-out regular product price near the actual tier price.',
					'tier-pricing-table' ),
			),
			
			array(
				'title'             => __( 'Show totals in a selected option', 'tier-pricing-table' ),
				'id'                => Settings::SETTINGS_PREFIX . 'options_show_total',
				'type'              => TPTSwitchOption::FIELD_TYPE,
				'default'           => 'yes',
				'desc'              => __( 'The selected pricing option will include totals.', 'tier-pricing-table' ),
			),
			array(
				'title'        => __( 'Pricing option template', 'tier-pricing-table' ),
				'id'           => Settings::SETTINGS_PREFIX . 'options_option_text',
				'default'      => __( '<strong>Buy {tp_quantity} pieces and save {tp_rounded_discount}%</strong>',
					'tier-pricing-table' ),
				'placeholders' => array(
					'tp_quantity',
					'tp_discount',
					'tp_rounded_discount',
					'tp_base_unit_name',
				),
				'type'         => TPTTextTemplate::FIELD_TYPE,
				'desc'         => __( 'Use the variables above to build the template for the pricing option.',
					'tier-pricing-table' ),
			),
			array(
				'title'   => __( 'Show first tier pricing option', 'tier-pricing-table' ),
				'id'      => Settings::SETTINGS_PREFIX . 'options_show_default_option',
				'type'    => TPTSwitchOption::FIELD_TYPE,
				'default' => 'yes',
				'desc'    => __( 'Show the option with a regular product price. This is the first pricing tier where no discount is offered.',
					'tier-pricing-table' ),
			),
			array(
				'title'        => __( 'First tier pricing option template', 'tier-pricing-table' ),
				'id'           => Settings::SETTINGS_PREFIX . 'options_default_option_text',
				'default'      => __( '<strong>Buy {tp_quantity} pieces</strong>', 'tier-pricing-table' ),
				'placeholders' => array(
					'tp_quantity',
					'tp_base_unit_name',
				),
				'type'         => TPTTextTemplate::FIELD_TYPE,
				'desc'         => __( 'Set up the first pricing tier template where a discount is not offered.',
					'tier-pricing-table' ),
			),
			array(
				'title'        => __( 'Pricing string template', 'tier-pricing-table' ),
				'id'           => Settings::SETTINGS_PREFIX . 'plain_text_template',
				'default'      => __( '<strong>Buy {tp_quantity} pieces for {tp_price} each and save {tp_rounded_discount}%</strong>',
					'tier-pricing-table' ),
				'placeholders' => array(
					'tp_quantity',
					'tp_discount',
					'tp_price',
					'tp_rounded_discount',
					'tp_base_unit_name',
				),
				'type'         => TPTTextTemplate::FIELD_TYPE,
				'desc'         => __( 'Use the variables above to build the template for the pricing string.',
					'tier-pricing-table' ),
			),
			array(
				'title'   => __( 'Show first tier pricing string', 'tier-pricing-table' ),
				'id'      => Settings::SETTINGS_PREFIX . 'plain_text_show_first_tier',
				'type'    => TPTSwitchOption::FIELD_TYPE,
				'default' => 'yes',
				'desc'    => __( 'Show the tier with a regular product price. This is the first pricing tier where no discount is offered.',
					'tier-pricing-table' ),
			),
			
			array(
				'title'        => __( 'First tier pricing string template', 'tier-pricing-table' ),
				'id'           => Settings::SETTINGS_PREFIX . 'plain_text_first_tier_template',
				'default'      => __( '<strong>Buy {tp_quantity} pieces for {tp_price} each</strong>',
					'tier-pricing-table' ),
				'placeholders' => array(
					'tp_quantity',
					'tp_price',
					'tp_base_unit_name',
				),
				'type'         => TPTTextTemplate::FIELD_TYPE,
				'desc'         => __( 'Set up the first pricing tier template where a discount is not offered.',
					'tier-pricing-table' ),
			),
			array(
				'title'             => __( 'Clickable tiered pricing', 'tier-pricing-table' ),
				'id'                => Settings::SETTINGS_PREFIX . 'clickable_table_rows',
				'type'              => TPTSwitchOption::FIELD_TYPE,
				'default'           => 'yes',
				'desc'              => __( 'To select the quantity, tiered pricing becomes clickable (table rows, blocks, options).',
					'tier-pricing-table' ),
			),
		);
	}
}
