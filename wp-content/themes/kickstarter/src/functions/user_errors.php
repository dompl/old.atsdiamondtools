<?php
/*  ********************************************************
 *
 *  ********************************************************
 */
if ( !  defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * @return null
 */

add_shortcode( 'ats_orders_error', 'ats_test_action_' );
function ats_test_action_()
{
    $allowed_users = array( 1, 2, 3 );

    if ( !  in_array( get_current_user_id(), $allowed_users ) || !  current_user_can( 'administrator' ) ) {
        return;
    }

    $guest_orders = wc_get_orders(
        array(
            'type'  => array( 'shop_order' ),
            'limit' => -1

        )
    );

    $i    = '<table>';
    $item = 1;
    foreach ( $guest_orders as $order ) {

        $order_id = $order->id;
        // Get order customer id
        $customer_id = get_post_meta( $order_id, '_customer_user' );

        if ( !  in_array( $customer_id[0], $allowed_users ) && get_userdata( $customer_id[0] )->user_email != '' ) {
            $order_email         = get_userdata( $customer_id[0] )->user_email;
            $order_billing_email = $order->billing_email;

            if ( is_numeric( $order_id ) ) {
                if ( $order_email !== $order_billing_email && !  email_exists( $order_billing_email ) ) {
                    $i .= '<tr>';
                    $i .= '<td><strong>' . $item . '</strong></td>';
                    $i .= '<td>' . $order_email . '</td>';
                    $i .= '<td>' . $order_billing_email . '</td>';
                    $i .= '<td><a href="' . get_admin_url() . "post.php?post={$order_id}&action=edit\" target='_blank'>{$order_id}</a></td>";
                    $i .= '</tr>';
                    $item++;
                }
            }
        }
    }
    $i .= '</table>';

    return $i;
}