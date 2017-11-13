<?php
/*  ********************************************************
 *   Main Navigation function
 *  ********************************************************
 */
add_action('navigation', 'header_navigation', 10, 2);

function header_navigation($responsive_from, $container)
{
  echo $container === true ? '<div class="container">' : '';
  ?>
  <nav id="navigation" class="navigation" data-breakpoint="<?php echo $responsive_from ? $responsive_from : '768' ?>">
    <div class="nav-header">
      <div class="nav-toggle"></div>
    </div>
    <div class="nav-menus-wrapper">
      <?php wp_nav_menu(array('theme_location' => 'main', 'menu_id' => 'menu-header', 'menu_class' => 'nav-menu align-to-left'));?>
    </div>
  </nav>
  <?php
  echo $container === true ? '</div>' : '';
}

/* Top Navigation */
add_action('top', 'top_navigation', 10);
function top_navigation()
{
  ?>
  <div class="left">
    <nav id="top-navigation" class="navigation" data-breakpoint="<?php echo $responsive_from ? $responsive_from : '768' ?>">
      <div class="nav-header">
        <div class="nav-toggle"></div>
      </div>
      <div class="nav-menus-wrapper">
        <?php wp_nav_menu(array('theme_location' => 'header', 'menu_id' => 'menu-header', 'menu_class' => 'nav-menu align-to-left'));?>
      </div>
    </nav>
  </div>
  <?php }

  /* Top collereals */
  add_action('top', 'top_colleterals', 15);
  function top_colleterals()
  {
    $email     = acf_fb(get_field('header_email', 'options'), 'info@atsdiamondtools.co.uk');
    $telephone = acf_fb(get_field('header_phone', 'options'), '0203 130 1720');
    echo '<div class="right">';

  // If tel number is present
    if ($email) {
      echo '<div class="email clx"><a href="mailto:' . antispambot($email) . '"><span><i class="icon-envelope"></i></span>' . antispambot($email) . '</a></div>';
    }

    if ($telephone) {
      echo '<div class="tel-number">';
      echo '<span class="desktop clx"><span><i class="icon-phone"></i></span>'.esc_html($telephone).'</span>';
      echo '<span class="mobile clx"><a href="tel:'.str_replace(' ', '', $telephone) .'"><span><i class="icon-phone"></i></span></a></span>';
      echo '</div>';
   }
    echo '</div>';
  }
