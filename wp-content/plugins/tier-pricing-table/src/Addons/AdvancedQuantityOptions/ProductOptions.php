<?php namespace TierPricingTable\Addons\AdvancedQuantityOptions;

class ProductOptions {

	/**
	 * Form
	 *
	 * @var AdvancedQuantityOptionsForm
	 */
	protected $form;
	
	public function __construct( AdvancedQuantityOptionsForm $form ) {
		$this->form = $form;

		// Rendering
		add_action( 'tiered_pricing_table/admin/after_minimum_order_quantity_field', function ( $productId, $loop ) {
			$this->form->render( $productId, $loop );
		}, 10, 2 );

		add_action( 'tiered_pricing_table/admin/after_variation_minimum_order_quantity_field',
			array( $this->form, 'render' ), 10, 2 );
		
		// Saving
		add_action( 'woocommerce_process_product_meta', array( $this, 'save' ) );
		add_action( 'woocommerce_save_product_variation', array( $this, 'save' ), 10, 2 );
	}

	public function save( $productId, $loop = null ) {
		DataProvider::updateFromRequest( 'maximum', $productId, null, $loop );
		DataProvider::updateFromRequest( 'group_of', $productId, null, $loop );
	}
}
