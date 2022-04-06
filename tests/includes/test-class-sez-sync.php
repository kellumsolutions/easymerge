<?php
/**
 * Class SEZ_Sync
 *
 * @package Synceasywp
 */

class Test_SEZ_Sync extends WP_UnitTestCase {

    function test_create_job(){
        // Ensure job is created, saved, and log is created. 
        $job_id = SEZ()->sync->create_job();

        $this->assertTrue( false === is_wp_error( $job_id ) );
        $this->assertTrue( false === empty( get_option( $job_id ) ) );

        $path = SEZ_Merge_Log::get_path( $job_id );
        $this->assertTrue( true === file_exists( $path ) );

        if ( file_exists( $path ) ){
            unlink( $path );
        }
    }


    // Ensure merge logging works.
    function test_log(){
        $job_id = SEZ()->sync->create_job();
        $path = SEZ_Merge_Log::get_path( $job_id );
        $log = sez_get_merge_log( $job_id );
        SEZ_Sync::log( $log, "First log message.", "WARNING" );
        SEZ_Sync::log( $log, "Another message. This one should be the error.", "ERROR" );
        SEZ_Sync::log( $log, "Last test message." );

        // Count lines in file.
        $linecount = 0;
        $handle = fopen( $path, "r" );
        while( !feof( $handle ) ){
            $line = fgets( $handle );
            $linecount++;
        }
        fclose($handle);
        
        // 4 because "\n" character is written after string.
        $this->assertTrue( 4 === $linecount ); 
        if ( file_exists( $path ) ){
            unlink( $path );
        }
    }
}