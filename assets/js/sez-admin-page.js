jQuery( document ).ready( function( $ ){
    if ( $( "#synceasy_staging" ).length ){
        var ezs = new Vue({
            el: "#synceasy_staging",
            data: {
                tabs: [
                    // {
                    //     label: "Pending Changes",
                    //     name: "pending_changes",
                    //     active: true
                    // },
                    {
                        label: "Synced Changes",
                        name: "synced_changes",
                        active: true
                    },
                    {
                        label: "Rules",
                        name: "rules",
                        active: false
                    },
                    {
                        label: "Settings",
                        name: "settings",
                        active: false
                    }
                ],
                // refresh_processing: false,
                sync: {
                    show_console: false,
                    processing: false,
                    tracking: false,
                    interval: false,
                    job_id: "",
                    output: [ "Starting sync..." ],
                    error: "",
                    additional_output: ""
                },
                settings: {
                    live_site: "",
                    live_sites: [ "google.com" ]
                }
            },
            computed: {
                selected_tab: function(){
                    var output = false;
                    this.tabs.forEach( function( tab, index ){
                        if ( tab.active ){
                            output = tab.name;
                            return;
                        }
                    });
                    return output;
                }
            },
            methods: {
                change_tab: function( i ){
                    this.tabs[0].active = false;
                    this.tabs[1].active = false;
                    this.tabs[2].active = false;
                    this.tabs[ parseInt( i ) ].active = true;
                },
        
                sync_changes: function(){
                    if ( this.sync.processing ){ return; }
                    this.sync.processing = true;
        
                    var self = this;
                    var data = {
                        "action": "sez_sync_changes",
                    };
            
                    jQuery.post(
                        ajaxurl,
                        data,
                        function( response, status ){
                            console.log( response, status );
                            if ( status == "success" ){
                                if ( response.success ){
                                    self.sync.job_id = response.data;
                                    self.sync.tracking = true;
                                    self.sync.interval = window.setInterval( self.track_sync_changes, 1000 );
                                }
                            }
                        }
                    )
                    .fail( function( jqXHR, textStatus, errorThrown ){
                        console.log( jqXHR, textStatus, errorThrown );
                        console.log( "oops we got an error" );
                    })
                    .always( function(){
                        self.sync.processing = false;
                    });
                },

                start_sync: function(){
                    this.sync.show_console = true;
                    this.sync_changes();
                },

                track_sync_changes: function(){
                    var self = this;
                    if ( !self.sync.tracking ){
                        window.clearInterval( self.sync.interval );
                        return;
                    }
                    
                    var data = {
                        "action": "sez_sync_get_status",
                        "sez_job_id": self.sync.job_id
                    };
            
                    jQuery.post(
                        ajaxurl,
                        data,
                        function( response, status ){
                            console.log( response, status );
                            if ( status == "success" ){
                                if ( response.success ){
                                    self.sync.output = response.data.output;
                                    if ( response.data.status == "complete" ){
                                        self.sync.tracking = false;
                                        self.sync.additional_output = "<h3>Sync is done.</h3><p>Congratulations! Your sync is complete. The console output can also be found in the sync logs.</p>";    
                                    }
                                    return;
                                }
                            }
                            self.sync.tracking = false;
                            var message = "An error occurred. Please try again later.";
                            if ( 
                                status == "success" && 
                                "data" in response && 
                                Array.isArray( response.data ) && 
                                response.data.length > 0 && 
                                "message" in response.data[0] 
                            ){
                                message = response.data[0].message[ "err_message" ];
                                self.sync.output = response.data[0].message[ "output" ];
                            }
                            self.sync.error = message;
                        }
                    )
                    .fail( function( jqXHR, textStatus, errorThrown ){
                        console.log( jqXHR, textStatus, errorThrown );
                        console.log( "oops we got an error" );
                        self.sync.tracking = false;
                        self.sync.error = "An unknown error occurred. Please try again later.";
                    })
                    .always( function(){
                        
                    });
                },

                close_console: function(){
                    this.sync.show_console = false;
                    this.sync.output = [ "Starting sync..." ];
                    this.sync.error = "";
                    this.sync.additional_output = "";
                }
            },
            created: function(){
                // Get live site changes.
                if ( this.settings.live_sites.length > 0 ){
                    this.settings.live_site = this.settings.live_sites[0];
                }

                // Get recently synced changes.

                // Get live sites for license key.
            }
        });

    } 
});
