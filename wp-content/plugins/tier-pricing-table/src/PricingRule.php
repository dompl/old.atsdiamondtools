<?php namespace TierPricingTable;

class PricingRule {
	
	protected $minimum = null;
	protected $rules = array();
	protected $type = 'fixed';
	protected $productId;
	
	public $provider = 'product';
	public $providerData = array();
	
	protected $modificationLog = array();
	
	public $data = array();
	
	public $customColumnsData = array();
	
	public $pricingData = array(
		'regular_price' => null,
		'sale_price'    => null,
		'discount'      => null,
		'pricing_type'  => null, // fixed or percentage
	);
	
	public function __construct( $productId ) {
		$this->productId = intval( $productId );
	}
	
	public function getProductId(): int {
		return $this->productId;
	}
	
	public function getMinimum( $forceValue = false ): ?int {
		
		if ( $forceValue ) {
			return $this->minimum ? $this->minimum : 1;
		}
		
		return $this->minimum;
	}
	
	public function setMinimum( ?int $minimum ) {
		$this->minimum = $minimum > 0 ? $minimum : null;
	}
	
	public function getRules(): array {
		return $this->rules;
	}
	
	public function setRules( array $rules ) {
		$this->rules = $rules;
	}
	
	public function getType(): string {
		return $this->type;
	}
	
	public function setType( string $type ) {
		$this->type = in_array( $type, array( 'fixed', 'percentage' ) ) ? $type : 'fixed';
	}
	
	public function isPercentage(): bool {
		return $this->getType() === 'percentage';
	}
	
	public function isFixed(): bool {
		return $this->getType() === 'fixed';
	}
	
	public function getTierPrice( $quantity, $withTaxes = true, $place = 'shop', $round = false ) {
		return PriceManager::getPriceByRules( $quantity, $this->getProductId(), 'view', $place, $withTaxes, $this,
			$round );
	}
	
	public function logPricingModification( string $modification ) {
		$this->modificationLog[] = $modification;
	}
	
	public function getPricingLog() {
		return $this->modificationLog;
	}
}
