<?php
/**
 * Class Test_SEZ_Api_Response
 *
 * @package Synceasywp
 */

/**
 * Test parsing api response.
 */
class Test_SEZ_Api_Response extends WP_UnitTestCase {
    
    function test_construct_response(){
        $response = wp_remote_post(
            "https://api.easysyncwp.com/wp-json/easysync/v1/licenses",
            array(
                "body" => array(
                    "name" => "Jane Doe",
                    "email" => "test2@email.com"
                )
            )
        );
        $response = new SEZ_Api_Response( $response );
        $response = $response->extract();
        $this->assertTrue( true );
    }
}