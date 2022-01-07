<?php

    class SEZ_Change {

        private $operation = "";

        private $table = "";
        
        private $primary_key = "";
        
        private $data = array();

        function __construct( $operation, $table, $primary_key, $data ){
            global $wpdb;

            $this->operation = $operation;
            $this->table = $table;
            $this->primary_key = $primary_key;
            $this->data = $data;

            $tables = sez_get_tables();
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
            foreach ( $table_rules as $field => $policy_map ){
                $field_index = array_search( $field, $fields );

                // Field in rules does not exist on the table.
                // Invalid rule was passed.
                if ( false === $field_index || is_null( $field_index ) ){
                    continue;
                }

                for( $i=0;$i<2;$i++ ){
                    $policy = $i === 0 ? "exclude" : "include";

                    if ( !isset( $rules[ $this->table ][ $field ][ $policy ] ) ){ continue; }

                    // Get value of field from diff file.
                    $value = $this->data[ $field_index ];
                    foreach ( $rules[ $this->table ][ $field ][ $policy ] as $operator => $expressions ){
                        foreach ( $expressions as $expression ){
                            $rule = $expression->get_rules( $value );
                            if ( $rule ){
                                //$this->rule = $rule;
                                //return;
                                return $rule;
                            }
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
        public function perform_create( $primary_key_field, $data ){
            global $wpdb;

            //  Remove previous primary key so new one can be set.
            unset( $data[ $primary_key_field ] );

            $rows_inserted = $wpdb->insert( $this->table, $data );
            if ( !$rows_inserted ){ return false; }

            $id = $wpdb->insert_id;

            if ( $id || $id === 0 ){
                $query = "SELECT * FROM {$this->table} WHERE {$primary_key_field} = '{$id}'";
                $results = $wpdb->get_results( $query, ARRAY_A );
                
                if ( $results ){
                    return $results[0];
                }
            }

            //  Row was created successfully but we couldn't get the insert_id.
            //  Table might not have auto incrementing primary key.
            //  Or there was an error fetching the inserted row.
            return true;
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
        public function perform_update( $primary_key, $primary_key_field, $data ){
            global $wpdb;

            unset( $data[ $primary_key_field ] );
            $where = array( $primary_key_field => $primary_key );
            
            $rows_updated = $wpdb->update( $this->table, $data, $where );

            //  Either the operation failed or the existing data is identical to 
            //  the data to be inserted.
            if ( !$rows_updated ){ return false; }

            $query = "SELECT * FROM {$this->table} WHERE {$primary_key_field} = '{$primary_key}'";
            $results = $wpdb->get_results( $query, ARRAY_A );
            
            if ( $results ){
                return $results[0];
            }
            return true;
        }

        /**
         * Do delete operation.
         * 
         * @param string $primary_key
         * @param string $primary_key_field
         * 
         * @return bool - True/false based on success of operation.
         */
        public function perform_delete( $primary_key, $primary_key_field ){
            global $wpdb;
            
            $where = array( $primary_key_field => $primary_key );

            $rows_deleted = $wpdb->delete( $this->table, $where );
            return $rows_deleted ? true : false;
        }

        
        /**
         * Execute change.
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


            $fields = is_null( $fields ) ? ezd_get_table_fields( $this->table ) : $fields;
            $primary_key_index = is_null( $primary_key_index ) ? ezd_get_table_primary_key_index( $this->table ) : $primary_key_index;
            
            if ( count( $fields ) <= $primary_key_index ){ return false; }

            $primary_key_field = $fields[ $primary_key_index ];
            $primary_key = $this->primary_key( $primary_key_index );
            $data = array_merge( $this->get_line_data( $fields, $primary_key_index ), $data );

            if ( !isset( $data[ $primary_key_field ] ) ){ return false; }

            /**
             * Allows changes to be made to a query before its executed.
             * This is where users can keep references synced that aren't foreign keys.
             * 
             * Only works for changes that are going to be added to the database.
             * Doesn't work for existing database entries.
             * 
             * Ex.  If a products ID is 17 in the live site and 20 in the dev site and
             *      that product ID is referenced in an option's value, the user can change
             *      17 to 20 in the option's serialized value. 
             * 
             * @hooked ezd_insert_dev_primary_key - 10
             *      
             */
            $data = apply_filters( "ezd_before_change_execute", $data, $this->table, $primary_key_field, $primary_key );

            // Execute query.
            $result = false;

            if ( $this->operation === "CREATE" ){
                $result = $this->perform_create( $primary_key_field, $data );

            } elseif ( $this->operation === "UPDATE" ){
                $result = $this->perform_update( $primary_key, $primary_key_field, $data );

            } elseif ( $this->operation === "DELETE" ){
                $result = $this->perform_delete( $primary_key, $primary_key_field );
            }

            if ( !$result ){ return false; }

            /**
             * Allows values to be added to the live/dev site map.
             * This is where users would create mappings for different tables based on the result of a query.
             * How users would keep foregin keys in sync with primary keys.
             * 
             * Ex.  If a product created has an ID of 17 in live site and 20 in dev site, add mapping in
             *      postmeta table changing all post_id (foreign key) values from 17 to 20 for 
             *      pending queries.
             * 
             * @hooked ezd_save_mapping_after_query_execution - 20
             * 
             */
            do_action( "ezd_after_change_execute", $this->table, $this->operation, $data, $result );

            return true;
        }
    }

?>