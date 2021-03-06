<?php
    defined( 'ABSPATH' ) || exit;   

    $dev_site = SEZ()->settings->dev_site;
    $live_site = SEZ()->settings->live_site;
    $site_env = "Live";
    if ( !empty( $dev_site ) || sez_clean_domain( $live_site ) !== sez_clean_domain( site_url() ) ){
        $site_env = "Dev";
    }
?>
<div class="easysync-site-details">
    <span>Current Environment: <span><?php echo esc_html( $site_env ); ?></span></span>
    <?php if ( !empty( SEZ()->settings->dev_site ) ): ?>
        <span>Live Site: <span><?php echo esc_html( SEZ()->settings->live_site ); ?></span></span>
    <?php endif; ?>
    <span>License Key: <span><?php echo esc_html( SEZ()->settings->license ); ?></span></span>
</div>