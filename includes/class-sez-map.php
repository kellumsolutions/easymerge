<?php

    class SEZ_Map {

        protected static $option_name = "sez_map";

        public static function retrieve( $table = false ){
            $map = get_option( self::$option_name );
            $map = false === $map ? array() : $map;

            if ( !empty( $table ) ){
                $table = sez_remove_table_prefix( $table );
                if ( isset( $map[ $table ] ) ){
                    return $map[ $table ];
                } 
            }
            return $map;
        }

        public static function get_value( $table, $primary_field, $value, $default = false ){
            $table = sez_remove_table_prefix( $table );
            $map = self::retrieve();

            if ( !empty( $map ) ){
                if ( isset( $map[ $table ][ $primary_field ][ $value ] ) ){
                    return $map[ $table ][ $primary_field ][ $value ];
                }
            }
            return $default;
        }


        public static function insert( $table, $primary_field, $previous_value, $new_value ){
            $table = sez_remove_table_prefix( $table );
            $map = self::retrieve();

            $map[ $table ][ $primary_field ][ $previous_value ] = $new_value;
            return self::save( $map );
        }


        public static function save( $map ){
            return update_option( self::$option_name, $map );
        }


        /**
         * Inverse of SEZ_Map::get_value.
         * Gets the live site value based on the dev site value.
         */
        public static function get_value_reverse( $table, $primary_field, $value ){
            $table = sez_remove_table_prefix( $table );
            $map = self::retrieve();

            if ( !empty( $map ) ){
                if ( isset( $map[ $table ][ $primary_field ] ) ){
                    $items = $map[ $table ][ $primary_field ];
                    foreach ( $items as $live_value => $dev_value ){
                        if ( $value == $dev_value ){
                            return $live_value;
                        }
                    }
                }
            }
        }
    }

?>