<?php
/*  ********************************************************
 *   Checkout functions
 *  ********************************************************
 */

function sv_require_second_street_address_field( $fields ) {
    $fields['address_1']['label'] = esc_attr__( 'House/flat name or number, streen name', 'woocommerce' );
    unset($fields['address_2']);
    return $fields;
}
add_filter( 'woocommerce_default_address_fields', 'sv_require_second_street_address_field' );