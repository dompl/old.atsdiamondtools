<?php
    $cron_logs = $wcsc->summarize_logs_by_cron();
    $data_count = count( $cron_logs );
?>
<p class="intro">
    <?php 
    $log_lifespan = wcsc_option( 'log_lifespan', WCSC_DEFAULT_LOG_LIFESPAN );
    ?>

    <?php if ( empty( $log_lifespan ) ): ?>
        <strong><?php _e( 'Logs are currently not being kept.', 'wcsc' ); ?></strong>
        <a href="<?php echo esc_url( menu_page_url( WCSC_OPTIONS_PAGE_ID, false ) ); ?>"><?php _e( 'Enable logging here.', 'wcsc' ); ?></a>
        <br>
    <?php else : ?>
    <?php printf( __( 'See WP-Cron runs for the last %s.  You can see how long each of the triggered hooks took to complete.', 'wcsc' ), WCSC::humanize_seconds( $log_lifespan ) );  ?>
    <?php endif; ?>
    <?php if ( $data_count == 0 ) : ?>
    <br><br>No logs yet.
    <?php endif; ?>
</p>
<?php if ( $data_count > 0 ) : ?>
<p class="toggle-all-block">
    <a href="#" class="toggle-all-link"><span class="expand">[+] <?php _e( 'Expand All', 'wcsc' ); ?></span><span class="collapse">[-] <?php _e( 'Collapse All', 'wcsc' ); ?></span></a>
</p>
<?php endif; ?>

<?php require( 'status-page-by-cron-base.php' ); ?>

<?php if ( !wcsc_fs()->can_use_premium_code() ) : ?>
    <p>
    <?php _e( 'The free version is limited to only the last 3 cron runs and recent uncompleted runs.', 'wcsc' ); ?>
    <a href="<?php echo WCSC_PLUGIN_WEBSITE; ?>" target="_blank"><?php _e( 'Get the PRO version to remove all limits!', 'wcsc' ); ?></a>
</p>
<?php endif; ?>