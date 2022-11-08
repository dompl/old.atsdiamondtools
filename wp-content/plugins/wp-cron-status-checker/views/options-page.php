<?php
/*********************************
 * Options page
 *********************************/

// don't load directly
if ( !defined('ABSPATH') )
    die('-1');

/**
 *  Add menu page
 */
function wcsc_options_add_page() {
    $wcsc_hook = add_options_page( 'WP Cron Status Checker Settings', // Page title
                      'WP Cron Status', // Label in sub-menu
                      'manage_options', // capability
                      WCSC_OPTIONS_PAGE_ID, // page identifier 
                      'wcsc_options_do_page' ); // call back function name
                      
    add_action( "admin_enqueue_scripts-" . $wcsc_hook, 'wcsc_admin_scripts' );
}
add_action('admin_menu', 'wcsc_options_add_page');

/**
 * Init plugin options to white list our options
 */
function wcsc_options_init() {
    register_setting( 'wcsc_options_options', WCSC_OPTIONS_NAME, 'wcsc_options_validate' );
}
add_action('admin_init', 'wcsc_options_init' );

/**
 * Draw the menu page itself
 */
function wcsc_options_do_page() {
    if ( isset( $_GET['wcsc_message'] ) ) {
        $messages = wcsc_messages();
        $msg_idx = wp_unslash( $_GET['wcsc_message'] );
        echo sprintf( '<div id="message" class="updated notice is-dismissible"><p>%s</p></div>', esc_html( $messages[$msg_idx]) );
    }
    
    $free_only = !wcsc_fs()->can_use_premium_code();
    $free_label =  ' ' . __( '(PRO only)', 'wcsc' );
    $class_pro_only = $free_only ? 'wcsc-pro-only' : '';
?>
    <style>
    .wcsc-options-page ul {
        list-style: disc;
        margin-left: 20px;
        padding-left: 20px;
    }
    .wcsc-email-flag-list {
        margin-top: 2px;
    }
    .wcsc-options-page input[type="text"] {
        width: 320px;
        max-width: 100%;
    }
    .wcsc-hint {
        font-size: 13px;
        color: #555d66;
        font-style: italic;
    }
    .wcsc-options-page textarea  {
        width: 320px;
        height: 100px;
        max-width: 100%;
    }
    .wcsc-options-page textarea.not-active  {      
        background:#e0e0e0;
    }
    .wcsc-pro-only {
        color: #919aa5;
    }
    </style>
    <div class="wrap wcsc-options-page">
            <div class="wcsc-header">
                <div class="wcsc-description">
                <h2>WP Cron Status Checker Settings</h2>
                </div>
            </div>
            <div class="clear"></div>
            <hr>
            <form method="post" action="options.php">
                <?php settings_fields( 'wcsc_options_options' ); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e( 'Email Address', 'wcsc' ); ?></th>
                        <td>
                            <fieldset>
                                <p>
                                    <input type="text" name="<?php echo WCSC_OPTIONS_NAME;?>[email]" value="<?php echo wcsc_get_email_address(); ?>" data-default-value="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>" />
                                </p>
                                <p>
                                    Get an email if
                                    <ul class="wcsc-email-flag-list">
                                        <li>WordPress cannot run WP-Cron due to a general server, permissions, or setup issue.</li>
                                        <li>A WP-Cron hook does not complete due to an error specific to that hook.</li>
                                    </ul>
                                </p>
                            </fieldset>
                        </td>
                    </tr>
                    <tr class="email-types-row">
                        <th scope="row"><?php _e( 'Incomplete Status', 'wcsc' ); ?></th>
                        <td>
                            <p><?php _e( 'On occasion a plugin doesn\'t go through the entire PHP process.  Sometimes this is on purpose and sometimes it\'s because of timeout or other errors.', 'wcsc' ); ?></p>
                            <fieldset>
                                <p>
                                    <?php
                                        $incomplete_not_error = wcsc_option( 'incomplete_not_error', 0 );
                                    ?>
                                    <label for="status-incomplete"><input id="status-incomplete" type="checkbox" name="<?php echo WCSC_OPTIONS_NAME;?>[incomplete_not_error]" value="1" <?php checked( true, $incomplete_not_error == 1 ); ?>> <?php _e( 'Consider incomplete statuses to not be an error for all hooks', 'wcsc' ); ?></label>
                                    <br>
                                </p>
                            </fieldset>
                            - OR -
                            <br>
                            <p class="<?php echo $class_pro_only; ?>">
                                <?php _e( 'List any hooks that are being marked incomplete, but should not be considered errors.', 'wcsc' ); ?><br><span class="wcsc-hint <?php echo $class_pro_only; ?>"><?php _e( 'One hook name per line', 'wcsc' ); ?></span>
                            </p>
                            <textarea <?php echo $free_only ? 'disabled="disabled"' : '' ?> name="<?php echo WCSC_OPTIONS_NAME;?>[incomplete_not_error_hooks]"><?php echo esc_textarea( wcsc_option( 'incomplete_not_error_hooks', '' ) ) ?></textarea>
                            <?php if ( !wcsc_fs()->can_use_premium_code() ) : ?>
                            <br><a href="<?php echo WCSC_PLUGIN_WEBSITE; ?>" target="_blank"><?php _e( 'Get the PRO version to list specific hooks.', 'wcsc' ); ?></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr class="email-freq-row">
                        <th scope="row"><?php _e( 'Email Frequency', 'wcsc' ); ?></th>
                        <td>
                            <fieldset>
                                <p>
                                    <?php 
                                        $email_frequency = wcsc_option( 'email_frequency', false ); 
                                        $email_options = wcsc_email_frequencies();
                                    ?>
                                    <select name="<?php echo WCSC_OPTIONS_NAME;?>[email_frequency]">
                                    <?php foreach ( $email_options as $val => $label ) : 
                                        $disabled = false;
                                        if ( $free_only ) {
                                            if ( $val < 86400 ) {
                                                $val = 86400;
                                                $label .= $free_label;
                                                $disabled = true;
                                            }
                                        }
                                    ?>
                                        <option value="<?php echo (int) $val?>" <?php selected( $val, $email_frequency ) ?> <?php disabled( $disabled, true ); ?>><?php echo $label ?></option>
                                    <?php endforeach; ?>
                                    </select>
                                    <br>
                                    <span class="wcsc-hint"><?php _e( 'The minimum amount of time between emails.', 'wcsc' ); ?></span>
                                    <?php if ( !wcsc_fs()->can_use_premium_code() ) : ?>
                                    <br>
                                    <a href="<?php echo WCSC_PLUGIN_WEBSITE; ?>" target="_blank"><?php _e( 'Get the PRO version for more options!', 'wcsc' ); ?></a>
                                    <?php endif; ?>
                                </p>
                            </fieldset>
                        </td>
                    </tr>
                    <tr class="keep-logs-row">
                        <th scope="row"><?php _e( 'Keep logs', 'wcsc' ); ?></th>
                        <td>
                            <fieldset>
                                <p>
                                    <?php 
                                        $log_lifespan = wcsc_option( 'log_lifespan', WCSC_DEFAULT_LOG_LIFESPAN ); 
                                        $lifespan_options = wcsc_log_lifespans();
                                    ?>
                                    <select name="<?php echo WCSC_OPTIONS_NAME;?>[log_lifespan]">
                                    <?php foreach ( $lifespan_options as $val => $label ) : 
                                        $disabled = false;
                                        if ( $free_only ) {
                                            if ( $val > WCSC_DEFAULT_LOG_LIFESPAN ) {
                                                $val = WCSC_DEFAULT_LOG_LIFESPAN - 1;
                                                $label .= $free_label;
                                                $disabled = true;
                                            }
                                        }
                                    ?>
                                        <option value="<?php echo (int) $val; ?>" <?php selected( $val, $log_lifespan ) ?> <?php disabled( $disabled, true ); ?>><?php echo $label; ?></option>
                                    <?php endforeach; ?>
                                    </select>
                                    <br>
                                    <span class="wcsc-hint"><?php _e( 'The amount of time to keep the logs.', 'wcsc' ); ?></span>
                                    <?php if ( !wcsc_fs()->can_use_premium_code() ) : ?>
                                    <br>
                                    <a href="<?php echo WCSC_PLUGIN_WEBSITE; ?>" target="_blank"><?php _e( 'Get the PRO version to remove all limits!', 'wcsc' ); ?></a>
                                    <?php endif; ?>
                                </p>
                            </fieldset>
                        </td>
                    </tr>
                    <tr class="keep-logs-row">
                        <th scope="row"><?php _e( 'Cleanup', 'wcsc' ); ?></th>
                        <td>
                            <fieldset>
                                <p>
                                    <?php 
                                        $delete_data_too = wcsc_option( 'delete_data_too', 0 ); 
                                    ?>
                                    <label for="delete-data-too"><input id="delete-data-too" type="checkbox" name="<?php echo WCSC_OPTIONS_NAME;?>[delete_data_too]" value="1" <?php checked( true, $delete_data_too == 1 ); ?>> <?php _e( 'Delete all tables created from this plugin when this plugin is deleted.', 'wcsc' ); ?></label>
                                </p>
                            </fieldset>
                        </td>
                    </tr>

                </table>
                <p class="submit">
                <input type="submit" class="button-primary" value="<?php _e('Save All') ?>" />
                </p>
        </form>
        <br>
        <hr>
        <br>
        <form name="clear-form" method="post" action="">
            <?php wp_nonce_field( 'wcsc_clear_logs' ); ?>
            <input type="hidden" name="wcsc_clear_logs" value="1">
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e( 'Clear all logs', 'wcsc' ); ?></th>
                        <td>
                            <fieldset>
                                <p>
                                    <?php _e( 'Start from a clean slate and remove all logging from this plugin.', 'wcsc' ); ?>
                                </p>
                                <p>
                                    <input type="submit" class="button" value="Clear Logs">
                                </p>
                            </fieldset>
                        </td>
                    </tr>

                </table>

        </form>
    </div>
    <script type="text/javascript">
        (function($) {
            $('form[name="clear-form"]').on('submit', function(e){
                if (!confirm('Once logs are cleared, there is no way to retrieve them unless you have a backup of your database.')) {
                    e.preventDefault();
                    return false;
                }
                return true;
            });
        })(jQuery);
    </script>
    <?php 
}

