<?php
/**
 * Class SEZ_Change
 *
 * @package Synceasywp
 */

class Test_SEZ_Change extends WP_UnitTestCase {

    function test_find_rule(){
        global $wpdb;

        // Add in additional rules.
        add_filter( 'sez_additional_rules', function( $current_rules ){
            foreach ( $this->get_additional_rules() as $rule ){
                $current_rules[] = $rule;
            }
            return $current_rules;
        }, 20 );

        // Enable additional rules.
        SEZ_Rules::enable_rules(
            array(
                "include_all_users",
                "ezsw_include_all_orders",
                "include_all_comments"
            )
        );

        $operation = "CREATE";
        $primary_key = 1;
        $table = "comments";
        $data = array( "1", "1", "travis", "test@email.com", "test@site.com", "174.74.74.74", "2022-01-05 02:08:57", "2022-01-05 02:08:57", "Just making change.", "0", "1", "Mozilla", "comment", 0, "1/n");
        $change = new SEZ_Change( $operation, $table, $primary_key, $data );
        $rule = $change->find_rule();

        $this->assertTrue( "include_all_comments" === $rule );

        $operation = "UPDATE";
        $primary_key = 0;
        $table = $wpdb->posts;
        $data = array( "12", "3", "", "", "test content", "sample post title", "", "pending", "closed", "closed", "", "sample-post-title", "", "", "", "", "", "0", "12", "0", "page", "", "5" );
        $change = new SEZ_Change( $operation, $table, $primary_key, $data );
        $rule = $change->find_rule();
       
        $this->assertTrue( "ezsw_include_all_order_posts" === $rule ); 

        $data[20] = "product";
        $change = new SEZ_Change( $operation, $table, $primary_key, $data );
        $rule = $change->find_rule();
        $this->assertTrue( true === empty( $rule ) ); 
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
                        // "policy" => "include",
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
                        // "policy" => "include",
                        "conditions" => array()
                    ),
                )
            )
        );
        return $rules;
    }


    function test_execute(){
        global $wpdb;

        // Basic create.
        $operation = "CREATE";
        $primary_key = 1;
        $table = "comments";
        $data = array( "{$primary_key}", "1", "travis", "test@email.com", "test@site.com", "174.74.74.74", "2022-01-05 02:08:57", "2022-01-05 02:08:57", "Just making change.", "0", "1", "Mozilla", "comment", 0, "1/n");
        $change = new SEZ_Change( $operation, $table, $primary_key, $data );
        $change->execute();

        $comments = $wpdb->get_results( "SELECT * FROM {$wpdb->comments}", ARRAY_A );
        $this->assertTrue( 1 === count( $comments ) );
        $comment_id = $comments[0][ "comment_ID" ];

        // Basic update.
        $operation = "UPDATE";

        // Comment ID doesn't insert to 1 (or 0) for some reason. Use actual comment id.
        $data[0] = $comment_id; 
        $data[2] = "Beowulf";
        $primary_key = $comment_id;

        $change = new SEZ_Change( $operation, $table, $primary_key, $data );
        $change->execute();
        $comments = $wpdb->get_results( "SELECT * FROM {$wpdb->comments} WHERE comment_ID = {$comment_id}", ARRAY_A );
        $this->assertTrue( "Beowulf" === $comments[0][ "comment_author" ] );


        // Basic delete.
        $operation = "DELETE";
        $change = new SEZ_Change( $operation, $table, $primary_key, $data );
        $change->execute();
        $comments = $wpdb->get_results( "SELECT * FROM {$wpdb->comments}", ARRAY_A );
        $this->assertTrue( 0 === count( $comments ) );

        
        // Test create where mapping is needed.
        $this->factory->comment->create_many( 25 );
        // Get latest comment id. Increment it.
        $results = $wpdb->get_results( "SELECT * FROM {$wpdb->comments} ORDER BY comment_ID DESC LIMIT 1", ARRAY_A );
        $next_id = (int)$results[0][ "comment_ID" ] + 1;
        $operation = "CREATE";
        $data[0] = 13; // Should be in use.
        $change = new SEZ_Change( $operation, $table, 13, $data );
        $change->execute();

        $comments = $wpdb->get_results( "SELECT * FROM {$wpdb->comments}", ARRAY_A );
        $this->assertTrue( 26 === count( $comments ) );
        $this->assertTrue( $next_id === (int)$comments[ count( $comments ) - 1 ][ "comment_ID" ] );
        

        // Test update where a mappping is used.
        $data[2] = "LeBron James";
        $operation = "UPDATE";
        $change = new SEZ_Change( $operation, $table, 13, $data );
        $change->execute();

        $comment = $wpdb->get_results( "SELECT * FROM {$wpdb->comments} WHERE comment_ID = 13", ARRAY_A );
        $this->assertTrue( "LeBron James" !== $comment[0][ "comment_author" ] );

        $comment = $wpdb->get_results( "SELECT * FROM {$wpdb->comments} WHERE comment_ID = {$next_id}", ARRAY_A );
        $this->assertTrue( "LeBron James" === $comment[0][ "comment_author" ] );


        // Test delete where a mapping is used.
        $operation = "DELETE";
        $change = new SEZ_Change( $operation, $table, 13, $data );
        $change->execute();

        $comment = $wpdb->get_results( "SELECT * FROM {$wpdb->comments} WHERE comment_ID = 13", ARRAY_A );
        $this->assertTrue( false === empty( $comment ) );

        $comment = $wpdb->get_results( "SELECT * FROM {$wpdb->comments} WHERE comment_ID = {$next_id}", ARRAY_A );
        $this->assertTrue( true === empty( $comment ) );
    }
}