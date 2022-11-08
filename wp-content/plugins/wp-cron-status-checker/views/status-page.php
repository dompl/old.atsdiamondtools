<?php

/**
 * Add a menu item to the tools menu.
 */
function wcsc_add_menu() {
    add_management_page( 'WP Cron Status', 'WP Cron Status', 'manage_options', 'wcsc_status', 'wcsc_status_page' );
}
add_action( 'admin_menu', 'wcsc_add_menu' );


/**
 * Enqueue the styls.
 */
function wcsc_admin_enqueue($hook) {
    if( stripos($hook, 'tools_page_wcsc_status' ) === FALSE)
        return;

    wp_enqueue_style( 'wp-pointer' );
    wp_enqueue_script( 'wp-pointer' );

    wp_enqueue_style( 'wcsc_style', 
        plugins_url('/assets/css/status-page.css', WCSC_PLUGIN ), 
        WCSC_VERSION 
    );

    wp_enqueue_script( 'wcsc_script', 
        plugins_url('assets/js/status-page.js', WCSC_PLUGIN ), 
        array( 'jquery' ),
        WCSC_VERSION,
        true 
    );
}
add_action( 'admin_enqueue_scripts', 'wcsc_admin_enqueue' );

/**
 * Output the status page.
 */
function wcsc_status_page() {
    $wcsc = new WCSC();
    $now = time();
    ?>
    <div class="wrap">
        <h2>WP Cron Status</h2>
        <div class="wcsc-status-page">
            <div class="wcsc-menu subsubsub">
                <a href="#" data-view="cron" class="current">View by WP-Cron</a>
                <span class="divider">|</span>
                <a href="#" data-view="hooks">View by Hooks</a>
                <span class="divider">|</span>
                <a href="#" data-view="cron-errors">View Uncompleted by WP-Cron</a>
                <span class="divider">|</span>
                <a href="#" data-view="hooks-errors">View Uncompleted by Hooks</a>
            </div>

            <div class="status-view status-by-cron current">
                <?php require_once( 'status-page-by-cron.php' ); ?>
            </div>
            <div class="status-view status-by-hooks">
                <?php require_once( 'status-page-by-hooks.php' ); ?>
            </div>
            <div class="status-view status-by-cron-errors">
                <?php require_once( 'status-page-by-cron-errors.php' ); ?>
            </div>
            <div class="status-view status-by-hooks-errors">
                <?php require_once( 'status-page-by-hooks-errors.php' ); ?>
            </div>
        </div>
    </div>
<?php
}