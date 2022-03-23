<?php

    class SEZ_Remote_Api {

        private static $endpoint = "https://api.easysyncwp.com/wp-json/easysync/v1";


		public static function get_registrations( $args ){
			$response = wp_remote_get(
                add_query_arg(
                	$args,
					"https://api.easysyncwp.com/wp-json/easysync/v1/register"
				)
            );

            $response = new SEZ_Api_Response( $response );
            $response = $response->extract();
            return $response;
		}
		
		
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
                self::$endpoint . "/license",
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


        public static function get_dump( $license_key, $live_domain, $staging_domain, $log = false ){
            $url = self::$endpoint . "/dump?license_key={$license_key}&live_domain={$live_domain}&staging_domain={$staging_domain}";
            
            if ( !empty( $log ) ){
                SEZ_Sync::log( $log, "SEZ_Remote_Api:get_dump -- endpoint: {$url}.", "DEBUG" );
            }

            $response = wp_remote_get( $url );
            $response = new SEZ_Api_Response( $response );
            $response = $response->extract();

            $json = json_encode( $response );

            if ( !empty( $log ) ){
                SEZ_Sync::log( $log, "SEZ_Remote_Api:get_dump -- response: {$json}.", "DEBUG" );
            }

            if ( is_wp_error( $response ) ){
                return $response;
            }
            return $response->exists;
        }


        public static function create_dump( $license_key, $live_domain, $staging_domain, $url, $log = false ){
            $endpoint = self::$endpoint . "/dump";
            $body = array(
                "license_key" => $license_key,
                "url" => $url,
                "live_domain" => $live_domain,
                "staging_domain" => $staging_domain
            );

            if ( !empty( $log ) ){
                SEZ_Sync::log( $log, "SEZ_Remote_Api:create_dump -- endpoint: {$endpoint}, payload: " . json_encode( $body ), "DEBUG" );
            }

            $response = wp_remote_post(
                $endpoint,
                array(
                    "body" => $body
                )
            );
            $response = new SEZ_Api_Response( $response );
            $response = $response->extract();

            if ( !empty( $log ) ){
                SEZ_Sync::log( $log, "SEZ_Remote_Api:create_dump -- response: " . json_encode( $response ), "DEBUG" );
            }

            if ( is_wp_error( $response ) ){
                return $response;
            }
            return $response->uploaded;
        }


        public static function get_changes( $args ){
            $url = isset( $args[ "url" ] ) ? $args[ "url" ] : "";
            $license_key = isset( $args[ "license_key" ] ) ? $args[ "license_key" ] : "";
            $staging_domain = isset( $args[ "staging_domain" ] ) ? $args[ "staging_domain" ] : "";
            $live_domain = isset( $args[ "live_domain" ] ) ? $args[ "live_domain" ] : "";
            $desc = isset( $args[ "desc" ] ) ? $args[ "desc" ] : "";
            $tables_with_rules = isset( $args[ "tables" ] ) ? $args[ "tables" ] : "";

            $response = wp_remote_post(
                self::$endpoint . "/changes",
                array(
                    "body" => array(
                        "url" => $url,
                        "license_key" => $license_key,
                        "staging_domain" => $staging_domain,
                        "live_domain" => $live_domain,
                        "desc" => $desc,
                        "tables" => $tables_with_rules
                    )
                )
            );

            $response = new SEZ_Api_Response( $response );
            $response = $response->extract();
            return $response;
        }
    }
?>