<?php
/*  ********************************************************
 *   Change Structure for prodyct sliting
 *  ********************************************************
 */

add_action('woocommerce_archive_description', 'ats_change_ordering_structure_', 10);

function ats_change_ordering_structure_()
{

  $cookie_name = 'sort';

  $cookie = isset($_COOKIE[$cookie_name]) && !empty($_COOKIE[$cookie_name]) ?  $_COOKIE[$cookie_name] : '';



  $selector = '<div id="product-sort"><div class="container"><div class="susy-reset">';
  $grid_class ='';
  $list_class ='';
  if ($cookie != '' ) {
    $grid_class = $cookie == 'grid' ? ' active' : '';
    $list_class = $cookie == 'list' ? ' active' : '';
  }

  $selector .= '
  <div class="left">
  <ul>
  <li><span class="selector grid' . $grid_class . ( $cookie == '' ? ' active':'').'" data-sort="grid"><i class="icon-th"></i></span></li>
  <li><span class="selector list' . $list_class . '" data-sort="list"><i class="icon-th-list"></i></span></li>
  </ul>
  </div>';

  $selector .= '<div class="right">';

  ob_start();
  woocommerce_catalog_ordering();
  $selector .= ob_get_contents();
  ob_end_clean();

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

function ats_change_orderings_structure_()
{

  // Remove all stuff
  remove_action('woocommerce_before_shop_loop', 'wc_print_notices', 10);
  remove_action('woocommerce_before_shop_loop', 'woocommerce_result_count', 20);
  remove_action('woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30);

  add_action('ats_loop_start', 'ats_loop_start_', 10);
}

/**
 * Open product loop ( includes cookies)
 * ---
 */

function ats_loop_start_()
{

  $cookie_name = 'sort';

  if (isset($_COOKIE[$cookie_name]) && !empty($_COOKIE[$cookie_name]))
  {
    $sort_class = $_COOKIE[$cookie_name] . '';
  }
  else
  {
    $sort_class = 'grid';
  }
  echo '<div class="container"><div class="susy-reset">';
  echo '<ul id="products-list" class="clx products ' . $sort_class . '">';
}
