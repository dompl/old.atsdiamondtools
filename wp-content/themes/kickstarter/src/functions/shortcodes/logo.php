<?php
/*  ********************************************************
 *   Logo shortcode
 *  ********************************************************
 */

/* Make textarea do shortcode */
add_filter('widget_text','do_shortcode');

add_shortcode('logo', 'logo_shortcode_function');

function logo_shortcode_function() {
  ob_start();
  main_logo(false, 55, 312);
  $content = ob_get_clean();
  return $content;
}
