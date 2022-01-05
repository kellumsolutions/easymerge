<?php

    class SEZ_Map {

        protected static $option_name = "sez_map";

        public static function retrieve(){
            $map = get_option( self::$option_name );
            return $map ? $map : false;
        }

        public static function get_value( $table, $primary_field, $value, $default = false ){
            $map = self::retrieve();

            if ( $map ){
                if ( isset( $map[ $table ][ $primary_field ][ $value ] ) ){
                    return $map[ $table ][ $primary_field ][ $value ];
                }
            }
            return $default;
        }


        public static function insert( $table, $primary_field, $previous_value, $new_value ){
            $map = self::retrieve();

            //  Create map if it does not exist.
            if ( $map === false ){
                $map = array();
            }

            $map[ $table ][ $primary_field ][ $previous_value ] = $new_value;
            return self::save( $map );
        }


        public static function save( $map ){
            return update_option( self::$option_name, $map );
        }
    }

?>