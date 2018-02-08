<?php
/*
Plugin Name: WooCommerce Shipping Coupons
Plugin URI: http://ignitewoo.com
Description: Allows the creation of shipping coupons to for fixed discount or percentage discounts
Version: 2.3.3
Author: IgniteWoo.com
Author URI: http://ignitewoo.com
*/

/*
	Copyright (c) 2013, 2014 - IgniteWoo.com - All Rights Reserved
*/

class IGN_Shipping_Coupons { 

	private static $instance = null;
	
	private static $validation_checked = false;
	
	private function __construct() { 
	
		add_action( 'init', array( 'IGN_Shipping_Coupons', 'load_plugin_textdomain' ) );
		//add_action( 'init', array( 'IGN_Shipping_Coupons', 'bust_cache' ) );
	
		add_action( 'admin_head', array( 'IGN_Shipping_Coupons', 'admin_head' ) );
		
		add_action( 'save_post', array( 'IGN_Shipping_Coupons', 'save_post' ), 1, 1 );
	
		add_filter( 'woocommerce_coupon_discount_types', array( 'IGN_Shipping_Coupons', 'add_coupon_code_type' ) );
		
		add_filter( 'woocommerce_coupon_is_valid', array( 'IGN_Shipping_Coupons', 'is_code_valid' ), 10, 2 );
		
		//add_action( 'woocommerce_coupon_options', array( 'IGN_Shipping_Coupons', 'woocommerce_coupon_options' ), 1, 1 );
		
		add_action( 'plugin_row_meta', array( 'IGN_Shipping_Coupons', 'plugin_meta_links' ), 10, 2 );
		
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( 'IGN_Shipping_Coupons', 'plugin_action_links' ) );
		
		add_action( 'woocommerce_shipping_init', array( 'IGN_Shipping_Coupons', 'init_flat_fee_shipping' ) );
		
		add_filter( 'woocommerce_package_rates', array( 'IGN_Shipping_Coupons', 'adjust_shipping_costs' ), 10, 2 );

