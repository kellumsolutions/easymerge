<?php
/**
 * Class SEZ_Change
 *
 * @package Synceasywp
 */

class Test_SEZ_Change extends WP_UnitTestCase {
    function test_find_rule(){
        $operation = "CREATE";
        $primary_key = 1;
        $table = "comments";
        $data = array( "1", "1", "travis", "test@email.com", "test@site.com", "174.74.74.74", "2022-01-05 02:08:57", "2022-01-05 02:08:57", "Just making change.", "0", "1", "Mozilla", "comment", 0, "1/n");
        $change = new SEZ_Change( $operation, $table, $primary_key, $data );
        $rule = $change->find_rule();

        var_dump( $rule );
        $this->assertTrue( false === is_null( $rule ) );
    }
}