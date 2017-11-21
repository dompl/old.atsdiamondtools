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
  $cols = -1;
  return $cols;
}