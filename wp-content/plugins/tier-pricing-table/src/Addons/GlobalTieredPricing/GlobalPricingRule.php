<?php namespace TierPricingTable\Addons\GlobalTieredPricing;

use TierPricingTable\Addons\GlobalTieredPricing\PricingRule\RuleSettings;
use TierPricingTable\Forms\Form;
use Exception;
use WC_Product;
use WP_User;

class GlobalPricingRule {
	
	/**
	 * Rule ID
	 *
	 * @var int
	 */
	public $id;
	
	/**
	 * Is suspended
	 *
	 * @var bool
	 */
	public $isSuspended = false;
	
	/**
	 * Regular pricing type
	 *
	 * @var string
	 */
	public $pricingType;
	
	/**
	 * Regular price
	 *
	 * @var ?float
	 */
	public $regularPrice;
	
	/**
	 * Sale price
	 *
	 * @var ?float
	 */
	public $salePrice;
	
	/**
	 * Percentage Discount
	 *
	 * @var ?float
	 */
	public $discount;
	
	/**
	 * Percentage Discount
	 *
	 * @var string
	 */
	public $discountType = 'sale_price';
	
	/**
	 * Applying type
	 *
	 * @var string
	 */
	public $applyingType;
	
	/**
	 * Tiered Pricing type
	 *
	 * @var string
	 */
	public $tieredPricingType;
	
	/**
	 * Percentage Tiered Pricing Rules
	 *
	 * @var array
	 */
	public $percentageTieredPricingRules = array();
	
	/**
	 * Fixed Tiered Pricing Rules
	 *
	 * @var array
	 */
	public $fixedTieredPricingRules = array();
	
	/**
	 * Included categories
	 *
	 * @var array
	 */
	public $includedProductCategories = array();
	
	/**
	 * Excluded categories
	 *
	 * @var array
	 */
	public $excludedProductCategories = array();
	
	/**
	 * Included products
	 *
	 * @var array
	 */
	public $includedProducts = array();
	
	/**
	 * Excluded products
	 *
	 * @var array
	 */
	public $excludedProducts = array();
	
	/**
	 * Included product roles
	 *
	 * @var array
	 */
	public $includedUsersRole = array();
	
	/**
	 * Excluded product roles
	 *
	 * @var array
	 */
	public $excludedUsersRole = array();
	
	/**
	 * Included users
	 *
	 * @var array
	 */
	public $includedUsers = array();
	
	/**
	 * Excluded users
	 *
	 * @var array
	 */
	public $excludedUsers = array();
	
	/**
	 * Product minimum purchase quantity
	 *
	 * @var int
	 */
	public $minimum;
	
	public $priorityOptions;
	
	/**
	 * Array with custom data from 3rd-party addons
	 *
	 * @var array
	 */
	public $data = array();
	
	public function getId(): int {
		return $this->id;
	}
	
	public function setId( int $id ) {
		$this->id = $id;
	}
	
	public function getDiscount(): ?float {
		return $this->discount;
	}
	
	public function setDiscount( ?float $discount ) {
		$this->discount = $discount;
	}
	
	public function getDiscountType(): string {
		return $this->discountType;
	}
	
	public function setDiscountType( string $discountType ) {
		$this->discountType = in_array( $discountType,
			array( 'sale_price', 'regular_price' ) ) ? $discountType : 'sale_price';
	}
	
	public function getTieredPricingType(): string {
		return $this->tieredPricingType;
	}
	
	public function setTieredPricingType( string $tieredPricingType ) {
		
		$this->tieredPricingType = in_array( $tieredPricingType, array(
			'percentage',
			'fixed',
		) ) ? $tieredPricingType : 'fixed';
	}
	
	public function getPercentageTieredPricingRules(): array {
		return $this->percentageTieredPricingRules;
	}
	
	public function setPercentageTieredPricingRules( array $percentageTieredPricingRules ) {
		$this->percentageTieredPricingRules = $percentageTieredPricingRules;
	}
	
	public function getFixedTieredPricingRules(): array {
		return $this->fixedTieredPricingRules;
	}
	
	public function setFixedTieredPricingRules( array $fixedTieredPricingRules ) {
		$this->fixedTieredPricingRules = $fixedTieredPricingRules;
	}
	
