<?php
/*  ********************************************************
 *   Global Woocommerce settings
 *  ********************************************************
 */

/* Theme Integration */
add_theme_support('woocommerce');

/* Remove all stylesheets */

add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );

/* Change the amount of products displayed on listing */

add_filter( 'loop_shop_per_page', 'new_loop_shop_per_page', 20 );

function new_loop_shop_per_page( $cols ) {
  // $cols contains the current number of products per page based on the value stored on Options -> Reading
  // Return the number of products you wanna show per page.
  $cols = 32;
  return $cols;
}

/*Add to cart*/
// add_filter( 'woocommerce_product_single_add_to_cart_text', 'sm_woo_custom_cart_button_text' );
// add_filter( 'woocommerce_product_add_to_cart_text', 'sm_woo_custom_cart_button_text' );

// function sm_woo_custom_cart_button_text() {
//   return __( 'Add to cart', 'woocommerce' );
// }

/*View Cart*/
function sm_text_view_cart_strings( $translated_text, $text, $domain ) {
  switch ( $translated_text ) {
    case 'View basket' :
    $translated_text = __( '', 'woocommerce' );
    break;
  }
  return $translated_text;
}
add_filter( 'gettext', 'sm_text_view_cart_strings', 20, 3 );

function my_custom_wc_get_variations_args( $args ) {
  $args['order'] = 'ASC';
  $args['orderby'] = 'menu_order';

  return $args;
}

add_filter( 'woocommerce_ajax_admin_get_variations_args', 'my_custom_wc_get_variations_args' );

/*  ********************************************************
 *   Disable select 2
 *  ********************************************************
 */

// add_action( 'wp_enqueue_scripts', 'mgt_dequeue_stylesandscripts', 100 );

// function mgt_dequeue_stylesandscripts() {
//   if ( class_exists( 'woocommerce' ) ) {
//     wp_dequeue_style( 'select2' );
//     wp_deregister_style( 'select2' );

//     wp_dequeue_script( 'select2');
//     wp_deregister_script('select2');

//   }
// }
