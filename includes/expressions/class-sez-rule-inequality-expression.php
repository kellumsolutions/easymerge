<?php

    class SEZ_Rule_Inequality_Expression extends SEZ_Rule_Expression {

        function __construct( $operator, $values ){
            if ( $operator !== "<" && $operator !== ">" ){ return false; }

            if ( is_string( $values ) ){
                $values = array( $values );
            }
            parent::__construct( $operator, $values );
        }

        function is_valid( $new_value ){
            foreach ( $this->values as $value ){
                $result = $this->operator === "<" ? $new_value < $value : $new_value > $value;
                if ( !$result ){ return false; }
                // if ( $this->operator === "<" ){
                //     return $new_value < $this->value;
                // } else if ( $this->operator === ">" ){
                //     return $new_value > $this->value;
                // }
            }
            
            return true;
        }
    }

?>