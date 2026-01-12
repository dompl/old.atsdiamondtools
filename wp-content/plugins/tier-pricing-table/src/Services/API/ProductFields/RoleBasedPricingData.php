<?php namespace TierPricingTable\Services\API\ProductFields;

use TierPricingTable\Addons\RoleBasedPricing\RoleBasedPriceManager;
use TierPricingTable\Addons\RoleBasedPricing\RoleBasedPricingRule;
use WC_Product;

class RoleBasedPricingData extends ProductField {
	
	public function getFieldSlug(): string {
		return 'tiered_pricing_roles_data';
	}
	
	public function sanitizeValue( $value, $productId ): array {
		
		$value          = is_array( $value ) ? $value : array();
		$sanitizedValue = array();
		
		wp_roles()->roles;
		
		foreach ( $value as $role => $roleData ) {
			
			if ( array_key_exists( $role, wp_roles()->roles ) ) {
				
				$roleBasedRule = RoleBasedPricingRule::build( $productId, $role );
				
				$sanitizedValue[ $role ] = wp_parse_args( $roleData, $roleBasedRule->asArray() );
			}
		}
		
		return $sanitizedValue;
	}
	
	public function getValue( array $product ): array {
		$data = array();
		
		foreach ( wp_roles()->roles as $WPRole => $role_data ) {
			
			if ( RoleBasedPriceManager::roleHasRules( $WPRole, $product['id'], 'edit' ) ) {
				
				$roleBasedRule = RoleBasedPricingRule::build( $product['id'], $WPRole );
				
				$data[ $WPRole ] = $roleBasedRule->asArray();
			}
		}
		
		return $data;
	}
	
	public function updateValue( $value, WC_Product $product ) {
		RoleBasedPriceManager::deleteAllRoleDataForProduct( $product->get_id() );
		
		$value = $this->sanitizeValue( $value, $product->get_id() );
		
		foreach ( $value as $role => $roleData ) {
			$rule = RoleBasedPricingRule::buildFromArray( $product->get_id(), $role, $roleData );
			
			try {
				$rule->save();
			} catch ( \Exception $e ) {
				continue;
			}
		}
	}
	
	public function getType(): string {
		return 'object';
	}
	
	public function getDescription(): string {
		return 'Roles pricing data. See RoleBasedPricingRule class to get more information about the structure of the data.';
	}
}
