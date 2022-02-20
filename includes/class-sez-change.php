<?php

    class SEZ_Change {

        public $operation = "";

        public $table = "";
        
        public $primary_key = "";
        
        public $data = array();

        public $id = 0;

        public $job_id = "";

        public $synced = "";

        public $synced_at = "";

        function __construct( $operation, $table, $primary_key, $data ){
            global $wpdb;

            $this->operation = $operation;
            $this->table = $table;
            $this->primary_key = $primary_key;
            $this->data = $data;

            $tables = sez_get_tables();

            // Add back table prefix if needed.
            if ( !in_array( $table, $tables ) ){
                $this->table = $wpdb->prefix . $table;
            }
        }

        /**
         * Find first valid rule.
         * 
         * @arg obj $rules
         *      @key string $table_name, @value obj
         *          @key string $field_name, @value obj
         *              @key string $policy - exclude|include, @value obj
         *                  @key string $operator - =,>,<, @value array<EZD_Expression> - List of EZD_Expression
         * 
         * @arg array $fields - List of db table columns names.
         * 
         * @return null
         */
        public function find_rule( $rules = null, $fields = null ){
            
            if ( !$rules ){
                $rules = SEZ_Rules::get_processed_rules();
            }
            
            if ( !$fields ){
                $fields = sez_get_table_columns( $this->table );
            }
            
            if ( !isset( $rules[ $this->table ] ) ) { return false; }

            $table_rules = $rules[ $this->table ];
            
            // Retrieve the value of the field that has rules.
            // Determine if the value qualifies it for the rule.
            foreach ( $table_rules as $field => $expressions_map ){
                
                $field_index = array_search( $field, $fields );
                
                // Field in rules does not exist on the table.
                // Invalid rule was passed.
                if ( false === $field_index || is_null( $field_index ) ){
                    continue;
                }
                // if ( !isset( $rules[ $this->table ][ $field ] ) ){ continue; }

                // Get value of field from diff file.
                $value = $this->data[ $field_index ];
                
                foreach ( $expressions_map as $operator => $expressions ){
                // foreach ( $rules[ $this->table ][ $field ] as $operator => $expressions ){
                    foreach ( $expressions as $expression ){
                        $rule = $expression->get_rules( $value );
                        if ( $rule ){
                            return $rule;
                        }
                    }
                }
            }
            return null;
        }


        /**
         * Do create operation.
         * 
         * @param string $primary_key_field
         * @param array_a $data - Values that will be inserted into database. Array<DB_Field, DB_Value>.
         * 
         * @return mixed - Returns the inserted row on success. If inserted row can't be fetched,
         *      returns true. If operation fails, returns false.
         */
        public function perform_create( $primary_key_index, $fields ){
            global $wpdb;

            $create_data = array();

            // Create insert data.
            foreach( $fields as $index => $field ){
                if ( $index === $primary_key_index ){ continue; }
                $create_data[ $field ] = $this->data[ $index ];
            }

            $rows_inserted = $wpdb->insert( $this->table, $create_data );
            if ( !$rows_inserted ){
                return new WP_Error( "perform_create_error", "Error creating new record." );
            }

            return $wpdb->insert_id;
        }

        /**
         * Do update operation.
         * 
         * @param string $primary_key
         * @param string $primary_key_field
         * @param array_a $data - Values that will be inserted into database. Array<DB_Field, DB_Value>.
         * 
         * @return mixed - Returns the updated row on success. If updated row can't be fetched,
         *      returns true. If operation fails, returns false.
         */
        public function perform_update( $primary_key_index, $fields ){
            global $wpdb;

            $primary_key_field = $fields[ $primary_key_index ];
            $where = array( $primary_key_field => $this->primary_key );
            
            $insert_data = array();

            // Create insert data.
            foreach( $fields as $index => $field ){
                if ( $index === $primary_key_index ){ continue; }
                $insert_data[ $field ] = $this->data[ $index ];
            }

            $rows_updated = $wpdb->update( $this->table, $insert_data, $where );

            if ( false === $rows_updated ){
                return new WP_Error( "perform_update_error", "Error performing update from change. Table: {$this->table}, PK: {$this->primary_key}." );
            
            } elseif ( 0 === $rows_updated ){
                // No error, but no rows were updated. Shouldn't happen.
                // Maybe show a warning here.
            }
            return $this->primary_key;
        }

        /**
         * Do delete operation.
         * 
         * @param string $primary_key
         * @param string $primary_key_field
         * 
         * @return bool - True/false based on success of operation.
         */
        public function perform_delete( $primary_key_index, $fields ){
            global $wpdb;
            
            $primary_key_field = $fields[ $primary_key_index ];
            $where = array( $primary_key_field => $this->primary_key );

            $rows_deleted = $wpdb->delete( $this->table, $where );

            if ( false === $rows_deleted ){
                return new WP_Error( "perform_delete_error", "Error deleting record from db. Table: {$this->table}, PK: {$this->primary_key}." );
            }
            return $this->primary_key;
        }

        
        /**
         * Execute change. Where the magic happens.
         * 
         * @param obj $data - Object containing fields/values for diff row for this change.
         *      @key string - Name of db field, @value string - DB value for preceding field.
         * 
         * @param array $fields (optional) - List of field names for table where this change takes place.
         * @param int|string $primary_key_index (optional) - Index of primary key for table where this change takes place.
         * 
         * @return bool - Success of operation. 
         */
        public function execute( $data = array(), $fields = null, $primary_key_index = null ){
            global $wpdb;

            if ( is_null( $fields ) ){
                if ( is_wp_error( $fields = sez_get_table_columns( $this->table ) ) ){
                    return $fields;
                }
            }

            if ( count( $fields ) !== count( $this->data ) ){
                return new WP_Error( "execute_changes_error", "Mismatch between data schema and table schema for table {$this->table}." );
            }

            if ( is_null( $primary_key_index ) ){
                if ( is_wp_error( $primary_key_index = sez_get_primary_key_index( $this->table ) ) ){
                    return $primary_key_index;
                }
            }
            
            // Fields and primary key don't match up.
            // Should never happen.
            if ( count( $fields ) <= $primary_key_index ){
                return new WP_Error( "execute_changes_error", "Primary key does not exist." );
            }

            $data = array();
            foreach ( $fields as $index => $field ){
                $data[] = $this->data[ $index ];
            }
            
            /**
             * Primary key is adjusted for updates and deletes.
             * This ensures the correct data is being updated/deleted for the 
             * staging environment.
             * 
             * sez_adjust_primary_key_for_updates_deletes - 10
             */
            $data = apply_filters( "sez_before_change_execute", $data, $this->table, $primary_key_index, $this->operation );
            
            // Validate returned data.
            if ( count( $fields ) !== count( $data ) ){
                return new WP_Error( "execute_changes_error", "Mismatch between data schema and table schema for table {$this->table} after filter." );
            }
            $this->data = $data;
            $this->primary_key = $data[ $primary_key_index ]; // Set new primary key (if data has changed).


            // Execute query.
            $result = false;

            if ( $this->operation === "CREATE" ){
                $result = $this->perform_create( $primary_key_index, $fields );

            } elseif ( $this->operation === "UPDATE" ){
                $result = $this->perform_update( $primary_key_index, $fields );

            } elseif ( $this->operation === "DELETE" ){
                $result = $this->perform_delete( $primary_key_index, $fields );
            }

            if ( is_wp_error( $result ) ){
                return $result;
            }

            // Update synced status.
            if ( !empty( $this->id ) ){
                $wpdb->update(
                    $wpdb->prefix . "sez_changes",
                    array( "synced" => 1 ),
                    array( "ID" => $this->id )
                );
            }

            /**
             * Allows values to be added to the live/dev site map.
             * This is where users would create mappings for different tables based on the result of a query.
             * How users would keep foregin keys in sync with primary keys.
             * 
             * Ex.  If a product created has an ID of 17 in live site and 20 in dev site, add mapping in
             *      postmeta table changing all post_id (foreign key) values from 17 to 20 for 
             *      pending queries.
             * 
             * @hooked sez_save_mapping - 20
             * 
             */
            do_action( "sez_after_change_execute", $this->table, $this->operation, $this->primary_key, $result, $fields[ $primary_key_index ] );

            return true;
        }


        /**
         *  Saves change to database.
         * 
         */
        public function save( $job_id ){
            global $wpdb;

            $inserted = $wpdb->insert(
                $wpdb->prefix . "sez_changes",
                array(
                    "operation" => $this->operation,
                    "table" => $this->table,
                    "primary_key" => $this->primary_key,
                    "data" => serialize( $this->data ),
                    "job_id" => $job_id,
                    "synced" => 0,
                    "synced_at" => current_time( 'mysql' )
                )
            );
            if ( false == $inserted ){ return false; }
            return true;
        }


        public function get_label(){
            global $wpdb;
            
            $label = "Change detected.";

            if ( $this->table === $wpdb->comments || $this->table === $wpdb->users ){
                $item = $this->table === $wpdb->comments ? "comment" : "user";
                if ( $this->operation === "CREATE" ){
                    $label = "New {$item} created.";
                } elseif( $this->operation === "UPDATE" ){
                    $label = ucfirst( $item ) . " updated.";
                } else {
                    $label = ucfirst( $item ) . " was deleted.";
                }
            } elseif( $this->table === $wpdb->usermeta ){
                if ( $this->operation === "CREATE" ){
                    $label = "Created new user metadata.";
                } elseif( $this->operation === "UPDATE" ){
                    $label = "User metadata was updated.";
                } else {
                    $label = "User metadata was deleted.";
                }
            }
            return apply_filters( "sez_change_label", $label, $this->table, $this->operation, $this->data );
        }


        public function as_data(){
            return array(
                "ID" => $this->id,
                "table" => $this->table,
                "operation" => $this->operation,
                "job_id" => $this->job_id,
                "synced" => $this->synced,
                "synced_at" => $this->synced_at,
                "primary_key" => $this->primary_key,
                "data" => $this->data,
                "label" => $this->get_label()
            );
        }


        /**
         * Init SEZ_Change object from db record.
         */
        public static function db_init( $args ){
            $required_args = array( "ID", "operation", "table", "primary_key", "data", "job_id", "synced", "synced_at" );
            foreach ( $required_args as $arg ){
                if ( !isset( $args[ $arg ] ) ){
                    return new WP_Error( "change_init_error", "Missing param {$arg}." );
                }
            }
            $change = new SEZ_Change(
                $args[ "operation" ], 
                $args[ "table" ], 
                $args[ "primary_key" ], 
                unserialize( $args[ "data" ] ) 
            );
            $change->id = $args[ "ID" ];
            $change->job_id = $args[ "job_id" ];
            $change->synced = $args[ "synced" ];
            $change->synced_at = $args[ "synced_at" ];
            return $change;
        }
    }

?>