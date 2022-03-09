<div class="wrap">
    <h1 style="position:relative">
        <?php echo esc_html( get_admin_page_title() ); ?>
    </h1>

    <div style="margin: 25px 0">
        <h5>Setup Live Site</h5>
        <p>Get your live site registered now, so you can begin syncing changes.</p>

        <div class="mb-3" style="max-width: 350px">
            <label for="sez_name" class="form-label">Name</label>
            <input id="sez_name" v-model="" type="text" class="form-control" placeholder="John Doe" >
        </div>

        <div class="mb-3" style="max-width: 350px;">
            <label for="sez_email" class="form-label">Email</label>
            <input id="sez_email" v-model="" type="text" class="form-control" placeholder="email@example.com" >
        </div>
        <br>

        <p id="error" style="color:red"></p>
        <button type="button" id="ezs_authorize" class="btn btn-success sez-dynamic-button">
            <span>
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                Processing...
            </span>
            <span>Authorize Live Site</span>
        </button>
    </div>
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
            name: jQuery( "#sez_name" ).val(),
            email: jQuery( "#sez_email" ).val(),
            register_live_site: true,
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