/**
 * Sanitize and validate input. Accepts an array, return a sanitized array.
 */
function wcsc_options_validate($input) {
    global $wp_settings_errors;

    // clear logs when no logs should be kept
    if ( isset( $input['log_lifespan'] ) && $input['log_lifespan'] == 0 ) {
        WCSC::clear_logs();
    }

    if ( !isset( $input['incomplete_not_error'] ) ) {
        $input['incomplete_not_error'] = 0;
    }

    if ( !isset( $input['delete_data_too'] ) ) {
        $input['delete_data_too'] = 0;
    }

    add_action( 'updated_option', 'wcsc_options_updated', 10, 3 );
    add_action( 'added_option', 'wcsc_options_updated', 10, 2 );

    return $input;
}


/**
 * Enqueue Scripts
 */
function wcsc_admin_scripts() {
    do_action ('wcsc_admin_scripts');
}

/**
 * Enqueue scripts for the admin side.
 */
function wcsc_enqueue_scripts($hook) {
    if( 'settings_page_wcsc-options' != $hook )
        return;
    /*
    wp_enqueue_style( 'wcsc-options',
        plugins_url( '/css/options.css', WCSC_PLUGIN ),
        array( ),
        WCSC_VERSION );
    */
}
add_action( 'admin_enqueue_scripts', 'wcsc_enqueue_scripts' );

