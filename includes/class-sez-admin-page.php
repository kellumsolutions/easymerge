<?php

    class SyncEasy_Admin_Page {

        public static function init(){
            add_action( 'admin_menu', array( __CLASS__, 'setup_admin_page' ) );
            add_action( 'admin_enqueue_scripts', array( __CLASS__, 'load_scripts' ) );
            add_action( 'wp_ajax_sez_sync_changes', array( __CLASS__, 'sync_changes' ) );
            add_action( 'wp_ajax_sez_sync_get_status', array( __CLASS__, 'get_sync_status' ) );
            add_action( 'wp_ajax_sez_get_license_key', array( __CLASS__, 'get_license_key' ) );
        }


        public static function setup_admin_page(){
            add_submenu_page(
                "tools.php",
                "SyncEasy",
                "SyncEasy",
                "manage_options",
                "synceasy",
                array( __CLASS__, "output" )
            );
        }


        public static function load_scripts(){
            if ( function_exists( 'get_current_screen' ) ) {
                $screen = get_current_screen();
                if ( is_object( $screen ) && property_exists( $screen, 'id' ) && $screen->id === "tools_page_synceasy" ){
                    wp_enqueue_script( 'sez-bootstrap-js', SEZ_ASSETS_URL . "js/bootstrap.bundle.min.js", array(), false, true );
                    wp_enqueue_style( 'sez-bootstrap-style', SEZ_ASSETS_URL . "css/bootstrap.min.css" );
                    wp_enqueue_style( 'sez-admin-style', SEZ_ASSETS_URL . "css/sez-admin-styles.css" );
                    wp_enqueue_script( 'sez-vue', SEZ_ASSETS_URL . "js/dev-vue.js", array(), false, true );

                    wp_enqueue_script( 'sez-admin-page-scripts', SEZ_ASSETS_URL . "js/sez-admin-page.js", array( 'jquery', 'sez-vue' ), false, true );
                    
                    $localize_obj = array();
                    wp_localize_script( 'sez-admin-page-scripts', 'SEZ_VARS', $localize_obj );
                }
            }
        }


        public static function output(){
            $sez_error = "";

            // Request to register site.
            if ( isset( $_POST[ "sez_site_type" ] ) && isset( $_POST[ "sez_license_key" ] ) ){
                // Send to API.
                if ( empty( $_POST[ "sez_site_type" ] ) || !in_array( strtolower( $_POST[ "sez_site_type" ] ), array( "live", "staging" ) ) ){
                    $sez_error = "Site type is required.";
                
                } else {
                    $args = array(
                        "ezs_license_key" => $_POST[ "sez_license_key" ]
                    );

                    if ( $_POST[ "sez_site_type" ] === "staging" ){
                        $args[ "ezs_live_site" ] = isset( $_POST[ "sez_live_site" ] ) ? $_POST[ "sez_live_site" ] : "";
                        $args[ "ezs_staging_site" ] = site_url();
                        
                    } else {
                        $args[ "ezs_live_site" ] = site_url();
                    }

                    $response = SEZ_Remote_Api::create_new_registration( $args );                    
                    if ( is_wp_error( $response ) ){
                        $sez_error = $response->get_error_message();

                    } else {
                        $sez_settings = get_option( 'sez_site_settings' );
                        $sez_settings[ "site_type" ] = strtolower( $_POST[ "sez_site_type" ] );
                        $sez_settings[ "license" ] = sanitize_text_field( $_POST[ "sez_license_key" ] );

                        if ( $_POST[ "sez_site_type" ] === "staging" ){
                            $sez_settings[ "live_site" ] = $_POST[ "sez_live_site" ];
                        }
                        update_option( 'sez_site_settings', $sez_settings );
                    }
                }
            
            // Request to change which rules are enabled.
            } elseif ( isset( $_POST[ "sez-edit-rules" ] ) ){
                $rules_to_enable = array();
                $rules = SEZ_Rules::get_rules( false );
                foreach( $rules as $index => $rule ){
                    $id = $rule[ "id" ];
                    if ( isset( $_POST[ $id ] ) ){
                        $rules_to_enable[] = $id;
                    } 
                }
                SEZ_Rules::enable_rules( $rules_to_enable );
            }

            $sez_settings = get_option( 'sez_site_settings' );
            // $sez_settings = array(
            //     "site_type" => "staging",
            //     "license" => "khjkjhkhj",
            //     "live_site" => "gogreen.com"
            // );

            // License not set.
            if ( !isset( $sez_settings[ "license" ] ) || empty( $sez_settings[ "license" ] ) ){
                require_once SEZ_ABSPATH . "includes/html/html-admin-dashboard-unset.php";
            
            // Staging site.
            } elseif ( isset( $sez_settings[ "site_type" ] ) && $sez_settings[ "site_type" ] === "staging" && isset( $sez_settings[ "live_site" ] ) ){
                $license_key = $sez_settings[ "license" ];
                $live_site = $sez_settings[ "live_site" ];

                // Request to store reference to live site.
                if ( isset( $_POST[ "sez_store_reference" ] ) ){
                    $response = self::store_reference( $license_key, $live_site, site_url() );
                    if ( is_wp_error( $response ) ){
                        // Do some kind of indication here.
                        $sez_error = "Error occurred storing reference to site {$live_site}. ERROR: " . $response->get_error_message();
                    }
                }

                // Check if live site dump exists.
                $dump_exists = SEZ_Remote_Api::get_dump( $license_key, $live_site, site_url() );
                if ( is_wp_error( $dump_exists ) ){
                    // Do some kind of indication?
                    $dump_exists = false;
                }
                require_once SEZ_ABSPATH . "includes/html/html-admin-dashboard-staging.php";
            
            // Live site.
            } elseif ( isset( $sez_settings[ "site_type" ] ) && $sez_settings[ "site_type" ] === "live" ) {
                $license_key = isset( $sez_settings[ "license" ] ) ? $sez_settings[ "license" ] : "";
                require_once SEZ_ABSPATH . "includes/html/html-admin-dashboard-live.php";
            
            // Catch all.
            } else {
                require_once SEZ_ABSPATH . "includes/html/html-admin-dashboard-unset.php";
            }
        }


        public static function sync_changes(){
            $job_id = SEZ()->sync->start();
            if ( is_wp_error( $job_id ) ) {
                return wp_send_json_error( $job_id );
            }
            return wp_send_json_success( $job_id );

            // Test perform change.
            // Setup database insert on successful and failed changes.
            // Figure out label for changes for the front-end. Make property on SEZ_Change.
        }


        public static function get_sync_status(){
            if ( !isset( $_POST[ "sez_job_id" ] ) ){
                return wp_send_json_error( new WP_Error( "get_sync_status_error", "Job id not provided." ) );
            }
            $job_id = $_POST[ "sez_job_id" ];
            $log = SEZ()->sync->get_log_path( $job_id );

            if ( !file_exists( $log ) ){
                return wp_send_json_error( new WP_Error( "get_sync_status_error", "Log file {$log} does not exist." ) );
            }

            $output = array();

            $handle = fopen( $log, "r" );
            if ( $handle ) {
                while ( ( $line = fgets( $handle ) ) !== false) {
                    $output[] = $line;
                    
                    // Search for error.
                    if ( strpos( $line, "[ERROR]" ) !== false ){
                        $components = explode( "[ERROR]", $line );
                        $message = $components[ count( $components ) - 1 ];
                        return wp_send_json_error( 
                            new WP_Error( 
                                "get_sync_status_error", 
                                array( 
                                    "err_message" => $message, 
                                    "output" => $output 
                                )
                            )
                        );
                    }
                }
                fclose($handle);

            } else {
                // error opening the file.
                return wp_send_json_error( new WP_Error( "get_sync_status_error", "Error reading log file {$log}." ) );
            }

            $job_still_exists = SEZ()->sync->get_job_param( $job_id, "log", "" );

            // Assume the process is done.
            $status = $job_still_exists ? "ongoing" : "complete";

            return wp_send_json_success( array( "output" => $output, "status" => $status ) );
        }


        public static function get_license_key(){
            $name = isset( $_POST[ "ezs_name" ] ) ? $_POST[ "ezs_name" ] : "";
            $email = isset( $_POST[ "ezs_email" ] ) ? $_POST[ "ezs_email" ] : "";

            $response = SEZ_Remote_Api::create_license_key( $name, $email );
            
            if ( is_wp_error( $response ) ){
                return wp_send_json_error( $response );
            }
            return wp_send_json_success( $response );
        }


        private static function store_reference( $license_key, $live_domain, $staging_domain ){
            $url = SEZ_Sync_Functions::export_site_db( $live_domain, $license_key );
            if ( is_wp_error( $url ) ){
                return $url;
            }

            $response = SEZ_Remote_Api::create_dump( $license_key, $live_domain, $staging_domain, $url );
            $response = new SEZ_Api_Response( $response );
            $response = $response->extract();

            if ( is_wp_error( $response ) ){
                return $response;
            }
            return $response->uploaded;
        }
    }

    SyncEasy_Admin_Page::init();

?>