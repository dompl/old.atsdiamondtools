<?php // ==== CONFIGURATION (CUSTOM) ==== //
/* Remove direct access */
if (!defined('ABSPATH'))
{
  exit; // Exit if accessed directly
}
// Specify custom configuration values in this file; these will override values in `functions-config-defaults.php`
// The general idea here is to allow for themes to be customized for specific installations

/*  ********************************************************
 *   Disable emojis on wordpress
 *  ********************************************************
 */
if ( VOIDX_REMOVE_EMOJIS ) {

  function disable_emojis_tinymce( $plugins ) {
    if ( is_array( $plugins ) ) {
      return array_diff( $plugins, array( 'wpemoji' ) );
    } else {
      return array();
    }
  }

  add_filter( 'tiny_mce_plugins', 'disable_emojis_tinymce' );

  function disable_emojis() {
    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );
    remove_action( 'admin_print_styles', 'print_emoji_styles' );
    remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
    remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
    remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
    add_filter( 'tiny_mce_plugins', 'disable_emojis_tinymce' );
  }
  add_action( 'init', 'disable_emojis' );
}

/*  ********************************************************
 *   Remove Weblog Client Link
 *  ********************************************************
 */
if ( VOIDX_REMOVE_WEBLOG_CLIENT_LINK ) {
  remove_action ('wp_head', 'rsd_link');
}

/*  ********************************************************
 *   Remove WP-Embed from footer
 *  ********************************************************
 */
if ( VOIDX_REMOVE_WP_EMBED ) {
  function my_deregister_scripts(){
    wp_deregister_script( 'wp-embed' );
  }
  add_action( 'wp_footer', 'my_deregister_scripts' );
}

/*  ********************************************************
 *   Remove post/page shortlink
 *  ********************************************************
 */
if ( VOIDX_REMOVE_POST_SHORTLINK ) {
  remove_action( 'wp_head', 'wp_shortlink_wp_head');
}

/*  ********************************************************
 *   Remove WP Generator
 *  ********************************************************
 */
if ( VOIDX_REMOVE_WP_GENERATOR ) {
  remove_action('wp_head', 'wp_generator');
}

/*  ********************************************************
 *   Disable RSS feed from header
 *  ********************************************************
 */
if ( VOIDX_REMOVE_RSS_FEED ) {
  function wpb_disable_feed() {
    wp_die( __('No feed available,please visit our <a href="'. get_bloginfo('url') .'">homepage</a>!') );
  }

  add_action('do_feed', 'wpb_disable_feed', 1);
  add_action('do_feed_rdf', 'wpb_disable_feed', 1);
  add_action('do_feed_rss', 'wpb_disable_feed', 1);
  add_action('do_feed_rss2', 'wpb_disable_feed', 1);
  add_action('do_feed_atom', 'wpb_disable_feed', 1);
  add_action('do_feed_rss2_comments', 'wpb_disable_feed', 1);
  add_action('do_feed_atom_comments', 'wpb_disable_feed', 1);
  remove_action( 'wp_head', 'feed_links_extra', 3 ); // Display the links to the extra feeds such as category feeds
  remove_action( 'wp_head', 'feed_links', 2 ); // Display the links to the general feeds: Post and Comment Feed
}
/*  ********************************************************
 *   Remove  Windows Live Writer Manifest Link
 *  ********************************************************
 */
if (VOIDX_REMOVE_WIN_MANIFEST ) {
  remove_action( 'wp_head', 'wlwmanifest_link');
}
/*  ********************************************************
 *   Remove JSON API links in header html
 *  ********************************************************
 */
if ( VOIDX_REMOVE_HEADER_JSON ) {
  remove_action( 'wp_head', 'rest_output_link_wp_head' );
  remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
  remove_action( 'template_redirect', 'rest_output_link_header', 11, 0 );
}

/*  ********************************************************
 *   Move jQuery to Footer
 *  ********************************************************
 */

if (VOIDX_MOVE_JQUERY_TO_FOOTER ) {
  function wpse_173601_enqueue_scripts() {
    wp_scripts()->add_data( 'jquery', 'group', 1 );
    wp_scripts()->add_data( 'jquery-core', 'group', 1 );
    wp_scripts()->add_data( 'jquery-migrate', 'group', 1 );
  }
  add_action( 'wp_enqueue_scripts', 'wpse_173601_enqueue_scripts' );
}

/*  ********************************************************
 *   Remove the <div> surrounding the dynamic navigation to cleanup markup
 *  ********************************************************
 */
function my_wp_nav_menu_args($args = '') {
  $args['container'] = false;
  return $args;
}
add_filter('wp_nav_menu_args', 'my_wp_nav_menu_args'); // Remove surrounding <div> from WP Navigation


/*  ********************************************************
 *   Remove Injected classes, ID's and Page ID's from Navigation <li> items
 *  ********************************************************
 */
function my_css_attributes_filter($var) {
  return is_array($var) ? array() : '';
}
// add_filter('nav_menu_css_class', 'my_css_attributes_filter', 100, 1); // Remove Navigation <li> injected classes (Commented out by default)
// add_filter('nav_menu_item_id', 'my_css_attributes_filter', 100, 1); // Remove Navigation <li> injected ID (Commented out by default)
// add_filter('page_css_class', 'my_css_attributes_filter', 100, 1); // Remove Navigation <li> Page ID's (Commented out by default)

/*  ********************************************************
 *   Remove invalid rel attribute values in the categorylist
 *  ********************************************************
 */
function remove_category_rel_from_category_list($thelist)
{
  return str_replace('rel="category tag"', 'rel="tag"', $thelist);
}
add_filter('the_category', 'remove_category_rel_from_category_list'); // Remove invalid rel attribute

/*  ********************************************************
 *   Remove the width and height attributes from inserted images
 *  ********************************************************
 */
function remove_width_attribute( $html ) {
 $html = preg_replace( '/(width|height)="\d*"\s/', "", $html );
 return $html;
}
add_filter('post_thumbnail_html', 'remove_width_attribute', 10 ); // Remove width and height dynamic attributes to post images
add_filter('image_send_to_editor', 'remove_width_attribute', 10 ); // Remove width and height dynamic attributes to post images

/*  ********************************************************
 *   Remove thumbnail width and height dimensions that prevent fluid images in the_thumbnail
 *  ********************************************************
 */
function remove_thumbnail_dimensions( $html )
{
  $html = preg_replace('/(width|height)=\"\d*\"\s/', "", $html);
  return $html;
}
add_filter('post_thumbnail_html', 'remove_thumbnail_dimensions', 10); // Remove width and height dynamic attributes to thumbnails

/*  ********************************************************
 *   Remove wp_head() injected Recent Comment styles
 *  ********************************************************
 */

function my_remove_recent_comments_style() {
  global $wp_widget_factory;
  remove_action('wp_head', array(
    $wp_widget_factory->widgets['WP_Widget_Recent_Comments'],
    'recent_comments_style'
  ));
}
add_action('widgets_init', 'my_remove_recent_comments_style'); // Remove inline Recent Comment Styles from wp_head()
