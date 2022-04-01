<?php
    defined( 'ABSPATH' ) || exit;
    
    $jobdata = sez_get_last_merge_job();
    $job_id = empty( $jobdata ) ? false : $jobdata[ "job_id" ];
?>

<!-- Last Merge Log Modal -->
<div class="modal fade" id="easysync-last-merge-log-modal" tabindex="-1" aria-labelledby="easysync-last-merge-log-modal-title" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="easysync-last-merge-log-modal-title">Merge Log</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
            <?php if ( $job_id ): ?>
                <p>Job ID: <?= esc_html( $job_id ); ?></p>
            <?php endif; ?>

            <?php
                if ( $job_id ){
                    $log = sez_get_merge_log( $job_id );
                    if ( is_wp_error( $log ) ){
                        echo "<p style='color:red'><strong>" . esc_html( $log->get_error_message() ) . "</strong></p>";
                    
                    } else {
                ?>
                    <div class="easysync-last-merge-log-blackbox">
                        <div class="easysync-last-merge-log-blackbox-content">
                            <?= $log->get_console_output(); ?>
                        </div>
                    </div>
                <?php
                    }
                } else {
                    echo "<p style='color:red'><strong>Unable to locate merge job log.</strong></p>";
                }
            ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      </div>
    </div>
  </div>
</div>