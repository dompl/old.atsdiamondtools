<?php // ==== FUNCTIONS ==== //
/* Remove direct access */
if (!defined('ABSPATH'))
{
  exit; // Exit if accessed directly
}
$functions = array(
  'function-wp-admin.php',              // Admin Settings for WP
  'functions-recaptcha-wplogin.php',    // Ad re-captach on Login form
  'functions-config-defaults.php',      // Load configuration defaults for this theme; anything not set in the custom configuration (above) will be set here
  'functions-config.php',               // Load the configuration file for this installation; all options are set here
  'functions-navigation.php',           // Additionals setting for navigation
  'functions-custom-image-sizes.php',   // Set your WP Image sizes
  'functions-acf.php',                  // Set your WP Image sizes
  'functions-tinymc-classes.php',      // Added TinyMC
);

foreach ($functions as $file) {
  if ( is_readable( trailingslashit( get_stylesheet_directory() ) . 'functions/' . $file ) )
    require_once( trailingslashit( get_stylesheet_directory() ) . 'functions/' . $file );
}

/* Check if Visual Composer is installed */
if (defined('WPB_VC_VERSION')) {
  $visual_coposers = array(
  'disable-components.php',   // Responsive spacer
  'resposive-spacer.php',   // Responsive spacer
  'custom-heading.php',    // Custom Heading
  'global-params.php',    // Global aparameters
);

  foreach ($visual_coposers as $vc) {
    if ( is_readable( trailingslashit( get_stylesheet_directory() ) . 'functions/visual_composer/' . $vc ) )
      require_once( trailingslashit( get_stylesheet_directory() ) . 'functions/visual_composer/' . $vc );
  }
}

$requireds = array(
  'assets.php',                       // An example of how to manage loading front-end assets (scripts, styles, and fonts)
  'page-navigation.php',              // Required to demonstrate WP AJAX Page Loader (as WordPress doesn't ship with even simple post navigation functions)
);

foreach ($requireds as $file) {
  require_once( trailingslashit( get_stylesheet_directory() ) . 'inc/' . $file );
}

// Only the bare minimum to get the theme up and running
function voidx_setup() {

  // HTML5 support; mainly here to get rid of some nasty default styling that WordPress used to inject
  add_theme_support( 'html5', array( 'search-form', 'gallery' ) );

  // Automatic feed links
  // add_theme_support( 'automatic-feed-links' );

  // $content_width limits the size of the largest image size available via the media uploader
  // It should be set once and left alone apart from that; don't do anything fancy with it; it is part of WordPress core
  global $content_width;
  $content_width = 960;

  // Register header and footer menus
  register_nav_menu( 'header', esc_html__( 'Header menu', TEXT_DOMAIN ) );
  register_nav_menu( 'footer', esc_html__( 'Footer menu', TEXT_DOMAIN ) );

}
add_action( 'after_setup_theme', 'voidx_setup', 11 );

// Sidebar declaration
function voidx_widgets_init() {
  register_sidebar( array(
    'name'          => esc_html__( 'Main sidebar', TEXT_DOMAIN ),
    'id'            => 'sidebar-main',
    'description'   => esc_html__( 'Appears to the right side of most posts and pages.', TEXT_DOMAIN ),
    'before_widget' => '<aside id="%1$s" class="widget %2$s">',
    'after_widget'  => '</aside>',
    'before_title'  => '<h2>',
    'after_title'   => '</h2>'
  ) );
}
add_action( 'widgets_init', 'voidx_widgets_init' );

/*  ********************************************************
 *   Get attachement data
 *  ********************************************************
 */

function wp_get_attachment( $attachment_id=null, $attachment_size=null ) {

  if ( !$attachment_id ) return;
  $attachment = get_post( $attachment_id );
  $src        = $attachment_size ? wp_get_attachment_image_src(  $attachment_id, $attachment_size )[0] : $attachment->guid;
  return array(
    'alt'         => get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true ),
    'caption'     => $attachment->post_excerpt,
    'description' => $attachment->post_content,
    'href'        => get_permalink( $attachment->ID ),
    'src'         => $src,
    'title'       => $attachment->post_title,
    'guid'        => $attachment->guid,
  );
}