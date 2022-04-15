<?php
    /**
     * Class SEZ_Settings
     *
     * @package Synceasywp
     */

    class Test_SEZ_Settings extends WP_UnitTestCase {

        // LIFE-56: Move license key out of sez_settings.json file.
        function test_load_save_license(){
            $path = SEZ()->settings->get_path();
            $license = "abcd1234";

            SEZ()->settings->license = $license;
            SEZ()->settings->save();
            SEZ()->settings->license = "a-test-license";
            SEZ()->settings->load( $path );
            $this->assertTrue( $license === SEZ()->settings->license );
            
            if ( file_exists( $path ) ){
                unlink( $path );
            }
        }
    }

?>