<?php defined( 'ABSPATH' ) || exit; ?>

<div style="display:none" id="easysync-nav-tab-advancedtools-content"> 
    <br>
    <table class="table" style="margin-left: 7px">
        <colgroup>
            <col span="1" style="width: 30%;">
            <col span="1" style="width: 70%;">
        </colgroup>
        <tbody style="border-width: 0">
            <tr>
                <td><p><strong>Reset EasySync Settings</strong></p></td>
                <td>
                    <button class="btn btn-light border-1 border-primary btn-sm text-primary easysync-reset-settings" type="button">
                        <span class="visually-hidden spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>
                        Reset Settings
                    </button>
                    <span class="easysync-advancedtool-response visually-hidden"></span>
                    <p class="text-secondary">Clears all EasySync settings (ex. license key, live site, dev site, etc). <strong>This action can not be undone.</strong></p>
                </td>
            </tr>
            <tr>
                <td><p><strong>Reset Data</strong></p></td>
                <td>
                    <button class="btn btn-light border-1 border-primary btn-sm text-primary easysync-reset-data" type="button">
                        <span class="visually-hidden spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>
                        Reset Data
                    </button>
                    <span class="easysync-advancedtool-response visually-hidden"></span>
                    <p class="text-secondary">Deletes all merge and change data from the database. <strong>This action can not be undone.</strong></p>
                </td>
            </tr>
        </tbody>
    </table>
</div>