<?php
/* Remove direct access */
if (!defined('ABSPATH'))
{
  exit; // Exit if accessed directly
}

/*  ********************************************************
 *   Create website navigations
 *  ********************************************************
 */

function ats_generate_navigation() {
  // Register header, main and footer menus
  register_nav_menu('header', esc_html__('Top menu', 'TEXT_DOMAIN'));
  register_nav_menu('main', esc_html__('Main menu', 'TEXT_DOMAIN'));
  register_nav_menu('footer', esc_html__('Footer menu', 'TEXT_DOMAIN'));
}

add_action('after_setup_theme', 'ats_generate_navigation', 12);
/*  ********************************************************
 *   Replace classes required for dropdown navigation
 *  ********************************************************
 */

function change_submenu_class($menu) {
  $menu = preg_replace('/ class="sub-menu"/','/ class="nav-dropdown" /',$menu);
  $menu = str_replace('current-menu-item','current-menu-item active',$menu);
  $menu = str_replace('menu-item-type-post_type','',$menu);
  $menu = str_replace('menu-item-object-page','',$menu);
  return $menu;
}
add_filter('wp_nav_menu','change_submenu_class');

/*  ********************************************************
 *   Add Extra Item to top navigation
 *  ********************************************************
 */
add_filter( 'wp_nav_menu_items', 'add_login_url', 10, 2 );
function add_login_url( $items, $args ) {
    /**
     * If menu primary menu is set & user is logged in.
     */
    if ( is_user_logged_in() && $args->theme_location == 'header' ) {
      $items .= '<li id="login-tab"><a class="log-in" href="'. wp_logout_url() .'"><i class="icon-user"></i>'. esc_html( 'Log Out', 'TEXT_DOMAIN').'</a></li>';
    }
    /**
     * Else display login menu item.
     */
    elseif ( !is_user_logged_in() && $args->theme_location == 'header' ) {
      $items .= '<li id="login-tab"><a class="log-in" href="'. site_url('wp-login.php') .'"><i class="icon-user"></i><span>'. esc_html( 'Log In', 'TEXT_DOMAIN').'</span></a></li>';
    }
    return $items;
  }

  add_filter('nav_menu_css_class', 'special_nav_class', 10, 2);
  function special_nav_class($classes, $item){
    if(($key = array_search('fa', $classes)) !== false) {
      unset($classes[$key]);
    }
    return $classes;
  }