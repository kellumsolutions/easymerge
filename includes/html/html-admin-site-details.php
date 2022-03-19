<div class="easysync-site-details">
    <span>Current Environment: <span><?= empty( SEZ()->settings->dev_site ) ? "Live" : "Dev"; ?></span></span>
    <?php if ( !empty( SEZ()->settings->dev_site ) ): ?>
        <span>Live Site: <span><?= SEZ()->settings->live_site; ?></span></span>
    <?php endif; ?>
    <span>License Key: <span><?= SEZ()->settings->license; ?></span></span>
</div>