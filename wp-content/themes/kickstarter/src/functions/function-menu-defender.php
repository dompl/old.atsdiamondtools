<?php
/*  ********************************************************
 *   Remove unwanted stuff from admin nav
 *  ********************************************************
 */

if (!defined('ABSPATH'))
{
  exit; // Exit if accessed directly
}

if (!in_array(get_current_user_id(), array(1)))
{
  add_filter('acf/settings/show_admin', '__return_false');
}

// Display all menu pages in the admin
// add_action( 'admin_init', 'the_dramatist_debug_admin_menu' );
// function the_dramatist_debug_admin_menu() {

//     echo '<pre>' . print_r( $GLOBALS[ 'menu' ], TRUE) . '</pre>';
// }

add_action('admin_init', 'my_remove_menu_pages');

function my_remove_menu_pages()
{

  if (!in_array(get_current_user_id(), array(1)))
  {

    /* Visual Composer */
    if (is_admin())
    {
      setcookie('vchideactivationmsg', '1', strtotime('+3 years'), '/');
      setcookie('vchideactivationmsg_vc11', (defined('WPB_VC_VERSION') ? WPB_VC_VERSION : '1'), strtotime('+3 years'), '/');
    }
    remove_menu_page('vc-general');
    remove_menu_page('aws-options');

    /* Comments */
    remove_menu_page('edit-comments.php');

    // Hummingbird
    remove_menu_page('wphb');
    // Ag License
    remove_menu_page('AG_licence');
    remove_menu_page('AG_plugins');

    /* Tools */
    remove_menu_page('tools.php');
    remove_menu_page('edit.php');

    /* Plugins */
    remove_menu_page('plugins.php');
    // remove_menu_page('woocommerce');

    remove_submenu_page('themes.php', 'themes.php');
    remove_submenu_page('themes.php', 'customize.php');
    remove_submenu_page('themes.php', 'theme-editor.php');


    /* Plugins */
    remove_submenu_page('plugins.php', 'plugin-install.php');
    remove_submenu_page('plugins.php', 'plugin-editor.php');

    remove_submenu_page('options-general.php', 'options-discussion.php');
    remove_submenu_page('options-general.php', 'options-reading.php');
    remove_submenu_page('options-general.php', 'options-media.php');

    remove_submenu_page('options-general.php', 'cms-tpv-options');

    /* Gravity Forms */
    remove_submenu_page('gf_edit_forms', 'gf_settings');
    remove_submenu_page('gf_edit_forms', 'gf_addons');
    remove_submenu_page('gf_edit_forms', 'gf_system_status');
    remove_submenu_page('gf_edit_forms', 'gf_help');
    remove_submenu_page('gf_edit_forms', 'gf_export');
    remove_submenu_page('gf_edit_forms', 'gf_new_form');

    remove_submenu_page('woocommerce', 'wc-ncr');
    // remove_menu_page( 'admin.php?page=wc-ncr' );

    /* Core updates */
    remove_submenu_page('index.php', 'update-core.php');

    /* Dashboard items */
    remove_meta_box('dashboard_incoming_links', 'dashboard', 'normal');
    remove_meta_box('dashboard_plugins', 'dashboard', 'normal');
    remove_meta_box('dashboard_primary', 'dashboard', 'side');
    remove_meta_box('dashboard_secondary', 'dashboard', 'normal');
    remove_meta_box('dashboard_quick_press', 'dashboard', 'side');
    remove_meta_box('dashboard_recent_drafts', 'dashboard', 'side');
    remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
    remove_meta_box('dashboard_right_now', 'dashboard', 'normal');
    remove_meta_box('dashboard_activity', 'dashboard', 'normal');       //since 3.8
    remove_meta_box('wpseo-dashboard-overview', 'dashboard', 'normal'); // Yoast
    remove_meta_box('rg_forms_dashboard', 'dashboard', 'side');         // GF

  }

}