<?php
/*  ********************************************************
 *   Global Woocommerce settings
 *  ********************************************************
 */

/* Theme Integration */
add_theme_support('woocommerce');

/* Remove all stylesheets */

add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );