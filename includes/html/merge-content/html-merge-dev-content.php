<?php defined( 'ABSPATH' ) || exit; ?>

<h5>Next Scheduled Merge</h5>
<div class="easysync-content-wrapper postbox">
    <div class="row">
        <div class="col-8">
            <p>Nothing currently scheduled.</p>
            <p><span class="easysync-current-time-label">Current Time: </span><span class="easysync-current-time"></span></p>
        </div>
        <div class="col-4">
            <button id="easysync-merge-now" type="button" class="btn btn-primary btn-lg">Merge Now</button>
        </div>  
    </div>
</div>

<br>
<h5>Last Merge</h5>
<div id="easysync-last-merge-section" class="easysync-content-wrapper postbox">
    <div class="row">
        <?php
                $last_merge = sez_get_last_job_data();
                if ( is_wp_error( $last_merge ) ): ?>
                    <div class="col">
                        <p>An error occurred fetching last merge data.</p>
                        <p><?= esc_html( $last_merge->get_error_message() ); ?></p>
                    </div>
        <?php    elseif ( false === $last_merge ): ?>
                    <div class="col">
                        <p>Nothing found for last merge.</p>
                    </div>
        <?php    else: ?>
                    <div class="col-6">
                        <p>Status: <strong><?= esc_html( $last_merge[ "status" ] ); ?></strong></p>
                        <?php if ( !empty( $last_merge[ "error" ] ) ): ?>
                            <p style="color:red"><strong>ERROR: <?= esc_html( $last_merge[ "error" ] ); ?></strong></p>
                        <?php endif; ?>
                        <p><?= $last_merge[ "merged_changes" ]; ?></p>
                        <p><?= $last_merge[ "unmerged_changes" ]; ?></p>
                        <?php
                            $job_id = $last_merge[ "job_id" ];
                            $log_path = SEZ_Merge_Log::get_path( $job_id );
                            if ( file_exists( $log_path ) ){
                                echo "<p><span data-job-id='{esc_html( $job_id )}' id='easysync-view-last-merge-log' class='easysync-hyperlink'>View Merge Log</span></p>";
                            }
                        ?>
                        
                    </div>
                    <div class="col-2"></div>
                    <div class="col-4" style="text-align:right">
                        <p>Job ID: <?= esc_html( $last_merge[ "job_id" ] ); ?></p>
                        <p>Started: <?= esc_html( $last_merge[ "start_time" ] ); ?></p>
                        <p>Finished: <?= esc_html( $last_merge[ "end_time" ] ); ?></p>
                        <p>Duration: <?= esc_html( $last_merge[ "duration" ] ); ?></p>
                    </div>
        <?php    endif; ?>            
    </div>
</div>

<br>
<h5>Merge Rules</h5>
<div class="easysync-content-wrapper postbox">
    <div class="row">
        <div class="col">
            <p>Merge rules determine which changes are brought in.</p>
            <form id="easysync-merge-rules-form" method="post" action="">
                <table class="table">
                    <tr>
                        <th>Rule</th>
                        <th>Enabled</th>
                    </tr>
                    <tbody>
                        <?php
                            $rules = SEZ_Rules::get_rules( false );
                            foreach ( $rules as $rule ):
                        ?>
                                <tr>
                                    <td>
                                        <label for="<?= esc_attr( $rule[ 'id' ] ); ?>">
                                            <p style="margin: 0"><?= esc_html( $rule[ "id" ] ); ?></p>
                                            <p class="text-secondary"><?= esc_html( $rule[ "description" ] ); ?></p>
                                        </label>
                                    </td>
                                    <td>
                                        <input type="checkbox" name="<?= esc_attr( $rule[ 'id' ] ); ?>" id="<?= esc_attr( $rule[ 'id' ] ); ?>" <?= $rule[ "enabled" ] ? "checked" : ""; ?> />
                                    </td>
                                </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <input type="hidden" name="sez-edit-rules" value="y" />
                <button type="submit" class="btn btn-primary" style="margin-top:20px" disabled>Save Rules</button>
            </form>
        </div>
    </div>
</div>