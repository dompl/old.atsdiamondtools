<?php namespace TierPricingTable\Addons\GlobalTieredPricing\CPT\Form\Tabs;

use TierPricingTable\Addons\GlobalTieredPricing\CPT\Form\FormTab;
use TierPricingTable\Addons\GlobalTieredPricing\Formatter;
use TierPricingTable\Addons\GlobalTieredPricing\GlobalPricingRule;

class UsersAndRoles extends FormTab {
	
	public function getId(): string {
		return 'user-and-roles';
	}
	
	public function getTitle(): string {
		return __( 'Users & Roles', 'tier-pricing-table' );
	}
	
	public function getDescription(): string {
		return __( 'Select users or user roles that the rule will apply to.', 'tier-pricing-table' );
	}
	
	public function render( GlobalPricingRule $pricingRule ) {
		
		$this->renderSectionTitle( __( 'Included Users', 'tier-pricing-table' ), array(
			'description' => __( 'Select users or user roles that the rule will apply to. The rule will work for all users with the selected roles.',
				'tier-pricing-table' ),
		) );
		
		if ( empty( $pricingRule->getIncludedUserRoles() ) && empty( $pricingRule->getIncludedUsers() ) ) {
			$this->renderHint( __( 'The rule will apply to all users if you do not specify user roles or specific customers (excluding those selected in the exclusions section).',
				'tier-pricing-table' ) );
		}
		
		$this->renderSelect2( array(
			'id'            => 'tpt_included_user_roles',
			'label'         => __( 'Include user roles', 'tier-pricing-table' ),
			'options'       => ( function () {
				$roles = [];
				foreach ( wp_roles()->roles as $key => $WPRole ) {
					$roles[ $key ] = $WPRole['name'];
				}
				
				return $roles;
			} )(),
			'value'         => $pricingRule->getIncludedUserRoles(),
			'placeholder'   => __( 'Select for a user role', 'tier-pricing-table' ),
			'search_action' => 'woocommerce_json_search_tpt_user_roles',
			'css_class'     => 'tpt-select-woo',
		) );
		
		$this->renderSelect2( array(
			'id'            => 'tpt_included_users',
			'label'         => __( 'Include specific customers', 'tier-pricing-table' ),
			'options'       => ( function () use ( $pricingRule ) {
				$users = [];
				foreach ( $pricingRule->getIncludedUsers() as $userId ) {
					$customer = new \WC_Customer( $userId );
					
					if ( $customer->get_id() ) {
						$users[ $userId ] = Formatter::formatCustomerString( $customer );
					}
				}
				
				return $users;
			} )(),
			'value'         => $pricingRule->getIncludedUsers(),
			'placeholder'   => __( 'Select for a customer', 'tier-pricing-table' ),
			'search_action' => 'woocommerce_json_search_tpt_customers',
			'css_class'     => 'rbp-select-woo wc-product-search',
		) );
		
		$this->renderSectionTitle( __( 'Exclusions', 'tier-pricing-table' ), array(
			'description' => __( 'Select users or user roles the rule will not work for.', 'tier-pricing-table' ),
		) );
		
		$this->renderSelect2( array(
			'id'            => 'tpt_excluded_user_roles',
			'label'         => __( 'Exclude user roles', 'tier-pricing-table' ),
			'options'       => ( function () {
				$roles = [];
				foreach ( wp_roles()->roles as $key => $WPRole ) {
					$roles[ $key ] = $WPRole['name'];
				}
				
				return $roles;
			} )(),
			'value'         => $pricingRule->getExcludedUserRoles(),
			'placeholder'   => __( 'Select for a user role', 'tier-pricing-table' ),
			'search_action' => 'woocommerce_json_search_tpt_user_roles',
			'css_class'     => 'tpt-select-woo',
		) );
		
		$this->renderSelect2( array(
			'id'            => 'tpt_excluded_users',
			'label'         => __( 'Exclude specific customers', 'tier-pricing-table' ),
			'options'       => ( function () use ( $pricingRule ) {
				$users = [];
				foreach ( $pricingRule->getExcludedUsers() as $userId ) {
					$customer = new \WC_Customer( $userId );
					
					if ( $customer->get_id() ) {
						$users[ $userId ] = Formatter::formatCustomerString( $customer );
					}
				}
				
				return $users;
			} )(),
			'value'         => $pricingRule->getExcludedUsers(),
			'placeholder'   => __( 'Select for a customer', 'tier-pricing-table' ),
			'search_action' => 'woocommerce_json_search_tpt_customers',
			'css_class'     => 'rbp-select-woo wc-product-search',
		) );
	}
	
	public function getIcon(): string {
		return 'dashicons-admin-users';
	}
}
