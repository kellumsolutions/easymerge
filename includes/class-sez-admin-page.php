<?php

    class SyncEasy_Admin_Page {

        public static $handle = "easysync";

        public static function init(){
            add_action( 'admin_menu', array( __CLASS__, 'setup_admin_page' ) );
            add_action( 'admin_enqueue_scripts', array( __CLASS__, 'load_scripts' ) );
            add_action( 'wp_ajax_sez_sync_changes', array( __CLASS__, 'sync_changes' ) );
            add_action( 'wp_ajax_sez_sync_get_status', array( __CLASS__, 'get_sync_status' ) );
            add_action( 'wp_ajax_sez_admin_actions', array( __CLASS__, 'do_admin_actions' ) );
            add_action( 'wp_ajax_sez_save_settings', array( __CLASS__, 'save_settings' ) );
        }


        public static function setup_admin_page(){
            add_submenu_page(
                "tools.php",
                "EasySync Database Merging",
                "EasySync",
                "manage_options",
                self::$handle,
                array( __CLASS__, "output" )
            );
        }


        public static function load_scripts(){
            if ( function_exists( 'get_current_screen' ) ) {
                $screen = get_current_screen();
                if ( is_object( $screen ) && property_exists( $screen, 'id' ) && $screen->id === "tools_page_" . self::$handle ){
                    wp_enqueue_script( 'sez-bootstrap-js', SEZ_ASSETS_URL . "js/bootstrap.bundle.min.js", array(), false, true );
                    wp_enqueue_style( 'sez-bootstrap-style', SEZ_ASSETS_URL . "css/bootstrap.min.css" );
                    wp_enqueue_style( 'sez-admin-style', SEZ_ASSETS_URL . "css/sez-admin-styles.css" );
                    wp_enqueue_script( 'easysync-admin-common', SEZ_ASSETS_URL . "js/easysync-admin-common.js", array( 'jquery' ), false, true );
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

            if ( empty( SEZ()->settings->live_site ) || empty( SEZ()->settings->license ) ){
                require_once SEZ_ABSPATH . "includes/html/html-admin-dashboard-setup-live.php";
            
            } elseif ( sez_clean_domain( site_url() ) === sez_clean_domain( SEZ()->settings->live_site ) ) {
                // Delete dev_site from site_options.
                // In case this is a restore from a dev site.
                SEZ()->settings->dev_site = "";
                SEZ()->settings->save();
                $license_key = SEZ()->settings->license;
                require_once SEZ_ABSPATH . "includes/html/html-admin-dashboard-live.php";
            
            } elseif ( empty( SEZ()->settings->dev_site ) ){
                $license_key = SEZ()->settings->license;
                $live_site = SEZ()->settings->live_site;
                require_once SEZ_ABSPATH . "includes/html/html-admin-dashboard-setup-dev.php";

            } elseif ( sez_clean_domain( site_url() ) === sez_clean_domain( SEZ()->settings->dev_site ) ) {
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
            $response = array(
                "progress" => "", 
                "output" => "",
                "additional_output" => "",
                "error" => array()
            );
            
            if ( !isset( $_POST[ "sez_job_id" ] ) ){
                $response[ "error" ] = array(
                    "code" => "get_sync_status_error",
                    "message" =>  "Job id not provided."
                );
                return wp_send_json_error( $response );
            }

            $job_id = $_POST[ "sez_job_id" ];
            
            $log = sez_get_merge_log( $job_id );
            if ( is_wp_error( $log ) ){
                $response[ "error" ] = array(
                    "code" => "get_sync_status_error",
                    "message" =>  $log->get_error_message()
                );
                return wp_send_json_error( $response );
            }

            $response[ "output" ] = $log->get_console_output();

            if ( $log->has_error() ){
                $response[ "error" ] = array(
                    "code" => "get_sync_status_error",
                    "message" =>  $log->get_error()
                );
                return wp_send_json_error( $response );
            }

            $merge_complete = false === get_option( $job_id );
            $response[ "progress" ] = $merge_complete ? "complete" : "ongoing";
            
            if ( $merge_complete ){
                $path = SEZ_Merge_Log::get_path( $job_id );
                $response[ "additional_output" ] .= "<h3>Merge Complete!</h3><p>The console output for this merge is saved at {$path}.</p>";
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

                SEZ()->settings->license = $license_key;
                SEZ()->settings->live_site = site_url();
                SEZ()->settings->save();
                return wp_send_json_success( true );
            
            } elseif ( isset( $_POST[ "register_dev_site" ] ) ){
                $license_key = SEZ()->settings->license;
                $live_site = SEZ()->settings->live_site;
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
                    
                // Create dev site registration.
                $response = SEZ_Remote_Api::create_new_registration( $args ); 
                if ( is_wp_error( $response ) ){
                    return wp_send_json_error( $response );
                }

                $uploaded = SEZ_Remote_Api::create_dump( $license_key, $live_site, site_url(), $url );

                if ( is_wp_error( $uploaded ) ){
                    return wp_send_json_error( $uploaded );
                }

                if ( false == $uploaded ){
                    return wp_send_json_error( new WP_Error( "admin_actions_error", "There was an error creating the live site dump." ) );
                }
                
                SEZ()->settings->dev_site = site_url();
                SEZ()->settings->save();
                return wp_send_json_success( true );
            
            } elseif ( isset( $_POST[ "reset_dev_site" ] ) ){
                SEZ()->settings->dev_site = "";
                SEZ()->settings->save();
            }
        }


        public static function save_settings(){
            SEZ()->settings->merge_log_level = $_POST[ "easysync-merge-log-level" ];
            SEZ()->settings->auto_delete_logs = isset( $_POST[ "easysync-auto-delete-logs" ] );
            SEZ()->auto_delete_change_files = isset( $_POST[ "easysync-auto-delete-change-files" ] );
            
            if ( is_wp_error( $result = SEZ()->settings->save() ) ){
                wp_send_json_error( $result );
            }
            return wp_send_json( $_POST );
        }
    }

    SyncEasy_Admin_Page::init();

?>