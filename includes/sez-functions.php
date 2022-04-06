<?php

    defined( 'ABSPATH' ) || exit;

    if ( !function_exists( 'sez_get_tables' ) ){
        function sez_get_tables( $with_prefix = true ){
            global $wpdb;

            $sql = "SHOW TABLES";

            $results = $wpdb->get_results( $sql, ARRAY_A );
            if ( empty( $results ) || !is_array( $results ) ){
                return new WP_Error( "export error", "Error getting tables." );
            }

            $tables = array();
            foreach( $results as $result ){
                foreach ( $result as $key => $table ){
                    $tables[] = $with_prefix ? $table : sez_remove_table_prefix( $table );
                }
            }
            return $tables;
        }
    }


    if ( !function_exists( 'sez_remove_table_prefix' ) ){
        function sez_remove_table_prefix( $table ){
            global $wpdb;

            $prefix = strtolower( $wpdb->prefix );
            $prefix_length = strlen( $prefix );

            if ( strlen( $table ) <= $prefix_length ){ return $table; }
            if ( $prefix === strtolower( substr( $table, 0, $prefix_length ) ) ){
                return substr( $table, $prefix_length );
            }
            return $table;
        }
    }


    // TODO: - Remove table prefix from table names. Security purposes.
    if ( !function_exists( 'sez_describe_db' ) ){
        function sez_describe_db(){
            global $wpdb;

            if ( is_wp_error( $tables = sez_get_tables() ) ){
                return $tables;
            }

            $output = array(
                "prefix" => $wpdb->prefix,
                "tables" => array()
            );

            foreach ( $tables as $table ){
                $result = $wpdb->get_results( "SHOW KEYS FROM {$table} WHERE Key_name = 'PRIMARY'" );

                if ( $result && count( $result ) > 0 ){
                    $key = $result[0]->Column_name;
                    $columns = $wpdb->get_col( "DESC {$table}", 0 );
                    $index = array_search( $key, $columns );
                    if ( false === $index ){
                        return new WP_Error( "describe_db_error", "Failed to get index of primary key for table {$table}." );
                    }
                    $output[ "tables" ][ $table ] = array(
                        "pk_index" => $index,
                        "field_count" => count( $columns )
                    );
                    
                } else {
                    return new WP_Error( "describe_db_error", "Failed to get primary key for table {$table}." );
                }
            }
            return $output;
        }
    }


    if ( !function_exists( 'sez_export_db' ) ){
        function sez_export_db( $to_dir ){
            global $wpdb;

            $path = untrailingslashit( $to_dir );
            $tables = sez_get_tables();

            if ( is_wp_error( $tables ) ){
                return $tables;
            }

            foreach ( $tables as $table ){
                $headers = $wpdb->get_col( "DESC {$table}", 0 );
                $headers = implode( "\t", $headers );
                
                // Write headers to file.
                file_put_contents( "{$path}/{$table}.txt", $headers . "\n" );

                $results = $wpdb->get_results( "SELECT * FROM {$table}", ARRAY_A );
                if ( $results ){
                    foreach ( $results as $result ){
                        $values = array();
                        foreach ( $result as $field => $value ){
                            // Get rid of new lines that throw off computing.
                            $value = str_replace( "\n", '\n', $value );
                            $value = str_replace( "\r", '\r', $value );
                            $value = str_replace( "\t", '\t', $value );
                            $values[] = $value;
                        }
                        file_put_contents( "{$path}/{$table}.txt", implode( "\t", $values ) . "\n", FILE_APPEND );
                    }
                }
            }
            return true;
        }
    }


    if ( !function_exists( 'sez_create_zip' ) ){
		function sez_create_zip( $name, $dir, $to ){
            
            // TODO: - Use PCLZip for when ziparchive isn't installed.

            if ( !class_exists( 'ZipArchive' ) ){
                return new WP_Error( "error_creating_zip", "Zip extension is not installed." );
            }
			$zip = new ZipArchive();
			$dir = trailingslashit( $dir );
			$to = trailingslashit( $to );
			
			if ( !is_dir( $to ) ){
				if ( false === mkdir( $to ) ){
					return new WP_Error( "error_creating_zip", "Permissions error. Could not create directory {$dir}." );
				}
			}
			
			if ( file_exists( $to . $name ) || is_dir( $to . $name ) ){
				if ( is_dir( $to . $name ) ){
					ezsa_rmdir( $to . $name );
				} else {
					unlink( $to . $name ); 	
				}			
			}
			
			if ( false === $zip->open( $to . $name, ZipArchive::CREATE ) ){
				return new WP_Error( "error_creating_zip", "Could not open zip archive at {$to}{$name}." );
			}
			
			foreach( scandir( $dir ) as $file ){
				if ( $file === "." || $file === ".." ){ continue; }
				if ( is_dir( $dir . $file ) ){ continue; }
				if ( ".txt" !== substr( $file, -4 ) ){ continue; }
				$zip->addFile( $dir . $file, $file );
			}
			$zip->close(); // close and save archive.
			return true;
		}
	}


    if ( !function_exists( 'sez_prepare_dir' ) ){
        function sez_prepare_dir( $dir ){
            global $wp_filesystem;

            if ( !$wp_filesystem ){
                require_once( trailingslashit( ABSPATH ) . "wp-admin/includes/file.php" );
                if ( !WP_Filesystem() ){
                    return new WP_Error( "prepare_dir_error", "Incorrect file permissions." );
                }                
            }

            $dir = $wp_filesystem->find_folder( $dir );

            if ( !$wp_filesystem->is_dir( $dir ) ){
                if ( !$wp_filesystem->mkdir( $dir, 0775 ) ){
                    return new WP_Error( "prepare_dir_error", "Could not create path {$dir}." );
                }
            } elseif ( !$wp_filesystem->is_writable( $dir ) ){
                return new WP_Error( "prepare_dir_error", "Directory {$dir} is not writable." );
            }
            return $dir;
        }
    }


    if ( !function_exists( 'sez_export_db_to_zip' ) ){
        function sez_export_db_to_zip(){
            $to_dir = trailingslashit( wp_upload_dir()[ "basedir" ] ) . "easymerge-dump";
            $result = sez_recursive_rmdir( $to_dir );
            if ( is_wp_error( $result ) ){
                return $result;
            }
            mkdir( $to_dir );

            // Export database to text files.
            $result = sez_export_db( $to_dir );
            if ( is_wp_error( $result ) ){
                return $result;
            }

            // Compress.
            $result = sez_create_zip( "dump.zip", $to_dir, $to_dir );
            if ( is_wp_error( $result ) ){
                return $result;
            }
            return trailingslashit( wp_upload_dir()[ "baseurl" ] ) . "easymerge-dump/dump.zip";
        }
    }


    if ( !function_exists( 'sez_recursive_rmdir' ) ){
        function sez_recursive_rmdir( $to_dir ){
            if ( is_dir( $to_dir ) ){
                try {
                    $it = new RecursiveDirectoryIterator( $to_dir, RecursiveDirectoryIterator::SKIP_DOTS );
                    $files = new RecursiveIteratorIterator( $it, RecursiveIteratorIterator::CHILD_FIRST );
                    foreach( $files as $file ) {
                        if ( $file->isDir() ){
                            rmdir( $file->getRealPath() );
                        } else {
                            unlink ( $file->getRealPath() );
                        }
                    }
                    rmdir( $to_dir );

                } catch( Exception $e ){
                    return new WP_Error( "sez_recursive_rmdir_error", "Error deleting directory {$dir}. ERR: " . $e->getMessage() );
                }
            }
            return true;
        }
    }


    if ( !function_exists( 'sez_get_primary_keys' ) ){
        function sez_get_primary_keys(){
            global $wpdb;
            
            $keys = array();
            $tables = $wpdb->get_results( "SHOW TABLES" );
            
            foreach( $tables as $table_data ){
                foreach( $table_data as $key => $table_name ){
                    $result = $wpdb->get_results( "SHOW KEYS FROM {$table_name} WHERE Key_name = 'PRIMARY'" );

                    if ( $result && count( $result ) > 0 ){
                        $keys[ $table_name ] = $result[0]->Column_name;
                    
                    } else {
                        return new WP_Error( "get_primary_keys_error", "Unable to get primary key for table {$table}." );
                    }
                }
            }
            return $keys;
        }
    }



    if ( !function_exists( 'sez_get_table_primary_key' ) ){
        function sez_get_table_primary_key( $table ){
            $keys = sez_get_primary_keys();
            if ( is_wp_error( $keys ) ){
                return $keys;
            } 

            return isset( $keys[ $table ] ) ? $keys[ $table ] : new WP_Error( "get_table_primary_key_error", "Unable to get primary key for table {$table}." );
        }
    }


    if ( !function_exists( 'sez_get_table_columns' ) ){
        function sez_get_table_columns( $table ){
            global $wpdb;

            $headers = $wpdb->get_col( "DESC {$table}", 0 );
            if ( !$headers || empty( $headers ) ){
                return new WP_Error( "get_table_columns_error", "Unable to get columns for table {$table}." );
            }
            return $headers;
        }
    }


    if ( !function_exists( 'sez_get_primary_key_index' ) ){
        function sez_get_primary_key_index( $table ){
            if ( is_wp_error( $key = sez_get_table_primary_key( $table ) ) ){ 
                return $key; 
            }

            if ( is_wp_error( $fields = sez_get_table_columns( $table ) ) ){
                return $fields;
            }

            $index = array_search( $key, $fields );

            if ( is_null( $index ) || false === $index ){
                return new WP_Error( "get_primary_key_index_error", "Unable to get the primary key index for table {$table}." );
            }
            return $index;
        }
    }


    if ( !function_exists( 'sez_save_mapping' ) ){
        function sez_save_mapping( $table, $operation, $initial_primary_key, $actual_primary_key, $primary_key_field ){
            if ( $operation === "CREATE" ){
                if ( $initial_primary_key != $actual_primary_key ){
                    SEZ_Map::insert( $table, $primary_key_field, $initial_primary_key, $actual_primary_key );
                }
            }
        }
    }


    if ( !function_exists( "sez_adjust_primary_key_for_updates_deletes" ) ){
        function sez_adjust_primary_key_for_updates_deletes( $data, $table, $primary_key_index, $operation ){
            if ( $operation === "CREATE" ){
                return $data;
            }
            $initial_primary_key = $data[ $primary_key_index ];

            if ( is_wp_error( $primary_key_field = sez_get_table_primary_key( $table ) ) ){
                return $primary_key_field;
            }

            // If there is no mapping for the primary key, 
            // the initial primary key will be returned.
            $new_primary_key = SEZ_Map::get_value( $table, $primary_key_field, $initial_primary_key, $initial_primary_key );
            $data[ $primary_key_index ] = $new_primary_key;
            return $data;
        }
    }


    if ( !function_exists( 'sez_get_units' ) ){
        function sez_get_units( $bytes ){
            if ( $bytes >= 1073741824 ) {
                $bytes = number_format( $bytes / 1073741824, 2 ) . ' GB';
            } elseif ( $bytes >= 1048576 ){
                $bytes = number_format( $bytes / 1048576, 2 ) . ' MB';            
            } elseif ( $bytes >= 1024 ){
                $bytes = number_format( $bytes / 1024, 2 ) . ' KB';
            } elseif ( $bytes > 1 ){
                $bytes = $bytes . ' bytes';
            } elseif ( $bytes == 1 ){
                $bytes = $bytes . ' byte';
            } else {
                $bytes = '0 bytes';
            }
            return $bytes;
        }
    }


    if ( !function_exists( 'sez_get_changes' ) ){
	    function sez_get_changes( $args ){
		    global $wpdb;
		    
		    // $where_site = "";
		    
		    // if ( isset( $args[ "site" ] ) && (int)$args[ "site" ] !== 0 ){
			//     $site_id = (int)$args[ "site" ];
			//     $where_site = " AND site_id = {$site_id}";
		    // }
		    
		    // $sql = "SELECT COUNT(*) as row_count FROM {$wpdb->prefix}sf_test_history WHERE 1=1 {$where_site}";
		    // $results = $wpdb->get_results( $sql, ARRAY_A );
		    
		    // $count = (int)$results[0][ "row_count" ];
		    // if ( $count === 0 ){ 
			//     return array(
			// 	    "page" => 0,
			// 	    "total_results" => 0,
			// 	    "total_pages" => 0,
			// 	    "results_per_page" => 0,
			// 	    "results" => array()
			//     );
			// }
		    
		    // $limit = isset( $args[ "limit" ] ) ? (int)$args[ "limit" ] : 10;
		    
		    // // Ensure we don't divide by zero.
		    // if ( $limit === 0 ){
			//     $limit = 10;
		    // }
		    
		    // $page = isset( $args[ "page" ] ) ? (int)$args[ "page" ] : 1;
		    
		    // // Never have a zero page.
		    // if ( $page === 0 ){
			//     $page = 1;
		    // }
		    
		    // $total_pages = intval( $count / $limit );
		    // $remainder = $results % $limit;
		    
		    // if ( $remainder > 0 ){ $total_pages++; }
		    
		    // $offset = ( $page - 1 ) * $limit;
		    // $offset = (int)$offset;
		    
		    // $sql = "SELECT * FROM {$wpdb->prefix}sf_test_history WHERE 1=1 {$where_site} ORDER BY `start_time` DESC LIMIT {$limit} OFFSET {$offset}";
		    // $results = $wpdb->get_results( $sql, ARRAY_A );
		    
		    // return array(
			//     "page" => $page,
			//     "total_results" => $count,
			//     "total_pages" => $total_pages,
			//     "results_per_page" => $limit,
			//     "results" => is_null( $results ) ? array() : $results
		    // );
	    }
    }


    if ( !function_exists( 'sez_clean_domain' ) ){
        function sez_clean_domain( $domain ){
            $domain = untrailingslashit( $domain );
            $domain = str_replace( "https://", "", $domain );
            $domain = str_replace( "http://", "", $domain );
            return $domain;
        }
    }


    if ( !function_exists( 'sez_get_merge_log' ) ){
        function sez_get_merge_log( $job_id ){
            $log_path = SEZ_Merge_Log::get_path( $job_id );

            if ( !file_exists( $log_path ) ){
                return new WP_Error( "get_merge_log_error", "Log file {$log_path} does not exist." );
            }

            return new SEZ_Merge_Log( $job_id );
        }
    }
    

    if ( !function_exists( 'sez_get_last_merge_job' ) ){
        function sez_get_last_merge_job(){
            global $wpdb;
            $jobdata = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}sez_jobs ORDER BY ID DESC LIMIT 1 ", ARRAY_A );

            if ( empty( $jobdata ) ){
                return false;
            }

            return $jobdata[0];
        }    
    }


    if ( !function_exists( 'sez_get_last_job_data' ) ){
        function sez_get_last_job_data(){
            global $wpdb;

            // Find latest job.
            $jobdata = sez_get_last_merge_job();
            if ( empty( $jobdata) ){
                return false;
            }

            $job_id = $jobdata[ "job_id" ];

            // Fetch all changes from job.
            $results = $wpdb->get_results( 
                $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}sez_changes WHERE job_id = %s ORDER BY ID ASC", $job_id ),
                ARRAY_A
            );

            $merged_changes = 0;
            $unmerged_changes = 0;
            if ( !empty( $results ) ){
                foreach ( $results as $result ){
                    if ( "1" === $result[ "synced" ] || 1 === $result[ "synced" ] ){
                        $merged_changes++;
                    } else {
                        $unmerged_changes++;
                    }
                }
            }

            $error = "";
            if ( 1 === (int)$jobdata[ "has_error" ] ){
                $metadata = unserialize( $jobdata[ "metadata" ] );
                if ( is_array( $metadata ) && isset( $metadata[ "error" ] ) ){
                    $error = $metadata[ "error" ];
                }
            }

            $start_timestamp = strtotime( $jobdata[ "started_at" ] );
            $end_timestamp = strtotime( $jobdata[ "finished_at" ] );
            $duration = "";

            if ( !empty( $start_timestamp ) && !empty( $end_timestamp ) ){
                $delta = $end_timestamp - $start_timestamp;
                if ( $delta <= 0 ){ return "0m 0s"; }

                $minutes = 0;

                if ( $delta > 59 ){
                    $minutes = floor( $delta / 60 );
                }
                $seconds = $delta % 60;
                $duration = "{$minutes}m {$seconds}s";
            }

            return array(
                "start_time" => $start_timestamp ? date( "D, M d, Y h:i:s a", $start_timestamp ) : "",
                "end_time" => $end_timestamp ? date( "D, M d, Y h:i:s a", $end_timestamp ) : "",
                "duration" => $duration,
                "error" => $error,
                "merged_changes" => $merged_changes,
                "unmerged_changes" => $unmerged_changes,
                "status" => $jobdata[ "status" ],
                "job_id" => $job_id
            );
        }
    }


    if ( !function_exists( 'sez_save_jobdata' ) ){
        function sez_save_jobdata( $job_id ){
            global $wpdb;

            $jobdata = get_option( $job_id );

            if ( empty( $jobdata ) ){
                return new WP_Error( "sez_save_jobdata", "No jobdata." );
            }

            $error = isset( $jobdata[ "error" ] ) ? $jobdata[ "error" ] : "";
            $has_error = !empty( $error );
            $metadata = array();

            if ( $has_error ){
                $metadata[ "error" ] = $jobdata[ "error" ];
            }
            $result = $wpdb->insert(
                $wpdb->prefix . "sez_jobs",
                array(
                    "job_id" => $job_id,
                    "status" => $has_error ? "fail" : "success",
                    "has_error" => $has_error ? 1 : 0,
                    "started_at" => isset( $jobdata[ "start_time" ] ) ? $jobdata[ "start_time" ] : "",
                    "finished_at" => current_time( 'mysql' ),
                    "metadata" => serialize( $metadata )
                )
            );
            if ( false == $result ){
                return new WP_Error( "sez_save_jobdata", "Error saving jobdata." );
            }
            return $result;
        }
    }


    if ( !function_exists( 'sez_fetch_changes' ) ){
        function sez_fetch_changes( $job_id, $merged = true ){
            global $wpdb;

            $synced = $merged ? 1 : 0;
            $results = $wpdb->get_results( 
                $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}sez_changes WHERE job_id = %s AND synced = %d ORDER BY ID ASC", $job_id, $synced ),
                ARRAY_A
            );
            if ( empty( $results ) ){
                return array();
            }

            $output = array();

            foreach ( $results as $result ){
                $change = SEZ_Change::db_init( $result );
                if ( is_wp_error( $change ) ){ continue; }
                $output[] = $change->get_label();
            }
            return $output;
        }
    }


    if ( !function_exists( 'sez_fetch_merged_changes' ) ){
        function sez_fetch_merged_changes( $job_id ){
            return sez_fetch_changes( $job_id, true );
        }
    }


    if ( !function_exists( 'sez_fetch_unmerged_changes' ) ){
        function sez_fetch_unmerged_changes( $job_id ){
            return sez_fetch_changes( $job_id, false );
        }
    }


    if ( !function_exists( 'sez_sync_usermeta' ) ){
        function sez_sync_usermeta( $change, $job_id, $log ){
            global $wpdb;

            $meta_key = $change->data[2];
            $update = array();

            // Bug fix: LIFE-28 - User role not syncing correctly after merge.
            // Ensures prefix on umeta_key wp_capabilities and wp_user_level are correct.
            // In case the table prefix is different in dev than live environment.
            if ( false !== strpos( $meta_key, "capabilities" ) ){
                $update[ "meta_key" ] = $wpdb->prefix . "capabilities";
            } elseif ( false !== strpos( $meta_key, "user_level" ) ){
                $update[ "mea_key" ] = $wpdb->prefix . "user_level";
            }

            $user_id = $change->data[1];
            $dev_user_id = SEZ_Map::get_value( $wpdb->users, "ID", $user_id, false );

            if ( false !== $dev_user_id ){
                $update[ "user_id" ] = $dev_user_id;
            }

            if ( !empty( $update ) ){
                // In case the user meta id changed.
                $umeta_id = $change->data[0];
                $umeta_id = SEZ_Map::get_value( $wpdb->usermeta, "umeta_id", $umeta_id, $umeta_id );
                $result = $wpdb->update(
                    $wpdb->usermeta,
                    $update,
                    array( "umeta_id" => $umeta_id )
                );
            }
        }
    }


    if ( !function_exists( 'sez_sync_comments' ) ){
        function sez_sync_comments( $change, $job_id, $log ){
            global $wpdb;
        
            $live_post_id = $change->data[1];
            $live_comment_parent = $change->data[13];
            $live_user_id = $change->data[14];
        
            $update = array();
        
            $dev_post_id = SEZ_Map::get_value( $wpdb->posts, "ID", $live_post_id, false );
            if ( false !== $dev_post_id ){
                $update[ "comment_post_ID" ] = $dev_post_id;
            }
        
            if ( 0 !== (int)$live_user_id ){
                $dev_user_id = SEZ_Map::get_value( $wpdb->users, "ID", $live_user_id, false );
                if ( false !== $dev_user_id ){
                    $update[ "user_id" ] = $dev_user_id;
                }
            }

            if ( 0 !== (int)$live_comment_parent ){
                $dev_comment_parent = SEZ_Map::get_value( $wpdb->comments, "comment_ID", $live_comment_parent, false );
                if ( false !== $dev_comment_parent ){
                    $update[ "comment_parent" ] = $dev_comment_parent;
                }
            }
        
            if ( !empty( $update ) ){
                $comment_id = SEZ_Map::get_value( $wpdb->comments, "comment_ID", $change->data[0], $change->data[0] );
                $result = $wpdb->update(
                    $wpdb->comments,
                    $update,
                    array( "comment_ID" => $comment_id )
                );
            }
        }
    }
?>