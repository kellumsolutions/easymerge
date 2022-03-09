<!-- Fetch websites being tracked in ajax call. Somehow. -->
<div class="wrap">
    <h1 style="position:relative">
        <?php echo esc_html( get_admin_page_title() ); ?>
    </h1>

    <div style="margin: 25px 0">
        <h5>Working on live site...</h5>
        <p>License Key: <strong class="text-success"><?= $license_key; ?></strong></p>
        <p></p>
        <br>
        <p>This site is currently not being tracked (Figure out how to show sites being tracked). To begin tracking:</p>
        <ol>
            <li>Clone this site to a staging environment.</li>
            <li>Navigate to Tools > EasySync on the staging environemnt.</li>
            <li>Authorize the dev site.</li>
            <li>Start tracking your database changes!</li>
        </ol>
    </div>
</div>