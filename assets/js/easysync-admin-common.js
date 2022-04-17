Date.prototype.timeNow = function () { 
    var days = [ "Sun", "Mon", "Tues", "Wed", "Thur", "Fri", "Sat" ];
    var months = [ "Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec" ];
    var space = " ";
    return (
        days[ this.getDay() ] + "," + space +
        months[ this.getMonth() ] + space +
        this.getDate() + "," + space +
        this.getFullYear() + space +
        ( this.getHours() > 12 ? this.getHours() - 12 : this.getHours() ) + ":" + 
        String( this.getMinutes() ).padStart( 2, '0' )  + space +
        ( this.getHours() > 12 ? "pm" : "am" )
    );
}

function is_wp_error( response ){
    return "object" === typeof response && "data" in response && Array.isArray( response.data ) && response.data.length > 0 && "code" in response.data[0] && "message" in response.data[0];
}

function wp_error_message( response ){
    return response.data[0].message;
}


jQuery( document ).ready( function( $ ){
    window.easysync = {
        $nav_tab_wrapper: $( ".easysync-nav-tab-wrapper" ),
        $settings_form: $( "#easysync-nav-tab-settings-content form" ),
        $license_content: $( "#easysync-nav-tab-license-content" ),
        updating_license: false,
        init: function(){
            this.$nav_tab_wrapper.on( "click", ".nav-tab", this.on_nav_tab_click );
            this.$settings_form.on( "submit", this.on_save_settings );
            this.$settings_form.on( "change", "input, select", this.on_settings_change );
            $( "#easysync-nav-tab-advancedtools-content" ).on( "click", "button", this.on_run_advancedtool );
            this.$license_content.on( "click", "#easymerge-toggle-update-license", this.toggle_update_license_field );
            this.$license_content.on( "click", "#easymerge-update-license", this.on_update_license );
        },
        on_nav_tab_click: function( e ){
            e.preventDefault();
            if ( e.target.id ){
                var parts = e.target.id.split( "-" );
                if ( parts.length > 0 ){
                    easysync.open_tab( parts[ parts.length - 1 ] );
                }
            }
        },
        open_tab: function( active_key ){
            // To be active.
            var active_tab_id = '#easysync-nav-tab-' + active_key;
            var active_content_id = active_tab_id + "-content";

            var keys = $( ".easysync-nav-tab-wrapper > .nav-tab" );
            keys.each( function( i, tab ){
                var tab = $( this );
                var tab_key = tab.attr( "id" ).split( "-" );
                tab_key = tab_key[ tab_key.length - 1 ];

                if ( $( active_tab_id ).length == 0 || $( active_content_id ).length == 0 ){ return; }

                if ( active_key == tab_key ){
                    // $( document.body ).trigger( 'opened_tab', active_key );

                    $( '#easysync-nav-tab-' + tab_key + '-content' ).show();
                    tab.addClass( 'nav-tab-active' );

                } else {
                    $( '#easysync-nav-tab-' + tab_key + '-content' ).hide();
                    tab.removeClass( 'nav-tab-active' );
                }
            });
        },
        on_save_settings: function( e ){
            e.preventDefault();
            easysync.$settings_form.find( ".error" ).html( "" );
            var submit_buton = easysync.$settings_form.find( "button[type=submit]" );
            submit_buton.addClass( "processing" );

            jQuery.post(
                ajaxurl,
                $( this ).serialize(),
                function( response, status ){
                    console.log( response, status );
                    
                    if ( status == "success" ){
                        if ( true == response ){
                            easysync.$settings_form.find( "button[type=submit]" ).attr( "disabled", true );
                        
                        // WP error.
                        } else if ( is_wp_error( response ) ){
                            easysync.$settings_form.find( ".error" ).html( wp_error_message( response ) );
                        }
                    
                    } else {
                        easysync.$settings_form.find( ".error" ).html( "An unknown error occurred. Please try again later." );
                    }
                }
            )
            .fail( function( jqXHR, textStatus, errorThrown ){
                console.log( jqXHR, textStatus, errorThrown );
                console.log( "oops we got an error" );
                easysync.$settings_form.find( ".error" ).html( "An unknown error occurred. Please try again later." );
            })
            .always( function(){
                submit_buton.removeClass( "processing" );
            });            
        },
        on_settings_change: function(){
            easysync.$settings_form.find( "button[type=submit]" ).attr( "disabled", false );
        },
        on_run_advancedtool: function( e ){
            var action = "";
            var self = $( this );

            if ( self.hasClass( "easysync-reset-settings" ) ){
                action = "reset_settings";
            } else if ( self.hasClass( "easysync-reset-data" ) ){
                action = "reset_data";
            } else if ( self.hasClass( "easysync-delete-change-files" ) ){
                action = "delete_change_files";
            } else if ( self.hasClass( "easysync-delete-merge-logs" ) ){
                action = "delete_merge_logs";
            }

            var response_el = self.next( ".easysync-advancedtool-response" );
            response_el.html( "" );

            // Start processing indicator.
            self.children( "span" ).removeClass( "visually-hidden" );

            jQuery.post(
                ajaxurl,
                { "action": "sez_run_advancedtool", easysync_advancedtools: action },
                function( response, status ){
                    console.log( response, status );
                    
                    if ( status == "success" ){                        
                        // WP error.
                        if ( is_wp_error( response ) ){
                            response_el.html( wp_error_message( response ) );
                        } else {
                            response_el.html( response );
                            if ( "reset_settings" == action || "reset_data" == action ){
                                window.location.reload();
                            }
                        }
                    
                    } else {
                        response_el.html( "<span class='easysync-advancedtool-fail'>An unknown error occurred. Please try again later.</span>" );
                    }
                }
            )
            .fail( function( jqXHR, textStatus, errorThrown ){
                console.log( jqXHR, textStatus, errorThrown );
                console.log( "oops we got an error" );
                response_el.html( "<span class='easysync-advancedtool-fail'>An unknown error occurred. Please try again later.</span>" );
            })
            .always( function(){
                self.children( "span" ).addClass( "visually-hidden" );
                response_el.removeClass( "visually-hidden" );
            });
        },
        toggle_update_license_field: function(e){
            var el = $( "#easymerge-toggle-update-license" );
            if ( el.hasClass( "active" ) ){
                el.removeClass( "active" );
                el.html( "Update" );
            } else {
                el.addClass( "active" );
                el.html( "Cancel" );
            }
        },
        on_update_license: function(){
            if ( easysync.updating_license ){ return; }

            var indicator = $( "#easymerge-update-license-progress" );
            indicator.html( "Processing..." );

            jQuery.post(
                ajaxurl,
                { "action": "sez_update_license", "license": $( "#easymerge-license-key").val() },
                function( response, status ){
                    console.log( response, status );
                    
                    if ( status == "success" ){                        
                        // WP error.
                        if ( is_wp_error( response ) ){
                            indicator.html( wp_error_message( response ) );
                        } else {
                            window.location.reload();
                        }
                    
                    } else {
                        indicator.html( "<span class='easysync-response-fail'>An unknown error occurred. Please try again later.</span>" );
                    }
                }
            )
            .fail( function( jqXHR, textStatus, errorThrown ){
                console.log( jqXHR, textStatus, errorThrown );
                console.log( "oops we got an error" );
                indicator.html( "<span class='easysync-response-fail'>An unknown error occurred. Please try again later.</span>" );
            })
            .always( function(){
                easysync.updating_license = false;
            });
        }
    };
    
    easysync.init();
});
