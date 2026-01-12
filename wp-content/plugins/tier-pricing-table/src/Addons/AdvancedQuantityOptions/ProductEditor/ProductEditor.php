<?php namespace TierPricingTable\Addons\AdvancedQuantityOptions\ProductEditor;

use Automattic\WooCommerce\Admin\BlockTemplates\BlockTemplateInterface;
use Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplates\ProductFormTemplateInterface;
use TierPricingTable\Addons\ReactProductEditorAddon\ProductEditor as MainProductEditor;
use TierPricingTable\Addons\ReactProductEditorAddon\Sections\MainSection;

class ProductEditor {
	
	public function __construct() {
		
		add_filter( 'woocommerce_block_template_register', function ( BlockTemplateInterface $template ) {
			
			if ( $template instanceof ProductFormTemplateInterface ) {
				
				$group = $template->get_group_by_id( MainProductEditor::GROUP_ID );
				
				if ( ! $group ) {
					return;
				}
				
				$blockWrapper = $template->get_section_by_id( MainSection::ID )->add_block( [
					'id'         => 'tiered-pricing-table/advanced-quantity-options',
					'blockName'  => 'woocommerce/product-collapsible',
					'attributes' => [
						'toggleText'       => __( 'Advanced Quantity Options', 'tier-pricing-table' ),
						'initialCollapsed' => true,
						'persistRender'    => true,
					],
				] );
				
				$blocks = [
					new MaximumOrderQuantityBlock(),
					new QuantityStepBlock(),
				];
				
				foreach ( $blocks as $block ) {
					
					$section = $template->get_section_by_id( $block->getSectionId() );
					
					if ( ! $section ) {
						continue;
					}
					$blockWrapper->add_block( [
						'id'         => $block->getId(),
						'order'      => $block->getOrder(),
						'blockName'  => $block->getBlockName(),
						'attributes' => $block->getAttributes(),
					] );
				}
			}
		}, 10000 );
	}
}
