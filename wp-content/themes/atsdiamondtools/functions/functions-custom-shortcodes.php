<?php
/*  ********************************************************
 *   Random shortcodes
 *  ********************************************************
 */

/* Button */

if (!function_exists('button_function')) {
  function button_function($atts) {

    extract(shortcode_atts(array(
      'text'      => '',
      'link'      => '',
    ), $atts));

    if (!$text || !$link) :
      return;
    else :
      $text = esc_html($text);
      $link = esc_url($link);

      $button = '';
      $button .= '<a class="shortcode-button" href="' . $link . '">' . $text . '</a>';

      return $button;
    endif;
  }
}

add_shortcode('button', 'button_function');