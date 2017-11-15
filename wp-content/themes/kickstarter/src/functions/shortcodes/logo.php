<?php
/*  ********************************************************
 *   Logo shortcode
 *  ********************************************************
 */

/* Make textarea do shortcode */
add_filter('widget_text','do_shortcode');

add_shortcode('logo', 'logo_shortcode_function');

function logo_shortcode_function() {
  return main_logo(false, 55, 312);
}
