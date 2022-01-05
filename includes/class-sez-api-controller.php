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

            $url = sez_export_db_to_zip();

            if ( is_wp_error( $url ) ){
                return rest_ensure_response( $url );
            }

            // Send url to zip in api call.
            return rest_ensure_response( array( "url" => $url ) );
        }
    }

    SEZ_Api_Controller::init();
?>