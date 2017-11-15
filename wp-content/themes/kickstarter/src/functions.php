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
load_files('action_hooks');

/* Check if Visual Composer is installed */
if (defined('WPB_VC_VERSION'))
{
  load_files('functions/visual_composer');
}

/* Insall all woocmmerce files */
if (class_exists('WooCommerce'))
{
  load_files('functions/woocommerce');
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

  // Register header and footer menus
  register_nav_menu('header', esc_html__('Top menu', 'TEXT_DOMAIN'));
  register_nav_menu('main', esc_html__('Main menu', 'TEXT_DOMAIN'));
  register_nav_menu('footer', esc_html__('Footer menu', 'TEXT_DOMAIN'));

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