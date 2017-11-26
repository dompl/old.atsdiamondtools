<?php
/*  ********************************************************
 *   Single product layout
 *  ********************************************************
 */

/* Redirects */
add_action('template_redirect', 'ats_setup_single_single');

function ats_setup_single_single()
{
  add_action('ats_single_add_to_cart', 'woocommerce_template_single_add_to_cart');
  add_action('ats_single_product_price', 'custom_wc_template_single_price');
  // removing the price of variable products
  remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_price', 10);
}

/* Main product function */
function ats_single_product_layout()
{
  global $product, $post;

  wp_register_script('single-product', get_template_directory_uri() . '/js/x-singleproduct.js', array('jquery'), false, true);
  wp_enqueue_script( 'single-product' );
  ?>


  <div class="main-left">
    <div class="product-header top">
      <h2><span class="prod_Name"><?php echo esc_attr($product->get_name()) ?></span><span id="product-option"></span></h2>
      <div class="product-price"><?php do_action('ats_single_product_price')?></div>
    </div>
    <div class="product-top-colleterals clx top">
      <div class="category">
        <?php echo wc_get_product_category_list($post->ID, ',', '<span class="category-in">' . _n('Category:', 'Categories:', sizeof(get_the_terms($post->ID, 'product_cat')), 'woocommerce') . ' ', '.</span>'); ?>
      </div>
      <div class="sku">
        <span class="name sku-name"><?php _e('SKU', 'TEXT_DOMAIN')?>: </span><?php echo ats_single_sku($post->ID); ?>
      </div>
    </div>
    <div class="product-short">
      <?php echo $product->get_short_description() ?>
      <?php if ($product->get_description() != '' ): ?>
        <div class="mobile" id="mobile_full"></div>
      <?php endif; ?>
    </div>
    <div id="variation-wrapper" class="clx"></div>
    <div class="product-header bottom">
      <div class="product-price"><?php do_action('ats_single_product_price')?></div>
    </div>
    <div id="variation_stock"></div>
    <div class="ats-add-to-cart"><?php do_action('ats_single_add_to_cart')?></div>
     <div class="social-shares clx"><?php do_action('social_shares') ?></div>
  </div>
  <div class="main-right">
    <?php echo ats_single_product_image_gallery() ?>
  </div>
  <?php if ($product->get_description() != '' ): ?>
  <div class="product-description clx">
  <h3 class="title"><?php esc_html_e( 'Prodct Description', 'TEXT_DOMAIN') ?></h3>
  <div class="product-description-content first-last"><?php echo $product->get_description()  ?></div>
  </div>
  <?php endif ?>
  <?php
}
