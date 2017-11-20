<?php
/*  ********************************************************
 *   Mini cart
 *  ********************************************************
 */
add_filter( 'woocommerce_add_to_cart_fragments', 'woocommerce_header_add_to_cart_fragment' );
function woocommerce_header_add_to_cart_fragment( $fragments ) {
  ob_start();
  do_action('cart');
  $fragments['#the-cart'] = ob_get_contents();
  ob_end_clean();
  return $fragments;
}
/* The same cart for mobile */
add_filter( 'woocommerce_add_to_cart_fragments', 'woocommerce_header_add_to_cart_fragment_mobile' );
function woocommerce_header_add_to_cart_fragment_mobile( $fragments ) {
  ob_start();
  do_action('cart_mobile');
  $fragments['#the-cart-mobile'] = ob_get_contents();
  ob_end_clean();
}
/*  ********************************************************
 *   Checkout cart styling
 *  ********************************************************
 */

add_action('template_redirect', 'change_cart_layout');
function change_cart_layout() {

  add_action('woocommerce_before_cart', 'open_main_cart_container', 10);
  add_action('woocommerce_after_cart', 'close_main_cart_container', 10);

  /* Cart opening container */
  function open_main_cart_container() {
    echo '<div class="inner-container"><div class="susy-reset">';
  }

  /* Cart closing container */
  function close_main_cart_container() {
    echo '</div></div>';
  }
}