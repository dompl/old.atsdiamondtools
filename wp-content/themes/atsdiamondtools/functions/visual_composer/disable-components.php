<?php
/*  ********************************************************
 *   List of elements to be removed from Visual Composer
 *  ********************************************************
 */

/* Disable front end editor */
vc_disable_frontend();

/* Remove front end link */
function vc_remove_frontend_links()
{
  vc_disable_frontend(); // this will disable frontend editor
}
add_action('vc_after_init', 'vc_remove_frontend_links');

/* Remove all poitners on init */
add_action( 'vc_before_init', 'vc_remove_all_pointers' );
function vc_remove_all_pointers() {
 remove_action( 'admin_enqueue_scripts', 'vc_pointer_load' );
}

$elements_to_remove = array(
  'vc_wp_rss',
  'vc_wp_archives',
  'vc_wp_categories',
  'vc_wp_text',
  'vc_wp_posts',
  'vc_wp_custommenu',
  'vc_wp_tagcloud',
  'vc_wp_pages',
  'vc_wp_calendar',
  'vc_wp_recentcomments',
  'vc_wp_meta',
  'vc_wp_search',
  'vc_empty_space',
  'vc_line_chart',
  'vc_round_chart',
  'vc_progress_bar',
  'vc_masonry_media_grid',
  'vc_masonry_grid',
  'vc_basic_grid',
  'vc_flickr',
  'vc_widget_sidebar',
  'vc_pie',
  'vc_media_grid',
  'vc_acf',
  'vc_gmaps',
  'vc_tta_tour',
  'vc_tta_accordion',
  'vc_tta_pageable',
  // 'vc_custom_heading',
  'vc_btn',
  'vc_btn',
  'vc_cta',
  'vc_tta_tabs',
  'vc_images_carousel',
  'vc_gallery',
  'vc_toggle',
  'vc_pinterest',
  'vc_googleplus',
  'vc_icon',
  'vc_separator',
  'vc_zigzag',
  'vc_message',
  'vc_tweetmeme',
  'vc_posts_slider',
  'vc_video',
  'vc_facebook',
  'vc_hoverbox',
  'vc_text_separator',
  'vc_tabs',
  'vc_tour',
  'vc_accordion',
);

foreach ($elements_to_remove as $element_to_remove)
{
  vc_remove_element($element_to_remove);
}

/* Remove Woocommerce Elements */

function remove_woocommerce_elements() {
  if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
    vc_remove_element( 'woocommerce_cart' );
    vc_remove_element( 'woocommerce_checkout' );
    vc_remove_element( 'woocommerce_my_account' );
    vc_remove_element( 'woocommerce_order_tracking' );
    vc_remove_element( 'recent_products' );
    vc_remove_element( 'product' );
    vc_remove_element( 'products' );
    vc_remove_element( 'add_to_cart_url' );
    vc_remove_element( 'featured_products' );
    vc_remove_element( 'product_page' );
    vc_remove_element( 'add_to_cart' );
    vc_remove_element( 'product_attribute' );
    vc_remove_element( 'top_rated_products' );
    vc_remove_element( 'best_selling_products' );
    vc_remove_element( 'sale_products' );
    vc_remove_element( 'add_to_cart_url' );
    vc_remove_element( 'product_category' );
    vc_remove_element( 'product_categories' );

  }
}
// Hook for admin editor.
add_action( 'vc_build_admin_page', 'remove_woocommerce_elements', 11 );
// Hook for frontend editor.
add_action( 'vc_load_shortcode', 'remove_woocommerce_elements', 11 );