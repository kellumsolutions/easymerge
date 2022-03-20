<?php
/**
 * Class SampleTest
 *
 * @package Synceasywp
 */

/**
 * Sample test case.
 */
class Test_SEZ_Functions extends WP_UnitTestCase {

    function test_export_db(){
        global $wpdb;

        $dir = trailingslashit( ABSPATH ) . "test-export"; 
        $comment_id = $this->factory->comment->create( array( "comment_content" => "This is my test comment.\n\nAnd here is another line." ) );
        
        $results = sez_export_db( $dir );
        $this->assertTrue( $results );
    }

    function test_prepare_dir(){

        $this->ezsa_rmdir( untrailingslashit( ABSPATH ) . "/test-dir" );
        $this->ezsa_rmdir( untrailingslashit( ABSPATH ) . "/nested-dir/inside-dir" );

        // Test one-level dir.
        $dir = untrailingslashit( ABSPATH ) . "/test-dir"; 
        $result = sez_prepare_dir( $dir );
        $this->assertTrue( is_dir( $result ) );
        
        // Test nested dir.
        $result = sez_prepare_dir( untrailingslashit( ABSPATH ) . "/nested-dir/" );
        $result = sez_prepare_dir( untrailingslashit( ABSPATH ) . "/nested-dir/inside-dir" );
        var_dump( $result );
        $this->assertTrue( is_dir( $result ) );
    }


    function test_create_zip(){
        $dir = untrailingslashit( ABSPATH ) . "/test-zip";
        $this->ezsa_rmdir( $dir );
        //$dir = sez_prepare_dir( $dir ); 
        sez_export_db( $dir ); 
        $result = sez_create_zip( "why_hello.zip", $dir, ABSPATH );
        $this->assertTrue( true === $result ); 
    }


    function test_describe_db(){
        global $wpdb;

        $result = sez_describe_db();
        $this->assertTrue( 0 === (int)$result[ $wpdb->users ][ "pk_index" ] );
        $this->assertTrue( 10 === (int)$result[ $wpdb->users ][ "field_count" ] );
    }


    function test_remove_table_prefix(){
        global $wpdb;

        $this->assertTrue( "posts" === sez_remove_table_prefix( $wpdb->posts ) );
        $this->assertTrue( "Some_cool_Table_name" === sez_remove_table_prefix( $wpdb->prefix . "Some_cool_Table_name" ) );
        $this->assertTrue( "g" === sez_remove_table_prefix( $wpdb->prefix . "g" ) );
        $this->assertTrue( "woocommerce_orders" === sez_remove_table_prefix( "woocommerce_orders" ) );
    }


    function test_save_jobdata(){
        global $wpdb;

        SEZ_Install::install();

        $job_id = "1234";
        $data = array(
            "start_time" => "2020-02-23 13:17:00"
        );
        update_option( $job_id, $data );
        
        $result = sez_save_jobdata( $job_id );
        $this->assertTrue( true === $result );

        $result = $wpdb->get_results( "select * from {$wpdb->prefix}sez_jobs where job_id = '{$job_id}'", ARRAY_A );
        $this->assertTrue( "success" === $result[0][ "status" ] );
        $this->assertTrue( "0" === $result[0][ "has_error" ] );
        $this->assertTrue( "2020-02-23 13:17:00" === $result[0][ "started_at" ] );
        $this->assertTrue( serialize( array() ) === $result[0][ "metadata" ] );

        // Test with error.
        $job_id = "5678";
        $data = array(
            "start_time" => "2020-02-23 13:17:00",
            "error" => "Just a test error"
        );
        update_option( $job_id, $data );
        
        $result = sez_save_jobdata( $job_id );
        $this->assertTrue( true === $result );

        $result = $wpdb->get_results( "select * from {$wpdb->prefix}sez_jobs where job_id = '{$job_id}'", ARRAY_A );
        $this->assertTrue( "fail" === $result[0][ "status" ] );
        $this->assertTrue( "1" === $result[0][ "has_error" ] );
        $this->assertTrue( "2020-02-23 13:17:00" === $result[0][ "started_at" ] );
        $this->assertTrue( serialize( array( "error" => "Just a test error" ) ) === $result[0][ "metadata" ] );
    }


    function ezsa_rmdir( $dir ){
        if (is_dir( $dir ) ) { 
            $objects = scandir($dir);
            foreach ($objects as $object) { 
                if ($object != "." && $object != "..") { 
                    if (is_dir($dir. DIRECTORY_SEPARATOR .$object) && !is_link($dir."/".$object))
                        $this->ezsa_rmdir($dir. DIRECTORY_SEPARATOR .$object);
                    else
                        unlink($dir. DIRECTORY_SEPARATOR .$object); 
                   } 
             }
             rmdir($dir); 
        }
    }
}

?>