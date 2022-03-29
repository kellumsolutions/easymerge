<?php

    defined( 'ABSPATH' ) || exit;

    abstract class SEZ_Rule_Expression {
        
        protected $operator = '';
        
        /**
         * Values that have been given to this expression.
         * 
         * The is_valid method will compare a new value to the existing values to 
         * determine validity.
         */
        protected $values = array();
        
        /**
         * Rule identifier array.
         * 
         * $rules - array
         *      @param $rule string - ID of the rule.
         * 
         */
        protected $rule = false;

        function __construct( $operator, $values ){
            $this->operator = $operator;
            $this->values = $values;
        }

        public function add_rule( $rule ){
            foreach ( $rule[ "conditions" ] as $condition ){
                if ( $condition[ "operator" ] !== $this->operator ){ return; }
            }
            $this->rule = $rule[ "id" ];
        }

        /**
         * Gets rules based on the value given. 
         */
        public function get_rules( $some_value ){
            if ( $this->is_valid( $some_value ) ){
                return $this->rule;
            }
            return false;
        }
        
        public function get_operator(){
            return $this->operator;
        }

        public function description(){
            $values = is_array( $this->values ) ? implode( ",", $this->values ) : $this->values;
            return "Operator: {$this->operator}, Value: {$values}, Rule: {$this->rule}";
        }

        abstract protected function is_valid( $new_value );
    }

?>