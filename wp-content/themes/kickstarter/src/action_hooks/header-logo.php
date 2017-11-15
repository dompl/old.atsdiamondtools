<?php
/*  ********************************************************
 *   Main logo
 *  ********************************************************
 */

add_action('logo', 'main_logo', 10, 1);

function main_logo($container, $width='', $height='')
{
  if (function_exists('image_figure'))
  {

    $width    = $width ? $width : 55;   // Logo height
    $height    = $height ? $height : 312;   // Logo height
    $crop     = false; // Logo crop
    $quality  = 100;   // Image quality
    $retina   = true;  // Display retina size
    $figure   = true;  // Display figure wrapper
    $lazy     = false; // Displat lazy load
    $logo_url = get_template_directory_uri() . '/img/theme/logo.png';

    $img = is_front_page() ? '<a href="' . esc_url(home_url('/')) . '" rel="home">' : '';

    /**
     * Image
     * $attach_id, $img_url, $width, $height, $crop, $quality, $retina, $figure, $lazy
     * ---
     */

    $img .= image_figure('', $logo_url, $height, $width, $crop, $quality, $retina, $figure, $lazy);

    $img .= is_front_page() ? '</a>' : '';

    /* HTML FROM HERE */
    echo $container ? '<div class="container">' : '';
    echo '<div class="logo-contaner">'.$img.'</div>';
    echo $container ? '</div>' : '';
  }
}