	public function getApplyingType(): string {
		return $this->applyingType;
	}
	
	public function setApplyingType( string $applyingType ) {
		$this->applyingType = in_array( $applyingType, array( 'individual', 'cross' ) ) ? $applyingType : 'cross';
	}
	
	public function getPricingType(): string {
		return $this->pricingType;
	}
	
	public function setPricingType( string $priceType ) {
		$this->pricingType = in_array( $priceType, array( 'percentage', 'flat' ) ) ? $priceType : 'flat';
	}
	
	public function getTieredPricingRules(): array {
		
		if ( $this->getTieredPricingType() === 'percentage' ) {
			return $this->getPercentageTieredPricingRules();
		}
		
		return $this->getFixedTieredPricingRules();
	}
	
	public function getRegularPrice(): ?float {
		return $this->regularPrice;
	}
	
	public function setRegularPrice( ?float $regularPrice ) {
		$this->regularPrice = ! Form::isEmpty( $regularPrice ) ? floatval( $regularPrice ) : null;
		
	}
	
	public function getSalePrice(): ?float {
		return $this->salePrice;
	}
	
	public function setSalePrice( ?float $salePrice ) {
		$this->salePrice = $salePrice;
	}
	
	/**
	 * Create instance from array
	 *
	 * @param  array  $data
	 *
	 * @return self
	 */
	public static function fromArray( array $data ): self {
		
		$applyingType = $data['applying_type'] ?? 'individual';
		$applyingType = in_array( $applyingType, array( 'individual', 'cross' ) ) ? $applyingType : 'cross';
		
		$tieredPricingType = $data['tiered_pricing_type'] ?? 'fixed';
		$tieredPricingType = in_array( $tieredPricingType, array(
			'flat',
			'percentage',
		) ) ? $tieredPricingType : 'fixed';
		
		$percentageRules = isset( $data['percentage_rules'] ) ? (array) $data['percentage_rules'] : array();
		$fixedRules      = isset( $data['fixed_rules'] ) ? (array) $data['fixed_rules'] : array();
		
		$pricingType = $data['pricing_type'] ?? 'flat';
		$pricingType = in_array( $pricingType, array( 'flat', 'percentage' ) ) ? $pricingType : 'flat';
		
		$regularPrice = $data['regular_price'] ?? null;
		$salePrice    = $data['sale_price'] ?? null;
		$discount     = $data['discount'] ?? null;
		$discountType = $data['discount_type'] ?? 'sale_price';
		
		$minimum = $data['minimum'] ?? null;
		
		$self = new self();
		
		$self->setPricingType( (string) $pricingType );
		$self->setRegularPrice( Form::isEmpty( $regularPrice ) ? null : (float) $regularPrice );
		$self->setSalePrice( Form::isEmpty( $salePrice ) ? null : (float) $salePrice );
		$self->setDiscount( Form::isEmpty( $discount ) ? null : (float) $discount );
		$self->setDiscountType( (string) $discountType );
		
		$self->setApplyingType( $applyingType );
		$self->setTieredPricingType( $tieredPricingType );
		$self->setPercentageTieredPricingRules( $percentageRules );
		$self->setFixedTieredPricingRules( $fixedRules );
		
		$self->setMinimum( Form::isEmpty( $minimum ) ? null : (int) $minimum );
		
		return $self;
	}
	
	/**
	 * Validate
	 *
	 * @throws Exception
	 */
	public function validatePricing() {
		
		$valid = ! Form::isEmpty( $this->getRegularPrice() );
		$valid = $valid || ! Form::isEmpty( $this->getSalePrice() );
		$valid = $valid || ! empty( $this->getTieredPricingRules() );
		$valid = $valid || ! Form::isEmpty( $this->getMinimum() );
		$valid = $valid || ! Form::isEmpty( $this->getDiscount() );
		$valid = $valid || $this->getSettings()->getPriorityType() === 'flexible';
		
		$valid = apply_filters( 'tiered_pricing_table/global_pricing/validation', $valid, $this );
		
		if ( ! $valid ) {
			throw new Exception( esc_html__( 'The pricing rule does not affect either prices or product quantity. The rule will be skipped.',
				'tier-pricing-table' ) );
		}
	}
	
	public function isValidPricing(): bool {
		try {
			$this->validatePricing();
		} catch ( Exception $e ) {
			return false;
		}
		
		return true;
	}
	
