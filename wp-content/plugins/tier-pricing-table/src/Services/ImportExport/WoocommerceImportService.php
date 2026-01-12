<?php namespace TierPricingTable\Services\ImportExport;

use TierPricingTable\TierPricingTablePlugin;
use WC_Product;

class WoocommerceImportService {
	
	/**
	 * Import constructor.
	 */
	public function __construct() {
		add_filter( 'woocommerce_csv_product_import_mapping_options', array( $this, 'addColumnsToImporter' ) );
		add_filter( 'woocommerce_csv_product_import_mapping_default_columns',
			array( $this, 'addColumnToMappingScreen' ) );
		add_filter( 'woocommerce_product_import_pre_insert_product_object', array( $this, 'processImport' ), 10, 2 );
	}
	
	/**
	 * Register the 'Tiered pricing' column in the importer.
	 *
	 * @param  array  $options
	 *
	 * @return array $options
	 */
	public function addColumnsToImporter( $options ) {
		
		$options['tiered_price_type']       = __( 'Tiered pricing type', 'tier-pricing-table' );
		$options['tiered_price_minimum']    = __( 'Tiered pricing minimum product quantity', 'tier-pricing-table' );
		$options['tiered_price_fixed']      = __( 'Fixed Tiered prices', 'tier-pricing-table' );
		$options['tiered_price_percentage'] = __( 'Percentage Tiered Prices', 'tier-pricing-table' );
		
		global $wp_roles;
		
		foreach ( wp_roles()->roles as $WPRole => $role_data ) {
			$roleName = isset( $wp_roles->role_names[ $WPRole ] ) ? translate_user_role( $wp_roles->role_names[ $WPRole ] ) : $WPRole;
			
			$options[ 'tpt_' . $WPRole ] = array(
				'name'    => $roleName,
				'options' => array(
					$WPRole . '_tiered_price_type'       => $roleName . ': ' . __( 'Tiered pricing type',
							'tier-pricing-table' ),
					$WPRole . '_tiered_price_minimum'    => $roleName . ': ' . __( 'Tiered pricing minimum product quantity',
							'tier-pricing-table' ),
					$WPRole . '_tiered_price_fixed'      => $roleName . ': ' . __( 'Fixed Tiered prices',
							'tier-pricing-table' ),
					$WPRole . '_tiered_price_percentage' => $roleName . ': ' . __( 'Percentage Tiered Prices',
							'tier-pricing-table' ),
				),
			);
		}
		
		return $options;
	}
	
	
	/**
	 * Add automatic mapping support for 'Tiered pricing'.
	 *
	 * @param  array  $columns
	 *
	 * @return array $columns
	 */
	public function addColumnToMappingScreen( $columns ) {
		
		$columns[ __( 'Fixed tiered prices', 'tier-pricing-table' ) ]                     = 'tiered_price_fixed';
		$columns[ __( 'Percentage tiered prices', 'tier-pricing-table' ) ]                = 'tiered_price_percentage';
		$columns[ __( 'Tiered pricing type', 'tier-pricing-table' ) ]                     = 'tiered_price_type';
		$columns[ __( 'Tiered pricing minimum product quantity', 'tier-pricing-table' ) ] = 'tiered_price_minimum';
		
		global $wp_roles;
		
		foreach ( wp_roles()->roles as $WPRole => $role_data ) {
			$roleName = isset( $wp_roles->role_names[ $WPRole ] ) ? translate_user_role( $wp_roles->role_names[ $WPRole ] ) : $WPRole;
			
			$columns[ $roleName . ' ' . __( 'Fixed tiered prices',
				'tier-pricing-table' ) ]                                                                        = $WPRole . '_tiered_price_fixed';
			$columns[ $roleName . ' ' . __( 'Percentage tiered prices',
				'tier-pricing-table' ) ]                                                                        = $WPRole . '_tiered_price_percentage';
			$columns[ $roleName . ' ' . __( 'Tiered pricing type',
				'tier-pricing-table' ) ]                                                                        = $WPRole . '_tiered_price_type';
			$columns[ $roleName . ' ' . __( 'Tiered pricing minimum product quantity',
				'tier-pricing-table' ) ]                                                                        = $WPRole . '_tiered_price_minimum';
		}
		
		return $columns;
	}
	
