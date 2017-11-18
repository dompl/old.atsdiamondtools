<?php
/* Remove direct access */
if (!defined('ABSPATH'))
{
  exit; // Exit if accessed directly
}
/*  ********************************************************
 *   Advanced Custom Fields Functions
 *  ********************************************************
 */

/* Check if the plugin is actice */

if (class_exists('acf'))
{

  /* Remove ACF admin for other users then ID 1 */
  get_current_user_id() != 1 ? add_filter('acf/settings/show_admin', '__return_false') : '';

  /* Add Options Page */
  $parent_args = array(

    /* (string) The title displayed on the options page. Required. */
    'page_title'  => 'Options',

    /* (string) The title displayed in the wp-admin sidebar. Defaults to page_title */
    'menu_title'  => 'Options',

    /* (string) The slug name to refer to this menu by (should be unique for this menu).
    Defaults to a url friendly version of menu_slug */
    'menu_slug'   => 'theme-options',

    /* (string) The capability required for this menu to be displayed to the user. Defaults to edit_posts.
    Read more about capability here: http://codex.wordpress.org/Roles_and_Capabilities */
    'capability'  => 'edit_posts',

    /* (int|string) The position in the menu order this menu should appear.
    WARNING: if two menu items use the same position attribute, one of the items may be overwritten so that only one item displays!
    Risk of conflict can be reduced by using decimal instead of integer values, e.g. '63.3' instead of 63 (must use quotes).
    Defaults to bottom of utility menu items */
    'position'    => false,

    /* (string) The slug of another WP admin page. if set, this will become a child page. */
    'parent_slug' => '',

    /* (string) The icon class for this menu. Defaults to default WordPress gear.
    Read more about dashicons here: https://developer.wordpress.org/resource/dashicons/ */
    'icon_url'    => 'dashicons-admin-plugins',

    /* (boolean) If set to true, this options page will redirect to the first child page (if a child page exists).
    If set to false, this parent page will appear alongside any child pages. Defaults to true */
    'redirect'    => false,

    /* (int|string) The '$post_id' to save/load data to/from. Can be set to a numeric post ID (123), or a string ('user_2').
    Defaults to 'options'. Added in v5.2.7 */
    'post_id'     => 'options',

    /* (boolean)  Whether to load the option (values saved from this options page) when WordPress starts up.
    Defaults to false. Added in v5.2.8. */
    'autoload'    => false,

  );

  if (function_exists('acf_add_options_page'))
  {
    $option_page = acf_add_options_page($parent_args);
  }

  $args = array(
    'page_title'  => 'Header settings',
    'menu_title'  => 'Header settings',
    'menu_slug'   => 'header-settings',
    'capability'  => 'edit_posts',
    'parent_slug' => $parent_args['menu_slug'],
  );

  if (function_exists('acf_add_options_page'))
  {
    $option_page = acf_add_options_page($args);
  }

  $args = array(
    'page_title'  => 'Footer settings',
    'menu_title'  => 'Footer settings',
    'menu_slug'   => 'footer-options',
    'capability'  => 'edit_posts',
    'parent_slug' => $parent_args['menu_slug'],
  );

  if (function_exists('acf_add_options_page'))
  {
    $option_page = acf_add_options_page($args);
  }

  // add default image setting to ACF image fields
  // let's you select a defualt image
  // this is simply taking advantage of a field setting that already exists

  add_action('acf/render_field_settings/type=image', 'add_default_value_to_image_field');
  function add_default_value_to_image_field($field)
  {
    acf_render_field_setting($field, array(
      'label'        => 'Default Image',
      'instructions' => 'Appears when creating a new post',
      'type'         => 'image',
      'name'         => 'default_value',
    ));
  }
}
