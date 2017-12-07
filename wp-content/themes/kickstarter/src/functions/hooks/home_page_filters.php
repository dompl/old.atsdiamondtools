<?php
/*  ********************************************************
 *   Home page header
 *  ********************************************************
 */

if (!defined('ABSPATH'))
{
  exit; // Exit if accessed directly
}

add_filter('the_content', 'home_slider_function', 10);           // Home Page Slider
add_filter('the_content', 'home_call_for_actions_function', 20); // Home Page Call for actions
add_filter('the_content', 'home_custom_products', 30);           // Home Product listings
add_filter('the_content', 'home_content_banner', 40);            // Home Content banner
add_filter('the_content', 'content_container_wrapper', 50);      // Home Content banner

if (!function_exists('home_slider_function') && class_exists('acf'))
{
  function home_slider_function($content)
  {
    /* Start ACF Loop */
    $slider      = '';
    $banner_type = get_field('select_banner_type'); // standard ::

    if (have_rows('home_banner_list') && is_front_page())
    {

      $slider .= '<section id="home-slider"><div class="container"><ul id="home-slider">';

      while (have_rows('home_banner_list'))
      {
        the_row();

        $product_name     = get_sub_field('home_banner_name');
        $product_subtitle = get_sub_field('home_banner_subtitle');
        $product_short    = get_sub_field('home_banner_short');
        $product_image    = get_sub_field('home_banner_image'); // ID
        $product_link     = get_sub_field('home_banner_link');  // ID

        $slider .= sprintf('
          <div class="slide-left">
            <div class="slider-title"><a href="%4$s" title="%1$s">%1$s</a>%6$s</div>
            <div class="slide-short"><a href="%4$s" title="%1$s">%2$s</a></div>
            <a href="%4$s" title="%1$s">%5$s</a>
          </div>
          <div class="slide-right">
            <div class="slide-image"><a href="%4$s" title="%1$s">%3$s</a></div>
          </div>
          ',
          $product_name,                                                                                                         // 1
          $product_short,                                                                                                        // 2
          is_numeric($product_image) && function_exists('image_figure') ? image_figure($product_image, '', 490, 270, false) : '', // 3
          $product_link ? get_the_permalink($product_link) : '',                                                                 // 4
          __('More &amp; buy', 'TEXT_DOMAIN'),                                                                                   // 5
          $product_subtitle ? '<span class="product-subtitle">' . $product_subtitle . '</span>' : ''                             //6
        );
      }

      $slider .= '<ul></div></section>';
    }

    return $content . $slider;
  }

}

/* Home call for actions */

if (!function_exists('home_call_for_actions_function') && class_exists('acf'))
{
  function home_call_for_actions_function($content)
  {
    $calls = '';

    if (is_front_page() && have_rows('add_home_calls'))
    {

      // Count items for css classes
      $count = count(get_field('add_home_calls'));

      $calls .= '<section id="home-calls"><div class="container"><div class="susy-reset"></ul>';

      while (have_rows('add_home_calls'))
      {
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

if (!function_exists('home_custom_products') && class_exists('acf'))
{

  function home_custom_products($content)
  {
    $products = '';

    if (have_rows('home_custom_products'))
    {

      while (have_rows('home_custom_products'))
      {
        the_row();

        $product_type     = get_sub_field('home_product_type');
        $sectoin_title    = get_sub_field('home_product_title');
        $product_ids      = get_sub_field('home_product_ids');
        $product_limit    = get_sub_field('home_product_limit');
        $product_category = get_sub_field('home_product_category');

        $limit    = $product_limit ? $product_limit : '4';
        $prod_ids = is_array($product_ids) && !empty($product_ids) ? implode(',', $product_ids) : '';

        switch ($product_type)
        {

          case 'product_category':

            $slugs = array();

            foreach ($product_category as $cat_id)
            {
              $a       = get_term_by('id', $cat_id, 'product_cat', 'ARRAY_A');
              $slugs[] = $a['slug'];
            }

            $product_list = !empty($slugs) ? do_shortcode('[product_category limit="' . $limit . '" category="' . implode(',', $slugs) . '"]') : '';
            break;

          case 'best_selling_products':
            $product_list = do_shortcode('[best_selling_products limit="' . $limit . '"]');
            break;

          case 'recent_products':
            $product_list = do_shortcode('[recent_products limit="' . $limit . '"]');
            break;

          default:
            $product_list = do_shortcode('[products ids="' . $prod_ids . '" limit="' . $limit . '"]');
            break;
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

if (!function_exists('home_content_banner') && class_exists('acf'))
{

  function home_content_banner($content, $banner = null)
  {

    /* ACF Fields variables */

    $banner_title     = get_field('home_banner_title');
    $banner_cotnent   = get_field('home_banner_cotnent');
    $banner_image     = get_field('home_banner_image');
    $banner_link      = get_field('home_banner_link');
    $banner_link_text = get_field('home_banner_link_text');

    if ($banner_cotnent == '' && $banner_image)
    {
      return $content;
    }

    $banner = sprintf('
      <section id="home-banner" style="background-image:url(\'%s\')">
        <div class="container">%s%s%s</div>
      </section>
      ',
      $banner_image && function_exists('image_array') ? image_array($banner_image, '', 1999, 500, false)['url'] : '',
      $banner_title ? '<h3 class="banner-title">' . $banner_title . '</h3>' : '',
      $banner_cotnent,
      $banner_link ? '<div class="banner-link"><a href="' . esc_url($banner_link) . '">' . ($banner_link_text ? $banner_link_text : __('DISCOVER MORE', 'TEXT_DOMAIN')) . '</a></div>' : ''
    );

    return $content . $banner;
  }
}

if (!function_exists('content_container_wrapper'))
{

  function content_container_wrapper($content)
  {
    return !is_front_page() || !is_product_category() ? '<div class="container">' . $content . '</div>' : $content;
  }
}