	/**
	 * Process the data read from the CSV file.
	 *
	 * @param  WC_Product  $product  - Product being imported or updated.
	 * @param  array  $data  - CSV data read for the product.
	 *
	 * @return WC_Product $object
	 */
	public function processImport( $product, $data ) {
		
		if ( isset( $data['tiered_price_fixed'] ) ) {
			
			$fixed = $this->decodeExport( $data['tiered_price_fixed'] );
			
			$product->update_meta_data( '_fixed_price_rules', $fixed );
		}
		
		if ( isset( $data['tiered_price_percentage'] ) ) {
			
			$percentage = $this->decodeExport( $data['tiered_price_percentage'] );
			
			$product->update_meta_data( '_percentage_price_rules', $percentage );
		}
		
		if ( isset( $data['tiered_price_type'] ) ) {
			
			if ( in_array( $data['tiered_price_type'], array( 'fixed', 'percentage' ) ) ) {
				$product->update_meta_data( '_tiered_price_rules_type', $data['tiered_price_type'] );
			}
		}
		
		if ( isset( $data['tiered_price_minimum'] ) ) {
			
			$minimum = (int) $data['tiered_price_minimum'];
			
			$product->update_meta_data( '_tiered_price_minimum_qty', $minimum );
		}
		
		foreach ( wp_roles()->roles as $WPRole => $role_data ) {
			if ( isset( $data[ $WPRole . '_tiered_price_fixed' ] ) ) {
				
				$fixed = $this->decodeExport( $data[ $WPRole . '_tiered_price_fixed' ] );
				
				$product->update_meta_data( '_' . $WPRole . '_fixed_price_rules', $fixed );
			}
			
			if ( isset( $data[ $WPRole . '_tiered_price_percentage' ] ) ) {
				
				$percentage = $this->decodeExport( $data[ $WPRole . '_tiered_price_percentage' ] );
				
				$product->update_meta_data( '_' . $WPRole . '_percentage_price_rules', $percentage );
			}
			
			if ( isset( $data[ $WPRole . '_tiered_price_type' ] ) ) {
				
				if ( in_array( $data[ $WPRole . '_tiered_price_type' ], array( 'fixed', 'percentage' ) ) ) {
					$product->update_meta_data( '_' . $WPRole . '_tiered_price_rules_type',
						$data[ $WPRole . '_tiered_price_type' ] );
				}
			}
			
			if ( isset( $data[ $WPRole . '_tiered_price_minimum' ] ) ) {
				
				$minimum = (int) $data[ $WPRole . '_tiered_price_minimum' ];
				
				$product->update_meta_data( '_' . $WPRole . '_tiered_price_minimum_qty', $minimum );
			}
		}
		
		return $product;
	}
	
	/**
	 * Decode export file format to array
	 *
	 * @param  string  $data
	 *
	 * @return array
	 */
	protected function decodeExport( $data ) {
		$rules = explode( TierPricingTablePlugin::getRulesSeparator(), $data );
		
		$data = array();
		
		if ( $rules ) {
			foreach ( $rules as $rule ) {
				$rule = explode( ':', $rule );
				
				if ( isset( $rule[0] ) && isset( $rule[1] ) ) {
					$data[ intval( $rule[0] ) ] = $rule[1];
				}
			}
			
		}
		
		$data = array_filter( $data );
		
		$data = array_filter( $data, function ( $itemKey ) {
			return is_numeric( $itemKey ) && $itemKey > 1;
		}, ARRAY_FILTER_USE_KEY );
		
		return ! empty( $data ) ? $data : array();
	}
}
