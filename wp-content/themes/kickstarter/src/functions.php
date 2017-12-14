<?php // ==== FUNCTIONS ==== //
/* Remove direct access */
if (!defined('ABSPATH'))
{
  exit; // Exit if accessed directly
}

/*  ********************************************************
 *   Load functions files
 *  ********************************************************
 */
function load_files($folder)
{
  if (!$folder)
  {
    return;
  }
  $directory = get_stylesheet_directory() . '/' . $folder . '/*.php';
  foreach (glob($directory) as $element)
  {
    require_once $element;
  }
}

/* Load Functions */
load_files('functions');

/* Load all files from visual composer */
if (defined('WPB_VC_VERSION'))
{
  load_files('functions/visual_composer');
}

/* Load all other assets */
load_files('inc');

/* Load action hooks */
load_files('functions/hooks');

/* Shortcodes */
load_files('functions/shortcodes');

/* AACF */
load_files('functions/acf');


/* Check if Visual Composer is installed */
if (defined('WPB_VC_VERSION'))
{
  load_files('functions/visual_composer');
}

/* Insall all woocmmerce files */
if (class_exists('WooCommerce'))
{
  load_files('functions/woocommerce');
  load_files('functions/woocommerce/single');
  load_files('functions/woocommerce/checkout');
}

// Only the bare minimum to get the theme up and running
function voidx_setup()
{

  // HTML5 support; mainly here to get rid of some nasty default styling that WordPress used to inject
  add_theme_support('html5', array('search-form', 'gallery'));

  // Automatic feed links
  // add_theme_support( 'automatic-feed-links' );

  // $content_width limits the size of the largest image size available via the media uploader
  // It should be set once and left alone apart from that; don't do anything fancy with it; it is part of WordPress core
  global $content_width;
  $content_width = 1140;

}
add_action('after_setup_theme', 'voidx_setup', 11);

// Sidebar declaration
function voidx_widgets_init()
{
  register_sidebar(
    array(
      'name'          => esc_html__('Footer', 'TEXT_DOMAIN'),
      'id'            => 'sidebar-footer',
      'description'   => esc_html__('Appear at the bottom of each page', 'TEXT_DOMAIN'),
      'before_widget' => '<div class="footer-item">',
      'after_widget'  => '</div>',
      'before_title'  => '<h3>',
      'after_title'   => '</h3>',
    ));
}
add_action('widgets_init', 'voidx_widgets_init');

/*  ********************************************************
 *   Get attachement data
 *  ********************************************************
 */

function wp_get_attachment($attachment_id = null, $attachment_size = null)
{

  if (!$attachment_id)
  {
    return;
  }

  $attachment = get_post($attachment_id);
  $src        = $attachment_size ? wp_get_attachment_image_src($attachment_id, $attachment_size)[0] : $attachment->guid;
  return array(
    'alt'         => get_post_meta($attachment->ID, '_wp_attachment_image_alt', true),
    'caption'     => $attachment->post_excerpt,
    'description' => $attachment->post_content,
    'href'        => get_permalink($attachment->ID),
    'src'         => $src,
    'title'       => $attachment->post_title,
    'guid'        => $attachment->guid,
  );
}

add_filter( 'woocommerce_redirect_single_search_result', '__return_false' );
function my_maybe_woocommerce_variation_permalink( $permalink ) {

  // check to see if the search was for a product variation SKU
  $sku = get_search_query();
  $args = array(
    'post_type'       => 'product_variation',
    'posts_per_page'  => 1,
    'fields'          => 'ids',
    'meta_query'      => array(
      array(
        'key'     => '_sku',
        'value'   => $sku,
      ),
    ),
  );
  $variation = get_posts( $args );
  // make sure the permalink we're filtering is for the parent product
  if ( get_permalink( wp_get_post_parent_id( $variation[0] ) ) !== $permalink ) {
    return $permalink;
  }
  if ( ! empty( $variation ) && function_exists( 'wc_get_attribute_taxonomy_names' ) ) {
    // this is a variation SKU, we need to prepopulate the filters
    $variation_id = absint( $variation[0] );
    $variation_obj = new WC_Product_Variation( $variation_id );
    $attributes = $variation_obj->get_variation_attributes();
    if ( empty( $attributes ) ) {
      return $permalink;
    }
    $permalink = add_query_arg( $attributes, $permalink );
  }
  return $permalink;
}
add_filter( 'the_permalink', 'my_maybe_woocommerce_variation_permalink' );