		add_filter( 'woocommerce_coupon_discount_amount_html', array( 'IGN_Shipping_Coupons', 'coupon_html' ), 99999, 3 );
	}

	
	public static function load_plugin_textdomain() {

		$locale = apply_filters( 'plugin_locale', get_locale(), 'ign_shipping_coupons' );

		load_textdomain( 'ign_shipping_coupons', WP_LANG_DIR.'/woocommerce/ign_shipping_coupons-'.$locale.'.mo' );

		$plugin_rel_path = apply_filters( 'ignitewoo_translation_file_rel_path', dirname( plugin_basename( __FILE__ ) ) . '/languages' );

		load_plugin_textdomain( 'ign_shipping_coupons', false, $plugin_rel_path );

	}

	/*
	public static function bust_cache() { 
		global $wpdb;
		$sql = 'delete from ' . $wpdb->options . ' where option_name like "_transient_timeout_wc_ship%" or option_name like "_transient_wc_ship%" ';
		$wpdb->query( $sql );
	}
	*/

	public static function init_flat_fee_shipping() {

		require_once( dirname( __FILE__ ) . '/wc-flat-fee-shipping.php' );
	
		add_filter( 'woocommerce_shipping_methods', array( 'IGN_Shipping_Coupons', 'add_flat_fee_rate_method' ), 10 );

	}


	public static function add_flat_fee_rate_method( $methods ) {

		if ( ! class_exists( 'IGN_Shipping_Flat_Fee' ) )
			require_once( dirname( __FILE__ ) . '/wc-flat-fee-shipping.php' );
			
		$methods[] = 'IGN_Shipping_Flat_Fee';

		return $methods;
	}


	public static function get_instance() {
	
		if ( null == self::$instance )
			self::$instance = new self;

		return self::$instance;
	}
	
	
	public static function admin_head() { 
		global $typenow;

		if ( 'shop_coupon' !== $typenow )
			return;
			
		if ( false == strpos( $_SERVER['REQUEST_URI'], 'post-new.php' ) && ( empty( $_GET['action'] ) || 'edit' !== $_GET['action'] ) )
			return;
			
		?>
		<script>
		jQuery( document ).ready( function( $ ) {

			function ign_handle_fields( cdata ) { 
			
				var ctype = cdata.val();
				
				if ( 'ign_shipping_amount' !== ctype && 'ign_shipping_percent' !== ctype && 'ign_shipping_flat_fee' !== ctype ) {
				
					$( '.free_shipping_field, .apply_before_tax_field, .exclude_sale_items_field' ).css( 'display', 'block' );
					
					$( '#usage_restriction_coupon_data .options_group:eq(1), #usage_restriction_coupon_data .options_group:eq(2)' ).css( 'display', 'block' );
					//$( '.shipping_methods' ).css( 'display', 'none' );
				
					return;
				
				} 

				$( '.free_shipping_field, .apply_before_tax_field, .exclude_sale_items_field' ).css( 'display', 'none' );
				
				$( '#usage_restriction_coupon_data .options_group:eq(1), #usage_restriction_coupon_data .options_group:eq(2)' ).css( 'display', 'none' );
				//$( '.shipping_methods' ).css( 'display', 'block' );
			
			}
			
			$( '#discount_type' ).on( 'change', function() { 
			
				ign_handle_fields( $( this ) );
				
			})

			$( '#discount_type' ).trigger( 'change' );
			
			//$( '#shipping_methods' ).chosen();
			
		});
		</script>
		<?php
	
	}
	
	
	public static function save_post( $post = null ) { 
		global $typenow; 
		
		if ( 'shop_coupon' !== $typenow )
			return $post;
			
		if ( empty( $_POST['discount_type'] ) )
			return $post;
			
		if ( 'ign_shipping_amount' !== $_POST['discount_type'] && 'ign_shipping_percent' !== $_POST['discount_type'] && 'ign_shipping_flat_fee' !== $_POST['discount_type'] )
			return $post;
		
		unset( $_POST['free_shipping'] );
		unset( $_POST['apply_before_tax'] );
		unset( $_POST['exclude_sale_items'] );
		$_POST['product_ids'] = array();
		$_POST['exclude_product_ids'] = array();
		$_POST['product_categories'] = array();
		$_POST['exclude_product_categories'] = array();
		
		//update_post_meta( $post, '_ign_shipping_methods', $_POST['shipping_methods'] );

	
	}

	
	public static function add_coupon_code_type( $types ) {

		$types['ign_shipping_amount'] = __( 'Shipping Discount', 'ign_shipping_coupons' );
		
		$types['ign_shipping_percent'] = __( 'Shipping Discount %', 'ign_shipping_coupons' );
		
		$types['ign_shipping_flat_fee'] = __( 'Shipping Flat Fee', 'ign_shipping_coupons' );

		return $types;
	}
	
	
	public static function is_code_valid( $valid, $coupon ) {
		global $woocommerce;

		if ( !$valid ) 
			return $valid;

		if ( self::$validation_checked )
			return $valid;
		else 
			self::$validation_checked = true;

		if ( 'ign_shipping_amount' != $coupon->type && 'ign_shipping_percent' != $coupon->type && 'ign_shipping_flat_fee' != $coupon->type )
			return $valid; 
			
		if ( 'ign_shipping_flat_fee' == $coupon->type ) { 

			$opts = get_option( 'woocommerce_flat_fee_shipping_settings' );

			if ( empty( $opts ) || 'yes' !== $opts['enabled'] )
				return false;
			
			// In 'both' mode check the min order total
			if ( 'both' == $opts['requires'] ) { 

				/*
				if ( WC()->cart->prices_include_tax )
					$total = WC()->cart->cart_contents_total + array_sum( WC()->cart->taxes );
				else
					$total = WC()->cart->cart_contents_total;
				*/

				if ( WC()->cart->cart_contents_total >= floatval( $opts['min_amount'] ) )
					$valid = true;
				else {

					$err = apply_filters( 'ign_flat_rate_shipping_error_msg', sprintf( __( 'The coupon "%s" requires a minimum cart subtotal of %s', 'ign_shipping_coupons' ), $coupon->code, strip_tags( wc_price( $opts['min_amount'] ) ) ), $coupon->code, $opts['min_amount'] );
					
					if ( !wc_has_notice( $err, 'error' ) )
						wc_add_notice( $err, 'error' );
					
					return false;
				}
			
			}

		} else if ( floatval( $coupon->amount ) <= 0 ) { 
		
			return false;
			
		}

		add_filter( 'woocommerce_package_rates', array( 'IGN_Shipping_Coupons', 'adjust_shipping_costs' ), 10, 2 );

		return $valid;
		
	}

	public static function coupon_html( $discount_amount_html, $coupon ) { 
	
		$coupons = WC()->cart->applied_coupons;
	
		$coupon = new WC_Coupon( $coupon );
		
		$type = $coupon->get_discount_type();
		
		if ( !in_array( $type, array( 'flat_fee_shipping', 'ign_shipping_amount', 'ign_shipping_percent' ) ) )
			return $coupon_html;
				
		$html = WC()->session->get( 'coupon_html' );
		
		if ( !empty( $html ) ) 
			return $html;
		else 
			return $coupon_html;
	}
	
	public static function adjust_shipping_costs ( $rates, $package ) {

		if ( empty( $rates ) )
			return $rates;
			
		$coupons = WC()->cart->applied_coupons;

		if ( empty( $coupons ) )
			return $rates;

		$country        = WC()->customer->get_shipping_country();
		$state          = WC()->customer->get_shipping_state();
		$postcode       = WC()->customer->get_shipping_postcode();
		$city           = WC()->customer->get_shipping_city();

		$matched_tax_rates = array();
		
		$tax_rates = false;
		
		if ( version_compare( WOOCOMMERCE_VERSION, '2.2', '<' ) && 'yes' == get_option( 'woocommerce_calc_taxes' ) ) {
		
			$wc_tax = new WC_Tax();

			$tax_rates = $wc_tax->find_rates( array(
				'country'   => $country,
				'state'     => $state,
				'postcode'  => $postcode,
				'city'      => $city,
				'tax_class' => ''
			) );

		} else if ( 'yes' == get_option( 'woocommerce_calc_taxes' ) ) { 
		
			$tax_rates = WC_Tax::find_rates( array(
				'country'   => $country,
				'state'     => $state,
				'postcode'  => $postcode,
				'city'      => $city,
				'tax_class' => ''
			) );
			
		}
		
		if ( $tax_rates ) {
			foreach ( $tax_rates as $key => $rate ) {
				if ( isset( $rate['shipping'] ) && 'yes' == $rate['shipping'] ) {
					$matched_tax_rates[ $key ] = $rate;
				}
			}
		}

		foreach( $coupons as $code ) { 

			$coupon = new WC_Coupon( $code );

			//$selected_methods = get_post_meta( $coupon->id, '_ign_shipping_methods', true );

			//if ( empty( $selected_methods ) )
			//	$selected_methods = array();
		
			if ( empty( $coupon ) || is_wp_error( $coupon ) || ( 'ign_shipping_amount' !== $coupon->type && 'ign_shipping_percent' !== $coupon->type && 'ign_shipping_flat_fee' !== $coupon->type ) )
				continue;

			foreach( $rates as $id => $vals ) { 

				//if ( !empty( $selected_methods ) && !in_array( $id, $selected_methods ) )
				//	continue;
					
				// In case calcs go wrong for whatever bizarre reason
				$discounted_amount = $rates[ $id  ]->cost;

				if ( $rates[ $id ]->cost > 0 ) {

					if ( 'flat_fee_shipping' != $id && 'ign_shipping_amount' == $coupon->type ) {
					
						$discounted_amount = $rates[ $id  ]->cost - $coupon->amount;
						
						if ( $discounted_amount < 0 ) 
							$discounted_amount = 0;
							
						$rates[ $id  ]->cost = $discounted_amount;
						
						WC()->session->set( 'coupon_html', '-' . wc_price( $coupon->amount ) );
						WC()->session->save_data();
					
					} else if ( 'ign_shipping_percent' == $coupon->type ) { 
					
						$discounted_amount = $rates[ $id  ]->cost - $rates[ $id  ]->cost * ( $coupon->amount / 100 );
						
						if ( $discounted_amount < 0 ) 
							$discounted_amount = 0;
							
						$rates[ $id  ]->cost = $discounted_amount;
						
						WC()->session->set( 'coupon_html', $coupon->amount . '%' . ' ' . __( 'off shipping', 'ign_shipping_coupons' ) );
						WC()->session->save_data();
					}
					
				} else if ( 'flat_fee_shipping' == $id && 'ign_shipping_flat_fee' == $coupon->type ) { 

					$discounted_amount = floatval( $coupon->amount );
					
					if ( $discounted_amount < 0 ) 
						$discounted_amount = 0;
						
					$rates[ $id  ]->cost = $coupon->amount;
					
				}

				if ( !empty( $matched_tax_rates ) ) {
				
					if ( version_compare( WOOCOMMERCE_VERSION, '2.2', '<' ) )
						$shipping_taxes = $wc_tax->calc_shipping_tax( $rates[ $id ]->cost, $matched_tax_rates );
					else
						$shipping_taxes = WC_Tax::calc_shipping_tax( $rates[ $id ]->cost, $matched_tax_rates );

					$rates[ $id  ]->taxes = $shipping_taxes;
					
				} else {
				
					$rates[ $id  ]->taxes = array();
				
				}
				
			}

		}
		return $rates;
	} 
	
	
	public static function plugin_meta_links( $links, $file ) {

		$plugin_path = trailingslashit( dirname(__FILE__) );
		
                $plugin_dir = trailingslashit( basename( $plugin_path ) );

		if ( $file != $plugin_dir . 'woocommerce-shipping-coupons.php' )
			return $links;

		$links[]= '<a href="http://ignitewoo.com/contact-us" target="_blank"><strong>' . __( 'Support', 'ign_shipping_coupons' ) . '</strong></a>';
		
		$links[]= '<a href="http://ignitewoo.com" target="_blank" style="color:#E15F5E;font-weight:bold">' . __( 'More WooCommerce Extensions', 'ign_shipping_coupons' ) . '</a>';

		return $links;
	}
	
	
	public static function plugin_action_links( $links ) {

		$slug = 'wc-settings';
		
		$plugin_links = array(
			'<a href="http://ignitewoo.com/ignitewoo-software-documentation/" target="_blank">' . __( 'Docs', 'ign_shipping_coupons' ) . '</a>',
		);
		
		return array_merge( $plugin_links, $links );
	}
	
}

add_action( 'init', array( 'IGN_Shipping_Coupons', 'get_instance' ), 0 );


if ( ! function_exists( 'ignitewoo_queue_update' ) )
	require_once( dirname( __FILE__ ) . '/ignitewoo_updater/ignitewoo_update_api.php' );

$this_plugin_base = plugin_basename( __FILE__ );

add_action( "after_plugin_row_" . $this_plugin_base, 'ignite_plugin_update_row', 1, 2 );

ignitewoo_queue_update( plugin_basename( __FILE__ ), 'd60b20b2e7f0ec89e13293e6655c9da1', '18626' );

