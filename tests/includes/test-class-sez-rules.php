<?php
/**
 * Class SEZ_Rules
 *
 * @package Synceasywp
 */

class Test_SEZ_Rules extends WP_UnitTestCase {
    function test_get_processed_rules(){
        global $wpdb;

        // Add in additional rules.
        add_filter( 'sez_additional_rules', function( $current_rules ){
            foreach ( $this->get_additional_rules() as $rule ){
                $current_rules[] = $rule;
            }
            return $current_rules;
        }, 20 );

        $rules = SEZ_Rules::get_processed_rules();
        
        $this->assertTrue( 0 < count( $rules ) );
        $this->assertTrue( true === isset( $rules[ $wpdb->users ] ) );
        $this->assertTrue( true === isset( $rules[ $wpdb->usermeta ] ) );
        $this->assertTrue( true === isset( $rules[ $wpdb->comments ] ) );
        $this->assertTrue( false === isset( $rules[ $wpdb->posts ] ) );

        SEZ_Rules::enable_rules(
            array(
                "include_all_users",
                "ezsw_include_all_orders"
            )
        );
        $rules = SEZ_Rules::get_processed_rules();
        $this->assertTrue( true === isset( $rules[ $wpdb->users ] ) );
        $this->assertTrue( true === isset( $rules[ $wpdb->usermeta ] ) );
        $this->assertTrue( false === isset( $rules[ $wpdb->comments ] ) );
        $this->assertTrue( true === isset( $rules[ $wpdb->posts ] ) );
        $this->assertTrue( true === isset( $rules[ $wpdb->postmeta ] ) );
    }


    function test_get_tables_with_rules(){
        $tables = SEZ_Rules::get_tables_with_rules();
        //var_dump( $tables );
        $this->assertTrue( $tables[ "users" ] === "1" );
        $this->assertTrue( $tables[ "comments" ] === "1" );
        $this->assertTrue( $tables[ "usermeta" ] === "1" );
    }


    function test_is_rule_enabled(){
        $this->assertTrue( true === SEZ_Rules::is_rule_enabled( "include_all_comments" ) );
        $this->assertTrue( false === SEZ_Rules::is_rule_enabled( "bad_rule" ) );
    }


    public function get_additional_rules(){
        global $wpdb;

        $rules = array(
            array(
                "id" => "ezsw_include_all_orders",
                "group" => true,
                "description" => "Allows test rules.",
                "rules" => array(
                    array(
                        "id" => "ezsw_include_all_order_posts",
                        "table" => $wpdb->posts,
                        "policy" => "include",
                        "conditions" => array(
                            array(
                                "field" => "post_type",
                                "operator" => "==",
                                "values" => array( "page", "post" )
                            )
                        )
                    ),
                    array(
                        "id" => "ezsw_include_all_order_postmeta",
                        "table" => $wpdb->postmeta,
                        "policy" => "include",
                        "conditions" => array()
                    ),
                )
            )
        );
        return $rules;
    }
}