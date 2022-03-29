<?php
    defined( 'ABSPATH' ) || exit;
    
    class SEZ_Advanced_Tools {
        public static function reset_settings(){
            try {
                if ( false === unlink( SEZ()->settings->get_path() ) ){
                    return new WP_Error( "reset_settings_error", "Unable to reset settings. Ensure upload directory has write permissions." );
                }
            
            } catch( Exception $e ){
                return new WP_Error( "reset_settings_error", $e->getMessage() );
            }
            return true;
        }


        public static function reset_data(){
            global $wpdb;

            if ( false === $wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}sez_changes" ) ){
                return new WP_Error( "reset_data_error", "Unable to reset merge changes." );
            }
            if ( false === $wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}sez_jobs" ) ){
                return new WP_Error( "reset_data_error", "Unable to reset merge jobs." );
            }            
            return true;
        }
    }
?>