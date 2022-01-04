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
                        label: "Settings",
                        name: "settings",
                        active: false
                    }
                ],
                // refresh_processing: false,
                sync: {
                    processing: false,
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
                    // this.tabs[2].active = false;
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

    } else if ( $( "#synceasy_unset" ).length ){
        var ezs = new Vue({
            el: "#synceasy_unset",
            data: {
                site_type: "",
                license_key: "",
                live_site: "",
                new_license: {
                    name: "",
                    email: "",
                    processing: false,
                    received: false,
                    error: ""
                },
            },
            methods: {
                prepare_get_license: function(){
                    // Open modal.
                    //var modalEl = document.getElementById( "ezs_fetch_license_modal" );
                    //var modal = bootstrap.Modal.getOrCreateInstance( modalEl ); // Returns a Bootstrap modal instance
                    //modal.show();
                    this.get_license();
                },

                get_license: function(){
                    if ( this.new_license.processing ){ return; }
                    this.new_license.processing = true;
                    var data = {
                        action: "sez_get_license_key",
                        ezs_name: this.new_license.name,
                        ezs_email: this.new_license.email
                    };
                    var self = this;

                    // Reset.
                    self.new_license.error = "";

                    jQuery.post(
                        ajaxurl,
                        data,
                        function( response, status ){
                            console.log( response, status );
                            if ( status == "success" ){
                                if ( response.success && "data" in response && "license_key" in response.data ){
                                    self.license_key = response.data.license_key;
                                    self.new_license.received = true;

                                    // Close modal.
                                    var modalEl = document.getElementById( "ezs_fetch_license_modal" );
                                    var modal = bootstrap.Modal.getOrCreateInstance( modalEl ); // Returns a Bootstrap modal instance
                                    modal.hide();

                                } else {
                                    // parse errors.
                                    if ( "data" in response && Array.isArray( response.data ) && response.data.length > 0 ){
                                        var error = response.data[0].message;
                                        self.new_license.error = error;
                                    
                                    } else {
                                        self.new_license.error = "Uknown error occurred. Please try again later.";
                                    }
                                }
                            } else {
                                self.new_license.error = "Uknown error occurred. Please try again later.";
                            }
                        }
                    )
                    .fail( function( jqXHR, textStatus, errorThrown ){
                        console.log( jqXHR, textStatus, errorThrown );
                        console.log( "oops we got an error" );
                    })
                    .always( function(){
                        self.new_license.processing = false;
                    });
                }
            }
        });
    }
});
