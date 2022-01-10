<?php

    class SEZ_Sync_Functions {
        

        public static function start( $job_id, $log ){
            SEZ_Sync::log( $log, "Starting sync." );
        }


        public static function validate( $job_id, $log ){
            $sez_settings = get_option( 'sez_site_settings' );
            $site_type = isset( $sez_settings[ "site_type" ] ) ? $sez_settings[ "site_type" ] : "blank"; 
            $license_key = isset( $sez_settings[ "license" ] ) ? $sez_settings[ "license" ] : "";
            $live_site = isset( $sez_settings[ "live_site" ] ) ? $sez_settings[ "live_site" ] : "blank";

            SEZ_Sync::log( $log, "Site type: {$site_type}." );
            SEZ_Sync::log( $log, "Live site: {$live_site}." );
            SEZ_Sync::log( $log, "License key: {$license_key}." );
            SEZ_Sync::log( $log, "Staging site (current site): " . site_url() );

            if ( $site_type !== "staging" ){
                return new WP_Error( "sync_changes_error", "Syncs can only be performed from a staging site." );
            }

            SEZ_Sync::log( $log, "Site validation passed successfully. Site is now ready for processing." );
            
            return array(
                "site_type" => $site_type,
                "live_site" => $live_site,
                "license_key" => $license_key,
                "staging_site" => site_url()
            );
        }


        public static function check_for_existing_dump( $job_id, $log ){
            $license_key = SEZ()->sync->get_job_param( $job_id, "license_key" );
            $live_domain = SEZ()->sync->get_job_param( $job_id, "live_site" );
            $staging_domain = SEZ()->sync->get_job_param( $job_id, "staging_site" );

            $url = "https://api.easysyncwp.com/wp-json/easysync/v1/dump?license_key={$license_key}&live_domain={$live_domain}&staging_domain={$staging_domain}";
            $response = wp_remote_get( $url );
            $response = new SEZ_Api_Response( $response );
            $response = $response->extract();

            if ( is_wp_error( $response ) ){
                return $response;
            }
            $exists = $response->exists;
            if ( false == $exists ){
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

            $response = wp_remote_post(
                "{$live_domain}/wp-json/easysync/v1/export",
                array(
                    "body" => array(
                        "license_key" => $license_key,
                    )
                )
            );

            $response = new SEZ_Api_Response( $response );
            $response = $response->extract();
            
            if ( is_wp_error( $response ) ){
                return ( $response );
            }

            SEZ_Sync::log( $log, "Successfully exported live site ({$live_domain}) database." );
            return array( "live_site_export_url" => $response->url );
        }


        // TODO: -  When fetching changes, somehow limit the size of the data returned.
        //          Implement paging.
        public static function fetch_changes( $job_id, $log ){
            $url = SEZ()->sync->get_job_param( $job_id, "live_site_export_url" );
            $license_key = SEZ()->sync->get_job_param( $job_id, "license_key" );
            $staging_domain = SEZ()->sync->get_job_param( $job_id, "staging_site" );
            $live_domain = SEZ()->sync->get_job_param( $job_id, "live_site" );
            $desc = SEZ()->sync->get_job_param( $job_id, "desc" );
            $tables_with_rules = SEZ_Rules::get_tables_with_rules();

            SEZ_Sync::log( $log, "Rules exist for " . count( $tables_with_rules ) . " tables on your staging site. " . join( ", ", array_keys( $tables_with_rules ) ) );

            $response = wp_remote_post(
                "https://api.easysyncwp.com/wp-json/easysync/v1/changes",
                array(
                    "body" => array(
                        "url" => $url,
                        "license_key" => $license_key,
                        "staging_domain" => $staging_domain,
                        "live_domain" => $live_domain,
                        "desc" => $desc,
                        "tables" => $tables_with_rules
                    )
                )
            );

            $response = new SEZ_Api_Response( $response );
            $_changes = $response->extract();
            
            if ( is_wp_error( $_changes ) ){
                return $_changes;
            }
            
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

            //     // Get all rules in priority order.
            //     $enabled_rules = array();
            //     $rules = SEZ_Rules::get_rule_ids_by_priority();
            //     foreach ( $rules as $rule ){
            //         if ( $rule[ "enabled" ] ){
            //             $enabled_rules[ $rule[ "id" ] ] = array();
            //         }
            //     }

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
            $url = SEZ()->sync->get_job_param( $job_id, "changes_file" );

            // Ensure file exists.
            if ( !file_exists( $file ) ){
                return new WP_Error( "sez_sync_error", "Changes file {$file} does not exist." );
            }

            // Ensure there is actual data.
            if ( 0 === (int)filesize( $file ) ){
                return new WP_Error( "sez_sync_error", "No data read from changes file {$file}." );
            }


        }


        public static function perform_changes( $job_id, $log ){
            $file = SEZ()->sync->get_job_param( $job_id, "changes_file" );

            if ( !file_exists( $file ) ){
                return new WP_Error( "sez_sync_error", "Changes file {$file} does not exist." );
            }

            $raw_changes = file_get_contents( $file );
            if ( false === $raw_changes ){
                return new WP_Error( "sez_sync_error", "Unable to get contents of file {$file}. Potential permissions issue." );
            }

            if ( 0 === (int)$raw_changes ){
                return new WP_Error( "sez_sync_error", "No data read from changes file {$file}." );        
            }
            $changes = array();
            $_changes = json_decode( $raw_changes, true );
            foreach ( $_changes as $index => $_change ){
                $operation = $_change->operation;
                $table = $_change->table;
                $primary_key = $_change->primary_key;
                $data = $_change->data;
                $change = new SEZ_Change( $operation, $table, $primary_key, $data );

                $rule = $change->find_rule();
                if ( $rule ){
                    //$change->execute();
                }
            }
        }


        public static function done( $job_id, $log ){
            SEZ_Sync::log( $log, "Done" );
        }



        public static function export_site_db( $live_domain, $license_key ){
            $response = wp_remote_post(
                "{$live_domain}/wp-json/easysync/v1/export",
                array(
                    "body" => array(
                        "license_key" => $license_key,
                    )
                )
            );

            $response = new SEZ_Api_Response( $response );
            $response = $response->extract();
            
            if ( is_wp_error( $response ) ){
                return $response;
            }
            return $response->url;
        }
    }

?>