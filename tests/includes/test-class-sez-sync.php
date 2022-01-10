<?php
/**
 * Class SEZ_Sync
 *
 * @package Synceasywp
 */

class Test_SEZ_Sync extends WP_UnitTestCase {
    function test_create_log(){
        $result = SEZ()->sync->create_log();
        var_dump( $result );
        $this->assertTrue( false === is_wp_error( $result ) );
        $this->assertTrue( true === file_exists( $result ) );
    }


    function test_create_job(){
        $job_id = SEZ()->sync->create_job();

        $this->assertTrue( false === is_wp_error( $job_id ) );
        $jobdata = get_option( $job_id );
        $this->assertTrue( file_exists( $jobdata[ "log" ] ) );
    }


    function test_log(){
        $log_file = SEZ()->sync->create_log();
        var_dump( $log_file );
        SEZ_Sync::log( $log_file, "First log message.", "DEBUG" );
        SEZ_Sync::log( $log_file, "Another message. This one should be the error.", "ERROR" );
        SEZ_Sync::log( $log_file, "Last test message." );
        $this->assertTrue( true );
    }


    function test_sync_start(){
        $log = SEZ()->sync->start();
        var_dump( $log );
        $this->assertTrue( true );
    }
}