/**
 * clear the logs
 */
function wcsc_maybe_clear_logs() {
    global $pagenow;
    if ( $pagenow == 'options-general.php' ) {
        if ( !empty( $_POST['wcsc_clear_logs'] ) && current_user_can( 'manage_options' ) ) {
            check_admin_referer( 'wcsc_clear_logs' );
            $args = array(
                'wcsc_message' => '1'
            );
            WCSC::clear_logs();
            wp_safe_redirect( add_query_arg( $args, menu_page_url( WCSC_OPTIONS_PAGE_ID, false ) ) );
        }
    }
}
add_action( 'admin_init', 'wcsc_maybe_clear_logs' );


/**
 * Filter list of removable query args. 
 */
function wcsc_removable_query_args( $args ) {
    return array_merge( $args, array(
        'wcsc_message'
    ) );
}
add_filter( 'removable_query_args', 'wcsc_removable_query_args' );

/**
 * Update the scheduled job when settings change.  used for both adding and updating.
 */
function wcsc_options_updated( $option_name, $old_value, $new_value = array() ) {
    if ( $option_name != WCSC_OPTIONS_NAME) {
        return;
    }
    if ( !is_array( $old_value ) || !is_array( $new_value ) ) {
        return;
    }
    $freq1 = !empty( $old_value['email_frequency'] ) ? $old_value['email_frequency'] : 0;
    $freq2 = !empty( $new_value['email_frequency'] ) ? $new_value['email_frequency'] : 0;
    if ( $freq1 == $freq2 ) {
        return;
    }

    WCSC::unschedule_email_notice_hook();
    WCSC::schedule_email_notice_hook();
}

