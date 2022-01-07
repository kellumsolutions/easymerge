<?php


    if ( !function_exists( 'sez_get_tables' ) ){
        function sez_get_tables(){
            global $wpdb;

            $sql = "SHOW TABLES";

            $results = $wpdb->get_results( $sql, ARRAY_A );
            if ( empty( $results ) || !is_array( $results ) ){
                return new WP_Error( "export error", "Error getting tables." );
            }

            $tables = array();
            foreach( $results as $result ){
                foreach ( $result as $key => $table ){
                    $tables[] = $table;
                }
            }
            return $tables;
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
            global $wpdb, $wp_filesystem;

            if ( !$wp_filesystem ){
                require_once( trailingslashit( ABSPATH ) . "wp-admin/includes/file.php" );
                if ( !WP_Filesystem() ){
                    return new WP_Error( "error exporting db", "Incorrect file permissions." );
                }                
            }

            $path = $wp_filesystem->find_folder( $to_dir );

            if ( !$wp_filesystem->is_dir( $path ) ){
                if ( !$wp_filesystem->mkdir( $path ) ){
                    return new WP_Error( "error exporting db", "Could not create path." );
                }
            } elseif ( !$wp_filesystem->is_writable( $path ) ){
                return new WP_Error( "error exporting db", "Directory {$path} is not writable." );
            }

            $path = untrailingslashit( $path );
            $tables = sez_get_tables();

            if ( is_wp_error( $tables ) ){
                return $tables;
            }

            foreach ( $tables as $table ){
                $headers = $wpdb->get_col( "DESC {$table}", 0 );
                $headers = implode( "\t", $headers );
                
                // Write headers to file.
                $wp_filesystem->put_contents( "{$path}/{$table}.txt", $headers . "\n" );

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
            $tmp_dir = sez_prepare_dir( SEZ_TMP_DIR );

            if ( is_wp_error( $tmp_dir ) ){
                return $tmp_dir;
            }

            $unique = bin2hex( random_bytes( 12 ) );
            $to_dir = untrailingslashit( $tmp_dir ) . "/" . $unique;
            $to_dir = sez_prepare_dir( $to_dir );

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
            return trailingslashit( SEZ_TMP_URL ) . $unique . "/dump.zip";
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

?>