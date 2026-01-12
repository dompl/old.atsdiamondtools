<?php namespace TierPricingTable\Addons\AdvancedQuantityOptions;

class RoleBasedOptions {
	
	/**
	 * Form
	 *
	 * @var AdvancedQuantityOptionsForm
	 */
	protected $form;
	
	public function __construct( AdvancedQuantityOptionsForm $form ) {
		
		$this->form = $form;
		
		add_action( 'tiered_pricing_table/admin/role_based_rules/after_minimum_order_quantity_field',
			function ( $productId, $role, $loop = null ) {
				$this->form->render( $productId, $loop, $role );
			}, 10, 3 );
		
		add_filter( 'tiered_pricing_table/role_based_rules/rule_exists_meta', function ( array $meta, $role ) {
			$meta[] = DataProvider::getMetaKey( 'maximum', $role );
			$meta[] = DataProvider::getMetaKey( 'group_of', $role );
			
			return $meta;
		}, 10, 2 );
		
		add_action( 'tiered_pricing_table/role_based_rules/delete_role_rule', function ( $productId, $role ) {
			DataProvider::updateMaximumQuantity( $productId, null, $role );
			DataProvider::updateGroupOfQuantity( $productId, null, $role );
		}, 10, 2 );
		
		add_action( 'tiered_pricing_table/role_based_rules/save_role_based_rules',
			function ( $productId, $data, $role, $loop ) {
				DataProvider::updateFromRequest( 'maximum', $productId, $role, $loop );
				DataProvider::updateFromRequest( 'group_of', $productId, $role, $loop );
			}, 10, 4 );
		
		add_filter( 'tiered_pricing_table/advanced_quantity/get_maximum', function ( $maximum, $productId, $role ) {
			
			if ( ! is_user_logged_in() ) {
				return $maximum;
			}
			
			$user = wp_get_current_user();
			
			foreach ( $user->roles as $role ) {
				$value = DataProvider::getMaximumQuantity( $productId, $role, 'edit' );
				
				if ( $value ) {
					return $value;
				}
			}
			
			return $maximum;
		}, 10, 3 );
		
		add_filter( 'tiered_pricing_table/advanced_quantity/get_group_of', function ( $groupOf, $productId, $role ) {
			
			if ( ! is_user_logged_in() ) {
				return $groupOf;
			}
			
			$user = wp_get_current_user();
			
			foreach ( $user->roles as $role ) {
				$value = DataProvider::getGroupOfQuantity( $productId, $role, 'edit' );
				
				if ( $value ) {
					return $value;
				}
			}
			
			return $groupOf;
		}, 10, 3 );
	}
}
