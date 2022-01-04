<?php

    class SyncEasy_Admin_Page {

        public static function init(){
            add_action( 'admin_menu', array( __CLASS__, 'setup_admin_page' ) );
            add_action( 'admin_enqueue_scripts', array( __CLASS__, 'load_scripts' ) );
            add_action( 'wp_ajax_sez_sync_changes', array( __CLASS__, 'sync_changes' ) );
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
                    
                    $localize_obj = array(
                        // "wave_authenticated" => json_encode( sf_wave_session_open() ),
                        // "wave_auth_url" => SF_Wave_Client::auth_request_url()
                    );
                    wp_localize_script( 'sez-admin-page-scripts', 'SEZ_VARS', $localize_obj );
                }
            }
        }


        public static function output(){
            // $result = request_filesystem_credentials( admin_url() . "tools.php?page=synceasy", "", false, SEZ_ABSPATH );
            // var_dump( $result );
            // return;

            $sez_error = "";

            // Register site.
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
                        $args[ "ezs_staging_site" ] = "https://test.staging.com";
                    } else {
                        $args[ "ezs_live_site" ] = site_url();
                    }

                    $response = wp_remote_post(
                        "https://api.easysyncwp.com/wp-json/easysync/v1/register",
                        array(
                            "body" => $args
                        )
                    );
        
                    $response = new SEZ_Api_Response( $response );
                    $response = $response->extract();
                    
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
            }

            $sez_settings = get_option( 'sez_site_settings' );
            // $sez_settings = array(
            //     "site_type" => "",
            //     "license" => "khjkjhkhj"
            // );

            // License not set.
            if ( !isset( $sez_settings[ "license" ] ) || empty( $sez_settings[ "license" ] ) ){
                require_once SEZ_ABSPATH . "includes/html/html-admin-dashboard-unset.php";
            
            // Staging site.
            } elseif ( isset( $sez_settings[ "site_type" ] ) && $sez_settings[ "site_type" ] === "staging" ){
                $license_key = isset( $sez_settings[ "license" ] ) ? $sez_settings[ "license" ] : "";
                require_once SEZ_ABSPATH . "includes/html/html-admin-dashboard-staging.php";
            
            // Live site.
            } elseif ( isset( $sez_settings[ "site_type" ] ) && $sez_settings[ "site_type" ] === "live" ) {
                require_once SEZ_ABSPATH . "includes/html/html-admin-dashboard-live.php";
            
            // Catch all.
            } else {
                require_once SEZ_ABSPATH . "includes/html/html-admin-dashboard-unset.php";
            }
        }


        public static function sync_changes(){
            // Ensure this is the staging site.
            // Get url of export zip from live site.
            // Send url, license to api and recieve changes.
        }


        public static function get_license_key(){
            $response = wp_remote_post(
                "https://api.easysyncwp.com/wp-json/easysync/v1/license",
                array(
                    "body" => array(
                        "name" => isset( $_POST[ "ezs_name" ] ) ? $_POST[ "ezs_name" ] : "",
                        "email" => isset( $_POST[ "ezs_email" ] ) ? $_POST[ "ezs_email" ] : ""
                    )
                )
            );

            $response = new SEZ_Api_Response( $response );
            $response = $response->extract();
            
            if ( is_wp_error( $response ) ){
                return wp_send_json_error( $response );
            }
            return wp_send_json_success( $response );
        }
    }

    SyncEasy_Admin_Page::init();

?>