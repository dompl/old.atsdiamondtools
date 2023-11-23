<?php
/*  ********************************************************
 *   Vimeo and youtube shortcodes
 *  ********************************************************
 */
if (  !  defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
/**
 * @param $atts
 */
function youtube( $atts ) {
    extract( shortcode_atts( array(
        'start' => '',
        'id'    => ''
    ), $atts ) );

    $time = $start ? '&start=' . $start : '';
    return '<div class="videoWrapper"><iframe width="960" height="540" src="https://www.youtube.com/embed/' . $id . '?' . $time . '&#038;feature=oembed&#038rel=0" frameborder="0" allowfullscreen></iframe></div>';
}
add_shortcode( 'youtube', 'youtube' );

/**
 * @param $atts
 */
function vimeo( $atts ) {
    return '<div class="embed-container"><iframe src="https://player.vimeo.com/video/' . $atts['id'] . '" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe></div>';
}
add_shortcode( 'vimeo', 'vimeo' );
add_shortcode( 'vimeos', 'vimeos' );

/**
 * @param $atts
 */

function vimeos( $atts ) {
    if ( isset( $atts['videos'] ) ) {
        $videos = explode( ',', $atts['videos'] );
        if (  !  empty( $videos ) ) {
            $html = '<div class="vimeos count-' . count( $videos ) . '">';
            foreach ( $videos as $video ) {
                $html .= '<div class="vim-container">' . do_shortcode( "[vimeo id={$video}]" ) . '</div>';
            }
            $html .= '</div>';
            return $html;
        }
    }
}