<?php
/*  ********************************************************
 *   Checkout cart styling
 *  ********************************************************
 */



add_action('template_redirect', 'change_cart_layout');
function change_cart_layout() {

  function open_main_cart_container() {
    echo '<div class="inner-container"><div class="susy-reset">';
  }

  function close_main_cart_container() {
    echo '</div></div>';
  }
  add_action('woocommerce_before_cart', 'open_main_cart_container', 10);
  add_action('woocommerce_after_cart', 'close_main_cart_container', 10);
}