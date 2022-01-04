<div class="wrap" id="synceasy_unset">
    <h1 style="position:relative">
        <?php echo esc_html( get_admin_page_title() ); ?>
    </h1>

    <div>
        <div class="row" style="margin: 25px 0">
            <p>This site has not yet been configured. <br>Start setup for this site so you can begin preventing data loss.</p>
            <?php
              if ( !empty( $sez_error ) ){
                echo "<p style='color:red'>" . $sez_error . "</p>";
              }
            ?>

            <div style="max-width: 300px">
                <form method="post" action="<?= admin_url(); ?>tools.php?page=synceasy">
                  <div class="mb-3" style="">
                      <label for="sez_site_type" class="form-label">Site Type</label>
                      <select name="sez_site_type" v-model="site_type" class="form-select form-select-lg" id="sez_site_type">
                          <option value="">-- Select Type --</option>
                          <option value="live">Live</option>
                          <option value="staging">Staging</option>
                      </select>
                  </div>

                  <div v-if="site_type == 'staging'" class="mb-3" style="">
                      <label for="sez_live_site" class="form-label">Live Site</label>
                      <input name="sez_live_site" v-model="live_site" type="text" class="form-control" id="sez_live_site" placeholder="https://www.example.com">
                  </div>

                  <div class="mb-3" style="">
                      <label for="sez_license_key" class="form-label">License Key</label>
                      <p v-if="site_type == 'staging'">License key must be the same as the one used on the live site.</p>
                      <input name="sez_license_key" v-model="license_key" type="text" class="form-control" id="sez_license_key">
                  </div>
                  <p v-if="!new_license.received && site_type == 'live'">Don't have a license key? 
                    <span style="text-decoration:underline;color:#2271b1" data-bs-toggle="modal" data-bs-target="#ezs_fetch_license_modal">Get one now.</span>
                  </p>
                  <!-- <p v-if="new_license.processing">Processing...</p> -->
                  <!-- <p style="color:red">{{ new_license.error }}</p> -->
                  <br>

                  <button type="submit" class="btn btn-success" style="min-width:250px">
                    Setup Now
                      <!-- <a href="<?= admin_url(); ?>tools.php?page=synceasy" style="color:#ffffff;text-decoration:none;display:block">Setup Now</a> -->
                  </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Fetch License Modal -->
  <div class="modal fade" id="ezs_fetch_license_modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="staticBackdropLabel">Get New License</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3" style="">
              <label for="sez_new_license_name" class="form-label">Name</label>
              <input v-model="new_license.name" type="text" class="form-control" id="sez_new_license_name">
          </div>

          <div class="mb-3" style="">
              <label for="sez_new_license_email" class="form-label">Email</label>
              <input v-model="new_license.email" type="text" class="form-control" id="sez_new_license_email">
          </div>

          <p style="color:red">{{ new_license.error }}</p>
          <!-- <div v-if="new_license.processing">Processing...</div> -->
          <!-- <div v-else>
              <p>{{ 'Your new license key has been fetched successfully. License key:' + license_key }}</p>
          </div> -->
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-success" v-on:click="get_license">
              <span v-if="new_license.processing">
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                Processing...
              </span>
              <span v-else>Get License Key</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</div>
