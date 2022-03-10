<?php

    class SyncEasy_Admin_Page {

        public static function init(){
            add_action( 'admin_menu', array( __CLASS__, 'setup_admin_page' ) );
            add_action( 'admin_enqueue_scripts', array( __CLASS__, 'load_scripts' ) );
            add_action( 'wp_ajax_sez_sync_changes', array( __CLASS__, 'sync_changes' ) );
            add_action( 'wp_ajax_sez_sync_get_status', array( __CLASS__, 'get_sync_status' ) );
            add_action( 'wp_ajax_sez_get_license_key', array( __CLASS__, 'get_license_key' ) );
            add_action( 'wp_ajax_sez_admin_actions', array( __CLASS__, 'do_admin_actions' ) );
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
            
            // Request to change which rules are enabled.
            if ( isset( $_POST[ "sez-edit-rules" ] ) ){
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

            /**
             * Live Site:
             *      - needs to be set if license or live site does not exist.
             *      - is live site if "" and current domain matches lives site.
             * 
             * 
             * Dev Site:
             *      - needs to be set if license, live site is set. 
             *        and current site domain is different than live site
             *        and dev site is not set.
             * 
             *      - is dev site if all above is true and dev site is set.
             */

            $sez_settings = get_option( 'sez_site_settings' );
            // $sez_settings = array(
            //     "site_type" => "staging",
            //     "license" => "6227a35665913",
            //     // "live_site" => "localhost/sample-store",
            //     "live_site" => "https://bojangles.com",
            //     // "dev_site" => "localhosts/sample-store"
            // );

            if ( !isset( $sez_settings[ "license" ] ) || !isset( $sez_settings[ "live_site" ] ) ){
                require_once SEZ_ABSPATH . "includes/html/html-admin-dashboard-setup-live.php";
            
            } elseif ( sez_clean_domain( site_url() ) === sez_clean_domain( $sez_settings[ "live_site" ] ) ) {
                // Delete dev_site from site_options.
                // In case this is a restore from a dev site.
                $license_key = $sez_settings[ "license" ];
                require_once SEZ_ABSPATH . "includes/html/html-admin-dashboard-live.php";
            
            } elseif ( !isset( $sez_settings[ "dev_site" ] ) ){
                $license_key = $sez_settings[ "license" ];
                $live_site = $sez_settings[ "live_site" ];
                require_once SEZ_ABSPATH . "includes/html/html-admin-dashboard-setup-dev.php";

            } elseif ( sez_clean_domain( site_url() ) === sez_clean_domain( $sez_settings[ "dev_site" ] ) ) {
                require_once SEZ_ABSPATH . "includes/html/html-admin-dashboard-staging.php";
            
            } else {
                // Bad install.
                // Dev domain name might have changed.
                // Prompt reinstall (clean)? Clears dev_site in site_options so dev site is re-registered.
                require_once SEZ_ABSPATH . "includes/html/html-admin-dashboard-bad-dev-install.php";
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


        public static function do_admin_actions(){
            if ( isset( $_POST[ "register_live_site" ] ) ){
                if ( !isset( $_POST[ "name" ] ) || empty( $_POST[ "name" ] ) ){
                    return wp_send_json_error( new WP_Error( "admin_actions_error", "Name is required." ) );
                }

                if ( !isset( $_POST[ "email" ] ) || empty( $_POST[ "email" ] ) ){
                    return wp_send_json_error( new WP_Error( "admin_actions_error", "Email is required." ) );
                }

                // Create license key.
                $response = SEZ_Remote_Api::create_license_key( $_POST[ "name" ], $_POST[ "email" ] );
                if ( is_wp_error( $response ) ){
                    return wp_send_json_error( $response );
                }
                $license_key = $response->license_key;

                // Register live site.
                $args = array(
                    "ezs_live_site" => site_url(),
                    "ezs_license_key" => $license_key
                );
                $response = SEZ_Remote_Api::create_new_registration( $args );
                if ( is_wp_error( $response ) ){
                    return wp_send_json_error( $response );
                }

                $props = array(
                    "license_key" => $license_key,
                    "live_site" => sez_clean_domain( site_url() )
                );
                SEZ_Settings::save_props( $props );
                return wp_send_json_success( true );
            
            } elseif ( isset( $_POST[ "register_dev_site" ] ) ){
                $settings = SEZ_Settings::get();
                $license_key = $settings[ "license_key" ];
                $live_site = $settings[ "live_site" ];
                $args = array(
                    "ezs_live_site" => $live_site,
                    "ezs_staging_site" => site_url(),
                    "ezs_license_key" => $license_key
                );

                // Store initial live site reference (dump).
                $url = SEZ_Sync_Functions::export_site_db( $live_site, $license_key );
                if ( is_wp_error( $url ) ){
                    return wp_send_json_error( $url );
                }

                $response = SEZ_Remote_Api::create_dump( $license_key, $live_site, site_url(), $url );
                $response = new SEZ_Api_Response( $response );
                $response = $response->extract();

                if ( is_wp_error( $response ) ){
                    return wp_send_json_error( $response );
                }

                if ( false == $response->uploaded ){
                    return wp_send_json_error( new WP_Error( "admin_actions_error", "There was an error creating the live site dump." ) );
                }
                    
                // Create dev site registration.
                $response = SEZ_Remote_Api::create_new_registration( $args ); 
                if ( is_wp_error( $response ) ){
                    return wp_send_json_error( $response );
                }
                
                SEZ_Settings::save_props( array( "dev_site" => site_url() ) );
                return wp_send_json_success( true );
            
            } elseif ( isset( $_POST[ "reset_dev_site" ] ) ){
                $settings = SEZ_Settings::get();
                unset( $settings[ "dev_site" ] );
                SEZ_Settings::save( $settings );
            }
        }


        // private static function store_reference( $license_key, $live_domain, $staging_domain ){
        //     $url = SEZ_Sync_Functions::export_site_db( $live_domain, $license_key );
        //     if ( is_wp_error( $url ) ){
        //         return $url;
        //     }

        //     $response = SEZ_Remote_Api::create_dump( $license_key, $live_domain, $staging_domain, $url );
        //     $response = new SEZ_Api_Response( $response );
        //     $response = $response->extract();

        //     if ( is_wp_error( $response ) ){
        //         return $response;
        //     }
        //     return $response->uploaded;
        // }
    }

    SyncEasy_Admin_Page::init();

?>