<?php
/*  ********************************************************
 *   Product SKU
 *  ********************************************************
 */

function ats_single_sku( $product_id , $show_sku = true, $sku = '' ) {
  global $product;
  if ( !$product_id ||  $show_sku == false ) {
    return;
  }
  if ( $product->is_type( 'variable' ) ) {
    $sku = '<span id="var_sku">'.__('Per Variation', 'TEXT_DOMAIN').'</span>';
  } else {
    if ($product->get_sku() != '') {
      $sku = $product->get_sku();
    } else {
      $sku = __('N/A', 'TEXT_DOMAIN');
    }
  }
  return $sku;
}


