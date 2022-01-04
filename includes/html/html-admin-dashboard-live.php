<div class="wrap">
    <h1 style="position:relative">
        <?php echo esc_html( get_admin_page_title() ); ?>
    </h1>

    <div style="margin: 25px 0">
        <p>This site is currently not being tracked.</p>
        <p>Start tracking changes from this site to a staging site now.</p>
        <ol>
            <li>Duplicate your current site to a staging site.</li>
            <li>Install SyncEasyWP on your staging site.</li>
            <li>Set your "Live Site" to <b class="text-success"><?= site_url(); ?></b> and "License Key" to <b class="text-success"><?= $license_key; ?></b>.</li>
            <li>Start tracking your database changes!</li>
        </ol>
        <br>
        <!-- <button type="button" class="btn btn-success" style="min-width:250px">
            <a href="" style="color:#ffffff;text-decoration:none;display:block">Edit Site Settings</a>
        </button> -->
    </div>
</div>