<?php
/**
 * Class SEZ_Rules
 *
 * @package Synceasywp
 */

class Test_SEZ_Rules extends WP_UnitTestCase {
    function test_get_processed_rules(){
        $rules = SEZ_Rules::get_processed_rules();
        //var_dump( $rules );
        $this->assertTrue( 0 < count( $rules ) );
    }


    function test_get_tables_with_rules(){
        $tables = SEZ_Rules::get_tables_with_rules();
        var_dump( $tables );
        $this->assertTrue( $tables[ "users" ] === "1" );
        $this->assertTrue( $tables[ "comments" ] === "1" );
        $this->assertTrue( $tables[ "usermeta" ] === "1" );
    }
}