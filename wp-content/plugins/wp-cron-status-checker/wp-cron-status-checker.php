<?php

/*
Plugin Name: WP-Cron Status Checker
Plugin URI: https://webheadcoder.com/wp-cron-status-checker
Description: If WP-Cron runs important things for you, you better make sure WP-Cron always runs!
Version: 1.2.4
Author: Webhead LLC
*/
define( 'WCSC_VERSION', '1.2.4' );
define( 'WCSC_PLUGIN', __FILE__ );
define( 'WCSC_DIR', dirname( WCSC_PLUGIN ) );
define( 'WCSC_OPTIONS_NAME', 'wcsc_options' );
define( 'WCSC_OPTIONS_PAGE_ID', 'wcsc-options' );
// used to disable the new monitoring feature.  can remove this and references to it later.
define( 'WCSC_ENABLE_MONITORING', true );
define( 'WCSC_DEFAULT_LOG_LIFESPAN', 86400 );
define( 'WCSC_PLUGIN_WEBSITE', 'https://webheadcoder.com/wp-cron-status-checker' );
require_once WCSC_DIR . '/classes/class-wcsc.php';
require_once WCSC_DIR . '/classes/class-error-log.php';
require_once WCSC_DIR . '/inc/tables.php';
require_once WCSC_DIR . '/inc/cron-checker.php';
require_once WCSC_DIR . '/views/dashboard.php';
require_once WCSC_DIR . '/views/status-page.php';
require_once WCSC_DIR . '/views/options-page.php';
register_activation_hook( WCSC_PLUGIN, 'wcsc_activation' );
register_deactivation_hook( WCSC_PLUGIN, 'wcsc_deactivation' );
add_action( 'wcsc_clean_up', array( 'WCSC', 'cleanup' ) );
// Use cron to send email for this because it is assumed working.
// If cron isn't working, the "Failed" email notice will be sent.
add_action( 'wcsc_email_notice_hook', array( 'WCSC', 'notify_user' ) );
if ( !defined( 'DOING_CRON' ) || !DOING_CRON ) {
    add_action( 'init', array( 'WCSC', 'check_cron_completion' ) );
}
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

