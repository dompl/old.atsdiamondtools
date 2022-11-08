<?php

foreach ( $cron_logs as $cron_key => $cron_log ) {
    $class_status = '';
    $title = '';
    $timestamp = round( (double) $cron_key );
    $logs = $cron_log['hooks'];
    
    if ( !empty($logs) ) {
        $class_status = 'completed';
        $title = __( 'All hooks completed', 'wcsc' );
        foreach ( $logs as $log ) {
            
            if ( !empty($log['in_progress']) ) {
                $class_status = 'in-progress';
                $title = __( 'One or more hooks are still running', 'wcsc' );
                break;
            } else {
                
                if ( empty($log['completed']) ) {
                    if ( $log['result'] == WCSC::RESULT_INCOMPLETE && !wcsc_is_incomplete_an_error( $row['hook_name'] ) ) {
                        continue;
                    }
                    $class_status = 'failed';
                    $title = __( 'One or more hooks did not complete', 'wcsc' );
                }
            
            }
        
        }
    } else {
        
        if ( !empty($cron_log['summary']['in_progress']) ) {
            $class_status = 'in-progress';
            $title = __( 'One or more hooks are still running', 'wcsc' );
        }
    
    }
    
    $elapsed_time = $cron_log['summary']['elapsed'];
    
    if ( $elapsed_time != WCSC::NO_VALUE ) {
        $elapsed_time .= ' ms';
    } else {
        $elapsed_time = __( 'N/A', 'wcsc' );
    }
    
    $blogtime = WCSC::utc_to_blogtime( $timestamp );
    ?>
<div class="hook-container <?php 
    echo  $class_status ;
    ?>">
    <div class="hook-top">
        <span type="button" class="toggle-indicator"></span>
        <h3 class="hook-title"><span class="date"><?php 
    echo  date_i18n( get_option( 'date_format' ), $blogtime ) ;
    ?></span> <?php 
    echo  date_i18n( get_option( 'time_format' ), $blogtime ) ;
    ?>
            <br><span class="subheader">Elapsed Time: <span class="subheader-value"><?php 
    echo  $elapsed_time ;
    ?></span></span>
        </h3>
        <span class="status-icon" title="<?php 
    echo  esc_attr( $title ) ;
    ?>"></span>
    </div>
    <div class="hook-details">
        <div class="hook-details-content">
            <div class="detail-row">
                <table class="last-runs">
                    <thead>
                        <tr class="header-row">
                            <th class="last-run-label">Hook Name</th>
                            <th class="last-run-lapse">Time (ms)</th>
                            <th class="last-run-status">Result</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
    foreach ( $logs as $row ) {
        $elapsed = __( 'N/A', 'wcsc' );
        
        if ( !empty($row['in_progress']) ) {
            $elapsed = '-';
        } else {
            if ( $row['elapsed'] != WCSC::NO_VALUE ) {
                $elapsed = number_format( floatval( $row['elapsed'] ), 1 );
            }
        }
        
        ?>
                        <tr class="<?php 
        echo  str_replace( ' ', '-', strtolower( $row['result'] ) ) ;
        ?>">
                            <td class="last-run-label"><?php 
        echo  esc_html( $row['hook_name'] ) ;
        ?></td>
                            <td class="last-run-lapse"><?php 
        echo  $elapsed ;
        ?></td>
                            <td class="last-run-status">
                                <span class="<?php 
        echo  str_replace( ' ', '-', strtolower( $row['result'] ) ) ;
        ?>-status-bar" title="<?php 
        echo  esc_attr( $row['message'] ) ;
        ?>">
                                    <?php 
        echo  $row['result'] ;
        ?>
                                    <?php 
        
        if ( $row['result'] == WCSC::RESULT_FAILED ) {
            ?>
                                        <span class="dashicons dashicons-code-standards"></span>
                                        <div class="failed-message <?php 
            echo  ( !empty($row['error']) ? 'has-error' : '' ) ;
            ?>" style="display:none;">
                                            <?php 
            
            if ( !empty($row['caught_error']) ) {
                $error = __( 'An error has been caught!  Purchase the PRO version to see it here.', 'wcsc' );
            } else {
                $error = __( 'No errors caught', 'wcsc' );
            }
            
            echo  $error ;
            ?>
                                        </div>
                                    <?php 
        } elseif ( $row['result'] == WCSC::RESULT_INCOMPLETE ) {
            ?>
                                        <span class="dashicons dashicons-editor-help"></span>
                                        <div class="incomplete-message" style="display:none;">
                                            A hook is marked incomplete when the normal WordPress and PHP process did not complete as normally.
                                        </div>
                                    <?php 
        }
        
        ?>
                                </span>
                            </td>
                        </tr>
                        <?php 
    }
    ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php 
}