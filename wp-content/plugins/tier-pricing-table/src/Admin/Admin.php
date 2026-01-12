<?php namespace TierPricingTable\Admin;

use TierPricingTable\Admin\ProductPage\AdvanceOptionsForVariableProduct;
use TierPricingTable\Admin\ProductPage\Product;
use TierPricingTable\Admin\ProductPage\TieredPricingTab;
use TierPricingTable\Core\ServiceContainerTrait;
use TierPricingTable\TierPricingTablePlugin;
use TierPricingTable\Admin\Tips\TipsManager;

/**
 * Class Admin
 *
 * @package TierPricingTable\Admin
 */
class Admin {
	
	use ServiceContainerTrait;
	
	/**
	 * Array of Managers
	 *
	 * @var array
	 */
	private $managers;
	
	/**
	 * Admin constructor.
	 *
	 * Register menu items and handlers
	 *
	 */
	public function __construct() {
		new Product();
		new TieredPricingTab();
		new AdvanceOptionsForVariableProduct();
		new TipsManager();
		
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueueAssets' ], 10, 2 );
	}
	
	/**
	 * Register assets on product create/update page
	 */
	public function enqueueAssets() {
		wp_enqueue_script( 'tiered-pricing-table-admin-js',
			$this->getContainer()->getFileManager()->locateJSAsset( 'admin/main' ), [ 'jquery' ],
			TierPricingTablePlugin::VERSION );
		wp_enqueue_style( 'tiered-pricing-table-admin-css',
			$this->getContainer()->getFileManager()->locateAsset( 'admin/style.css' ), array(),
			TierPricingTablePlugin::VERSION );
	}
}
