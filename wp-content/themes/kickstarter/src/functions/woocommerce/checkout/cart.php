<?php
/*  ********************************************************
 *   Cart functions
 *  ********************************************************
 */

add_action( 'template_redirect', 'ats_checkout_redirects' );

function ats_checkout_redirects()
{
    add_action( 'woocommerce_after_cart', 'woocommerce_cross_sell_display', 10 );
    remove_action( 'woocommerce_cart_collaterals', 'woocommerce_cross_sell_display', 10 );
}