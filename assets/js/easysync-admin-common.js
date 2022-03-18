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
        init: function(){
            this.$nav_tab_wrapper.on( "click", ".nav-tab", this.on_nav_tab_click );
            this.$rules_form.on( "change", "input[type=checkbox]", this.on_rules_change );

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
        }
    };
    
    easysync.init();
});
