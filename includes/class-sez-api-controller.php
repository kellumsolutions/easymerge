<?php

    class SEZ_Api_Controller {
        protected static $version = 'v1';
	
	    protected static $base = "easysync";
	
	    protected static $route = "export";

        public static function init(){
            add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );	
        }
        
        
        public static function register_routes(){
            register_rest_route( 
                self::$base . "/" . self::$version, 
                self::$route, 
                array(
                    'methods' => 'POST',
                    'callback' => array( __CLASS__, 'export_db' ),
                    'args' => array(),
                    'permission_callback' => function(){ return true; }
                )
            );
        }


        public static function export_db(){
            if ( !isset( $_POST[ "license_key" ] ) ){
                $err = new WP_Error( "export_db_error", "License key is required." );
                return rest_ensure_response( $err );
            }

            $license_key = $_POST[ "license_key" ];

            // Validate license key.
            $sez_settings = get_option( 'sez_site_settings' );
            
            if ( empty( $sez_settings ) || !isset( $sez_settings[ "license"] ) || $sez_settings[ "license"] !== $license_key ){
                $err = new WP_Error( "export_db_error", "Invalid license key." );
                return rest_ensure_response( $err );
            } 

            $tmp_dir = sez_prepare_dir( SEZ_TMP_DIR );

            if ( is_wp_error( $tmp_dir ) ){
                return rest_ensure_response( $tmp_dir );
            }

            $unique = bin2hex( random_bytes( 12 ) );
            $to_dir = untrailingslashit( $tmp_dir ) . "/" . $unique;
            $to_dir = sez_prepare_dir( $to_dir );

            // Export database to text files.
            $result = sez_export_db( $to_dir );
            if ( is_wp_error( $result ) ){
                return rest_ensure_response( $result );
            }

            // Compress.
            $result = sez_create_zip( "dump.zip", $to_dir, $to_dir );
            if ( is_wp_error( $result ) ){
                return rest_ensure_response( $result );
            }

            // Send url to zip in api call.
            return rest_ensure_response( array( "url" => trailingslashit( SEZ_TMP_URL ) . $unique . "/dump.zip" ) );
        }
    }

    SEZ_Api_Controller::init();
?>