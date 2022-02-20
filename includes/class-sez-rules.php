<?php

    class SEZ_Rules {

        private static $option = "sez_rules_props";


        public static function init(){
            add_action( 'init', array( __CLASS__, 'maybe_enable_default_rules' ) );
        }


        public static function maybe_enable_default_rules(){
            $rule_options = get_option( self::$option );
            
            // Only enable default rules if the property doesn't exist at all.
            // If the property exists but no rules are enabled, the user did this.
            if ( false === $rule_options ){
                // Inital save so property exists.
                self::save_rule_options( array() );

                $result = self::enable_rules(
                    array(
                        "include_all_comments",
                        "include_all_users",
                        // "include_all_usermeta"
                    )
                );
                if ( is_wp_error( $result ) ){
                    // Do something here?
                    // Maybe show notice on output screen.
                }
            }
        }


        public static function enable_rules( $rule_ids = array() ){
            $rule_opts = get_option( self::$option );

            // Should never occur. Sanity check.
            if ( false === $rule_opts ){
                return new WP_Error( "enable_rules_error", "Unable to retrieve saved rules." );
            }

            $enabled_rules = array();

            foreach ( $rule_ids as $id ){
                $enabled_rules[] = $id;
            }
            $rule_opts[ "enabled" ] = $enabled_rules;
            return self::save_rule_options( $rule_opts );
        }

        
        public static function save_rule_options( $rule_opts ){
            update_option( self::$option, $rule_opts );
            return true;
        }


        public static function get_rules( $expand_groups = true ){
            $rules = self::get_default_rules();
            $rules = apply_filters( 'sez_additional_rules', $rules );
           
            // Parse rules. Flesh out groups.
            // Ensure there are no duplicate rules.
            $rules = self::parse_rules( $rules, $expand_groups );

            $rule_opts = get_option( self::$option );
            $enabled_rule_map = array();
            
            // Create hash table.
            foreach ( $rule_opts[ "enabled" ] as $enabled_rule_id ){
                $enabled_rule_map[ $enabled_rule_id ] = "1";
            }

            foreach ( $rules as &$rule ){
                $rule[ "enabled" ] = isset( $enabled_rule_map[ $rule[ "id" ] ] );

                // Set enabled property for sub rules.
                // If parent rule is enabled, enable all the sub rules.
                // "parent_group" property is only set when $expand_groups is true.
                if ( isset( $rule[ "parent_group" ] ) && isset( $enabled_rule_map[ $rule[ "parent_group" ] ] ) ){
                    $rule[ "enabled" ] = true;
                }
            }
            return $rules;
        }


        // public static function get_rules_as_groups(){
        //     $rules = self::get_rules();
        //     $output = array();
        //     $map = array();

        //     $raw_rules = self::get_default_rules();
        //     $raw_rules = apply_filters( 'sez_additional_rules', $rules );

        //     foreach ( $rules as $rule ){
        //         // Individual rule.
        //         if ( !isset( $rule[ "parent_group" ] ) ){
        //             $output[] = $rule;
        //             continue;
        //         }

        //         $group = $rule[ "parent_group" ];
        //         if ( !isset( $map[ $group ] ) ){
        //             $rules
        //         }
        //     }
        // }



        /**
         * Verifies rules are accurate and ready for processing.
         * 
         * TODO: Maybe output already existing rule as a notice/alert on the front end?
         */
        public static function parse_rules( $raw_rules, $expand_groups = true ){
            $rules = array();
            $rule_map = array();

            foreach ( $raw_rules as $rule ){
                // Rule group.
                if ( $expand_groups && isset( $rule[ "group" ] ) && true == (int)$rule[ "group" ] ){
                    foreach ( $rule[ "rules" ] as &$sub_rule ){
                        $sub_rule_id = $sub_rule[ "id" ];

                        if ( isset( $rule_map[ $sub_rule_id ] ) ){
                            // Sub rule already exists.
                            continue;
                        }
                        $sub_rule[ "parent_group" ] = $rule[ "id" ];
                        $rules[] = $sub_rule; 
                        $rule_map[ $sub_rule_id ] = 1;
                    }
                
                } else {
                    $rule_id = $rule[ "id" ];
                    if ( isset( $rule_map[ $rule_id ] ) ){
                        // Rule already exists.
                        continue;
                    }
                    $rules[] = $rule; 
                    $rule_map[ $rule_id ] = 1;
                }
            }
            return $rules;
        }


        public static function get_tables_with_rules( $allow_prefix = false ){
            global $wpdb;

            $rules = self::get_rules();
            $tables = array();

            foreach ( $rules as $rule ){
                // Skip disabled rules.
                if ( false === $rule[ "enabled" ] ){ continue; }
                
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
                // Skip disabled rules.
                if ( false === $rule[ "enabled" ] ){ continue; }

                $table = $rule[ "table" ];

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
                    $output[ $table ][ $primary_key ][ "==" ][] = $expression;
                
                } else {
                    foreach( $rule[ "conditions" ] as $condition ){
                        $operator = $condition[ "operator" ];
                        $field = $condition[ "field" ];
                        $values = $condition[ "values" ];
    
                        //foreach ( $fields as $field ){
                        if ( !isset( $output[ $table ][ $field ][ $operator ] ) ){
                            $output[ $table ][ $field ][ $operator ] = array();
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
                            $output[ $table ][ $field ][ $operator ][] = $expression;
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


        // Only include rules for comments and users.
        // By default, user's can't upload items.
        private static function get_default_rules(){
            global $wpdb;
            
            return array(
                array(
                    "id" => "include_all_comments",
                    "description" => "Allows all comments.",
                    "table" => $wpdb->comments,
                    // "policy" => "include",
                    // "priority" => 20,
                    "conditions" => array()
                ),
                array(
                    "id" => "include_all_users",
                    "description" => "Allows all users",
                    "group" => true,
                    "rules" => array(
                        array(
                            "id" => "include_all_users_raw",
                            "description" => "Allows all users.",
                            "table" => $wpdb->users,
                            // "policy" => "include",
                            // "priority" => 20,
                            "conditions" => array()
                        ),
                        array(
                            "id" => "include_all_usermeta",
                            "description" => "Allows all user metadata.",
                            "table" => $wpdb->usermeta,
                            // "policy" => "include",
                            // "priority" => 20,
                            "conditions" => array()
                        ),
                    )
                )
                
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

    SEZ_Rules::init();

?>