<?php
/* Remove direct access */
if (!defined('ABSPATH'))
{
  exit; // Exit if accessed directly
}
/*  ********************************************************
*   Add theme support for images
*  ********************************************************
*/

function custom_thumbs() {

    // Add Thumbnail Theme Support
  add_theme_support('post-thumbnails');
    add_image_size('large', 700, '', true); // Large Thumbnail

    add_image_size('custom-size', 700, 200, true); // Custom Thumbnail Size call using the_post_thumbnail('custom-size');

  }
  add_action( 'after_setup_theme', 'custom_thumbs' );

// Add image sizes to WP admin
  function add_image_sizes_to_admin($sizes) {
    $addsizes = array(
      "custom-size" => __( "Custom Size")
    );
    $newsizes = array_merge($sizes, $addsizes);
    return $newsizes;
  }
  add_filter('image_size_names_choose', 'add_image_sizes_to_admin');