<?php
/*  ********************************************************
 *   Single product image
 *  ********************************************************
 */

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
      $attachment      = get_post($image_id);
      $full_size_image = $attachment->guid;
      $image           = image_array($image_id, '', $image_width / 4.1, $image_height / 4.1, true);

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
    $image           = image_figure($single_image_id, '', $image_width, $image_height, $image_crop);
    $attachment      = get_post($single_image_id);
    $full_size_image = $attachment->guid;
    $caption         = $attachment->post_excerpt;
    $gallery .= '<div class="single-image">';
    $gallery .= '<a href="' . $full_size_image . '" data-lightbox="image-1" data-title="' . $caption . '">';
    $gallery .= $image;
    $gallery .= '</a>';
    $gallery .= '</div>';
  }

  $gallery .= '</div>';
  return $gallery;
}