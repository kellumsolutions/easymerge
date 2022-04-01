<?php
    defined( 'ABSPATH' ) || exit;
    
    $merged_changes = array();
    $unmerged_changes = array();
    $jobdata = sez_get_last_merge_job();
    
    if ( !empty( $jobdata ) ){
        $job_id = $jobdata[ "job_id" ];
        $merged_changes = sez_fetch_merged_changes( $job_id );
        $unmerged_changes = sez_fetch_unmerged_changes( $job_id );
    }
?>

<!-- Last Merge Changes Modal -->
<div class="modal fade" id="easysync-last-merge-changes-modal" tabindex="-1" aria-labelledby="easysync-last-merge-changes-modal-title" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="easysync-last-merge-changes-modal-title">Merge Changes</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
            <p><strong>Merged Changes:</strong></p>
            <?php if ( empty( $merged_changes ) ): ?>
                <p>None</p>
            <?php else: ?>
                <ul style="list-style-type: disc">
                <?php foreach( $merged_changes as $change ): ?>
                    <li><?= esc_html( $change ); ?></li>
                <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <br>

            <p><strong>Unmerged Changes:</strong></p>
            <?php if ( empty( $unmerged_changes ) ): ?>
                <p>None</p>
            <?php else: ?>
                <ul style="list-style-type: disc">
                <?php foreach( $unmerged_changes as $change ): ?>
                    <li><?= esc_html( $change ); ?></li>
                <?php endforeach; ?>
                </ul>
            <?php endif; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      </div>
    </div>
  </div>
</div>