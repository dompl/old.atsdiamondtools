<?php
/*  ********************************************************
 *   countdown clock
 *  ********************************************************
 */
if ( !  defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
$timezone = new DateTimeZone( 'Europe/London' );
wp_date( 'd-m-Y H:i:s', null, $timezone );
add_action( 'clock', 'add_clock_hours' );
/**
 * @return null
 */
function add_clock_hours()
{
    $delivery_notes = get_field( 'add_delivery_note', 'options' );

    if ( empty( $delivery_notes ) ) {
        return;
    }

    $count = 0;
    $i     = 0;
    $note  = '';

    foreach ( $delivery_notes as $item ) {
        $today = date( 'Y-m-d ' . $item['time'] );
        $date  = new DateTime( $today, new DateTimeZone( 'Europe/London' ) );
        $now   = new DateTime( 'now', new DateTimeZone( 'Europe/London' ) );

        $start = strtotime( $now->format( 'Y-m-d H:i:s' ) );
        $end   = strtotime( $today );
        if ( $start < $end ) {
            $count++;
        }

    }

    if ( $count > 0 ) {

        foreach ( $delivery_notes as $item ) {

            $today           = date( 'Y-m-d' . $item['time'] );
            $date            = new DateTime( $today, new DateTimeZone( 'Europe/London' ) );
            $now             = new DateTime( 'now', new DateTimeZone( 'Europe/London' ) );
            $current_weekday = strtolower( $date->format( 'l' ) );
            $start           = strtotime( $now->format( 'Y-m-d H:i:s' ) );
            $end             = strtotime( $today );

            if ( in_array( $current_weekday, $item['days'] ) && $start < $end ) {

                $hidden = isset( $_COOKIE['ats_del'] ) && !  empty( $_COOKIE['ats_del'] ) ? ' hidden' : '';

                $note = $i == 0 ? '<div class="countdown-container ' . ( $count > 1 ? 'multi' : 'single' ) . $hidden . '" style="display:none"><div class="truck"><i class="icon-truck"></i></div><ul class="countdown-list list-unstyled">' : '';

                $countdown_html = '<span data-countdown="' . $date->format( 'Y/m/d H:i:s' ) . '" data-ats-countdown=' . json_encode( array( 'show_seconds' => $item['display_seconds'] ? 1 : 0 ) ) . '></span>';

                $note .= '<li>';
                $note .= $item['note']['first'] ? '<div class="first-line">' . ( str_replace( array( '%time%' ), $countdown_html, $item['note']['first'] ) ) . '</div>' : '';
                $note .= $item['note']['second'] ? '<div class="second-line">' . ( str_replace( array( '%time%' ), $countdown_html, $item['note']['second'] ) ) . '</div>' : '';
                $note .= $item['note']['third'] ? '<div class="second-third">' . ( str_replace( array( '%time%' ), $countdown_html, $item['note']['third'] ) ) . '</div>' : '';
                $note .= '</li>';

                $i++;
                $note .= $i == $count ? '</ul></div>' : '';
            }

            echo $note;

        }
    }
}