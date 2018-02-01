<?php
/*  ********************************************************
 *   Global Woocommerce settings
 *  ********************************************************
 */

/* Theme Integration */
add_theme_support('woocommerce');

/* Remove all stylesheets */

add_filter('woocommerce_enqueue_styles', '__return_empty_array');

/* Change the amount of products displayed on listing */

add_filter('loop_shop_per_page', 'new_loop_shop_per_page', 20);

function new_loop_shop_per_page($cols)
{
  // $cols contains the current number of products per page based on the value stored on Options -> Reading
  // Return the number of products you wanna show per page.
  $cols = (int) get_option('posts_per_page');
  return $cols;
}

/*Add to cart*/
// add_filter( 'woocommerce_product_single_add_to_cart_text', 'sm_woo_custom_cart_button_text' );
// add_filter( 'woocommerce_product_add_to_cart_text', 'sm_woo_custom_cart_button_text' );

// function sm_woo_custom_cart_button_text() {
//   return __( 'Add to cart', 'woocommerce' );
// }

/*View Cart*/
function sm_text_view_cart_strings($translated_text, $text, $domain)
{

  switch ($translated_text)
  {
    case 'View basket':
      $translated_text = __('', 'woocommerce');
      break;
  }

  return $translated_text;
}

add_filter('gettext', 'sm_text_view_cart_strings', 20, 3);

function my_custom_wc_get_variations_args($args)
{
  $args['order']   = 'ASC';
  $args['orderby'] = 'menu_order';

  return $args;
}

add_filter('woocommerce_ajax_admin_get_variations_args', 'my_custom_wc_get_variations_args');

/**
 * Rename Select Options to Read more
 * ---
 */
add_filter('woocommerce_product_add_to_cart_text', 'custom_woocommerce_product_add_to_cart_text');
function custom_woocommerce_product_add_to_cart_text()
{
  global $product;
  $product_type = $product->get_type();

  switch ($product_type)
  {
    case 'external':
      return __('More Info/Buy', 'woocommerce');
      break;
    case 'grouped':
      return __('More Info/Buy', 'woocommerce');
      break;
    case 'simple':
      return __('More Info/Buy', 'woocommerce');
      break;
    case 'variable':
      return __('More Info/Buy', 'woocommerce');
      break;
    default:
      return __('More Info/Buy', 'woocommerce');
  }
}

/* Remove sufix if product is not taxable */
add_filter('woocommerce_get_price_suffix', 'custom_woocommerce_get_price_suffix', 10, 2);
function custom_woocommerce_get_price_suffix($price_display_suffix, $product)
{
  if (!$product->is_taxable())
  {
    return '';
  }
  return $price_display_suffix;
}

/* Disable deliver oto a different address checkbox */
add_filter('woocommerce_ship_to_different_address_checked', '__return_false');

/* Disable downloads for on user menu */
function CM_woocommerce_account_menu_items_callback($items)
{
  unset($items['downloads']);
  return $items;
}
add_filter('woocommerce_account_menu_items', 'CM_woocommerce_account_menu_items_callback', 10, 1);

add_action('woocommerce_register_form', 'ats_add_week_password_confirmation', 10);

function ats_add_week_password_confirmation()
{

  $password = sprintf('<div class="pw-weak"><label><input type="checkbox" name="pw_weak" class="pw-checkbox" />%s</label></div>', __('Confirm use of weak password', 'TEXT_DOMAIN'));
  echo $password;
}

