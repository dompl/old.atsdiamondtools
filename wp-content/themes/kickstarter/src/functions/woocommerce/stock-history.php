<?php
/**
 * @snippet       Save & Show Product Stock History
 * @how-to        Get CustomizeWoo.com FREE
 * @author        Rodolfo Melogli
 * @testedwith    WooCommerce 7
 * @donate $9     https://businessbloomer.com/bloomer-armada/
 */

add_action( 'woocommerce_product_before_set_stock', 'bbloomer_historical_stock_product_parent' );
add_action( 'woocommerce_variation_before_set_stock', 'bbloomer_historical_stock_product_parent' );

function bbloomer_historical_stock_product_parent( $product ) {
    $stock_history         = get_post_meta( $product->get_id(), '_stock_history', true ) ? get_post_meta( $product->get_id(), '_stock_history', true ) : array();
    $stock_history[time()] = (int) get_post_meta( $product->get_id(), '_stock', true );
    update_post_meta( $product->get_id(), '_stock_history', $stock_history );
}

add_action( 'add_meta_boxes', 'bbloomer_product_meta_box' );

function bbloomer_product_meta_box() {
    add_meta_box( 'stock_history', 'Stock History', 'bbloomer_display_stock_history', 'product', 'advanced', 'high' );
}

function bbloomer_display_stock_history() {
    global $post;
    $product = wc_get_product( $post->ID );

    if ( $product->get_type() == 'variable' ) {
        foreach ( $product->get_available_variations() as $key ) {
            $products[] = $key['variation_id'];
        }
    } else {
        $products[] = $post->ID;
    }

    foreach ( $products as $product_id ) {
        $product = wc_get_product( $product_id );
        echo '<div style="margin-bottom:-10px">' . $product->get_name() . '</div>';
        $stock_history = get_post_meta( $product_id, '_stock_history', true );
        if ( $stock_history ) {
            foreach ( $stock_history as $timestamp => $stockvalue ) {
                if (  !  $stockvalue ) {
                    continue;
                }

                echo '<p>' . date( DATE_COOKIE, $timestamp ) . ': <b>' . $stockvalue . '</b></p>';
            }
        }
        ;
        echo '<p>Current Stock: <b>' . $product->get_stock_quantity() . '</b></p>';
    }

}