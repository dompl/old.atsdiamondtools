<?php
/*  ********************************************************
 *   Product Listing
 *  ********************************************************
 */

// if (is_shop()) {
add_action('woocommerce_before_shop_loop_item', 'ats_create_product_listing_layout_', 20);

function ats_create_product_listing_layout_()
{

  // Get product ID
  global $product, $post, $woocommerce;

  $id   = $product->get_id();

  // Product
  $prod_url   = get_the_permalink($id);
  $prod_title = get_the_title($id);

  // Product Image
  $prod_image = '';
  if (has_post_thumbnail($id))
  {
    $image_width  = get_option('shop_catalog_image_size')['width'];  // Image width
    $image_height = get_option('shop_catalog_image_size')['height']; // Image height
    $image_crop   = get_option('shop_catalog_image_size')['crop'];   // Image crop
    $image_id     = get_post_thumbnail_id($id);

    $prod_image = image_figure($image_id, '', $image_width, $image_height, $image_crop);
  }
  else
  {
    // TODO - create product image if there is no product image ;)
  }

  $prod = '<div class="product" id="prod-id-' . $id . '">';

  $prod .= '<div class="product-list-start">';

  // Product image
  $prod .= sprintf('
    <div class="product-list-image">
    <a href="%s" title="%s">%s</a>
    </div>
    ',
    $prod_url, // Product URL
    esc_html($prod_title),
    $prod_image
  );

  $prod .= '<div class="product-content-container">'; // Start conten container

  // Product
  $prod .= sprintf('
    <div class="product-list-title">
    <a href="%s" title="%s">%s</a>
    </div>
    <div class="product-list-short">
    %s
    </div>
    ',
    $prod_url,
    esc_html($prod_title),
    $prod_title,
    get_the_excerpt($id)
  );

  $prod .= '</div>';

  $prod .= '</div>'; // End content container

  // Add to cart
  $add_to_cart = sprintf('<a href="%s" rel="nofollow" data-product_id="%s" data-product_sku="%s" data-quantity="%s" class="button product_type_%s">%s</a>',
    esc_url($product->add_to_cart_url()),
    esc_attr($id),
    esc_attr($product->get_sku()),
    esc_attr(isset($quantity) ? $quantity : 1),
    esc_attr($product->get_type()),
    esc_html($product->add_to_cart_text())
  );

  // Product categories

  $cat_list = '<ul>';
  foreach (get_the_terms($id, 'product_cat') as $category)
  {
    $category_id = $category->term_id;
    $cat_list .= '<li>' . esc_html($category->name) . '</li>';
  }
  $cat_list .= '</ul>';

  $prod .= sprintf('
    <div class="product-content-colleterals clx">
    <div class="product-list-cats">%s</div>
    <div class="product-list-price">%s</div>
    <div class="product-list-add-cart">%s</div>
    </div>
    ',
    $cat_list,
    $product->get_price_html(),
    $add_to_cart
  );

  $prod .= '</div>';

  echo $prod;
}

// With this one we are going to remove all actions from woocommerce. Sorry woo!
add_action('template_redirect', 'ats_setup_product_listing_layout_');
function ats_setup_product_listing_layout_()
{
  remove_action('woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open');
  remove_all_actions('woocommerce_before_shop_loop_item_title');
  remove_all_actions('woocommerce_shop_loop_item_title');
  remove_all_actions('woocommerce_shop_loop_item_title');
  remove_all_actions('woocommerce_after_shop_loop_item_title');
  remove_all_actions('woocommerce_after_shop_loop_item');
}
//}
