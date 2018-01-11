<?php
/*  ********************************************************
 *   Checkout functions
 *  ********************************************************
 */

function sv_require_second_street_address_field( $fields ) {
    $fields['address_2']['required'] = true;
    $fields['address_2']['label'] = esc_attr__( 'House/flat name or number', 'woocommerce' );
    return $fields;
}
add_filter( 'woocommerce_default_address_fields', 'sv_require_second_street_address_field' );