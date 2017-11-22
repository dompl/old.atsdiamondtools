<?php
/*  ********************************************************
 *   Product price
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

    ?>
    <style>
    div.woocommerce-variation-price,
    div.woocommerce-variation-availability,
    div.hidden-variable-price {
      height: 0px !important;
      overflow:hidden;
      position:relative;
      line-height: 0px !important;
      font-size: 0% !important;
    }
  </style>
  <script>
    jQuery(document).ready(function($) {
      $('select').blur( function(){
        if( '' != $('input.variation_id').val() ){
            //$('p.price').html($('div.woocommerce-variation-price > span.price').html()).append('<p class="availability">'+$('div.woocommerce-variation-availability').html()+'</p>');
            $('p.price').html($('div.woocommerce-variation-price > span.price').html());
            console.log($('input.variation_id').val());
          } else {
            $('p.price').html($('div.hidden-variable-price').html());
            if($('p.availability'))
              $('p.availability').remove();
            console.log('NULL');
          }
        });
    });
  </script>
  <?php
  echo '<p class="price">'.$price.'</p><div class="hidden-variable-price" >'.$price.'</div>';
endif;
}
