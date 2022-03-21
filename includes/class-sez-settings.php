<?php

    class SEZ_Settings {

        protected static $_instance = null;

        public $license = "";

        public $live_site = "";

        public $dev_site = "";

        public $merge_log_level = "INFO";

        public $auto_delete_logs = false;

        public $auto_delete_change_files = true;

        private $file_name = "sez_settings.json";


        public function __construct(){
            
            // Try to load data from json file.
            $file = trailingslashit( wp_upload_dir()[ "basedir" ] ) . $this->file_name;
            $this->load( $file );
        }


        public function load( $file ){
            if ( file_exists( $file ) ){
                $data = file_get_contents( $file );
                $data = json_decode( $data, true );

                $this->license = isset( $data[ "license" ] ) ? $data[ "license" ] : "";
                $this->live_site = isset( $data[ "live_site" ] ) ? $data[ "live_site" ] : "";
                $this->dev_site = isset( $data[ "dev_site" ] ) ? $data[ "dev_site" ] : "";
                $this->merge_log_level = isset( $data[ "merge_log_level" ] ) ? $data[ "merge_log_level" ] : "INFO";
                $this->auto_delete_logs = isset( $data[ "auto_delete_logs" ] ) ? $data[ "auto_delete_logs" ] : false;
                $this->auto_delete_change_files = isset( $data[ "auto_delete_change_files" ] ) ? $data[ "auto_delete_change_files" ] : true;
            }
        }


        public function save(){
            $file = trailingslashit( wp_upload_dir()[ "basedir" ] ) . $this->file_name;
            $data = array(
                "license" => $this->license,
                "live_site" => $this->live_site,
                "dev_site" => $this->dev_site,
                "merge_log_level" => in_array( strtoupper( $this->merge_log_level ), array_keys( SEZ_LOG_LEVELS ) ) ? $this->merge_log_level : "INFO",
                "auto_delete_logs" => $this->auto_delete_logs,
                "auto_delete_change_files" => $this->auto_delete_change_files
            );
            try {
                if ( false === file_put_contents( $file, json_encode( $data ) ) ){
                    return new WP_Error( "easysync_save_error", "Unable to save settings. Ensure the uploads directory is writeable by Wordpress." );
                }
                return true;

            } catch ( Exception $e ){
                return new WP_Error( "easysync_save_error", "Unable to save settings. " . $e->getMessage() );
            }
        }


        static function instance(){
            if ( is_null( self::$_instance ) ) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }
    }

?>