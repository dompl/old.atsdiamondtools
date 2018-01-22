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

  $id = $product->get_id();

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

  $prod .= '<div class="product-list-start clx">';

  // Product image
  $prod .= sprintf('
    <div class="product-list-image clx" data-mh="prod-img">
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
    <div class="product-content-list">
    <h2 class="product-list-title mh">
    <a href="%s" title="%s">%s</a>
    </div>
    <div class="product-list-short">
    %s
    </h2>
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
  $add_to_cart =
  apply_filters( 'woocommerce_loop_add_to_cart_link',
    sprintf('<a href="%s" rel="nofollow" data-product_id="%s" data-product_sku="%s" data-quantity="%s" class="button product_type_%s %s %s">%s</a>',
      // esc_url($product->add_to_cart_url()),
      is_product() ? esc_url($product->add_to_cart_url()) : esc_url(get_permalink( $product->get_id() )),
      esc_attr($id),
      esc_attr($product->get_sku()),
      esc_attr(isset($quantity) ? $quantity : 1),
      esc_attr($product->get_type()),
      $product->is_purchasable() && $product->is_in_stock() ? 'add_to_cart_button' : '',
      // $product->supports( 'ajax_add_to_cart' ) ? 'ajax_add_to_cart' : '',
      '',
      esc_html($product->add_to_cart_text())
    ),
    $product );


  // Product categories
  $cat_list = '';
  if (get_the_terms($id, 'product_cat') && !is_cart()) {
    $cat_list = '<ul>';
    foreach (get_the_terms($id, 'product_cat') as $category)
    {
      $category_id = $category->term_id;
      $cat_list .= '<li>' . esc_html($category->name) . '</li>';
    }
    $cat_list .= '</ul>';
  }

  // Stock Status
  $stock        = $product->get_stock_status();
  $stock_status = '';
  switch ($stock)
  {
    case 'instock':
    $stock_status = __('In Stock', 'TEXT_DOMAIN');
    break;
    case 'outofstock':
    $stock_status = __('Out of Stock', 'TEXT_DOMAIN');
    break;
    default:
    $stock_status = '';
    break;
  }
  // Stock quantity
  $stock_quantity = $product->get_stock_quantity();
  $stock_manage   = $product->get_manage_stock();
  $stock_min      = 5;

  //$info_button = $product->get_type() !== 'variable' ? '<a class="gf info outline" href="'.get_the_permalink().'">'.__('Info', 'TEXT_DOMAIN').'</a>' : '';
  $info_button ='';
  if ($stock_quantity > $stock_min && $stock == 'instock') {
    $stock_info = __('+' . $stock_min);
  } elseif($stock_quantity <= $stock_min && $stock == 'instock' ) {
    $stock_info = __('Less then' . ' ' .$stock_min, 'TEXT_DOMAIN');
  } else {
    $stock_info = '';
    $info_button = '';
  }

  $stock_info_html = $stock_manage == 1 ? '<span class="stock-count">' . $stock_info . '</span>' : '';

  $prod .= sprintf('
    <div class="product-content-colleterals clx">
    <div class="product-list-price">%1$s</div>
    %4$s
    %5$s
    <div class="product-list-cats">%2$s</div>
    <div class="product-list-add-cart">%3$s</div>
    </div>
    ',
    '<span class="gh ls">'.__('Price', 'TEXT_DOMAIN').': </span><strong>' . $product->get_price_html()  . '</strong>',
    '<span class="gh ls">'.__('Category', 'TEXT_DOMAIN').': </span>' . $cat_list,
    $info_button . $add_to_cart,
    $stock_status ? '<div class="product-list-stock gh"><span class="ls">'.__('Stock', 'TEXT_DOMAIN').': </span><strong class="stock-status-' . $stock . '">' . $stock_info_html . ' ' . $stock_status . '</strong></div>' : '',
    $product->get_sku() != '' ? '<div class="product-sku gh"><span class="ls">'.__('SKU', 'TEXT_DOMAIN').': </span><strong>' . $product->get_sku() . '</strong></div>' : ''
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
  remove_action('woocommerce_after_shop_loop', 'woocommerce_pagination', 10);
}
//}