	public function getMinimum(): ?int {
		return $this->minimum;
	}
	
	public function setMinimum( ?int $minimum ) {
		$this->minimum = intval( $minimum ) > 1 ? $minimum : null;
	}
	
	public function getIncludedProductCategories(): array {
		return $this->includedProductCategories;
	}
	
	public function getExcludedProductCategories(): array {
		return $this->excludedProductCategories;
	}
	
	public function setIncludedProductCategories( array $includedProductCategories ) {
		$this->includedProductCategories = $includedProductCategories;
	}
	
	public function setExcludedProductCategories( array $excludedProductCategories ) {
		$this->excludedProductCategories = $excludedProductCategories;
	}
	
	public function getIncludedProducts(): array {
		return $this->includedProducts;
	}
	
	public function getExcludedProducts(): array {
		return $this->excludedProducts;
	}
	
	public function setIncludedProducts( array $includedProducts ) {
		$this->includedProducts = $includedProducts;
	}
	
	public function setExcludedProducts( array $excludedProducts ) {
		$this->excludedProducts = $excludedProducts;
	}
	
	public function getIncludedUserRoles(): array {
		return $this->includedUsersRole;
	}
	
	public function getExcludedUserRoles(): array {
		return $this->excludedUsersRole;
	}
	
	public function setIncludedUsersRole( array $includedUsersRole ) {
		$this->includedUsersRole = $includedUsersRole;
	}
	
	public function setExcludedUsersRole( array $excludedUsersRole ) {
		$this->excludedUsersRole = $excludedUsersRole;
	}
	
	public function getIncludedUsers(): array {
		return $this->includedUsers;
	}
	
	public function getExcludedUsers(): array {
		return $this->excludedUsers;
	}
	
	public function setIncludedUsers( array $includedUsers ) {
		$this->includedUsers = $includedUsers;
	}
	
	public function setExcludedUsers( array $excludedUsers ) {
		$this->excludedUsers = $excludedUsers;
	}
	
	public function getSettings(): RuleSettings {
		if ( ! $this->priorityOptions ) {
			$this->priorityOptions = new RuleSettings( $this );
		}
		
		return $this->priorityOptions;
	}
	
	public function asArray(): array {
		return array(
			// Pricing
			'pricing_type'        => $this->getPricingType(),
			'regular_price'       => $this->getRegularPrice(),
			'sale_price'          => $this->getSalePrice(),
			'discount'            => $this->getDiscount(),
			'discount_type'       => $this->getDiscountType(),
			
			// Tiered Pricing
			'applying_type'       => $this->getApplyingType(),
			'tiered_pricing_type' => $this->getTieredPricingType(),
			'percentage_rules'    => $this->getPercentageTieredPricingRules(),
			'fixed_rules'         => $this->getFixedTieredPricingRules(),
			
			// MOQ
			'minimum'             => $this->getMinimum(),
			
			// Applying rules
			'included_categories' => $this->getIncludedProductCategories(),
			'included_products'   => $this->getIncludedProducts(),
			'included_users'      => $this->getIncludedUsers(),
			'included_users_role' => $this->getIncludedUserRoles(),
			
			'excluded_categories' => $this->getExcludedProductCategories(),
			'excluded_products'   => $this->getExcludedProducts(),
			'excluded_users'      => $this->getExcludedUsers(),
			'excluded_users_role' => $this->getExcludedUserRoles(),
			
			'rule_id'      => $this->getId(),
			'is_suspended' => $this->isSuspended(),
		);
	}
	
