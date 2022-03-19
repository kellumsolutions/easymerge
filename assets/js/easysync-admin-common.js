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


jQuery( document ).ready( function( $ ){
    var easysync = {
        $nav_tab_wrapper: $( ".easysync-nav-tab-wrapper" ),
        $rules_form: $( "#easysync-merge-rules-form" ),
        modals: {
            $merge_confirmation: $( "#easysync-confimation-modal" ),
            $merge_console: $( "#sez_sync_modal" )
        },
        merge: {
            processing: false,
            job_id: "",
            interval: false,
        },
        init: function(){
            this.$nav_tab_wrapper.on( "click", ".nav-tab", this.on_nav_tab_click );
            this.$rules_form.on( "change", "input[type=checkbox]", this.on_rules_change );
            $( "#easysync-merge-now" ).on( "click", this.on_merge_now );
            $( "#easysync-merge-confirmation-start-merge" ).on( "click", this.on_merge_now_confirmed );
            this.modals.$merge_console.on( "click", "#sez_sync_modal_close_button", this.on_close_merge_console );
            
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
                    // if ( status == "success" && response.success ){
                    //     self.sync.output = response.data.output;
                    //     if ( response.data.status == "complete" ){
                    //         easysync.stop_merge_status_polling();
                    //         self.sync.additional_output = "<h3>Sync is done.</h3><p>Congratulations! Your sync is complete. The console output can also be found in the sync logs.</p>";    
                    //     }
                    //     return;
                    // }
                    // easysync.stop_merge_status_polling();
                    // var message = "An error occurred. Please try again later.";
                    
                    
                }
            )
            .fail( function( jqXHR, textStatus, errorThrown ){
                console.log( jqXHR, textStatus, errorThrown );
                console.log( "oops we got an error" );
                easysync.stop_merge_status_polling();
                // self.sync.error = "An unknown error occurred. Please try again later.";
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
        }
    };
    
    easysync.init();
});
