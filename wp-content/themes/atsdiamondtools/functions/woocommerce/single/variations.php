<?php
/*  ********************************************************
 *   Variable add to cart changes
 *  ********************************************************
 */

add_filter('woocommerce_attribute_label', 'ats_attribute_label', 99, 3);
add_filter('woocommerce_reset_variations_link', 'ats_change_clear_option');
add_filter('woocommerce_dropdown_variation_attribute_options_html', 'ats_variation_attribute_options_html', 10, 2);
add_filter('woocommerce_variation_option_name', 'display_price_in_variation_option_name');

/* Change Variation label */
function ats_attribute_label($label, $name, $product)
{
  $name = __('Select Option', 'TEXT_DOMAIN');
  if (is_cart() || is_checkout())
  {
    $name = __('Option', 'TEXT_DOMAIN');
  }
  return ($name);
}

/* Change variation reset link */
function ats_change_clear_option()
{
  return '<a class="reset_variations clx" href="#"><i class="icon-times-circle"></i></a>';
}

/* Change "No variation selected" in variation dropdown */
function ats_variation_attribute_options_html($html, $args)
{
  $html = str_replace('Choose an option', __('-- No option selected --', 'TEXT_DOMAIN'), $html);
  return $html;
}

/* Add price to variation dropdown */
function display_price_in_variation_option_name($term)
{
  global $wpdb, $product;

  $result = $wpdb->get_col("SELECT slug FROM {$wpdb->prefix}terms WHERE name = '$term'");

  $term_slug   = (!empty($result)) ? $result[0] : $term;
  $product__id = $product->get_id();
  $query       = "SELECT postmeta.post_id AS product_id
                  FROM {$wpdb->prefix}postmeta AS postmeta
                  LEFT JOIN {$wpdb->prefix}posts AS products ON ( products.ID = postmeta.post_id )
                  WHERE postmeta.meta_key LIKE 'attribute_%'
                  AND postmeta.meta_value = '$term_slug'
                  AND products.post_parent = $product__id";

  $variation_id = $wpdb->get_col($query);

  $parent = wp_get_post_parent_id($variation_id[0]);

  if ($parent > 0)
  {
    $_product = new WC_Product_Variation($variation_id[0]);

// $is_tax = get_option('woocommerce_tax_display_shop');

    $tax_tag = get_option('woocommerce_price_display_suffix');

//this is where you can actually customize how the price is displayed
    return $term . ' (' . strip_tags(wc_price($_product->get_price())) . $tax_tag . ')';
  }
  return $term;

}
