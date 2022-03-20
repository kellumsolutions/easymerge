<?php

    defined( 'ABSPATH' ) || exit;

    if ( ! class_exists( 'WP_Async_Request', false ) ) {
        include_once SEZ_ABSPATH . '/includes/background-process/wp-async-request.php';
    }

    if ( ! class_exists( 'WP_Background_Process', false ) ) {
        include_once SEZ_ABSPATH . '/includes/background-process/wp-background-process.php';
    }

    class SEZ_Sync extends WP_Background_Process {
        
        protected $prefix = "sez";


        public function start(){
            if ( $this->is_process_running() ){
                return new WP_Error( "sync_error", "Another process is already running." );
            }

            // Ensure no other batches/queue items exist.
            while ( !$this->is_queue_empty() ){
                $this->cancel_process();
            }

            // Create job.
            $job_id = $this->create_job();
            if ( is_wp_error( $job_id ) ){
                return $job_id;
            }

            $tasks = $this->get_tasks( $job_id );
            foreach ( $tasks as $task ){
                foreach ( $task as $item ){
                    $this->push_to_queue( $item );
                }
                $this->save(); // Saves new batch.
                $this->data = array();
            }            
            $this->dispatch();
            return $job_id;
        }


        public function get_tasks( $job_id ){
            return array(
                array(
                    array( "action" => "start", "job_id" => $job_id )
                ),
                // Queue 2
                array(
                    array( "action" => "validate", "job_id" => $job_id ),
                    array( "action" => "check_for_existing_dump", "job_id" => $job_id ),
                ),
                // Queue 3
                array(
                    array( "action" => "get_live_site_data", "job_id" => $job_id ),
                    array( "action" => "export_live_site", "job_id" => $job_id )
                ),
                // Queue 4
                array(
                    array( "action" => "fetch_changes", "job_id" => $job_id )
                ),
                // Queue 5
                array(
                    array( "action" => "replace_existing_dump", "job_id" => $job_id ),
                    array( "action" => "save_changes_to_db", "job_id" => $job_id )
                ),
                // Queue 6
                array(
                    array( "action" => "perform_changes", "job_id" => $job_id ),
                ),
                // // Queue 7
                array(
                    array( "action" => "perform_adjustments", "job_id" => $job_id ),
                    array( "action" => "done", "job_id" => $job_id )
                )
            );
        }


        protected function task( $item ){
            // file_put_contents( SEZ_ABSPATH . "/test.txt", json_encode( $item ) . "\n", FILE_APPEND );
            $job_id = $item[ "job_id" ];
            // $jobdata = get_option( $job_id );
            // file_put_contents( SEZ_ABSPATH . "/test.txt", json_encode( $jobdata ) . "\n", FILE_APPEND );
            // When an error exists, no other processes run.
            if ( !empty( $this->get_job_param( $job_id, "error", "" ) ) ){
                return false;
            }

            $log = sez_get_merge_log( $job_id );

            $result = call_user_func_array( 
                array( "SEZ_Sync_Functions", $item[ "action" ] ),
                array( $job_id, $log )
            );
            
            if ( is_wp_error( $result ) ){ 
                $this->handle_error( $job_id, $result ); 
            } else {
                $this->parse_params( $job_id, $result );
            }
            return false;
        }


        /**
         * Complete.
         *
         * Override if applicable, but ensure that the below actions are
         * performed, or, call parent::complete().
         */
        protected function complete() {
            global $wpdb;

            parent::complete();
    
            $results = $wpdb->get_results( 
                "SELECT * FROM {$wpdb->options} WHERE option_name LIKE 'sez-sync-job%' ORDER BY option_name DESC", 
                ARRAY_A 
            );
            if ( !empty( $results ) ){

                // Save job data in db.
                $job_id = $results[0][ "option_name" ];
                // file_put_contents( SEZ_ABSPATH . "/test.txt", json_encode( $results[0][ "option_name" ] ) . "\n", FILE_APPEND );

                if ( is_wp_error( $result = sez_save_jobdata( $job_id ) ) ){
                    // Maybe do some kind of indication?
                    // file_put_contents( SEZ_ABSPATH . "/test.txt", json_encode( $result ) . "\n", FILE_APPEND );
                }

                // Delete job data.
                foreach ( $results as $option ){
                    $option_name = $option[ "option_name" ];
                    delete_option( $option_name );
                }
            }
        }


        public function create_job( $create_log = true ){
            $prefix = "sez-sync-job-";
            $unique = bin2hex( random_bytes( 12 ) );
            $job_id = $prefix . $unique;
            
            if ( $create_log ){
                $log = SEZ_Merge_Log::create( $job_id );

                if ( is_wp_error( $log ) ){
                    return $log;
                }
            } 
            // else {
            //     $log = "blank";
            // }
            
            $jobdata = array( 
                // "log" => $log, 
                "data" => array(), 
                "start_time" => current_time( 'mysql' ) 
            );
            update_option( $job_id, $jobdata );
            return $job_id;
        }


        public function set_job_param( $job_id, $key, $value, $context = "data" ){
            $jobdata = get_option( $job_id );
            if ( empty( $context ) ){
                $jobdata[ $key ] = $value; 
            } else {
                $jobdata[ $context ][ $key ] = $value;
            }
            update_option( $job_id, $jobdata );
        }


        public function get_job_param( $job_id, $key, $context = "data" ){
            $jobdata = get_option( $job_id );
            if ( empty( $context ) ){
                return isset( $jobdata[ $key ] ) ? $jobdata[ $key ] : "";
            }
            return isset( $jobdata[ $context ][ $key ] ) ? $jobdata[ $context ][ $key ] : "";
        }


        private function handle_error( $job_id, $err ){
            $message = $err->get_error_message();
            $this->set_job_param( $job_id, "error", $message, "" );

            $jobdata = get_option( $job_id );
            $log = sez_get_merge_log( $job_id );
            
            if ( is_wp_error( $log ) ){
                return;
            }
            $log->write( $message, "ERROR" );
        }


        private function parse_params( $job_id, $set ){
            if ( empty( $set ) || !is_array( $set ) ){
                return;
            }

            foreach( $set as $key => $value ){
                $this->set_job_param( $job_id, $key, $value );
            }
        }


        public static function log( $log, $message, $type = "INFO" ){
            if ( is_wp_error( $log ) ){
                return;
            }
            if ( $log === "blank" ){ return; }
            
            $log->write( $message, $type );
        }
    }

