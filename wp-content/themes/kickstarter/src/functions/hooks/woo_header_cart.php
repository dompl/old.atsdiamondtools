<?php
/*  ********************************************************
 *   Header cart
 *  ********************************************************
 */

add_action('cart', 'header_cart');
function header_cart()
{
  global $woocommerce;
  $items = sprintf(_n('%d item', '%d items', WC()->cart->get_cart_contents_count()), WC()->cart->get_cart_contents_count());
  $count = WC()->cart->get_cart_contents_count();
  $total = WC()->cart->get_cart_total();
  $cart_url = wc_get_cart_url();
  $shop_url = get_permalink( wc_get_page_id( 'shop' ) );
  ?>
  <div id="the-cart">
    <div class="clx">
      <i class="icon-shopping-cart"></i>
      <?php if ( $count > 0 ): ?>
        <span class="total-items"><?php echo $items ?></span><span class="colon">:</span>
        <span class="total-total"><a href="<?php echo $cart_url; ?>"><?php echo $total ?> <?php esc_html_e('View Basket', 'TEXT_DOMAIN')?></a></span>
      <?php else: ?>
        <span class="total-items empty"><?php esc_html_e('Cart is empty', 'TEXT_DOMAIN')?></span><span class="colon">:</span>
        <span class="total-total"><a href="<?php echo $shop_url; ?>"><?php esc_html_e('Start Shopping', 'TEXT_DOMAIN' ) ; ?></a></span>
      <?php endif ?>
    </div>
  </div>
  <?php }