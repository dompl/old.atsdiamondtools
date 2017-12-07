<?php
/*  ********************************************************
 *   Home page header
 *  ********************************************************
 */

if (!defined('ABSPATH')) {
  exit; // Exit if accessed directly
}

add_filter('the_content', 'home_slider_function', 10);           // Home Page Slider
add_filter('the_content', 'home_call_for_actions_function', 20); // Home Page Call for actions
add_filter('the_content', 'home_custom_products', 30);           // Home Product listings

if (!function_exists('home_slider_function') && class_exists('acf')) {
  function home_slider_function($content)
  {
    /* Start ACF Loop */
    $slider      = '';
    $banner_type = get_field('select_banner_type'); // standard ::

    if (have_rows('home_banner_list') && is_front_page()) {

      $slider .= '<section id="home-slider"><div class="container"><ul id="home-slider">';

      while (have_rows('home_banner_list')) {
        the_row();

        $product_name  = get_sub_field('home_banner_name');
        $product_short = get_sub_field('home_banner_short');
        $product_image = get_sub_field('home_banner_image'); // ID
        $product_link  = get_sub_field('home_banner_link');  // ID

        $slider .= sprintf('
          <div class="slide-left">
            <div class="slider-title"><a href="%4$s" title="%1$s">%1%s</a></div>
            <div class="slide-short"><a href="%4$s" title="%1$s">%2$s</a></div>
            <a href="%4$s" title="%1$s">%2$s</a>
          </div>
          <div class="slide-right">
            <div class="slide-image"><a href="%4$s" title="%1$s">%3$s</a></div>
          </div>
          ',
          esc_attr($product_name),
          esc_attr($product_short),
          esc_attr($product_image),
          esc_url($product_link),
          esc_url($product_link),
          __('More &amp; buy', 'TEXT_DOMAIN')
        );
      }

      $slider .= '<ul></div></section>';
    }

    return $content . $slider;
  }

}

/* Home call for actions */

if (!function_exists('home_call_for_actions_function') && class_exists('acf')) {
  function home_call_for_actions_function($content)
  {
    $calls = '';

    if (is_front_page() && have_rows('add_home_calls')) {

      // Count items for css classes
      $count = count(get_field('add_home_calls'));

      $calls .= '<section id="home-calls"><div class="container"><div class="susy-reset"></ul>';

      while (have_rows('add_home_calls')) {
        the_row();

        $home_call_icon    = get_sub_field('home_call_icon'); // Image ID
        $home_call_title   = get_sub_field('home_call_title');
        $home_call_content = get_sub_field('home_call_content');

        $calls .= sprintf('
          <li class="col-%4$s">
            <div class="clx">
              <div class="left">
               %1$s
              </div>
              <div class="right">
                <div class="call-title">%2$s</div>
                %3$s
              </div>
            </div>
          </li>
          ',
          $home_call_icon && is_numeric($home_call_icon) && function_exists('image_figure') ? '<div class="call-image">' . image_figure($home_call_icon, '', 99, 99, true) . '<div>' : '',
          $home_call_title,
          $home_call_content ? '<p class="call-content">' . $home_call_content . '</p>' : '',
          $count
        );
      }

      $calls .= '</ul></div></div></section>';

    }

    return $content . $calls;

  }

}

if (!function_exists('home_custom_products') && class_exists('acf')) {

  function home_custom_products($content)
  {
    $products = '';

    if (have_rows('home_custom_products')) {

      while (have_rows('home_custom_products')) {
        the_row();

        $product_type     = get_sub_field('home_product_type');
        $sectoin_title    = get_sub_field('home_product_title');
        $product_ids      = get_sub_field('home_product_ids');
        $product_limit    = get_sub_field('home_product_limit');
        $product_category = get_sub_field('home_product_category');

        $limit    = $product_limit ? $product_limit : '4';
        $prod_ids = is_array($product_ids) && !empty($product_ids) ? implode(',', $product_ids) : '';

        switch ($product_type) {

          case 'product_category':

            $slugs = array();

            foreach ($product_category as $cat_id) {
              $a       = get_term_by('id', $cat_id, 'product_cat', 'ARRAY_A');
              $slugs[] = $a['slug'];
            }

            $product_list = !empty($slugs) ? do_shortcode('[product_category limit="' . $limit . '" category="' . implode(',', $slugs) . '"]') : '';
            continue;

          case 'best_selling_products':
            $product_list = do_shortcode('[best_selling_products limit="' . $limit . '"]');
            continue;

          case 'recent_products':
            $product_list = do_shortcode('[recent_products limit="' . $limit . '"]');
            continue;

          default:
            $product_list = do_shortcode('[products ids="' . $prod_ids . '" limit="' . $limit . '"]');
            continue;
        }

        $products .= sprintf('
        <section class="home-products %1$s">
        <div class="container">
          %2$s
          <div class="home-products-container">
            <div class="no-container">
              %3$s
            </div>
          </div>
          </div>
        </section>
        ',
          $product_type,
          $sectoin_title ? '<h3>' . $sectoin_title . '</h3>' : '',
          $product_list
        );
      }

    }

    return $content . $products;
  }

}
