<?php
/*  ********************************************************
 *   Single product layout
 *  ********************************************************
 */

function ats_single_product_layout()
{
  global $product, $post;
  wp_enqueue_script('single-product', get_template_directory_uri() . '/js/x-single-product.js', array('jquery'), false, true);?>

  <div class="main-left"><?php echo ats_single_product_image_gallery() ?></div>
  <div class="main-right">
    <div class="product-header">
      <h2><?php echo esc_attr($product->get_name()) ?></h2>
      <div class="product-price"><?php echo $product->get_price_html() ?></div>
    </div>
    <div class="product-top-colleterals">
      <div class="category">
        <?php echo wc_get_product_category_list($post->ID, ',', '<span class="category-in">' . _n('Category:', 'Categories:', sizeof(get_the_terms($post->ID, 'product_cat')), 'woocommerce') . ' ', '.</span>'); ?>
      </div>
      <div class="sku">
        <span class="name sku-name"><?php _e('SKU', 'TEXT_DOMAIN')?>: </span><?php echo ats_single_sku($post->ID); ?>
      </div>
    </div>
    <div class="product-short">
      <?php echo $product->get_short_description() ?>
    </div>
  </div>
  <div class="description"></div>

  <?php }
