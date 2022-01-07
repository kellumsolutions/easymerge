<?php

    class SEZ_Rules {

        public static function get_rules(){
            $rules = self::get_default_rules();
            $rules = apply_filters( 'sez_additional_rules', $rules );
            return $rules;
        }


        public static function get_tables_with_rules( $allow_prefix = false ){
            global $wpdb;

            $rules = self::get_rules();
            $tables = array();

            foreach ( $rules as $rule ){
                $table = $rule[ "table" ];
                if ( empty( $allow_prefix ) ){
                    $table = substr( $table, strlen( $wpdb->prefix ) );
                }

                if ( !isset( $tables[ $table ] ) ){
                    $tables[ $table ] = "1";
                }
            }
            return $tables;
        }


        public static function get_processed_rules(){
            $output = array();
            $rules = self::get_rules();

            foreach( $rules as $rule ){
                $table = $rule[ "table" ];
                $policy = $rule[ "policy" ];

                if ( !isset( $output[ $table ] ) ){
                    $output[ $table ] = array();
                }

                //  Evaluate rules with no conditions.
                //  Bring in all data if no conditions exist.
                if ( !isset( $rule[ "conditions" ] ) || empty( $rule[ "conditions" ] ) ){

                    //  Use primary key just so we have a field to latch expression onto.
                    $primary_key = sez_get_table_primary_key( $table );
                    $expression = new SEZ_Rule_Equality_Expression( "*" );
                    $expression->add_rule( $rule );
                    $output[ $table ][ $primary_key ][ $policy ][ "==" ][] = $expression;
                
                } else {
                    foreach( $rule[ "conditions" ] as $condition ){
                        $operator = $condition[ "operator" ];
                        $field = $condition[ "field" ];
                        $values = $condition[ "values" ];
    
                        //foreach ( $fields as $field ){
                        if ( !isset( $output[ $table ][ $field ][ $policy ][ $operator ] ) ){
                            $output[ $table ][ $field ][ $policy ][ $operator ] = array();
                        }
    
                        $expression = false;
    
                        if ( $operator === "==" ){
                            $expression = new SEZ_Rule_Equality_Expression( $values );
    
                        } elseif ( $operator === "<" || $operator === ">" ){
                            $expression = new SEZ_Rule_Inequality_Expression( $operator, $values );
                        
                        } elseif ( strtolower( $operator ) === "like" ){
                            $expression = new SEZ_Rule_Like_Expression( $values );
                        }
    
                        if ( $expression ){
                            $expression->add_rule( $rule );
                            $output[ $table ][ $field ][ $policy ][ $operator ][] = $expression;
                        }    
                    }
                }
            }
            return $output;
        }


        public static function get_rule_ids_by_priority(){
            $rules = self::get_rules();

            usort(
                $rules,
                function( $a, $b ){
                    $a_int = (int)$a[ "priority" ];
                    $b_int = (int)$b[ "priority" ];
    
                    if ( $a_int === $b_int ){ return 0; }
                    return $a_int < $b_int ? -1 : 1; 
                }
            );

            return array_map(
                function( $rule ){
                    return $rule[ 'id' ];
                },
                $rules
            );
        }


        private static function get_default_rules(){
            global $wpdb;
            
            return array(
                array(
                    "id" => "include_all_comments",
                    "table" => $wpdb->comments,
                    "policy" => "include",
                    "priority" => 20,
                    "conditions" => array()
                ),
                array(
                    "id" => "include_all_users",
                    "table" => $wpdb->users,
                    "policy" => "include",
                    "priority" => 20,
                    "conditions" => array()
                ),
                array(
                    "id" => "include_all_usermeta",
                    "table" => $wpdb->usermeta,
                    "policy" => "include",
                    "priority" => 20,
                    "conditions" => array()
                ),
                // array(
                //     "id" => "include_woocommerce_products_meta",
                //     "table" => $wpdb->postmeta,
                //     "policy" => "include",
                //     "priority" => 30,
                //     "conditions" => array(
                //         array(
                //             "field" => "meta_key",
                //             "operator" => "==",
                //             "values" => array(
                //                 "_stock_status",
                //                 "_manage_stock",
                //                 "_sku",
                //                 "_virtual",
                //                 "_downloadable"
                //             )
                //         )
                //     )
                // ),
                // array(
                //     "id" => "exclude_woocommerce_products_meta_stock_status",
                //     "table" => $wpdb->postmeta,
                //     "policy" => "exclude",
                //     "priority" => 30,
                //     "conditions" => array(
                //         array(
                //             "field" => "meta_key",
                //             "operator" => "==",
                //             "values" => "_stock_status"
                //         )
                //     )
                // ),
            );
        }
    }

?>