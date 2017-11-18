<?php
/*  ********************************************************
 *   Footer navigation
 *  ********************************************************
 */
add_action('footer_navigation', 'footer_navigation_');

if (!function_exists('footer_navigation_'))
{
  function footer_navigation_()
  {
    /* ACF Filed */
    $pages = get_field('footer_navigation', 'options');

    if (is_array($pages) && !empty($pages))
    {
      echo '<div class="bottom-nav-container"><ul class="bottom-nav">';
      foreach ($pages as $page)
      {
        // @var $pages returns URL to we need to convert it to ID
        $page_id = url_to_postid( $page );
        echo '<li><a href="' . get_the_permalink($page_id) . '">' . esc_html(get_the_title($page_id)) . '</a></li>';
      }
      echo '</ul>' . "\n\t\t" . '</div>';
    }
  }
}
