<?php

    class SEZ_Remote_Api {

        private static $endpoint = "https://api.easysyncwp.com/wp-json/easysync/v1";


        public static function create_new_registration( $args ){
            $response = wp_remote_post(
                "https://api.easysyncwp.com/wp-json/easysync/v1/register",
                array(
                    "body" => $args
                )
            );

            $response = new SEZ_Api_Response( $response );
            $response = $response->extract();
            return $response;
        }


        public static function create_license_key( $name, $email ){
            $response = wp_remote_post(
                self::$endpoint . "license",
                array(
                    "body" => array(
                        "name" => $name,
                        "email" => $email
                    )
                )
            );

            $response = new SEZ_Api_Response( $response );
            $response = $response->extract();
            return $response;
        }


        public static function get_dump( $license_key, $live_domain, $staging_domain ){
            $url = self::$endpoint . "/dump?license_key={$license_key}&live_domain={$live_domain}&staging_domain={$staging_domain}";
            $response = wp_remote_get( $url );
            $response = new SEZ_Api_Response( $response );
            $response = $response->extract();

            if ( is_wp_error( $response ) ){
                return $response;
            }
            return $response->exists;
        }


        public static function create_dump( $license_key, $live_domain, $staging_domain, $url ){
            $response = wp_remote_post(
                self::$endpoint . "/dump",
                array(
                    "body" => array(
                        "license_key" => $license_key,
                        "url" => $url,
                        "live_domain" => $live_domain,
                        "staging_domain" => $staging_domain
                    )
                )
            );
            $response = new SEZ_Api_Response( $response );
            $response = $response->extract();

            if ( is_wp_error( $response ) ){
                return $response;
            }
            return $response->uploaded;
        }
    }
?>