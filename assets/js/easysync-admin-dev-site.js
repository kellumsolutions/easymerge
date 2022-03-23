jQuery( document ).ready( function( $ ){
	var easysync_dev = {
        $rules_form: $( "#easysync-merge-rules-form" ),
        $last_merge_section: $( "#easysync-last-merge-section" ),
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
            this.$rules_form.on( "change", "input[type=checkbox]", this.on_rules_change );
            $( "#easysync-merge-now" ).on( "click", this.on_merge_now );
            $( "#easysync-merge-confirmation-start-merge" ).on( "click", this.on_merge_now_confirmed );
            this.modals.$merge_console.on( "click", "#sez_sync_modal_close_button", this.on_close_merge_console );
            this.$last_merge_section.on( "click", "#easysync-view-last-merge-log", this.on_view_last_merge_log );
            this.$last_merge_section.on( "click", "#easysync-view-merged-details, #easysync-view-unmerged-details", this.on_show_last_merged_changes );

            // Triggers
            $( document.body ).on( "start_merge", this.start_merge );

            // Set current time interval.
            // Update every 30 seconds.
            this.set_current_time();
            setInterval( this.set_current_time, 1000 * 30 );
        },
        set_current_time: function(){
            $( ".easysync-current-time" ).html( new Date().timeNow() );
        },
        on_rules_change: function( e ){
            $( "#easysync-merge-rules-form button[type=submit]" ).prop( "disabled", false );
        },
        on_merge_now: function( e ){
            var modal = bootstrap.Modal.getOrCreateInstance( easysync_dev.modals.$merge_confirmation[0] );
            modal.show();   
        },
        on_merge_now_confirmed: function( e ){
            var modal = bootstrap.Modal.getOrCreateInstance( easysync_dev.modals.$merge_confirmation[0] );
            modal.hide(); 
            $( document.body ).trigger( 'start_merge' );
        },
        start_merge: function(){
            if ( !easysync_dev.merge.processing ){
                easysync_dev.modals.$merge_console.addClass( "active" );
                easysync_dev.merge.processing = true;

                $( "#easysync-console-additional-output" ).html( "<h5>Starting merge...</h5>" );

                // Kickoff merge.        
                $.post(
                    ajaxurl,
                    { "action": "sez_sync_changes" },
                    function( response, status ){
                        console.log( response, status );
                        if ( status == "success" ){
                            if ( response.success ){
                                easysync_dev.merge.job_id = response.data;
                                easysync_dev.merge.interval = window.setInterval( easysync_dev.poll_merge_status, 1000 );
                            }
                        }
                    }
                )
                .fail( function( jqXHR, textStatus, errorThrown ){
                    console.log( jqXHR, textStatus, errorThrown );
                    console.log( "oops we got an error" );
                    easysync_dev.clear_merge_console();
                })
                .always( function(){
                    easysync_dev.merge.processing = false;
                });
            }
        },
        stop_merge_status_polling: function(){
            window.clearInterval( easysync_dev.merge.interval );
            $( "#sez_sync_modal_close_button" ).removeClass( "sez-hidden" );
        },
        poll_merge_status: function(){
            // Skip if another request is in-progress.
            if ( easysync_dev.merge.processing ){
                return;
            }

            easysync_dev.merge.processing = true;
    
            jQuery.post(
                ajaxurl,
                { "action": "sez_sync_get_status", "sez_job_id": easysync_dev.merge.job_id },
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
                            easysync_dev.stop_merge_status_polling();
                        
                        } else if ( "complete" == response.data.progress ){
                            easysync_dev.stop_merge_status_polling();
                        }

                    } else {
                        $( "#easysync-console-error h5" ).html( "<p>An unknown error occurred. Please try again later.</p>" );
                        $( "#easysync-console-error" ).show();
                        easysync_dev.stop_merge_status_polling();
                    }
                }
            )
            .fail( function( jqXHR, textStatus, errorThrown ){
                console.log( jqXHR, textStatus, errorThrown );
                console.log( "oops we got an error" );
                $( "#easysync-console-error h5" ).html( "An unknown error occurred. Please try again later." );
                $( "#easysync-console-error" ).show();
                $( "#easysync-console-additional-output" ).html( "" );
                easysync_dev.stop_merge_status_polling();
            })
            .always( function(){
                easysync_dev.merge.processing = false;
            });
        },
        on_close_merge_console: function( e ){
            window.location.reload();
            // easysync.modals.$merge_console.removeClass( "active" );
            // easysync.clear_merge_console();
        },
        clear_merge_console: function(){
            $( "#easysync-console-error" ).hide();
            $( "#easysync-console-additional-output" ).html( "" );
            $( "#sez-blackbox > .sez-blackbox-content" ).html( "" );
        },
        on_view_last_merge_log: function(){
            var modal = bootstrap.Modal.getOrCreateInstance( easysync_dev.modals.$last_merge_log[0] );
            modal.show(); 
        },
        on_show_last_merged_changes: function(){
            var modal = bootstrap.Modal.getOrCreateInstance( easysync_dev.modals.$last_merge_changes[0] );
            modal.show();  
        },
    };

    easysync_dev.init();
});