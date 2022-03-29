<?php

    defined( 'ABSPATH' ) || exit;
    
    class SEZ_Rule_Like_Expression extends SEZ_Rule_Expression {

        function __construct( $values ){
            // Handle strings or arrays.
            if ( is_string( $values ) ){
                $values = array( $values );
            }
            parent::__construct( "LIKE", $values );
        }

        function is_valid( $new_value ){
            if ( in_array( "*", $this->values ) ){ return true; }
            
            foreach( $this->values as $value ){
                if ( strpos( strtolower( $value ), strtolower( $new_value ) ) !== false ){
                    return true;
                }
            }
            return false;
        }
    }

?>