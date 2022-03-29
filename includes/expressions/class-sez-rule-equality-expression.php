<?php

    defined( 'ABSPATH' ) || exit;

    class SEZ_Rule_Equality_Expression extends SEZ_Rule_Expression {

        function __construct( $values ){
            // Handle strings or arrays.
            if ( is_string( $values ) ){
                $values = array( $values );
            }
            parent::__construct( "==", $values );
        }

        function is_valid( $new_value ){
            if ( in_array( "*", $this->values ) ){ return true; }
            return in_array( $new_value, $this->values );
        }
    }

?>