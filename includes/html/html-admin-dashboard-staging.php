<div class="wrap" id="synceasy_staging">
    <h1 style="position:relative">
        <?php echo esc_html( get_admin_page_title() ); ?>
    </h1>

    <?php 
        if ( false == $dump_exists ):
    ?>
            <div class="row">
                <div class="col-6" style="margin: 25px 0 50px 0;padding:25px;border:3px solid red">
                    <form method="post" action="">
                        <h5>Additional Action Required:</h5>
                        <p>We have no reference of your live site (<?= $live_site; ?>) stored on our servers. This must be done before any syncs can be made.</p>
                        <input type="hidden" name="sez_store_reference" value="y" />
                        <button type="submit" class="btn btn-success" style="width: 100%;max-width:250px">Store Reference</button>
                    </form>
                </div>
            </div>
    <?php endif; ?>

    <div class="row" style="margin: 25px 0">
        <div class="col-4">
            <p>Last Synced: December 8, 2021 10:22am</p>
            <button type="button" class="btn btn-success" v-on:click="sync_changes" style="min-width:250px">
                <span v-if="sync.processing">
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    Syncing...
                </span>
                <span v-else>Sync Changes</span>
            </button>
        </div>
    </div>

    <div class="row">
        <ul class="nav nav-tabs">
            <li v-for="( tab, i ) in tabs" v-on:click="change_tab(i)" class="nav-item" style="margin-bottom:0">
                <a class="nav-link" :class="{active: tab.active }" href="#">{{ tab.label }}</a>
            </li>
        </ul>
        <div style="background:#ffffff;border: 1px solid #dee2e6;padding:20px">
            <div v-if="selected_tab == 'pending_changes'">
                <p>pending changes...</p>
            </div>
            <div v-else-if="selected_tab == 'synced_changes'">
                <p>synced changes...</p>
            </div>
            <div v-else-if="selected_tab == 'settings'">
                <div>
                    <table class="table">
                        <tr>
                            <td style="width:50%"></td>
                            <td style="width:50%"></td>
                        </tr>
                        <tr>
                            <td><p>License Key:</p></td>
                            <td><p><?= $license_key; ?></p></td>
                        </tr>
                        <tr>
                            <td>
                                <p>Live Site:</p>
                            </td>
                            <td>
                                <p><?= isset( $sez_settings[ "live_site" ] ) ? $sez_settings[ "live_site" ] : "Unknown"; ?></p>
                            </td>
                        </tr>
                    </table>
                    <button class="btn btn-success" type="button">Save Settings</button>
                </div>
            </div>
        </div>
    </div>
</div>