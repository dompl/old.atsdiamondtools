<?php
    $cron_logs = $wcsc->summarize_logs_by_cron( true );
    $data_count = count( $cron_logs );
?>
<p class="intro">
    <?php _e( 'Recent uncompleted crons.', 'wcsc' );  ?>
    <?php if ( $data_count == 0 ) : ?>
    <br><br><?php _e( 'Awesome!  No uncompleted cron logs.', 'wcsc' ); ?>
    <?php else : ?>
        <?php _e( 'Note: Sometimes crons can take longer than expected to complete.  If you got an email saying a cron did not complete, but shows completed here, it may just have completed after the email was sent.', 'wcsc' );  ?>
    <?php endif; ?>
</p>
<?php if ( $data_count > 0 ) : ?>
<p class="toggle-all-block">
    <a href="#" class="toggle-all-link"><span class="expand">[+] <?php _e( 'Expand All', 'wcsc' ); ?></span><span class="collapse">[-] <?php _e( 'Collapse All', 'wcsc' ); ?></span></a>
</p>
<?php endif; ?>

<?php require( 'status-page-by-cron-base.php' ); ?>
