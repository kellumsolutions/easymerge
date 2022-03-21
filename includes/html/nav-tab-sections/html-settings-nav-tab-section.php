<?php
    $log_levels = array_keys( SEZ_LOG_LEVELS );
?>

<div style="display:none" id="easysync-nav-tab-settings-content"> 
    <br>
    <form method="post" action="">
        <table class="table" style="margin-left: 7px">  
        <colgroup>
            <col span="1" style="width: 50%;">
            <col span="1" style="width: 50%;">
        </colgroup>

            <tbody style="border-width: 0">
                <tr>
                    <td>Merge Log Level:</td>
                    <td>
                        <select name="easysync-merge-log-level">
                            <?php foreach( $log_levels as $level ): ?>
                                <option value="<?= $level; ?>" <?= strtoupper( SEZ()->settings->merge_log_level ) === $level ? "selected" : ""; ?>><?= $level; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>Auto-Delete Logs<br><span class="text-secondary">Old logs files will be automatically deleted. Only the most recent log file will be retained.</span></td>
                    <td><input type="checkbox" name="easysync-auto-delete-logs" <?= SEZ()->settings->auto_delete_logs ? "checked" : ""; ?>/></td>
                </tr>
                <tr>
                    <td>Auto-Delete Change Files<br>
                        <span class="text-secondary">Old change files will be automatically deleted. Only the most recent change file will be retained. Change files by default are located in <?= trailingslashit( wp_upload_dir()[ "basedir" ] ) . "sez-changes"; ?></span>
                    </td>
                    <td><input type="checkbox" name="easysync-auto-delete-change-files" <?= SEZ()->settings->auto_delete_change_files ? "checked" : ""; ?>/></td>
                </tr>
            </tbody>
        </table>
        <br>
        <input type="hidden" name="action" value="sez_save_settings" />
        <p class="error" style="color:red"></p>
        <button type="submit" class="btn btn-primary easysync-dynamic">
            <span>
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                Saving...
            </span>
            <span>Save Settings</span>
        </button>
    </form>
</div>
