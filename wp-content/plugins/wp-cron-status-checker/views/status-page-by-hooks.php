<?php
    $cron_logs = $wcsc->summarize_logs_by_hooks();
    $data_count = count( $cron_logs );
    $max_rows = $data_count > 6  ? ceil( $data_count / 2 ) : $data_count;
    $r = 0;
    $log_lifespan = wcsc_option( 'log_lifespan', WCSC_DEFAULT_LOG_LIFESPAN );
    ?>
    <p class="intro">
        <?php if ( empty( $log_lifespan ) ): ?>
            <strong><?php _e( 'Logs are currently not being kept.', 'wcsc' ); ?></strong>
            <a href="<?php echo esc_url( menu_page_url( WCSC_OPTIONS_PAGE_ID, false ) ); ?>"><?php _e( 'Enable logging here.', 'wcsc' ); ?></a>
            <br>
        <?php else : ?>
        <?php _e( 'Below are the hooks that are scheduled to run or have recently run.  If hooks are not run in over a week, the log for the hooks are removed.', 'wcsc' ); ?>
        <?php endif; ?>
    </p>
    
    <?php if ( !empty( $log_lifespan ) ): ?>

    <p class="toggle-all-block">
        <a href="#" class="toggle-all-link"><span class="expand">[+] <?php _e( 'Expand All', 'wcsc' ); ?></span><span class="collapse">[-] <?php _e( 'Collapse All', 'wcsc' ); ?></span></a>
    </p>

    <?php require( 'status-page-by-hooks-base.php' ); ?>

    <?php endif; ?>


<?php if ( !wcsc_fs()->can_use_premium_code() ) : ?>
    <p>
    <?php _e( 'The free version is limited to only the last 3 cron runs and recent uncompleted runs.', 'wcsc' ); ?>
    <a href="<?php echo WCSC_PLUGIN_WEBSITE; ?>" target="_blank"><?php _e( 'Get the PRO version to remove all limits!', 'wcsc' ); ?></a>
</p>
<?php endif; ?>