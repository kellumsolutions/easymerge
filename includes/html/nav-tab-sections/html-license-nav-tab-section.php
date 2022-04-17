<?php defined( 'ABSPATH' ) || exit; ?>

<div style="display:none" id="easysync-nav-tab-license-content"> 
    <br>
    <table class="table table-striped" style="margin-left: 7px">
        <tbody>
            <tr>
                <td>License Key:</td>
                <td>
                    <span><?php echo esc_html( SEZ()->settings->license ); ?></span>
                    <span id="easymerge-toggle-update-license" style="margin-left:10px" class="easysync-hyperlink">Update</span>
                    <div>
                        <div style="margin: 10px 0" class="easymerge-single-line-field">
                            <input id="easymerge-license-key" placeholder="New license key" type="text" />
                            <button id="easymerge-update-license" type="button" class="btn btn-primary">Update</button>
                        </div>
                        <p id="easymerge-update-license-progress"></p>
                    </div>
                </td>
            </tr>
            <tr>
                <td>License Type:</td>
                <td>Basic<br><span style="font-style:italic;font-size: 14px">Basic licenses only allow one live site and one dev site.</span></td>
            </tr>
        </tbody>
    </table>
    <p><strong style="text-transform:uppercase;font-weight: bold">Important:</strong> Do not share license key.</p>
</div>