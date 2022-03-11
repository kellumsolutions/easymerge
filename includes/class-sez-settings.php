<?php

    class SEZ_Settings {

        protected static $_instance = null;

        public $license = "";

        public $live_site = "";

        public $dev_site = "";

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
            }
        }


        public function save(){
            $file = trailingslashit( wp_upload_dir()[ "basedir" ] ) . $this->file_name;
            $data = array(
                "license" => $this->license,
                "live_site" => $this->live_site,
                "dev_site" => $this->dev_site
            );
            file_put_contents( $file, json_encode( $data ) );
        }


        static function instance(){
            if ( is_null( self::$_instance ) ) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }
    }

?>