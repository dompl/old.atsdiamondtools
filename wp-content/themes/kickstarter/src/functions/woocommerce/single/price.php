<?php
/*  ********************************************************
 *   Product variable to From ...
 *  ********************************************************
 */

function custom_wc_template_single_price(){
  global $product;

  // Variable product only
  if($product->is_type('variable')):

    // Main Price
    $prices = array( $product->get_variation_price( 'min', true ), $product->get_variation_price( 'max', true ) );
    $price = $prices[0] !== $prices[1] ? sprintf( __( 'From: %1$s', 'woocommerce' ), wc_price( $prices[0] ) ) : wc_price( $prices[0] );

    // Sale Price
    $prices = array( $product->get_variation_regular_price( 'min', true ), $product->get_variation_regular_price( 'max', true ) );
    sort( $prices );
    $saleprice = $prices[0] !== $prices[1] ? sprintf( __( 'From: %1$s', 'woocommerce' ), wc_price( $prices[0] ) ) : wc_price( $prices[0] );

    if ( $price !== $saleprice && $product->is_on_sale() ) {
      $price = '<del>' . $saleprice . $product->get_price_suffix() . '</del> <ins>' . $price . $product->get_price_suffix() . '</ins>';
    }

    echo '<p class="price">'.$price.'</p><div class="hidden-variable-price" >'.$price.'</div>';
  else :
    echo $product->get_price_html();
  endif;
}
