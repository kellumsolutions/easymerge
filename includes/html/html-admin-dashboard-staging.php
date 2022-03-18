<div class="wrap" id="synceasy_staging">
    <h1 style="position:relative">
        <?php echo esc_html( get_admin_page_title() ); ?>
    </h1>
    <br>
    <div class="nav-tab-wrapper easysync-nav-tab-wrapper">
        <a class="nav-tab nav-tab-active" id="easysync-nav-tab-merge" href="<?= admin_url( "tools.php?page=" . SyncEasy_Admin_Page::$handle . "#merge" ); ?>">Merge/Sync</a>
        <a class="nav-tab" id="easysync-nav-tab-license" href="<?= admin_url( "tools.php?page=" . SyncEasy_Admin_Page::$handle . "#license" ); ?>">License</a>
        <a class="nav-tab" id="easysync-nav-tab-settings" href="<?= admin_url( "tools.php?page=" . SyncEasy_Admin_Page::$handle . "#settings" ); ?>s">Settings</a>
        <a class="nav-tab" id="easysync-nav-tab-advancedtools" href="<?= admin_url( "tools.php?page=" . SyncEasy_Admin_Page::$handle . "#advanced-tools" ); ?>">Advanced Tools</a>
    </div>

    <div class="easysync-nav-tab-content">
        <?php require_once( __DIR__ . "/nav-tab-sections/html-merge-nav-tab-section.php" ); ?>
        <?php require_once( __DIR__ . "/nav-tab-sections/html-license-nav-tab-section.php" ); ?>
        <?php require_once( __DIR__ . "/nav-tab-sections/html-settings-nav-tab-section.php" ); ?>
        <?php require_once( __DIR__ . "/nav-tab-sections/html-advanced-tools-nav-tab-section.php" ); ?>
    </div>
    


    <!-- Sync modal -->
    <div id="sez_sync_modal" :class="{active: sync.show_console}" style="padding-top:100px">
        <img :class="{ 'sez-hidden': sync.processing }" id="sez_sync_modal_close_button" v-on:click="close_console" src="<?= SEZ_ASSETS_URL; ?>icons/3x/close-icon@3x.png" />
        <div class="container" style="max-width:1000px">
            <div class="row">
                <div class="col-9">
                    <div class="sez-topbar">
                        <span class="sez-bubble"></span>
                        <span class="sez-bubble"></span>
                        <span class="sez-bubble"></span>
                        <p class="sez-console-title">SyncEasy Console</p>
                    </div>
                    <div id="sez-blackbox">
                        <div class="sez-blackbox-content">
                            <p v-for="line in sync.output">{{ line }}</p>
                        </div>
                    </div>
                </div>
                <div class="col-3">
                    <div v-html="sync.additional_output"></div>
                    <div :class="{ 'sez-active': sync.error, 'sez-hidden': !sync.error }">
                        <h3 style="color:red"><b>Error</b></h3>
                        <h5 style="color:red">{{ sync.error }}</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>