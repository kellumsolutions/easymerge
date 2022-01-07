<?php

    class SEZ_Api_Response {
        
        private $data = null;

        public function __construct( $raw_response ){
            if ( is_wp_error( $raw_response ) ){
                $this->data = $raw_response;
                return;
            }

            if ( $raw_response[ "response" ][ "code" ] < 200 || $raw_response[ "response" ][ "code" ] > 299 ){
                $code = "rest_api_error";
                $message = "An error occurred. Please try again later.";
                //file_put_contents( SEZ_ABSPATH . "/test.txt", $raw_response[ "body" ]  );
                if ( isset( $raw_response[ "body" ] ) ){
                    $body = json_decode( $raw_response[ "body" ], ARRAY_A );
                    if ( isset( $body[ "message" ] ) && !empty( $body[ "message" ] ) ){
                        $message = $body[ "message" ];
                    }
                }
                $this->data = new WP_Error( $code, $message );
                return;
            }
            $this->data = json_decode( $raw_response[ "body" ] );
        }


        public function extract(){
            if ( is_null( $this->data ) ){
                return array();
            }

            if ( is_wp_error( $this->data ) ){
                return $this->data;
            }
            return $this->data;
        }
    }

?>