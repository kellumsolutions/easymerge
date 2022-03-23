<div class="wrap" id="easysync-admin">
    <h1 style="position:relative">
        <?php echo esc_html( get_admin_page_title() ); ?>
    </h1>

    <?php require_once( __DIR__ . "/html-admin-site-details.php" ); ?>

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
    
    <!-- Include modals. -->
    <?php require_once( __DIR__ . "/modals/html-merge-modal.php" ); ?>
    <?php require_once( __DIR__ . "/modals/html-merge-confirmation-modal.php" ); ?>
    <?php require_once( __DIR__ . "/modals/html-last-merge-log-modal.php" ); ?>
    <?php require_once( __DIR__ . "/modals/html-last-merge-changes-modal.php" ); ?>
</div>