if ( function_exists( 'wcsc_fs' ) ) {
    wcsc_fs()->set_basename( false, __FILE__ );
} else {
    // DO NOT REMOVE THIS IF, IT IS ESSENTIAL FOR THE `function_exists` CALL ABOVE TO PROPERLY WORK.
    
    if ( !function_exists( 'wcsc_fs' ) ) {
        // Create a helper function for easy SDK access.
        function wcsc_fs()
        {
            global  $wcsc_fs ;
            
            if ( !isset( $wcsc_fs ) ) {
                // Activate multisite network integration.
                if ( !defined( 'WP_FS__PRODUCT_4604_MULTISITE' ) ) {
                    define( 'WP_FS__PRODUCT_4604_MULTISITE', true );
                }
                // Include Freemius SDK.
                require_once dirname( __FILE__ ) . '/freemius/start.php';
                $wcsc_fs = fs_dynamic_init( array(
                    'id'             => '4604',
                    'slug'           => 'wp-cron-status-checker',
                    'type'           => 'plugin',
                    'public_key'     => 'pk_b1d6ce4fb7a9e8cdff026f3ba14a9',
                    'is_premium'     => false,
                    'premium_suffix' => 'Pro',
                    'has_addons'     => false,
                    'has_paid_plans' => true,
                    'menu'           => array(
                    'slug'    => 'wcsc-options',
                    'support' => false,
                    'parent'  => array(
                    'slug' => 'options-general.php',
                ),
                ),
                    'is_live'        => true,
                ) );
            }
            
            return $wcsc_fs;
        }
        
        // Init Freemius.
        wcsc_fs();
        // Signal that SDK was initiated.
        do_action( 'wcsc_fs_loaded' );
        wcsc_fs()->add_action( 'after_uninstall', 'wcsc_fs_uninstall_cleanup' );
    }
    
    /**
     * Activate plugin
     */
    function wcsc_activation( $network_wide )
    {
        global  $wpdb ;
        wcsc_setup_tables();
        
        if ( $network_wide && is_multisite() ) {
            // Get all blogs in the network and activate plugin on each one
            $blog_ids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}" );
            foreach ( $blog_ids as $blog_id ) {
                switch_to_blog( $blog_id );
                wcsc_setup_tables();
                restore_current_blog();
            }
        } else {
            wcsc_setup_tables();
        }
        
        $time = time();
        try {
            $datetime = new DateTime( 'midnight', new DateTimeZone( WCSC::get_timezone_string() ) );
            $datetime->setTimezone( new DateTimeZone( 'UTC' ) );
            $time = $datetime->format( 'U' );
        } catch ( Exception $e ) {
        }
        if ( !wp_next_scheduled( 'wcsc_clean_up' ) ) {
            wp_schedule_event( $time, 'twicedaily', 'wcsc_clean_up' );
        }
        WCSC::schedule_email_notice_hook();
    }
    
    /**
     * Delete the tables
     */
    function wcsc_fs_uninstall_cleanup()
    {
        $delete_data_too = wcsc_option( 'delete_data_too', 0 );
        
        if ( !empty($delete_data_too) ) {
            wcsc_destroy_tables();
            delete_option( '_wcsc_version' );
        }
    
    }
    
    /**
     * Setup the 
     */
    function wcsc_add_email_frequency( $schedules )
    {
        // add to the existing set
        $email_frequency = (int) wcsc_option( 'email_frequency', 86400 );
        if ( !wcsc_fs()->is_premium() ) {
            $email_frequency = max( $email_frequency, 86400 );
        }
        $schedules['wcsc_email_interval'] = array(
            'interval' => $email_frequency,
            'display'  => wcsc_email_frequencies( $email_frequency ),
        );
        return $schedules;
    }
    
    add_filter( 'cron_schedules', 'wcsc_add_email_frequency' );
    /**
     * Check to see if the db is up to date.
     */
    function wcsc_update_db_check()
    {
        if ( get_option( '_wcsc_version', 0 ) != WCSC_VERSION ) {
            wcsc_activation( false );
        }
    }
    
    add_action( 'plugins_loaded', 'wcsc_update_db_check' );
    /**
     * Deleting the table whenever a blog is deleted
     */
    function wcsc_on_delete_blog( $tables )
    {
        global  $wpdb ;
        $tables = array_merge( $tables, wcsc_table_names() );
        return $tables;
    }
    
    add_filter( 'wpmu_drop_tables', 'wcsc_on_delete_blog' );
    /**
     * Deactivate plugin
     */
    function wcsc_deactivation()
    {
        wp_clear_scheduled_hook( 'wcsc_clean_up' );
        WCSC::unschedule_email_notice_hook();
    }
    
    /**
     * Returns the timestamp in the blog's time and format.
     */
    function wcsc_get_datestring( $timestamp = '' )
    {
        if ( empty($timestamp) ) {
            $timestamp = current_time( 'timestamp', true );
        }
        return get_date_from_gmt( date( 'Y-m-d H:i:s', $timestamp ), get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) );
    }
    
    /**
     * Get option
     */
    function wcsc_option( $name, $default = false )
    {
        $options = get_option( WCSC_OPTIONS_NAME );
        
        if ( !empty($options) && isset( $options[$name] ) ) {
            $ret = $options[$name];
        } else {
            $ret = $default;
        }
        
        return $ret;
    }
    
    /**
     * Email the user if the results for the general WP Cron system is bad
     */
    function wcsc_notify_user( $result, $forced )
    {
        
        if ( !$forced && is_wp_error( $result ) && $result->get_error_code() != 'wcsc_notice' ) {
            $last_emailed = get_option( '_wcsc_last_emailed', 0 );
            $email_frequency = (int) wcsc_option( 'email_frequency', 86400 );
            if ( !$forced && $last_emailed > time() - $email_frequency ) {
                return;
            }
            
            if ( !wcsc_fs()->is_premium() ) {
                $email_frequency = max( $email_frequency, 86400 );
                if ( $last_emailed > time() - $email_frequency ) {
                    return;
                }
            }
            
            $email_address = wcsc_get_email_address();
            
            if ( !empty($email_address) ) {
                $msg = get_option( 'wcsc_status' );
                $msg .= sprintf( __( '<p style="font-size:.9em;">This message has been sent from %s by the WP-Cron Status Checker plugin.  You can change the email address in your WordPress admin section under Settings -> WP Cron Status.  Only one email will be mailed every 24 hours.</p>', 'wcsc' ), site_url() );
                $headers = array( ' Content-Type: text/html; charset=UTF-8' );
                wp_mail(
                    $email_address,
                    get_bloginfo( 'name' ) . ' - ' . __( 'WP-Cron Cannot Run!', 'wcsc' ),
                    $msg,
                    $headers
                );
                update_option( '_wcsc_last_emailed', time() );
            }
        
        }
    
    }
    
    add_action(
        'wcsc_run_status',
        'wcsc_notify_user',
        10,
        2
    );
    /**
     * Get the email address taking account the old settings.
     */
    function wcsc_get_email_address()
    {
        $email_address = wcsc_option( 'email', -1 );
        // if not set, fall back to old setting
        if ( $email_address == -1 ) {
            
            if ( get_option( 'wcsc-email-flag' ) ) {
                $email_address = get_option( 'admin_email' );
            } else {
                $email_address = '';
            }
        
        }
        return $email_address;
    }
    
    /**
     * List of messages
     */
    function wcsc_messages()
    {
        $messages = array(
            '1' => __( 'Logs successfully cleared', 'wcsc' ),
        );
        return $messages;
    }
    
    /**
     * Return true if incomplete should be considered an error (for this hook)
     */
    function wcsc_is_incomplete_an_error( $hook_name = '' )
    {
        $incomplete_not_error = wcsc_option( 'incomplete_not_error', 0 );
        if ( !empty($incomplete_not_error) ) {
            return false;
        }
        $hooks = wcsc_option( 'incomplete_not_error_hooks', '' );
        $hook_names = explode( "\n", $hooks );
        
        if ( !empty($hook_name) && !empty($hook_names) && is_array( $hook_names ) ) {
            $hook_names = array_map( 'trim', $hook_names );
            if ( in_array( trim( $hook_name ), $hook_names ) ) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Email frequencies
     */
    function wcsc_email_frequencies( $val = '' )
    {
        $frequencies = array(
            '300'    => __( 'Once every 5 minutes' ),
            '3600'   => __( 'Once an hour' ),
            '86400'  => __( 'Once a day' ),
            '604800' => __( 'Once a week' ),
        );
        if ( !empty($val) && !empty($frequencies[$val]) ) {
            return $frequencies[$val];
        }
        return $frequencies;
    }
    
    /**
     * Log lifespan options
     */
    function wcsc_log_lifespans( $val = '' )
    {
        $lifespans = array(
            '0'       => __( 'Do Not Keep Logs', 'wcsc' ),
            '43200'   => __( '12 hours', 'wcsc' ),
            '86400'   => __( '24 hours', 'wcsc' ),
            '604800'  => __( '1 week', 'wcsc' ),
            '2592000' => __( '1 month', 'wcsc' ),
        );
        if ( !empty($val) && !empty($lifespans[$val]) ) {
            return $lifespans[$val];
        }
        return $lifespans;
    }

}

//function_exists( 'wcsc_fs' )