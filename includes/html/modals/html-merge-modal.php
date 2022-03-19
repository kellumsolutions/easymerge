<!-- Sync modal -->
<div id="sez_sync_modal" style="padding-top:100px">
    <img class="sez-hidden" id="sez_sync_modal_close_button" src="<?= SEZ_ASSETS_URL; ?>icons/3x/close-icon@3x.png" />
    <div class="container" style="max-width:1000px">
        <div class="row">
            <div class="col-9">
                <div class="sez-topbar">
                    <span class="sez-bubble"></span>
                    <span class="sez-bubble"></span>
                    <span class="sez-bubble"></span>
                    <p class="sez-console-title">EasySync Merge Console</p>
                </div>
                <div id="sez-blackbox">
                    <div class="sez-blackbox-content"></div>
                </div>
            </div>
            <div class="col-3">
                <div id="easysync-console-additional-output"></div>
                <div id="easysync-console-error" style="display:none">
                    <h3 style="color:red"><b>Error</b></h3>
                    <h5 style="color:red"></h5>
                </div>
            </div>
        </div>
    </div>
</div>