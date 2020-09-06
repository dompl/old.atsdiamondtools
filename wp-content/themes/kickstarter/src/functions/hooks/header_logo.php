<?php
/*  ********************************************************
 *   Main logo
 *  ********************************************************
 */

add_action( 'logo', 'main_logo', 10, 1 );

/**
 * @param $container
 * @param $width
 * @param $height
 */
function main_logo( $container, $width = '', $height = '' )
{
    $img = !  is_front_page() ? '<a href="' . esc_url( home_url( '/' ) ) . '" rel="home">' : '';
    $img .= '<img src="' . get_template_directory_uri() . '/img/theme/logo-new.png' . '" alt="' . get_bloginfo( 'name' ) . ' - ' . get_bloginfo( 'description' ) . '">';
    $img .= !  is_front_page() ? '</a>' : '';

    /* HTML FROM HERE */
    echo $container ? '<div class="container">' : '';
    echo '<div class="logo-container">' . $img . '</div>';
    echo $container ? '</div>' : '';
}