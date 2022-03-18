<?php
    /**
     * Class SEZ_Change
     *
     * @package Synceasywp
     */

    class Test_SEZ_Merge_Log extends WP_UnitTestCase {

        function test_merge_log_process(){
            $job_id = "fred_flintstone";
            $logger = SEZ_Merge_Log::create( $job_id );

            $type = 'Y-m-d H:i:s';
            $timezone = wp_timezone();
            $datetime = new DateTime( 'now', $timezone );

            $data = array(
                array( "orange", new DateTime( '+2 minutes', $timezone ), "INFO" ),
                array( "yellow", new DateTime( '+5 minutes', $timezone ), "INFO" ),
                array( "purple", new DateTime( '+10 minutes', $timezone ), "INFO" ),
                array( "green", new DateTime( '+20 minutes', $timezone ), "ERROR" ),
                array( "brown", new DateTime( '+45 minutes 19 seconds', $timezone ), "INFO" ),
            );
            foreach( $data as $item ){
                $logger->write( $item[0], $item[2], ( $item[1] )->format( $type ) );
            }

            $logger->process();
            $this->assertTrue( true === $logger->has_error() );
            $this->assertTrue( "green" === $logger->get_error() );
            $this->assertTrue( $data[0][1]->format( $type ) === $logger->get_start_time() );
            $this->assertTrue( $data[4][1]->format( $type ) === $logger->get_end_time() );
            $this->assertTrue( "43m 19s" === $logger->get_duration() );
            
            // Test loading from job_id.
            $logger = new SEZ_Merge_Log( $job_id );
            $this->assertTrue( true === $logger->has_error() );
            $this->assertTrue( "green" === $logger->get_error() );
            $this->assertTrue( $data[0][1]->format( $type ) === $logger->get_start_time() );
            $this->assertTrue( $data[4][1]->format( $type ) === $logger->get_end_time() );
            $this->assertTrue( "43m 19s" === $logger->get_duration() );

            // Test with no error.
            unset( $data[3] );
            $logger = SEZ_Merge_Log::create( $job_id );
            foreach( $data as $item ){
                $logger->write( $item[0], $item[2], ( $item[1] )->format( $type ) );
            }
            $logger->process();
            $this->assertTrue( false === $logger->has_error() );
            $this->assertTrue( "" === $logger->get_error() );

            // Test when no log exists.
            unlink( SEZ_Merge_Log::get_path( $job_id ) );
            $logger = new SEZ_Merge_Log( $job_id );
            $this->assertTrue( false === $logger->has_error() );
            $this->assertTrue( "" === $logger->get_error() );
            $this->assertTrue( "" === $logger->get_start_time() );
            $this->assertTrue( "" === $logger->get_end_time() );
            $this->assertTrue( "" === $logger->get_duration() );
        }

    }
?>