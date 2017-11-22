<?php
/*  ********************************************************
 *   Global page header
 *  ********************************************************
 */

add_action('page_header', 'page_header_', 10, 1);

function ats_acf_banner_helper($post_id)
{

  $class       = ' has-image';
  $banner_id   = '';
  $banner_type = get_field('banner_set_image', $post_id); // none :: custom :: featured

  /* If banner is set to use no image */
  if ($banner_type === 'none')
  {
    $class = ' no-image';
  }
  /* If banner is set to use custom image */

  elseif ($banner_type === 'custom')
  {
    $banner_id = get_field('banner_set_image', $post_id);
  }
  /* If banner is set to use featured image */

  elseif ($banner_type === 'featured')
  {
    $banner_id = get_post_thumbnail_id($shop_pid);
  }

  return $banner_id;
}

function page_header_($thumnail_id)
{

  global $wp_query;

  $image_widht  = 800;
  $image_height = 300;

  $page_title        = '';
  $page_breadcrumbs  = '';
  $page_image        = '';
  $page_image_retina = '';

  $page_description = '';

  $header_classes = '';

  /* Get header values for wooocomrece */

  if (is_product_category())
  {

    $wp_category = $wp_query->get_queried_object();

    $thumnail_id = get_woocommerce_term_meta($wp_category->term_id, 'thumbnail_id', true);

    if ($thumnail_id)
    {

      $page_image        = image_array($thumnail_id, '', $image_widht, $image_height)['url'];
      $page_image_retina = image_array($thumnail_id, '', $image_widht * 2, $image_height * 2)['url'];
    }
    // Description
    ob_start();
    woocommerce_taxonomy_archive_description();
    $page_description = ob_get_contents();
    ob_end_clean();

    // Breadcrumbs
    ob_start();
    woocommerce_breadcrumb();
    $page_breadcrumbs = ob_get_contents();
    ob_end_clean();

    // Get page title if is active for woocommerce
    if (apply_filters('woocommerce_show_page_title', true))
    {
      ob_start();
      woocommerce_page_title();
      $page_title = ob_get_contents();
      ob_end_clean();
    }

    // Add extra class if is product category
    $header_classes .= 'is-category';

  }
  /**
   * Header for pages other then page categories
   * ---
   */
  else
  {

    $pid         = is_shop() ? get_option('woocommerce_shop_page_id') : get_the_ID();
    $page_title  = get_the_title($pid);
    $banner_type = get_field('banner_background', $pid); // none :: custom :: featured
    $thumnail_id = '';

    if ((has_post_thumbnail($pid) && $banner_type === 'featured') || ($banner_type === 'custom' && get_field('banner_set_image', $pid)))
    {
      if ($banner_type === 'featured')
      {
        $thumnail_id = get_post_thumbnail_id($pid);
      }
      else
      {
        $thumnail_id = get_field('banner_set_image', $pid);
      }

      $page_image        = image_array($thumnail_id, '', $image_widht, $image_height)['url'];
      $page_image_retina = image_array($thumnail_id, '', $image_widht * 2, $image_height * 2)['url'];
    }

    /* Breadcrumbs */
    ob_start();
    woocommerce_breadcrumb();
    $page_breadcrumbs = ob_get_contents();
    ob_end_clean();
  }

// If is single product
  if (is_product())
  {
    ob_start();
    woocommerce_breadcrumb();
    $page_breadcrumbs = ob_get_contents();
    ob_end_clean();
  }

// Chack in classes to the header - for css use
  $header_classes .= $thumnail_id ? ' has-image' : ' no-image';
  $header_classes .= $page_description ? ' has-description' : '';
  $header_classes .= $page_breadcrumbs ? ' has-breadcrumb' : '';

// Start container
  $page_header = '<header id="page-header" class="clx ' . $header_classes . '"><div class="container">';

// Page Title
  if ($page_title != '')
  {

    $page_header .= '<h1>' . $page_title . '</h1>';

  }
// Description
  if ($page_description)
  {
    $page_header .= '<div class="header-description">' . $page_description . '</div>';
  }

// Breadcrumbs
  if ($page_breadcrumbs != '')
  {
    $page_header .= '<div class="page-breadcrumbs">';
    $page_header .= $page_breadcrumbs;
    $page_header .= '</div>';
  }
// End container
  $page_header .= '</div></header>';

  if ($page_image != '')
  {
    $page_header .= backstretch($page_image, null);
  }

  echo $page_header;

};

/**
 * Header changes for woocommerce
 * ---
 */
add_action('template_redirect', 'change_header_layout_');

function change_header_layout_()
{

  // Remove breadcrumbs
  remove_action('woocommerce_before_main_content', 'woocommerce_breadcrumb', 20);

  // Remove taxonomy description
  remove_action('woocommerce_archive_description', 'woocommerce_taxonomy_archive_description', 10);

  // Remove archive description
  remove_action('woocommerce_archive_description', 'woocommerce_product_archive_description', 10);

}
