<?php
    defined( 'ABSPATH' ) || exit;
    
    class SEZ_Api_Controller {
        protected static $version = 'v1';
	
	    protected static $base = "easysync";
	
	    //protected static $route = "export";

        public static function init(){
            add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );	
        }
        
        
        public static function register_routes(){
            register_rest_route( 
                self::$base . "/" . self::$version, 
                "export", 
                array(
                    'methods' => 'POST',
                    'callback' => array( __CLASS__, 'export_db' ),
                    'args' => array(),
                    'permission_callback' => function(){ return true; }
                )
            );

            register_rest_route( 
                self::$base . "/" . self::$version, 
                "describe_db", 
                array(
                    'methods' => 'GET',
                    'callback' => array( __CLASS__, 'describe_db' ),
                    'args' => array(),
                    'permission_callback' => function(){ return true; }
                )
            );


            register_rest_route( 
                self::$base . "/" . self::$version, 
                "clean", 
                array(
                    'methods' => 'POST',
                    'callback' => array( __CLASS__, 'clean' ),
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

            $license_key = sanitize_text_field( $_POST[ "license_key" ] );
            
            if ( $license_key !== SEZ()->settings->license ){
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

    
        public static function describe_db(){
            if ( !isset( $_GET[ "license_key" ] ) ){
                $err = new WP_Error( "describe_db_error", "License key is required." );
                return rest_ensure_response( $err );
            }

            $license_key = sanitize_text_field( $_GET[ "license_key" ] );

            // Validate license key.            
            if ( SEZ()->settings->license !== $license_key ){
                $err = new WP_Error( "describe_db_error", "Invalid license key." );
                return rest_ensure_response( $err );
            } 

            $result = sez_describe_db();
            return rest_ensure_response( $result );
        }


        public static function clean(){
            if ( !isset( $_POST[ "license_key" ] ) ){
                $err = new WP_Error( "clean_error", "License key is required." );
                return rest_ensure_response( $err );
            }

            $license_key = sanitize_text_field( $_POST[ "license_key" ] );
            
            if ( $license_key !== SEZ()->settings->license ){
                $err = new WP_Error( "clean_error", "Invalid license key." );
                return rest_ensure_response( $err );
            } 

            $dir = trailingslashit( wp_upload_dir()[ "basedir" ] ) . "easymerge-dump";
            if ( is_dir( $dir ) ){
                $it = new RecursiveDirectoryIterator( $dir, RecursiveDirectoryIterator::SKIP_DOTS );
                $files = new RecursiveIteratorIterator( $it, RecursiveIteratorIterator::CHILD_FIRST );
                foreach( $files as $file ) {
                    if ( $file->isDir() ){
                        rmdir( $file->getRealPath() );
                    } else {
                        unlink ($file->getRealPath() );
                    }
                }
                rmdir( $dir );
            }
            return rest_ensure_response( true );
        }
    }

    SEZ_Api_Controller::init();
?>