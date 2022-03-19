<?php

?>

<!-- Merge Confirmation Modal -->
<div class="modal fade" id="easysync-confimation-modal" tabindex="-1" aria-labelledby="easysync-confimation-modal-title" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="easysync-confimation-modal-title">Merge Confirmation</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
            <p>Are you sure you want to merge in your database changes from your live site to your local site?</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="easysync-merge-confirmation-start-merge">Start Merge</button>
      </div>
    </div>
  </div>
</div>