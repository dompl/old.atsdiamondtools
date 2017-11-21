<?php
/*  ********************************************************
 *   Woocommerce Messages - Add to cart Success
 *  ********************************************************
 */


add_filter( 'wc_add_to_cart_message_html', 'ats_add_to_cart_message' , 10, 2 );

function ats_add_to_cart_message( $message, $products ) {

  foreach ($products  as $product_id => $quantity) {
    $product = wc_get_product( $product_id );
  }

  $image_id = $product->get_image_id();
  $image = '';
  if ($image_id != '' ) {
    $image = '<div class="product-image"><a href="'.$product->get_permalink().'"><img src="' . image_array($image_id,'',26,26)['url'] . '" alt="'.esc_attr($product->get_title()).' ' . __('successfully added to your cart.', 'woocommerce').'"></a></div>';
  }

  $message    = sprintf('
    %6$s
    <div class="product-name clx"><i class="icon-cart-plus"></i><strong><a href="%1$s">%2$s</a></strong> %5$s</div>
    <div class="product-link"><a class="button batch" href="%3$s" class="button wc-forwards">%4$s</a></div>
    ',
    esc_attr($product->get_permalink()),
    esc_attr($product->get_title()),
    get_permalink(wc_get_page_id('cart')),
    __('View Cart', 'woocommerce'),
    __('successfully added to your cart.', 'woocommerce'),
    $image
  );
  return $message;
}

add_action('woocommerce_archive_description', 'wc_print_notices', 20);
