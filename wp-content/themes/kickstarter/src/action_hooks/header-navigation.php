<?php
/*  ********************************************************
 *   Main Navigation function
 *  ********************************************************
 */
add_action('navigation', 'header_navigation', 10, 2);

function header_navigation( $responsive_from, $container )
{
  echo $container === true ? '<div class="container">':'';
  ?>
  <nav id="navigation" class="navigation" data-breakpoint="<?php echo $responsive_from ? $responsive_from : '768' ?>">
    <div class="nav-header">
      <div class="nav-toggle"></div>
    </div>
    <div class="nav-menus-wrapper">
      <?php wp_nav_menu(array('theme_location' => 'header', 'menu_id' => 'menu-header', 'menu_class' => 'nav-menu align-to-left'));?>
    </div>
  </nav>
  <?php
  echo $container === true ? '</div>':'';
}
