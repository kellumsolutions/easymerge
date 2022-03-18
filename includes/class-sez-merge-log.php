<?php

    defined( 'ABSPATH' ) || exit;

    class SEZ_Merge_Log {

        private $job_id = "";

        private $start_time = "";

        private $end_time = "";

        private $error = "";


        public function __construct( $job_id ){
            $this->job_id = $job_id;

            // Read log and populate class properties.
            $this->process();
        }


        public function process(){
            $file = self::get_path( $this->job_id );
            $line_num = 0;

            try {
                $handle = fopen( $file, "r" );
                if ( $handle ) {
                    while ( ( $line = fgets( $handle ) ) !== false) {
                        $timestamp = self::get_timestamp( $line );
                        if ( 0 === $line_num ){
                            $this->start_time = $timestamp;
                        } else {
                            $this->end_time = $timestamp;
                        }

                        // Search for error.
                        if ( strpos( $line, "[ERROR]" ) !== false ){
                            $components = explode( "[ERROR]", $line );
                            $this->error = trim( $components[ count( $components ) - 1 ] );
                        }
                        $line_num++;
                    }
                    fclose($handle);

                } else {
                    // error opening the file.
                    return new WP_Error( "get_sync_status_error", "Error reading log file {$log}." );
                }
            } catch ( Exception $e ){
                return new WP_Error( "get_sync_status_error", $e->getMessage() );
            }
        }


        public function write( $message, $type = "INFO", $timestamp = false ){
            // Incorporate log levels.
            if ( !isset( SEZ_LOG_LEVELS[ $type ] ) ){ return; }

            $level = SEZ_LOG_LEVELS[ $type ];
            $app_level = SEZ_LOG_LEVELS[ SEZ_LOG_LEVEL ];

            if ( $level > $app_level ){ return; }

            $timestamp = empty( $timestamp ) ? current_time( 'mysql' ) : $timestamp;
            $line = "{$timestamp} [{$type}] {$message}\n";
            
            file_put_contents( self::get_path( $this->job_id ), $line, FILE_APPEND );
        }


        public function get_start_time(){
            return $this->start_time;
        }


        public function get_end_time(){
            return $this->end_time;
        }


        public function get_error(){
            return $this->error;
        }


        public function has_error(){
            return !empty( $this->error );
        }


        public function get_duration(){
            $start_timestamp = strtotime( $this->start_time );
            $end_timestamp = strtotime( $this->end_time );

            if ( empty( $start_timestamp ) || empty( $end_timestamp ) ){
                return "";
            }
            $delta = $end_timestamp - $start_timestamp;
            if ( $delta <= 0 ){ return "0m 0s"; }

            $minutes = 0;

            if ( $delta > 59 ){
                $minutes = floor( $delta / 60 );
            }
            $seconds = $delta % 60;
            return "{$minutes}m {$seconds}s";
        }


        public static function get_timestamp( $line ){
            $parts = explode( " ", $line, 3 );
            if ( 3 === count( $parts ) ){
                return $parts[0] . " " . $parts[1];
            }
        }


        public static function get_path( $job_id ){
            return trailingslashit( wp_upload_dir()[ "basedir" ] ) . "sez-logs/" . $job_id . ".log";
        }


        public static function create( $job_id ){
            $base = trailingslashit( wp_upload_dir()[ "basedir" ] ) . "sez-logs";
            if ( false === is_dir( $base ) ){
                if ( false === mkdir( $base ) ){
                    return new WP_Error( "sez_create_merge_log_error", "Unable to create {$base} directory. Permissions issue." );
                }
            }
            
            $log = trailingslashit( $base ) . $job_id . ".log";
            if ( file_exists( $log ) ){
                if ( false === unlink( $log ) ){
                    return new WP_Error( "sync_create_merge_log_error", "Unable to delete existing log file {$log}. Permissions error." );
                }
            }

            if ( false === file_put_contents( $log, "" ) ){
                return  new WP_Error( "sync_create_merge_log_error", "Unable to create log file. Permissions error." );
            }
            return new SEZ_Merge_Log( $job_id );
        }
    }
?>