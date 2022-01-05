<?php

    class SEZ_Rules {

        public static function get_rules(){
            $rules = self::get_default_rules();
            $rules = apply_filter( 'sez_additional_rules', $rules );
        }


        private static function get_default_rules(){
            global $wpdb;
            
            return array(
                array(
                    "id" => "include_woocommerce_products",
                    "table" => $wpdb->posts,
                    "policy" => "include",
                    "priority" => 20,
                    "conditions" => array(
                        array(
                            "field" => "post_type",
                            "operator" => "==",
                            "values" => "product"
                        )
                    )
                ),
                array(
                    "id" => "include_woocommerce_products_meta",
                    "table" => $wpdb->postmeta,
                    "policy" => "include",
                    "priority" => 30,
                    "conditions" => array(
                        array(
                            "field" => "meta_key",
                            "operator" => "==",
                            "values" => array(
                                "_stock_status",
                                "_manage_stock",
                                "_sku",
                                "_virtual",
                                "_downloadable"
                            )
                        )
                    )
                ),
                array(
                    "id" => "exclude_woocommerce_products_meta_stock_status",
                    "table" => $wpdb->postmeta,
                    "policy" => "exclude",
                    "priority" => 30,
                    "conditions" => array(
                        array(
                            "field" => "meta_key",
                            "operator" => "==",
                            "values" => "_stock_status"
                        )
                    )
                ),
            );
        }
    }

?>