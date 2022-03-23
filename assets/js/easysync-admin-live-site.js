jQuery( document ).ready( function( $ ){
	var easysync_live = {
		$currently_tracking: $( ".easysync-currently-tracking" ),
		init: function(){
			
			// Get dev sites tracking this site.
			this.get_site_trackers();
		},
		get_site_trackers: function(){
			$.post(
                ajaxurl,
                { "action": "sez_get_trackers" },
                function( response, status ){
                    console.log( response, status );
                    
                    if ( status == "success" ){
	                    easysync_live.$currently_tracking.html( response );
                    } else {
	                    easysync_live.$currently_tracking.html( "<div class='row'><div class='col'><p class='easysync-response-fail'>An error occurred fetching trackers for this site. Please try again later.</p></div></div>" );
                    }
                }
            )
            .fail( function( jqXHR, textStatus, errorThrown ){
                console.log( jqXHR, textStatus, errorThrown );
                console.log( "oops we got an error" );
                easysync_live.$currently_tracking.html( "<div class='row'><div class='col'><p class='easysync-response-fail'>An error occurred fetching trackers for this site. Please try again later.</p></div></div>" );
            })
            .always( function(){
	            
            });
		}
	};
	
	easysync_live.init();	
});