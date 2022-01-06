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