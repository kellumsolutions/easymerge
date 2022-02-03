<?php
/**
 * Class SEZ_Sync_Functions
 *
 * @package Synceasywp
 */

class Test_SEZ_Sync_Functions extends WP_UnitTestCase {
    function test_perform_changes(){
        global $wpdb;
        SEZ_Install::install();

        $job_id = "test-job-id";
        $log = ABSPATH . "/" . $job_id . ".log";
        $changes_file = ABSPATH . "/" . $job_id . ".json";

        if ( file_exists( $log ) ){
            unlink( $log );
        }

        if ( file_exists( $changes_file ) ){
            unlink( $changes_file );
        }

        $this->make_changes_file( $changes_file );

        // Set option.
        $data = array( 
            "data" => array(
                "changes_file" => $changes_file
            ) 
        );
        update_option( $job_id, $data );

        SEZ_Sync_Functions::save_changes_to_db( $job_id, $log );
        $result = SEZ_Sync_Functions::perform_changes( $job_id, $log );
        $this->assertTrue( true === $result );

        $results = $wpdb->get_results( 
            $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}sez_changes WHERE job_id = %s", $job_id ),
            ARRAY_A
        );
        foreach ( $results as $result ){
            $this->assertTrue( 1 === (int)$result[ "synced" ] );
        }

        $results = $wpdb->get_results( "SELECT * FROM {$wpdb->comments}" );
        $this->assertTrue( 1 === count( $results ) );
    }



    function make_changes_file( $path ){
        $json = '[{"operation":"UPDATE","table":"comments","primary_key":2,"data":["2","1","travis","travis@kellumsolutions.com","https:\/\/demo.easysyncwp.com","174.62.59.37","2022-01-05 02:08:57","2022-01-05 02:08:57","Just making another change. Will it pick it up?","0","1","Mozilla\/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/95.0.4638.69 Safari\/537.36","comment","0","1\n"]},{"operation":"CREATE","table":"comments","primary_key":3,"data":["3","1","John Doe","kellumtravis@yahoo.com","http:\/\/mycoolwensite.com","174.62.59.37","2022-01-07 14:59:27","2022-01-07 14:59:27","Just an outside user leaving a comment.","0","1","Mozilla\/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/95.0.4638.69 Safari\/537.36","comment","0","0\n"]},{"operation":"UPDATE","table":"usermeta","primary_key":16,"data":["16","1","session_tokens","a:1:{s:64:\"98be0bc8b20506ad32b9f0f0a4862ab12a25da1ab769872f102c400bde7b9669\";a:4:{s:10:\"expiration\";i:1641928926;s:2:\"ip\";s:12:\"174.62.59.37\";s:2:\"ua\";s:120:\"Mozilla\/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/95.0.4638.69 Safari\/537.36\";s:5:\"login\";i:1641756126;}}\n"]},{"operation":"UPDATE","table":"usermeta","primary_key":22,"data":["22","1","wc_last_active","1641686400\n"]}]';
        file_put_contents( $path, $json );
    }
}