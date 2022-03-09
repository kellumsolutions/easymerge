<?php

    class SEZ_Settings {

        public static function get(){
            $settings = get_option( 'sez_site_settings' );
            return $settings;
        }


        public static function save( $settings ){
            return update_option( 'sez_site_settings', $settings );
        }


        public static function save_props( $props ){
            $settings = self::get();
            foreach ( $props as $key => $value ){
                $settings[ $key ] = $value;
            }
            self::save( $settings );
        }
    }

?>