	public function save() {
		$dataToUpdate = array(
			// Pricing
			'_tpt_pricing_type'        => $this->getPricingType(),
			'_tpt_regular_price'       => $this->getRegularPrice(),
			'_tpt_sale_price'          => $this->getSalePrice(),
			'_tpt_discount'            => $this->getDiscount(),
			'_tpt_discount_type'       => $this->getDiscountType(),
			
			// Tiered Pricing
			'_tpt_applying_type'       => $this->getApplyingType(),
			'_tpt_tiered_pricing_type' => $this->getTieredPricingType(),
			'_tpt_percentage_rules'    => $this->getPercentageTieredPricingRules(),
			'_tpt_fixed_rules'         => $this->getFixedTieredPricingRules(),
			
			// MOQ
			'_tpt_minimum'             => $this->getMinimum(),
			
			// Applying rules
			'_tpt_included_categories' => $this->getIncludedProductCategories(),
			'_tpt_included_products'   => $this->getIncludedProducts(),
			'_tpt_included_users'      => $this->getIncludedUsers(),
			'_tpt_included_user_roles' => $this->getIncludedUserRoles(),
			
			'_tpt_excluded_categories' => $this->getExcludedProductCategories(),
			'_tpt_excluded_products'   => $this->getExcludedProducts(),
			'_tpt_excluded_users'      => $this->getExcludedUsers(),
			'_tpt_excluded_user_roles' => $this->getExcludedUserRoles(),
			
			'_tpt_is_suspended' => wc_bool_to_string( $this->isSuspended() ),
		);
		
		foreach ( $dataToUpdate as $key => $value ) {
			update_post_meta( $this->getId(), $key, $value );
		}
	}
	
	public static function build( $ruleId ): self {
		
		// Simple data to read
		$dataToRead = array(
			// Pricing
			'_tpt_pricing_type'        => 'pricing_type',
			'_tpt_sale_price'          => 'sale_price',
			'_tpt_regular_price'       => 'regular_price',
			'_tpt_discount'            => 'discount',
			'_tpt_discount_type'       => 'discount_type',
			
			// Tiered Pricing
			'_tpt_applying_type'       => 'applying_type',
			'_tpt_tiered_pricing_type' => 'tiered_pricing_type',
			
			// Moq
			'_tpt_minimum'             => 'minimum',
			
			'_tpt_is_suspended' => 'is_suspended',
		);
		
		$data = array();
		
		foreach ( $dataToRead as $key => $name ) {
			$data[ $name ] = get_post_meta( $ruleId, $key, true );
		}
		
		$priceRule     = self::fromArray( $data );
		$existingRoles = wp_roles()->roles;
		
		$includedCategoriesIds = array_filter( array_map( 'intval',
			(array) get_post_meta( $ruleId, '_tpt_included_categories', true ) ) );
		$includedProductsIds   = array_filter( array_map( 'intval',
			(array) get_post_meta( $ruleId, '_tpt_included_products', true ) ) );
		$includedUsersRole     = array_filter( (array) get_post_meta( $ruleId, '_tpt_included_user_roles', true ),
			function ( $role ) use ( $existingRoles ) {
				return array_key_exists( $role, $existingRoles );
			} );
		$includedUsers         = array_filter( array_map( 'intval',
			(array) get_post_meta( $ruleId, '_tpt_included_users', true ) ) );
		
		
		$excludedCategoriesIds = array_filter( array_map( 'intval',
			(array) get_post_meta( $ruleId, '_tpt_excluded_categories', true ) ) );
		$excludedProductsIds   = array_filter( array_map( 'intval',
			(array) get_post_meta( $ruleId, '_tpt_excluded_products', true ) ) );
		$excludedUsersRole     = array_filter( (array) get_post_meta( $ruleId, '_tpt_excluded_user_roles', true ),
			function ( $role ) use ( $existingRoles ) {
				return array_key_exists( $role, $existingRoles );
			} );
		$excludedUsers         = array_filter( array_map( 'intval',
			(array) get_post_meta( $ruleId, '_tpt_excluded_users', true ) ) );
		
		
		$isSuspended = get_post_meta( $ruleId, '_tpt_is_suspended', true ) === 'yes';
		
		$priceRule->setPercentageTieredPricingRules( self::readPricingRules( 'percentage', $ruleId ) );
		$priceRule->setFixedTieredPricingRules( self::readPricingRules( 'fixed', $ruleId ) );
		
		$priceRule->setIncludedProductCategories( $includedCategoriesIds );
		$priceRule->setIncludedUsers( $includedUsers );
		$priceRule->setIncludedUsersRole( $includedUsersRole );
		$priceRule->setIncludedProducts( $includedProductsIds );
		
		$priceRule->setExcludedProductCategories( $excludedCategoriesIds );
		$priceRule->setExcludedUsers( $excludedUsers );
		$priceRule->setExcludedUsersRole( $excludedUsersRole );
		$priceRule->setExcludedProducts( $excludedProductsIds );
		
		$priceRule->setIsSuspended( $isSuspended );
		
		$priceRule->setId( $ruleId );
		
		return apply_filters( 'tiered_pricing_table/global_pricing/after_built_rule', $priceRule );
	}
	
