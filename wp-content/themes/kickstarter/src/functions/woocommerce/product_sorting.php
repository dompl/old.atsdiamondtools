<?php
/*  ********************************************************
 *   Change Structure for prodyct sliting
 *  ********************************************************
 */

add_action( 'woocommerce_before_shop_loop', 'ats_change_ordering_structure', 10);

function ats_change_ordering_structure() {

  $selector = '<div id="product-sort"><div class="container"><div class="susy-reset">';

  $selector .= '
  <div class="left">
  <ul>
  <li><span class="selector list" data-sort="list"><i class="icon-th"></i></span></li>
  <li><span class="selector grid" data-sort="grid"><i class="icon-th-list"></i></span></li>
  </ul>
  </div>';

  $selector .= '<div class="right">';
  $selector .= '</div>';

  $selector .= '</div></div></div>';
  $selector .= '';

  echo $selector;

}

/**
 * Remove current structure
 * ---
 */

add_action('template_redirect', 'ats_change_orderings_structure_');

function ats_change_orderings_structure_() {

  // Remove all stuff
  remove_action( 'woocommerce_before_shop_loop' , 'wc_print_notices', 10);
  remove_action( 'woocommerce_before_shop_loop' , 'woocommerce_result_count', 20);
  remove_action( 'woocommerce_before_shop_loop' , 'woocommerce_catalog_ordering', 30);
}

/**
 * Open product loop ( includes cookies)
 * ---
 */
add_action('ats_loop_start', 'ats_loop_start_');
function ats_loop_start_() {

  $cookie_name = 'sort';
  if(!isset($_COOKIE[$cookie_name])) {
    $sort_class = $_COOKIE[$cookie_name];
  } else {
    $sort_class = 'grid';
  }
  echo '<ul id="products-list" class="products '.$sort_class.'">';
}
