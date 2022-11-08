<?php

/**
 * Setup all the admin stuff.
 */
function wcsc_admin_init() {
    add_action('wp_dashboard_setup', 'wcsc_dashboard_widget' );
}
add_action( 'admin_init', 'wcsc_admin_init' ); 

/**
 * Add dashboard widget
 */
function wcsc_dashboard_widget() {
    $title = 'WP-Cron Status Checker';
    if ( current_user_can( 'manage_options' ) ) {
        $title .= '<span class="wcsc-title-email"><a href="' . esc_url( menu_page_url( WCSC_OPTIONS_PAGE_ID, false ) ) . '" style="text-decoration: none; font-weight: normal;" title="Settings"><span class="dashicons dashicons-admin-generic"></span></a></span>';   
    }
    wp_add_dashboard_widget('dashboard_wcsc_widget', $title, 'wcsc_dashboard_widget_output');
}

/**
 * Show the status and check button.
 */
function wcsc_dashboard_widget_output() { 
    $link = '<span class="spinner"></span> <a id="wcsc-force-check" href="#" class="check-status-link">' . __( 'You can also click here to check it now.', 'wcsc' ) . '</a>';
    echo '<p>' . sprintf( __( 'The ability for the WP-Cron system to run will be automatically checked once every 24 hours.  %s', 'wcsc' ), $link ) . '</p>';

    $time_in_minutes = WCSC::CRON_TIME_ALLOWANCE / 60;
    $minutes = sprintf( _n( '%s minute', '%s minutes', $time_in_minutes, 'wcsc' ), $time_in_minutes );
    echo '<p>' . sprintf( __( 'Whenever WP-Cron takes longer than %s to complete, it\'s assumed to have failed.  You\'ll get an email to check the WP Cron Status page once the failure is detected.', 'wcsc' ), $minutes ) . '</p>';
    echo '<div class="wcsc-status-container">' . wcsc_dashboard_get_status() . '</div>';
?>
    <a class="btn-log button" href="<?php echo admin_url( 'tools.php?page=wcsc_status' ); ?>"><?php echo __( 'View Logs', 'wcsc' ); ?></a>
<?php
}

/**
 * Return the dashboard friendly status.
 */
function wcsc_dashboard_get_status() {
    if ( $status = get_option( 'wcsc_status' ) ) {
        
        // use the site health way instead of _wcsc_hooks_status.

        $last_run = get_option( '_wcsc_last_run' );
        $wcsc = new WCSC();
        $info = $wcsc->quick_wp_cron_info();
        if ( !empty( $info['last'] ) ) {
            $date_format = get_option( 'date_format' );
            $time_format = get_option( 'time_format' );
            $time_string = date( $date_format . ' ' . $time_format, WCSC::utc_to_blogtime( (int) $info['last']['start'] ) );
            if ( $info['last']['completed'] === false ) {
                $msg = __( '<span class="wcsc-label">WP Cron failed to complete:</span><span class="wcsc-value">%s</span>' );
                $status .= '<span class="wcsc-status wcsc-error">' . sprintf( $msg, $time_string ) . '</span>';
            }
            else {
                $msg = __( '<span class="wcsc-label">Last time WP Cron succeeded:</span><span class="wcsc-value">%s</span>' );
                $status .= '<span class="wcsc-status wcsc-success">' . sprintf( $msg, $time_string ) . '</span>';
            }
        }
        return $status;
    }
    else {
        return __( 'WP-Cron Status Checker has not run yet.', 'wcsc' );
    }
}

/**
 * Enqueue the scripts
 */
function wcsc_dashboard_widget_enqueue( $hook ) {
    if( 'index.php' != $hook && 'options-general.php' != $hook ) {
        return;
    }

    wp_enqueue_style( 'wcsc-dashboard-widget', 
        plugins_url( '/assets/css/dashboard.css', WCSC_PLUGIN ),
        array(),
        WCSC_VERSION );

    wp_enqueue_script( 'wcsc-dashboard-widget', 
        plugins_url( '/assets/js/dashboard.js', WCSC_PLUGIN ), 
        array( 'jquery' ), 
        WCSC_VERSION,
        true );

    wp_localize_script( 'wcsc-dashboard-widget', 'wcsc', array(
        'ajaxurl'       => admin_url( 'admin-ajax.php' ),
        'nonce'         => wp_create_nonce( 'wcsc-nonce' )
    ) );
}
add_action( 'admin_enqueue_scripts', 'wcsc_dashboard_widget_enqueue' );

/**
 * Force check the status.
 */
function wcsc_ajax_check() {
    if ( !check_ajax_referer('wcsc-nonce', 'nonce', false) ){
        die(); 
    }
    wcsc_run( true );
    $html = wcsc_dashboard_get_status();
    wp_send_json( array( 'html' => $html ) );
}
add_action('wp_ajax_wcsc-force-check', 'wcsc_ajax_check');
