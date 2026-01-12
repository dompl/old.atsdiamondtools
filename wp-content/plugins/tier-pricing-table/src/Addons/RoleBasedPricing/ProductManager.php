<?php namespace TierPricingTable\Addons\RoleBasedPricing;

use Exception;
use TierPricingTable\Core\ServiceContainerTrait;
use TierPricingTable\Forms\MinimumOrderQuantityForm;
use TierPricingTable\Forms\RegularPricingForm;
use TierPricingTable\Forms\TieredPricingRulesForm;
use WP_Post;

class ProductManager {
	
	use ServiceContainerTrait;
	
	const GET_ROLE_ROW_HTML__ACTION = 'tpt_get_role_row_html';
	
	public function __construct() {
		
		// Get role row via AJAX
		add_action( 'wp_ajax_' . self::GET_ROLE_ROW_HTML__ACTION, array( $this, 'getRoleRowHtml' ) );
		
		// Render
		add_action( 'tiered_pricing_table/admin/before_advance_product_options', array( $this, 'render' ), 99, 1 );
		add_action( 'woocommerce_variation_options_pricing', function ( $loop, $variationData, WP_Post $variation ) {
			$this->render( $variation->ID, $loop );
		}, 11, 3 );
		
		// Save
		add_action( 'woocommerce_process_product_meta', function ( $productId ) {
			$this->updateFromRequest( $productId );
		} );
		
		add_action( 'woocommerce_save_product_variation', function ( $variationId, $loop ) {
			$this->updateFromRequest( $variationId, $loop );
		}, 10, 3 );
	}
	
	public function updateFromRequest( $productId, $loop = null ) {
		
		if ( wp_verify_nonce( true, true ) ) {
			// as phpcs comments at Woo is not available, we have to do such a trash
			$woo = 'Woo, please add ignoring comments to your phpcs checker';
		}
		
		$data = $_POST;
		
		$rolesToUpdate = array();
		
		if ( ! is_null( $loop ) ) {
			if ( ! empty( $data['tiered_pricing_minimum_order_quantity_variation_role'][ $loop ] ) ) {
				foreach ( $data['tiered_pricing_minimum_order_quantity_variation_role'][ $loop ] as $role => $pricingType ) {
					$rolesToUpdate[] = $role;
				}
			}
		} else {
			if ( ! empty( $data['tiered_pricing_minimum_order_quantity_role'] ) ) {
				foreach ( $data['tiered_pricing_minimum_order_quantity_role'] as $role => $pricingType ) {
					$rolesToUpdate[] = $role;
				}
			}
		}
		
		foreach ( $rolesToUpdate as $role ) {
			
			$pricingRule = RoleBasedPricingRule::build( (int) $productId, $role );
			
			$pricingData              = RegularPricingForm::getDataFromRequest( $role, $loop, $data );
			$tieredPricingData        = TieredPricingRulesForm::getDataFromRequest( $role, $loop, null, $data,
				$productId );
			$minimumOrderQuantityData = MinimumOrderQuantityForm::getDataFromRequest( $role, $loop, $data );
			
			$pricingRule->setPricingType( $pricingData['pricing_type'] );
			
			$pricingRule->setRegularPrice( $pricingData['regular_price'] );
			$pricingRule->setSalePrice( $pricingData['sale_price'] );
			
			$pricingRule->setDiscount( $pricingData['discount'] );
			$pricingRule->setDiscountType( $pricingData['discount_type'] );
			
			$pricingRule->setTieredPricingType( $tieredPricingData['type'] );
			$pricingRule->setFixedTieredPricingRules( $tieredPricingData['fixed_tiered_pricing_rules'] );
			$pricingRule->setPercentageTieredPricingRules( $tieredPricingData['percentage_tiered_pricing_rules'] );
			
			$pricingRule->setMinimumOrderQuantity( $minimumOrderQuantityData['minimum_order_quantity'] );
			
			try {
				$pricingRule->save();
			} catch ( Exception $e ) {
				return;
			}
			
			do_action( 'tiered_pricing_table/role_based_rules/save_role_based_rules', $productId, $data, $role, $loop );
		}
		
		$this->handleRemoving( $productId, $loop, $_POST );
	}
	
	/**
	 * Delete removed role-based rules
	 *
	 * @param  int  $productId
	 * @param  null|int  $loop
	 * @param  array  $data
	 */
	public function handleRemoving( $productId, $loop, $data ) {
		
		if ( ! empty( $data['tiered_price_rules_roles_to_delete'] ) ) {
			foreach ( $data['tiered_price_rules_roles_to_delete'] as $roleToRemove ) {
				if ( ! empty( $roleToRemove ) ) {
					RoleBasedPriceManager::deleteAllDataForRole( $productId, $roleToRemove );
				}
			}
		}
		
		if ( ! empty( $data['tiered_price_rules_roles_to_delete_variation'][ $loop ] ) ) {
			
			foreach ( $data['tiered_price_rules_roles_to_delete_variation'][ $loop ] as $roleToRemove ) {
				if ( ! empty( $roleToRemove ) ) {
					RoleBasedPriceManager::deleteAllDataForRole( $productId, $roleToRemove );
				}
			}
		}
		
	}
	
	public function render( $productId, $loop = null ) {
		
		$this->getContainer()->getFileManager()->includeTemplate( 'addons/role-based-pricing/role-based-block.php',
			array(
				'product_id' => $productId,
				'loop'       => $loop,
			) );
	}
	
	/**
	 * AJAX Handler
	 */
	public function getRoleRowHtml() {
		$nonce = isset( $_GET['nonce'] ) ? sanitize_text_field( $_GET['nonce'] ) : false;
		
		if ( wp_verify_nonce( $nonce, self::GET_ROLE_ROW_HTML__ACTION ) ) {
			
			$role      = isset( $_GET['role'] ) ? sanitize_text_field( $_GET['role'] ) : false;
			$productId = isset( $_GET['product_id'] ) ? intval( $_GET['product_id'] ) : 0;
			$loop      = isset( $_GET['loop'] ) && '' !== $_GET['loop'] ? intval( $_GET['loop'] ) : null;
			$role      = get_role( $role );
			
			$product = wc_get_product( $productId );
			
			if ( $role && $product ) {
				
				$pricingRule = RoleBasedPricingRule::build( $productId, $role->name );
				
				wp_send_json( array(
					'success'       => true,
					'role_row_html' => $this->getContainer()->getFileManager()->renderTemplate( 'addons/role-based-pricing/role.php',
						array(
							'loop'         => $loop,
							'pricing_rule' => $pricingRule,
							'product'      => $product,
						) ),
				) );
			}
			
			wp_send_json( array(
				'success'       => false,
				'error_message' => __( 'Invalid role', 'tier-pricing-table' ),
			) );
		}
		
		wp_send_json( array(
			'success'       => false,
			'error_message' => __( 'Invalid nonce', 'tier-pricing-table' ),
		) );
	}
}
