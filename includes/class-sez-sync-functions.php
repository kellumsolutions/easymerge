<?php
    defined( 'ABSPATH' ) || exit;
    
    class SEZ_Sync_Functions {
        

        public static function start( $job_id, $log ){
            $path = SEZ_Merge_Log::get_path( $job_id );

            SEZ_Sync::log( $log, "Starting sync." );
            SEZ_Sync::log( $log, "Job ID: {$job_id}." );
            SEZ_Sync::log( $log, "A complete log of this sync can be found at {$path}." );
        }


        public static function validate( $job_id, $log ){
            $live_site = SEZ()->settings->live_site;
            $license_key = SEZ()->settings->license;
            
            SEZ_Sync::log( $log, "Live site: {$live_site}." );
            SEZ_Sync::log( $log, "License key: {$license_key}." );
            SEZ_Sync::log( $log, "Staging site (current site): " . site_url() );

            SEZ_Sync::log( $log, "Site validation passed successfully. Site is now ready for processing." );
            return array(
                "live_site" => $live_site,
                "license_key" => $license_key,
                "staging_site" => site_url()
            );
        }


        public static function check_for_existing_dump( $job_id, $log ){
            $license_key = SEZ()->sync->get_job_param( $job_id, "license_key" );
            $live_domain = SEZ()->sync->get_job_param( $job_id, "live_site" );
            $staging_domain = SEZ()->sync->get_job_param( $job_id, "staging_site" );

            $response = SEZ_Remote_Api::get_dump( $license_key, $live_domain, $staging_domain, $log );
            
            if ( is_wp_error( $response ) ){
                return $response;
            }
           
            if ( false == $response ){
                return new WP_Error( "sync_error", "Dump reference does not exist for site {$live_domain}." );
                
            } else {
                SEZ_Sync::log( $log, "Dump reference exists for site {$live_domain}." );
            }
            return true;
        }


        public static function get_live_site_data( $job_id, $log ){
            $license_key = SEZ()->sync->get_job_param( $job_id, "license_key" );
            $live_site = SEZ()->sync->get_job_param( $job_id, "live_site" );

            $url = "{$live_site}/wp-json/easysync/v1/describe_db?license_key={$license_key}";
            $response = wp_remote_get( $url );
            $response = new SEZ_Api_Response( $response );
            $response = $response->extract();
            
            if ( is_wp_error( $response ) ){
                SEZ_Sync::log( $log, "Unable live site data.", "WARNING" );
                return $response;

            } else {
                SEZ_Sync::log( $log, "Successfully fetched live site ({$live_site}) data." );
                SEZ_Sync::log( $log, "{$live_site} table prefix: " . $response->prefix );
                return array( "desc" => $response );
            }
        }


        public static function export_live_site( $job_id, $log ){
            $license_key = SEZ()->sync->get_job_param( $job_id, "license_key" );
            $live_domain = SEZ()->sync->get_job_param( $job_id, "live_site" );

            $response = self::export_site_db( $live_domain, $license_key, $log );
            
            if ( is_wp_error( $response ) ){
                return ( $response );
            }

            SEZ_Sync::log( $log, "Successfully exported live site ({$live_domain}) database. Export Url: {$response}" );
            return array( "live_site_export_url" => $response );
        }


        // TODO: -  When fetching changes, somehow limit the size of the data returned.
        //          Maybe implement paging.
        public static function fetch_changes( $job_id, $log ){
            $url = SEZ()->sync->get_job_param( $job_id, "live_site_export_url" );
            $license_key = SEZ()->sync->get_job_param( $job_id, "license_key" );
            $staging_domain = SEZ()->sync->get_job_param( $job_id, "staging_site" );
            $live_domain = SEZ()->sync->get_job_param( $job_id, "live_site" );
            $desc = SEZ()->sync->get_job_param( $job_id, "desc" );
            $tables_with_rules = SEZ_Rules::get_tables_with_rules();

            SEZ_Sync::log( $log, "Rules exist for " . count( $tables_with_rules ) . " tables on your staging site. " . join( ", ", array_keys( $tables_with_rules ) ) );

            $_changes = SEZ_Remote_Api::get_changes(
                array(
                    "url" => $url,
                    "license_key" => $license_key,
                    "staging_domain" => $staging_domain,
                    "live_domain" => $live_domain,
                    "desc" => $desc,
                    "tables" => $tables_with_rules
                )
            );
            
            if ( is_wp_error( $_changes ) ){
                return $_changes;
            }

            // file_put_contents( trailingslashit( ABSPATH ) . "test.txt", json_encode( $_changes ) );
            
            $estimated_size = sez_get_units( strlen( json_encode( $_changes ) ) );
            SEZ_Sync::log( $log, "Calculated " . count( $_changes ) . " new changes ({$estimated_size}) from the live site." );

            $changes = array();
            $changes_with_rules = 0;

            foreach ( $_changes as $index => $_change ){
                $operation = $_change->operation;
                $table = $_change->table;
                $primary_key = $_change->primary_key;
                $data = $_change->data;
                $change = new SEZ_Change( $operation, $table, $primary_key, $data );

                $rule = $change->find_rule();
                $change_num = $index + 1;

                if ( $rule ){
                    SEZ_Sync::log( $log, "Pending Change #{$change_num}: {$operation} on table {$table}. Rule found with id of {$rule}." );
                    $changes[] = $_change;
                    $changes_with_rules++;

                } else {
                    SEZ_Sync::log( $log, "Pending Change #{$change_num}: {$operation} on table {$table}. No rule was found." );
                }
            }
            SEZ_Sync::log( $log, "{$changes_with_rules} pending changes with matched rule." );

            // Write changes to file on disk.
            $base = trailingslashit( wp_upload_dir()[ "basedir" ] ) . "sez-changes";
            if ( false === is_dir( $base ) ){
                if ( false === mkdir( $base ) ){
                    return new WP_Error( "sez_sync_error", "Unable to create {$base} directory. Permissions issue." );
                }
            }
            
            $file = trailingslashit( $base ) . $job_id . ".json";
            if ( file_exists( $file ) ){
                if ( false === unlink( $file ) ){
                    return new WP_Error( "sez_sync_error", "Unable to delete existing changes file {$file}. Permissions error." );
                }
            }

            if ( false === file_put_contents( $file, json_encode( $_changes ) ) ){
                return  new WP_Error( "sez_sync_error", "Unable to create changes file. Permissions error." );
            }
            SEZ_Sync::log( $log, "Saved changes to file {$file}." );

            $changes_file_size = sez_get_units( (int)filesize( $file ) );
            SEZ_Sync::log( $log, "Changes file size: {$changes_file_size}." );

            return array( "changes_file" => $file );
        }


        public static function replace_existing_dump( $job_id, $log ){
            $file = SEZ()->sync->get_job_param( $job_id, "changes_file" );

            // Ensure file exists.
            if ( !file_exists( $file ) ){
                return new WP_Error( "sez_sync_error", "Changes file {$file} does not exist." );
            }

            // Ensure there is actual data. 
            // Should have a couple of bytes even if there are no changes. 
            if ( 0 === (int)filesize( $file ) ){
                return new WP_Error( "sez_sync_error", "No data read from changes file {$file}." );
            }

            $url = SEZ()->sync->get_job_param( $job_id, "live_site_export_url" );
            $license_key = SEZ()->sync->get_job_param( $job_id, "license_key" );
            $staging_domain = SEZ()->sync->get_job_param( $job_id, "staging_site" );
            $live_domain = SEZ()->sync->get_job_param( $job_id, "live_site" );

            SEZ_Sync::log( $log, "Changes file is valid. Proceeding to update reference to live site {$live_domain}." );

            $response = SEZ_Remote_Api::create_dump( $license_key, $live_domain, $staging_domain, $url, $log );

            if ( is_wp_error( $response ) ){
                return $response;
            }
            
            if ( false == $response ){
                return new WP_Error( "sez_sync_error", "Unable to update the state of the live site ({$live_domain})." );
            }
            SEZ_Sync::log( $log, "Successfully updated reference to live site {$live_domain}." );
        }


        /**
         * TODO:    - Incorporate plugin filter function for changes.
         *          - If change doesn't pass filter function, don't save to db. 
         *          - This allows plugins that include all items from a table to filter on more specific change data.
         */
        public static function save_changes_to_db( $job_id, $log ){
            // Read changes from file.
            // Repetitive.
            $file = SEZ()->sync->get_job_param( $job_id, "changes_file" );
            SEZ_Sync::log( $log, "Loading changes from file {$file} to store in database." );

            // Ensure file exists.
            if ( !file_exists( $file ) ){
                return new WP_Error( "sez_sync_error", "Changes file {$file} does not exist." );
            }

            // Ensure there is actual data.
            if ( 0 === (int)filesize( $file ) ){
                return new WP_Error( "sez_sync_error", "No data read from changes file {$file}." );
            }

            $changes = json_decode( file_get_contents( $file ), true );
            $total_changes = count( $changes );

            // Loop and write to db.
            $successful_saves = 0;
            foreach ( $changes as $change ){
                $_change = new SEZ_Change(
                    $change[ "operation" ],
                    $change[ "table" ],
                    $change[ "primary_key" ],
                    $change[ "data" ]
                );
                if ( false === $_change->save( $job_id ) ){
                    SEZ_Sync::log( $log, "Error saving change to database. Continuing. Details: table - {$_change->table}, primary key - {$_change->primary_key}", "WARNING" );
                } else {
                    $successful_saves++;
                }
            }
            SEZ_Sync::log( $log, "{$successful_saves}/{$total_changes} changes were saved successfully to the database." );
            SEZ_Sync::log( $log, "Changes that were not saved successfully to the database can be tried again after the sync." );
        }


        public static function perform_changes( $job_id, $log ){
            global $wpdb;

            // Read changes from db.
            $sql = "SELECT * FROM {$wpdb->prefix}sez_changes WHERE job_id = %s";
            $results = $wpdb->get_results(
                $wpdb->prepare( $sql, $job_id),
                ARRAY_A
            );

            if ( is_null( $results ) ){
                return new WP_Error( "sez_sync_error", "Unable to get job changes for job ID {$job_id} from database." );
            }

            if ( empty( $results ) ){
                SEZ_Sync::log( $log, "No changes to process. Skipping perform_changes()." );
                return true;
            }

            $changes = array();
            $unprocessed_changes = 0;

            // Loop thru and perform change.
            foreach( $results as $index => $result ){
                $change = SEZ_Change::db_init( $result );

                if ( is_wp_error( $change ) ){
                    $id = $result[ "ID" ];
                    SEZ_Sync::log( $log, "Unable to process database change for execution. ID: {$id}.", "WARNING" );
                    $unprocessed_changes++;
                } else {
                    $changes[] = $change;
                }
            }

            if ( $unprocessed_changes > 0 ){
                return new WP_Error( "sez_sync_error", "{$unprocessed_changes} database changes were unable to be processed. Please try again later." );
            }

            SEZ_Sync::log( $log, count( $changes ) . " changes to process." );
            $unexecuted_changes = 0;

            foreach ( $changes as $index => $change ){
                if ( is_wp_error( $result = $change->execute() ) ){
                    SEZ_Sync::log( $log, "Change " . ( $index + 1 ) . "/" . count( $changes ) . ": Unable to execute change.", "WARNING" );
                    SEZ_Sync::log( $log, "Change process error: " . $result->get_error_message(), "DEBUG" );
                    $unexecuted_changes++;
                } else {
                    SEZ_Sync::log( $log, "Change " . ( $index + 1 ) . "/" . count( $changes ) . ": Executed successfully." );
                }
            }

            if ( $unexecuted_changes > 0 ){
                SEZ_Sync::log( $log, "{$unexecuted_changes} changes were unable to be executed. Please reference this log and try again later.", "WARNING" );
            } else {
                SEZ_Sync::log( $log, "All changes were executed successfully." );
            }
            return true;
        }


        /**
         * Where plugins can keep foreign keys and other data in sync.
         * 
         */
        public static function perform_adjustments( $job_id, $log ){
            global $wpdb;

            $with_prefix = false;
            $tables = sez_get_tables( $with_prefix );
            foreach ( $tables as $table ){
                $sql = "SELECT * FROM {$wpdb->prefix}sez_changes WHERE job_id = %s AND synced = 1 AND `table` = %s";
                $results = $wpdb->get_results(
                    $wpdb->prepare( $sql, $job_id, $table ),
                    ARRAY_A
                );
                if ( is_null( $results ) ){
                    return new WP_Error( "sez_sync_error", "Unable to get changes for job ID {$job_id} from " . $wpdb->prefix . $table . " from database." );
                }

                if ( empty( $results ) ){ continue; }

                $changes = array();
                
                foreach( $results as $index => $result ){
                    $change = SEZ_Change::db_init( $result );

                    if ( is_wp_error( $change ) ){
                        $id = $result[ "ID" ];
                        SEZ_Sync::log( $log, "Unable to get database change. ID: {$id}.", "WARNING" );
                        
                    } else {
                        $changes[] = $change;
                    }
                }

                if ( !empty( $changes ) ){
                    foreach ( $changes as $index => $change ){
                        do_action( "sez_perform_adjustments_" . $wpdb->prefix . $table, $change, $job_id, $log );
                    }
                }
            }

            do_action( "sez_post_perform_adjustments", $job_id, $log );
            return true;
        }


        public static function clean( $job_id, $log ){
            $license_key = SEZ()->sync->get_job_param( $job_id, "license_key" );
            $live_domain = SEZ()->sync->get_job_param( $job_id, "live_site" );

            if ( SEZ()->settings->auto_delete_change_files ){
                SEZ_Sync::log( $log, "Deleting change files." );
                if ( is_wp_error( $result = SEZ_Advanced_Tools::delete_change_files() ) ){
                    SEZ_Sync::log( $log, "Could not delete change files. Error message: " . esc_html( $result->get_error_message() ) . ".", "WARNING" );
                }
            }

            // Delete created live site dump file.
            SEZ_Sync::log( $log, "Deleting dump from live site." );
            if ( is_wp_error( $result = self::clean_live_site( $live_domain, $license_key ) ) ){
                SEZ_Sync::log( $log, "Unable to delete live site dump. Error message: " . esc_html( $result->get_error_message() ) . ".", "WARNING" );
            }
            return true;
        }


        public static function done( $job_id, $log ){
            SEZ_Sync::log( $log, "Done." );
        }


        /**
         * Helpers
         */
        public static function export_site_db( $live_domain, $license_key, $log = false ){
            $endpoint = "{$live_domain}/wp-json/easysync/v1/export";
            $body = array(
                "license_key" => $license_key,
            );

            $response = wp_remote_post(
                $endpoint,
                array(
                    "body" => $body
                )
            );

            $json = json_encode( $body );

            if ( !empty( $log ) ){
                SEZ_Sync::log( $log, "SEZ_Sync_Functions::export_site_db -- endpoint: {$endpoint}, body: {$json}", "DEBUG" );
            }
            $response = new SEZ_Api_Response( $response );
            $response = $response->extract();
            
            $json = json_encode( $response );

            if ( !empty( $log ) ){
                SEZ_Sync::log( $log, "SEZ_Sync_Functions::export_site_db -- response: {$json}", "DEBUG" );
            }
            
            if ( is_wp_error( $response ) ){
                return $response;
            }
            return $response->url;
        }


        public static function clean_live_site( $live_domain, $license_key ){
            $endpoint = "{$live_domain}/wp-json/easysync/v1/clean";

            $response = wp_remote_post(
                $endpoint,
                array(
                    "body" => array( "license_key" => $license_key )
                )
            );

            $response = new SEZ_Api_Response( $response );
            $response = $response->extract();
            
            if ( is_wp_error( $response ) ){
                return $response;
            }
            return true;
        }
    }

?>