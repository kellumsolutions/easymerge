<?php defined( 'ABSPATH' ) || exit; ?>

<div style="display:none" id="easysync-nav-tab-license-content"> 
    <br>
    <table class="table table-striped" style="margin-left: 7px">
        <tbody>
            <tr>
                <td>License Key:</td>
                <td><?= esc_html( SEZ()->settings->license ); ?></td>
            </tr>
            <tr>
                <td>License Type:</td>
                <td>Basic<br><span style="font-style:italic;font-size: 14px">Basic licenses only allow one live site and one dev site.</span></td>
            </tr>
        </tbody>
    </table>
    <p><strong style="text-transform:uppercase;font-weight: bold">Important:</strong> Do not share license key.</p>
</div>