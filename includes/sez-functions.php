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
                //file_put_contents( "{$path}/{$table}.txt", $headers . "\n" );
                $wp_filesystem->put_contents( "{$path}/{$table}.txt", $headers . "\n" );

                $results = $wpdb->get_results( "SELECT * FROM {$table}", ARRAY_A );
                if ( $results ){
                    foreach ( $results as $result ){
                        $values = array();
                        foreach ( $result as $field => $value ){
                            $values[] = $value;
                        }
                        file_put_contents( "{$path}/{$table}.txt", implode( "\t", $values ) . "\n", FILE_APPEND );
                    }
                }
            }
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

?>