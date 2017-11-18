<?php
/*  ********************************************************
 *   Single product
 *  ********************************************************
 */
/* Product Image */
function ats_single_product_image_gallery()
{

  global $product, $post;

  /* Product Variables */
  $id              = $post->ID;                                  // Product ID
  $image_ids       = $product->get_gallery_image_ids($post->ID); // Product image gallery IDs
  $single_image_id = $product->get_image_id($post->ID);          // Product single image ID

  $large_image = '';
  $thumb_image = '';

  /* Image Sizes */
  $image_width  = get_option('shop_single_image_size')['width'];  // Image width
  $image_height = get_option('shop_single_image_size')['height']; // Image height
  $image_crop   = get_option('shop_single_image_size')['crop'];   // Image crop

  $gallery = '<div id="ats-product-images">';

  if (!empty($image_ids))
  {

    $gallery .= '<div id="product__slider">';

    /* Main gallery images */
    $gallery = '<div class="product__slider-main">';
    foreach ($image_ids as $image_id)
    {
      $image = image_figure($image_id, '', $image_width, $image_height, $image_crop);

      $attachment      = get_post($image_id);
      $full_size_image = $attachment->guid;
      $caption         = $attachment->post_excerpt;
      $description     = $attachment->post_content;
      $alt             = get_post_meta($attachment->ID, '_wp_attachment_image_alt', true);
      $title           = $attachment->post_title;

      $gallery .= '<div class="slide">';
      $gallery .= '<a href="' . $full_size_image . '" data-lightbox="image-1" data-title="' . $caption . '">';
      $gallery .= $image;
      $gallery .= '</a>';
      $gallery .= '</div>';
    }
    $gallery .= '</div>';

    /* Tbumbnails */
    $gallery .= '<div class="product__slider-thmb">';
    foreach ($image_ids as $image_id)
    {
      $image = image_array($image_id, '', $image_width / 4.1, $image_height / 4.1, true);

      $gallery .= '<div class="slide">';
      $gallery .= '<img src="' . $image['url'] . '" alt="' . $image['alt'] . '" class="img-responsive">';
      $gallery .= '</div>';
    }
    $gallery .= '</div>';

    $gallery .= '</div>';
  }
  elseif ($single_image_id != '')
    /* If is sinlge image */
  {
    $image = image_figure($single_image_id, '', $image_width, $image_height, $image_crop);

    $gallery .= '<div class="single-image">';
    $gallery .= $image;
    $gallery .= '</div>';
  }

  $gallery .= '</div>';
  return $gallery;
}

function ats_single_product_()
{
  wp_enqueue_script('single-product', get_template_directory_uri() . '/js/x-single-product.js', array('jquery'), false, true);
  echo ats_single_product_image_gallery();
}

add_action('template_redirect', 'ats_setup_signle_product');
function ats_setup_signle_product()
{
  /* Set the product */
  add_action('woocommerce_before_single_product_summary', 'ats_single_product_');
  /* Remove all acttions */
  remove_action('woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10);
  remove_action('woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20);

  // remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title',  5 );
  // remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );
  // remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
  // remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );
  // remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
  // remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
  // remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_sharing', 50 );

  // remove_action('woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10);
  // remove_action('woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15);
  // remove_action('woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20);
}
