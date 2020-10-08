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
// add_action( 'clock', 'add_clock_hours' );
/**
 * @return null
 */

function ats_times( $schedule = '' )
{

    $time_from = new DateTime( date( 'Y-m-d' . $schedule['schedules']['time_start'] ), new DateTimeZone( 'Europe/London' ) );
    $time_till = new DateTime( date( 'Y-m-d' . $schedule['schedules']['time_end'] ), new DateTimeZone( 'Europe/London' ) );
    $now       = new DateTime( 'now', new DateTimeZone( 'Europe/London' ) );
    // $current_weekday = strtolower( $date->format( 'l' ) );
    // $start           = strtotime( $now->format( 'Y-m-d H:i:s' ) );
    // $end             = strtotime( $today );

    $day_lowercase = $schedule['schedules']['day_till']['label'];
    $time_till->modify( "next {$day_lowercase}" );
    $new = new DateTime( date( $time_till->format( 'Y/m/d' ) . $schedule['schedules']['time_start'] ), new DateTimeZone( 'Europe/London' ) );

    $schedules = array(
        'timestamp_now'        => $now->format( 'U' ),
        'timestamp_hours_now'  => $now->format( 'Gis' ),
        'timestamp_hours_from' => $time_from->format( 'Gis' ),
        'timestamp_hours_till' => $time_till->format( 'Gis' ),
        'time_from'            => $time_from->format( 'Y/m/d H:i:s' ),
        'time_till'            => $new->format( 'Y/m/d H:i:s' ),
        'today_weekday'        => intval( $now->format( 'w' ) ) == (int) 0 ? (int) 7 : intval( $now->format( 'w' ) )
    );

    return $schedules;
}

/**
 * @return null
 */
add_action( 'clock', 'ats_countdown_loop' );
function ats_countdown_loop()
{

    $items = get_field( 'add_countdown_item', 'option' );

    if ( empty( $items ) ) {
        return;
    }

    $counter = '';

    $counter .= '<div class="countdown-container"><div class="truck"><i class="icon-truck"></i></div><ul class="countdown-list list-unstyled">';

    foreach ( $items as $item ) {

        $ats_times = ats_times( $item );

        $times = $item['schedules'];
        $note  = $item['schedules_note']['first'] == '' && $item['schedules_note']['second'] == '' && $item['schedules_note']['third'] == '' ? false : $item['schedules_note'];

        $today_weekday = intval( $ats_times['today_weekday'] );
        $day_from      = intval( $times['day_from']['value'] );
        $day_till      = intval( $times['day_till']['value'] ) < $day_from ? 7 - intval( $times['day_till']['value'] ) : intval( $times['day_till']['value'] );

        $range = range( $day_from, $day_till );

        $html_lines = array( 'first', 'second', 'third' );

        /* Here we are going to check if the current day is in range od selected dates */
        // if ( in_array( $today_weekday, $range ) && $note ) {
        if ( $note ) {

            /*  Here we are checking if current time is in range of selected times */
            if ( in_array( intval( $ats_times['timestamp_hours_now'] ), range( intval( $ats_times['timestamp_hours_from'] ), intval( $ats_times['timestamp_hours_till'] ) ) ) ) {
                echo 'sdfsdfsd';

                /* Here we are going to try sort out the future dates */

                $countdown_html = '<span data-countdown="' . $ats_times['time_till'] . '"></span>';
                /* Fallbacks - What happens when the note expores */

                $fallbacks = array();

                foreach ( $html_lines as $line ) {
                    $fallbacks[] = $item['schedules_note_expired'][$line] ? '<div class="' . $line . '-line">' . ( str_replace( array( '%time%' ), $countdown_html, $item['schedules_note_expired'][$line] ) ) . '</div>' : '';
                }

                $json = !  empty( $fallbacks ) ? ' class="has-fallback" data-clock-fallbacks=\'' . json_encode( $fallbacks ) . '\'' : '';

                $counter .= '<li>';

                foreach ( $html_lines as $line ) {
                    $counter .= $item['schedules_note'][$line] ? '<div class="' . $line . '-line">' . ( str_replace( array( '%time%' ), $countdown_html, $item['schedules_note'][$line] ) ) . '</div>' : '';
                }
                $counter .= '</li>';

            }

        }

    }
    $counter .= '</div>';
    echo $counter;
}

/**
 * @param $schedule
 * @param $item
 */
add_filter( '_ats_countdown_loop', '_ats_countdown_loop_cb', 10, 2 );
function _ats_countdown_loop_cb( $schedule, $item )
{

}

/**
 * @param $field
 * @return mixed
 */
function acf_populate_weekdays( $field )
{

    // reset choices
    $field['choices'] = array();

    $days = array(
        '1' => __( 'Monday', TEXT_DOMAIN ),
        '2' => __( 'Tuesday', TEXT_DOMAIN ),
        '3' => __( 'Wednesday', TEXT_DOMAIN ),
        '4' => __( 'Thursday', TEXT_DOMAIN ),
        '5' => __( 'Friday', TEXT_DOMAIN ),
        '6' => __( 'Saturday', TEXT_DOMAIN ),
        '7' => __( 'Sunday', TEXT_DOMAIN )
    );

    foreach ( $days as $id => $day ) {

        $value = $id;
        $label = $day;

        $field['choices'][$value] = $label;

    }

    // return the field
    return $field;

}

add_filter( 'acf/load_field/key=field_5f7e874eedc24', 'acf_populate_weekdays' );
add_filter( 'acf/load_field/key=field_5f7e87bfedc25', 'acf_populate_weekdays' );
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

        $start = strtotime( $now->format( 'Y-m-d H:i:s' ) );
        $end   = strtotime( $today );
        if ( $start < $end ) {
            $count++;
        }

    }

    if ( $count > 0 ) {

        foreach ( $delivery_notes as $item ) {

            $today = date( 'Y-m-d' . $item['time'] );
            $date  = new DateTime( $today, new DateTimeZone( 'Europe/London' ) );
            $now   = new DateTime( 'now', new DateTimeZone( 'Europe/London' ) );
            $start = strtotime( $now->format( 'Y-m-d H:i:s' ) );
            $end   = strtotime( $today );

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