<?php
/*  ********************************************************
 *   Header cart
 *  ********************************************************
 */

add_action('cart', 'header_cart');
add_action('cart_mobile', 'header_cart_mobile');

function header_cart()
{

  $items     = sprintf(_n('%d item', '%d items', WC()->cart->get_cart_contents_count()), WC()->cart->get_cart_contents_count());
  $count     = WC()->cart->get_cart_contents_count();
  $total     = WC()->cart->get_cart_total();
  $cart_url  = wc_get_cart_url();
  $shop_url  = get_permalink(wc_get_page_id('shop'));
  $taxes     = WC()->cart->get_tax_totals();
  $total_inc = WC()->cart->get_total();
  $t         = '';
  foreach ($taxes as $tax)
  {
    $t .= $tax->formatted_amount . $tax->label;
  }
  ?>
  <div id="the-cart">
    <div class="clx">
      <?php if ($count > 0): ?>
        <a href="<?php echo $cart_url; ?>"><i class="icon-shopping-cart"></i></a>
        <span class="total-items"><?php echo $items ?></span><span class="colon">:</span>
        <span class="total-total"><a href="<?php echo $cart_url; ?>"><?php echo $total ?><span class="button"><?php esc_html_e('View Basket', 'TEXT_DOMAIN')?></span></a></span>
        <div class="price-break"><span class="total-tax"><?php echo __('Total:', 'TEXT_DOMAIN') . ' '. $total_inc . ' (' . __('inc') . ' ' . $t . ')' ?></span><span class="shipping"><?php echo __('Shipping and discounts will be calculated at checkout.', 'TEXT_DOMAIN') ?></div>
      <?php else: ?>
        <a href="<?php echo $shop_url; ?>"><i class="icon-shopping-cart"></i></a>
        <span class="total-items empty"><?php esc_html_e('Cart is empty', 'TEXT_DOMAIN')?></span><span class="colon">:</span>
        <span class="total-total"><a href="<?php echo $shop_url; ?>"><span class="button"><?php esc_html_e('Visit Shop', 'TEXT_DOMAIN');?></span></a></span>
      <?php endif?>
    </div>
  </div>
  <?php }

function header_cart_mobile()
{
  $items    = WC()->cart->get_cart_contents_count();
  $count    = WC()->cart->get_cart_contents_count();
  $cart_url = wc_get_cart_url();
  $shop_url = get_permalink(wc_get_page_id('shop'));
  ?>
    <div id="the-cart-mobile">
      <?php if ($count > 0): ?>
        <a href="<?php echo $cart_url; ?>"><i class="icon-shopping-cart"></i> <span><?php echo $items ?></span></a>
      <?php else: ?>
        <a href="<?php echo $shop_url; ?>"><i class="icon-shopping-cart"></i> <span>0</span></a>
      <?php endif?>
    </div>
    <?php }