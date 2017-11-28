<?php
/*  ********************************************************
 *   Variable add to cart changes
 *  ********************************************************
 */

add_filter( 'woocommerce_attribute_label' , 'ats_attribute_label',99,3 );
add_filter('woocommerce_reset_variations_link', 'ats_change_clear_option');
add_filter('woocommerce_dropdown_variation_attribute_options_html', 'ats_variation_attribute_options_html', 10, 2);

/* Change Variation label */
function ats_attribute_label( $label, $name, $product ) {
  $name = __('Select Option', 'TEXT_DOMAIN');
  if (is_cart() || is_checkout()) {
    $name = __('Option', 'TEXT_DOMAIN');
  }
  return ( $name );
}

/* Change variation reset link */
function ats_change_clear_option() {
  return '<a class="reset_variations clx" href="#"><i class="icon-times-circle"></i></a>';
}

/* Change "No variation selected" in variation dropdown */
function ats_variation_attribute_options_html($html, $args){
  $html = str_replace('Choose an option', __('-- No option selected --', 'TEXT_DOMAIN'), $html);
  return $html;
}