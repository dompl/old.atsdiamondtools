<?php
/* Remove direct access */
if (!defined('ABSPATH'))
{
  exit; // Exit if accessed directly
}
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
 *   Add additional classes to the navigation
 *  ********************************************************
 */

// function my_secondary_menu_classes( $classes, $item, $args ) {
//     // Only affect the menu placed in the 'secondary' wp_nav_bar() theme location
//     if ( 'header' === $args->theme_location ) {
//         // Make these items 3-columns wide in Bootstrap
//         $classes[] = 'col-md-3';
//     }
//     return $classes;
// }
// add_filter( 'nav_menu_css_class', 'my_secondary_menu_classes', 10, 3 );