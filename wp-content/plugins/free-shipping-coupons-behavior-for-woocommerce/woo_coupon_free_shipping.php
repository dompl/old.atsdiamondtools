<?php
/*
Plugin Name: "Free Shipping" Coupons Behavior for WooCommerce
Plugin URI: 
Description: Plugin allows you to select how "Free Shipping" coupons will be applied on checkout. Compatible with WPML and Polylang.
Version: 1.0.0
Author: BlueGlass Tallinn
WC tested up to: 3.7.1
WC requires at least: 4.0
Author URI: https://www.blueglass.ee/en/
Text Domain: woo-coupon-free-shipping
Domain Path: /languages
*/



if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class Woo_Coupon_Free_Shipping{

    public $lang = '';
    
    public function __construct(){

        add_action( 'init', array($this, 'get_languages') );
        add_action( 'init', array($this, 'init_current_language') );

        $this->default_discount_text = __('Free Shipping Discount', 'woo-coupon-free-shipping');

        // Add setting fields
        add_filter( 'woocommerce_get_settings_shipping', array( $this, 'free_shipping_settings'), 10, 2 );

        // Get selected option
        $coupon_apply_type = get_option( 'woo_coupon_free_shipping_options' );
        $coupon_apply_type = $coupon_apply_type ? $coupon_apply_type : 'discount';
        
        // Use selected option
        switch ($coupon_apply_type) {
            case 'discount':
                add_action( "woocommerce_cart_calculate_fees", array( $this, "apply_coupon_as_discount") );
                break;

            case 'methods':
                add_filter( 'woocommerce_package_rates', array( $this, 'apply_coupon_for_shipping_methods' ), 10, 2 );
                break;

            case 'hide_all_except_free':
                add_filter( 'woocommerce_package_rates', array( $this, 'hide_all_except_free' ), 10, 2 );
                break;

            case 'hide_all_except_free_and_localpickup':
                add_filter( 'woocommerce_package_rates', array( $this, 'hide_all_except_free_and_localpickup' ), 10, 2 );
                break;
        }

        /* Add WOO CDEK plugin hooks, to apply correct values to order */
        if ( in_array('woocommerce-edostavka/woocommerce-edostavka.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ))) ) {
            if( $coupon_apply_type == 'discount' ){
                add_filter( 'woocommerce_edostavka_order_atts_array', array( $this, 'delivery_recipient_cost' ), 20, 2 );
            }
        }
    }

    public function init_current_language(){

        if ( in_array( 'sitepress-multilingual-cms/sitepress.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
        //if (function_exists('icl_get_languages')) {

            $this->lang = ICL_LANGUAGE_CODE;

            //get list of used languages from WPML
            $langs = icl_get_languages('skip_missing=N&orderby=KEY&order=DIR&link_empty_to=str');
            //Set current language for language based variables in theme.

            if( !empty($langs) ){
                $this->languages = $langs;
            }

        }

        if ( function_exists( 'pll_the_languages' ) ) {
            $this->lang = pll_current_language();
            if( $this->lang == '' ) $this->lang = $this->default_lang;
        }

    }

    public function get_languages(){
        $default_lang   = explode('-', get_bloginfo( 'language' ));
        $dlang          = $default_lang[0];
        $this->languages[$dlang] = $dlang;

        $this->default_lang = $dlang;
        $this->lang         = $dlang;

        if ( in_array( 'sitepress-multilingual-cms/sitepress.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
        //if (function_exists('icl_get_languages')) {
            //get list of used languages from WPML
            $languages = icl_get_languages('skip_missing=N&orderby=KEY&order=DIR&link_empty_to=str');
            //Set current language for language based variables in theme.

            if( !empty($langs) ){
                $this->languages = $languages;
            }

        }else{
            $default_lang   = explode('_', get_locale());
            $dlang          = $default_lang[0];
            $this->languages[$dlang] = $dlang;

            $this->default_lang = $dlang;
            $this->lang         = $dlang;
        }
        
        if ( function_exists( 'pll_the_languages' ) ) {
            $langs = pll_languages_list( array('raw'=>1) );

            if( !empty($langs) ){
                $langs_proccessed = array();
                foreach($langs as $key => $lang){
                    $langs_proccessed[ $lang ] = $lang;
                }
                $this->languages = $langs_proccessed;
            }
        }

    }

    public function get_current_lang(){
        return $this->lang;
    }

    /*
    Comptibility filter.
    Sets shipping price to zero, when order sent to CDEK courier.
    */
    public function delivery_recipient_cost( $array, $self ){
        foreach( $self->order->get_used_coupons() as $coupon_code ){

            // Retrieving the coupon ID
            $coupon_post_obj = get_page_by_title($coupon_code, OBJECT, 'shop_coupon');
            $coupon_id       = $coupon_post_obj->ID;
        
            // Get an instance of WC_Coupon object in an array(necessary to use WC_Coupon methods)
            $coupon = new WC_Coupon($coupon_id);
            
            $free_shipping  = get_post_meta($coupon_id, 'free_shipping', true);

            // IF order has freeshipping coupon, set shipping price to 0
            if( $free_shipping == 'yes' ){
                $array['DeliveryRecipientCost'] = 0;
                break;
            }
        }
	
		return $array;
    }

    /*
    Applies the discount in the amount of shipping cost if available shipping method has cost
    */
    public function apply_coupon_as_discount( WC_Cart $cart ){

        $discount_text = get_option( 'woo_coupon_free_shipping_discount_text_'. $this->get_current_lang() );
        $discount_text = $discount_text ? $discount_text : $this->default_discount_text;

        $applied = WC()->cart->get_applied_coupons();
        if( !empty($applied) ){
            foreach ($applied as $key => $coupon_code) {
                if( $this->coupon_has_free_shipping( $coupon_code ) ){
                    $shipping_total = $cart->shipping_total;
                    if( $shipping_total && $shipping_total > 0 ) {
                        $discount_text = apply_filters('woo_coupon_free_shipping/discount_text', $discount_text, $coupon_code, $cart);
                        $cart->add_fee( 
                            $discount_text, 
                            -$shipping_total, 
                            apply_filters('woo_coupon_free_shipping/discount_taxable', false)
                        );
                        //Exit loop if discount added
                        break;
                    }
                }
            }
        }

    }

    /*
    Filteres shipping methods and nulles the available shipping method costs
    */
    public function apply_coupon_for_shipping_methods( $rates, $package ){
        $applied = WC()->cart->get_applied_coupons();
        if( !empty($applied) ){
            foreach ($applied as $key => $coupon_code) {
                if( $this->coupon_has_free_shipping( $coupon_code ) ){
                    foreach ( $rates as $rate_id => $rate ) {
                        $rates[$rate_id]->cost = 0;
                    }
                }
            }
        }
		return $rates;
    }

    /*
    Filteres shipping methods and leaves only available free shipping methods
    */
    public function hide_all_except_free( $rates ) {
        $free_methods = array();
        foreach ( $rates as $rate_id => $rate ) {
            if ( 'free_shipping' === $rate->method_id ) {
                $free_methods[$rate_id] = $rate;
                break;
            }
        }
        return !empty( $free_methods ) ? $free_methods : $rates;
    }

    /*
    Filteres shipping methods and leaves only available free shipping and local pickup methods
    */
    function hide_all_except_free_and_localpickup( $rates, $package ) {
        $output_rates = array();
        foreach ( $rates as $rate_id => $rate ) {
            if ( 'free_shipping' === $rate -> method_id ) {
                $output_rates[ $rate_id ] = $rate;
                break;
            }
        }
    
        if ( !empty( $output_rates ) ) {
            foreach ( $rates as $rate_id => $rate ) {
                if ('local_pickup' === $rate->method_id ) {
                    $output_rates[ $rate_id ] = $rate;
                    break;
                }
            }
            return $output_rates;
        }
    
        return $rates;
    }

    /*
    Returns bool for available free shipping on coupon
    */
    public function coupon_has_free_shipping( $coupon_code ){
        $coupon         = new WC_Coupon($coupon_code);
        $coupon_id      = $coupon->get_id();
        $free_shipping  = get_post_meta($coupon_id, 'free_shipping', true);

        if( $free_shipping == 'yes' ){
            return true;
        }

        return false;
    }
    
    
    /* 
    Plugin settings
    */
    public function free_shipping_settings( $settings ) {

        //$this->init_current_language();

        $settings[] = array( 'title' => __( 'Coupon Free Shipping Settings', 'woo-coupon-free-shipping' ), 'type' => 'title', 'id' => 'woo_coupon_free_shipping' );

        $coupon_apply_type = get_option( 'woo_coupon_free_shipping_options' );
        $coupon_apply_type = $coupon_apply_type ? $coupon_apply_type : 'discount';
        
        $settings[] = array(
                'title'     => __( 'Select how "Free Shipping" coupons should be applied', 'woo-coupon-free-shipping' ),
                'desc'      => __( '', 'woocommerce' ),
                'id'        => 'woo_coupon_free_shipping_options',
                'type'      => 'radio',
                'desc_tip'  => true,
                'options'   => array( 
                    'discount'              => __('Add discount with amount that is equal to the chosen shipping price.', 'woo-coupon-free-shipping'),
                    'methods'               => __('Null the prices of available shipping methods.', 'woo-coupon-free-shipping'),
                    'hide_all_except_free'  => __('Hide all shipping methods, except "Free Shipping"', 'woo-coupon-free-shipping'),
                    'hide_all_except_free_and_localpickup' => __('Hide all shipping methods, except "Free Shipping" and "Local Pickup"', 'woo-coupon-free-shipping')
                ),
                'default' => $coupon_apply_type
            );

        
        $settings[] = array(
                'title'     => __( 'Discount text', 'woo-coupon-free-shipping' ),
                'desc'      => __( 'Text that appears on checkout and cart before total price, to indicate the discount.', 'woocommerce' ),
                'id'        => 'woo_coupon_free_shipping_discount_text_'. $this->get_current_lang(),
                'type'      => 'text',
                'desc_tip'  => true,
                'default'   => $this->default_discount_text
            );

        $settings[] = array( 'type' => 'sectionend', 'id' => 'woo_coupon_free_shipping' );
        return $settings;
    }
}

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    new Woo_Coupon_Free_Shipping();
}
