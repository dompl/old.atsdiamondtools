<?php
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