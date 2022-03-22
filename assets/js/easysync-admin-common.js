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
    var easysync = {
        $nav_tab_wrapper: $( ".easysync-nav-tab-wrapper" ),
        $rules_form: $( "#easysync-merge-rules-form" ),
        $last_merge_section: $( "#easysync-last-merge-section" ),
        $settings_form: $( "#easysync-nav-tab-settings-content form" ),
        modals: {
            $merge_confirmation: $( "#easysync-confimation-modal" ),
            $merge_console: $( "#sez_sync_modal" ),
            $last_merge_log: $( "#easysync-last-merge-log-modal" ),
            $last_merge_changes: $( "#easysync-last-merge-changes-modal" )
        },
        merge: {
            processing: false,
            job_id: "",
            interval: false,
            successful: false
        },
        init: function(){
            this.$nav_tab_wrapper.on( "click", ".nav-tab", this.on_nav_tab_click );
            this.$rules_form.on( "change", "input[type=checkbox]", this.on_rules_change );
            $( "#easysync-merge-now" ).on( "click", this.on_merge_now );
            $( "#easysync-merge-confirmation-start-merge" ).on( "click", this.on_merge_now_confirmed );
            this.modals.$merge_console.on( "click", "#sez_sync_modal_close_button", this.on_close_merge_console );
            this.$last_merge_section.on( "click", "#easysync-view-last-merge-log", this.on_view_last_merge_log );
            this.$last_merge_section.on( "click", "#easysync-view-merged-details, #easysync-view-unmerged-details", this.on_show_last_merged_changes );
            this.$settings_form.on( "submit", this.on_save_settings );
            this.$settings_form.on( "change", "input, select", this.on_settings_change );
            $( "#easysync-nav-tab-advancedtools-content" ).on( "click", "button", this.on_run_advancedtool );

            // Triggers
            $( document.body ).on( "start_merge", this.start_merge );

            // Set current time interval.
            // Update every 30 seconds.
            this.set_current_time();
            setInterval( this.set_current_time, 1000 * 30 );
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
        set_current_time: function(){
            $( ".easysync-current-time" ).html( new Date().timeNow() );
        },
        on_rules_change: function( e ){
            $( "#easysync-merge-rules-form button[type=submit]" ).prop( "disabled", false );
        },
        on_merge_now: function( e ){
            var modal = bootstrap.Modal.getOrCreateInstance( easysync.modals.$merge_confirmation[0] );
            modal.show();   
        },
        on_merge_now_confirmed: function( e ){
            var modal = bootstrap.Modal.getOrCreateInstance( easysync.modals.$merge_confirmation[0] );
            modal.hide(); 
            $( document.body ).trigger( 'start_merge' );
        },
        start_merge: function(){
            if ( !easysync.merge.processing ){
                easysync.modals.$merge_console.addClass( "active" );
                easysync.merge.processing = true;

                $( "#easysync-console-additional-output" ).html( "<h5>Starting merge...</h5>" );

                // Kickoff merge.        
                $.post(
                    ajaxurl,
                    { "action": "sez_sync_changes" },
                    function( response, status ){
                        console.log( response, status );
                        if ( status == "success" ){
                            if ( response.success ){
                                easysync.merge.job_id = response.data;
                                easysync.merge.interval = window.setInterval( easysync.poll_merge_status, 1000 );
                            }
                        }
                    }
                )
                .fail( function( jqXHR, textStatus, errorThrown ){
                    console.log( jqXHR, textStatus, errorThrown );
                    console.log( "oops we got an error" );
                    easysync.clear_merge_console();
                })
                .always( function(){
                    easysync.merge.processing = false;
                });
            }
        },
        stop_merge_status_polling: function(){
            window.clearInterval( easysync.merge.interval );
            $( "#sez_sync_modal_close_button" ).removeClass( "sez-hidden" );
        },
        poll_merge_status: function(){
            // Skip if another request is in-progress.
            if ( easysync.merge.processing ){
                return;
            }

            easysync.merge.processing = true;
    
            jQuery.post(
                ajaxurl,
                { "action": "sez_sync_get_status", "sez_job_id": easysync.merge.job_id },
                function( response, status ){
                    console.log( response, status );
                    if ( status == "success" ){
                        // Get output.
                        $( "#sez-blackbox > .sez-blackbox-content" ).html( response.data.output );
                        $( "#easysync-console-additional-output" ).html( response.data.additional_output );

                        // Parse errors.
                        if ( "error" in response.data && "" != response.data.error ){
                            $( "#easysync-console-error h5" ).html( response.data.error.message );
                            $( "#easysync-console-error" ).show();
                            easysync.stop_merge_status_polling();
                        
                        } else if ( "complete" == response.data.progress ){
                            easysync.stop_merge_status_polling();
                        }

                    } else {
                        $( "#easysync-console-error h5" ).html( "<p>An unknown error occurred. Please try again later.</p>" );
                        $( "#easysync-console-error" ).show();
                        easysync.stop_merge_status_polling();
                    }
                }
            )
            .fail( function( jqXHR, textStatus, errorThrown ){
                console.log( jqXHR, textStatus, errorThrown );
                console.log( "oops we got an error" );
                $( "#easysync-console-error h5" ).html( "An unknown error occurred. Please try again later." );
                $( "#easysync-console-error" ).show();
                $( "#easysync-console-additional-output" ).html( "" );
                easysync.stop_merge_status_polling();
            })
            .always( function(){
                easysync.merge.processing = false;
            });
        },
        on_close_merge_console: function( e ){
            easysync.modals.$merge_console.removeClass( "active" );
            easysync.clear_merge_console();
        },
        clear_merge_console: function(){
            $( "#easysync-console-error" ).hide();
            $( "#easysync-console-additional-output" ).html( "" );
            $( "#sez-blackbox > .sez-blackbox-content" ).html( "" );
        },
        on_view_last_merge_log: function(){
            var modal = bootstrap.Modal.getOrCreateInstance( easysync.modals.$last_merge_log[0] );
            modal.show(); 
        },
        on_show_last_merged_changes: function(){
            var modal = bootstrap.Modal.getOrCreateInstance( easysync.modals.$last_merge_changes[0] );
            modal.show();  
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
                            window.location.reload();
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
        }
    };
    
    easysync.init();
});