	protected static function readPricingRules( $type, $id ): array {
		
		$type = in_array( $type, array( 'percentage', 'fixed' ) ) ? $type : 'fixed';
		
		$rules = get_post_meta( $id, "_tpt_{$type}_rules", true );
		
		$rules = ! empty( $rules ) ? $rules : array();
		$rules = is_array( $rules ) ? array_filter( $rules ) : array();
		
		ksort( $rules );
		
		return $rules;
	}
	
	public function setIsSuspended( bool $isSuspended ) {
		$this->isSuspended = $isSuspended;
	}
	
	public function suspend() {
		$this->setIsSuspended( true );
	}
	
	public function reactivate() {
		$this->setIsSuspended( false );
	}
	
	public function isSuspended(): bool {
		return $this->isSuspended;
	}
	
	/**
	 * Wrapper for the main "match" function to provide the hook for 3rd party devs
	 *
	 * @param  WP_User  $user
	 * @param  WC_Product  $product
	 *
	 * @return bool
	 */
	public function matchRequirements( WP_User $user, WC_Product $product ): bool {
		$matched = $this->_matchRequirements( $user, $product );
		
		return apply_filters( 'tiered_pricing_table/global_pricing/match_requirements', $matched, $this, $user,
			$product );
	}
	
	protected function _matchRequirements( WP_User $user, WC_Product $product ): bool {
		
		$parentProduct = $product->is_type( array(
			'variation',
			'subscription-variation',
		) ) ? wc_get_product( $product->get_parent_id() ) : $product;
		
		/**
		 * 1. Check for product exclusion
		 *
		 * If product in exclusion - pricing rule does not match immediately
		 */
		if ( ! empty( $this->getExcludedProducts() ) ) {
			if ( in_array( $product->get_id(), $this->getExcludedProducts() ) || in_array( $parentProduct->get_id(),
					$this->getExcludedProducts() ) ) {
				return false;
			}
		}
		
		if ( ! empty( $this->getExcludedProductCategories() ) ) {
			if ( ! empty( array_intersect( $parentProduct->get_category_ids(),
				$this->getExcludedProductCategories() ) ) ) {
				return false;
			}
		}
		
		/**
		 * 2. Check for user exclusion
		 *
		 * If users in exclusion - pricing rule does not match immediately
		 */
		if ( in_array( $user->ID, $this->getExcludedUsers() ) ) {
			return false;
		}
		foreach ( $this->getExcludedUserRoles() as $role ) {
			if ( in_array( $role, $user->roles ) ) {
				return false;
			}
		}
		
		/**
		 * 3. Check for rule limitation for specific products
		 *
		 * If yes - match rule only for selected product/product categories
		 */
		
		$productMatched     = false;
		$productLimitations = false;
		
		if ( ! empty( $this->getIncludedProducts() ) ) {
			$productLimitations = true;
			
			if ( in_array( $product->get_id(), $this->getIncludedProducts() ) || in_array( $parentProduct->get_id(),
					$this->getIncludedProducts() ) ) {
				$productMatched = true;
			}
		}
		
		if ( ! empty( $this->getIncludedProductCategories() ) ) {
			$productLimitations = true;
			
			if ( ! empty( array_intersect( $parentProduct->get_category_ids(),
				$this->getIncludedProductCategories() ) ) ) {
				$productMatched = true;
			}
		}
		
		// There is product limitation and the product/category does not match the rule
		if ( $productLimitations && ! $productMatched ) {
			return false;
		}
		
		/**
		 * 4. If there are no user limits - match the rule immediately
		 */
		if ( empty( $this->getIncludedUserRoles() ) && empty( $this->getIncludedUsers() ) ) {
			return true;
		}
		
		/**
		 * 5. If there are user limits - check for user ID and user role.
		 */
		if ( in_array( $user->ID, $this->getIncludedUsers() ) ) {
			return true;
		}
		
		foreach ( $this->getIncludedUserRoles() as $role ) {
			if ( in_array( $role, $user->roles ) ) {
				return true;
			}
		}
		
		return false;
	}
}
