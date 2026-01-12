<?php namespace TierPricingTable\Addons\CustomColumns;

use TierPricingTable\Addons\AbstractAddon;
use TierPricingTable\Addons\CustomColumns\Form\ColumnsForm;

class CustomColumnsAddon extends AbstractAddon {

	/**
	 * Form
	 *
	 * @var ColumnsForm
	 */
	public $columnsForm;

	/**
	 * Columns Manager
	 *
	 * @var CustomColumnsManager
	 */
	public $columnsManager;

	public function getName(): string {
		return __( 'Custom table columns', 'tier-pricing-table' );
	}

	public function getDescription(): string {
		return __( 'This feature allows you to add custom columns to your pricing table.', 'tier-pricing-table' );
	}

	public function getSlug(): string {
		return 'custom-columns';
	}

	public function run() {
		$this->columnsForm    = new ColumnsForm( $this );
		$this->columnsManager = CustomColumnsManager::getInstance();
	}
}
