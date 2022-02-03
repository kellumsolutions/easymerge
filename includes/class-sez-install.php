<?php

    class SEZ_Install {

        public static function install(){
            if ( ! is_blog_installed() ) {
                return;
            }
            self::maybe_create_db_tables();
        }


        public static function maybe_create_db_tables(){
            global $wpdb;
            $wpdb->hide_errors();

            require_once ABSPATH . 'wp-admin/includes/upgrade.php';

            $schemas = self::get_schemas();
            foreach ( $schemas as $schema ){
                dbDelta( $schema );
            }
        }


        public static function get_schemas(){
            global $wpdb;
    
            $collate = '';
    
            if ( $wpdb->has_cap( 'collation' ) ) {
                $collate = $wpdb->get_charset_collate();
            }

            return array(
                "CREATE TABLE {$wpdb->prefix}sez_changes (
                ID BIGINT(20) NOT NULL AUTO_INCREMENT,
                operation VARCHAR(20) NOT NULL DEFAULT '',
                `table` VARCHAR(100) NOT NULL DEFAULT '',
                primary_key VARCHAR(100) NOT NULL DEFAULT '',
                `data` LONGTEXT NOT NULL,
                job_id VARCHAR(100) NOT NULL DEFAULT '',
                synced INT(2) NOT NULL DEFAULT '0',
                synced_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
                metadata LONGTEXT NOT NULL,
                PRIMARY KEY (ID)
                ) $collate;",
            );
        }
    }
?>