<?php defined( 'ABSPATH' ) || exit; ?>

<div style="margin: 25px 0">
    <h5>Authorize Dev Site</h5>
    <p>Authorize your dev site now, so you can begin syncing changes from <strong class="text-success"><?= esc_html( SEZ()->settings->live_site ); ?></strong>.</p>
    <p>License Key: <strong class="text-success"><?= esc_html( SEZ()->settings->license ); ?></strong></p>
    <p>Live Site: <strong class="text-success"><?= esc_html( SEZ()->settings->live_site ); ?></strong></p>
    <br>
    <p id="error" style="color:red"></p>
    <button type="button" id="ezs_authorize" class="btn btn-success easysync-dynamic">
        <span>
            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
            Processing...
        </span>
        <span>Authorize Dev Site</span>
    </button>
</div>

<script>

    function ezs_on_success( response, status ){
        console.log( response, status );
        var $ = jQuery;
        var errorMessage = "An error occurred. Please try again later.";
        if ( "success" == status ){
            if ( response.success ){
                $( "#ezs_authorize > span:last-of-type" ).html( "Redirecting..." );
                window.location.reload();
                return;
            } else if ( 
                "data" in response && 
                Array.isArray( response.data ) && 
                response.data.length > 0 && 
                "message" in response.data[0] 
            ){
                errorMessage = response.data[0].message;
            }
        }
        $( "#error" ).html( errorMessage );
    }

    function ezs_on_fail( jqXHR ){
        console.log( jqXHR );
        jQuery( "#error" ).html( "An unknown error occurred. Please try again later." );
    }

    function ezs_post_authorize( callback_success, callback_fail ){
        var data = { 
            register_dev_site: true,
            action: "sez_admin_actions"
        };

        jQuery( "#error" ).html( "" );
        jQuery( "#ezs_authorize" ).addClass( "processing" );
        
        jQuery.post(
            window.ajaxurl,
            data,
            function( response, status ){
                callback_success( response, status );
            }
        )
        .fail( function( jqXHR, textStatus, errorThrown ){
            callback_fail( jqXHR );
        })
        .always( function(){
            jQuery( "#ezs_authorize" ).removeClass( "processing" );
        });
    }

    jQuery( document ).ready( function( $ ){
        $( document ).on( "click", "#ezs_authorize", function( e ){
            ezs_post_authorize( ezs_on_success, ezs_on_fail );
        });
    });
</script>