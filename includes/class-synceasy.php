<?php

    final class SyncEasy {
        
        protected static $_instance = null;

        static function instance(){
            if ( is_null( self::$_instance ) ) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }

        public function __construct() {
            $this->define_constants();
            $this->includes();
            $this->init_hooks();
        }

        function define_constants(){
            $this->define( 'SEZ_ABSPATH', dirname( SEZ_PLUGIN_FILE ) . '/' );
            $this->define( 'SEZ_PLUGIN_URL', trailingslashit( plugin_dir_url( SEZ_PLUGIN_FILE ) ) );
            $this->define( 'SEZ_ASSETS_URL', SEZ_PLUGIN_URL . '/assets/' );
            $this->define( 'SEZ_TMP_DIR', SEZ_ABSPATH . 'tmp/' );
            $this->define( 'SEZ_TMP_URL', SEZ_PLUGIN_URL . '/tmp/' );
            // $this->define( 'SEZ_LOG_LEVEL', 'DEBUG' );
            $this->define( 
                'SEZ_LOG_LEVELS', 
                array(
                    'ERROR' => 1,
                    'WARNING' => 2,
                    'INFO' => 3,
                    'DEBUG' => 4,
                    'TRACE' => 5
                )
            );
        }

        function includes(){
            //include_once SEZ_ABSPATH . '/vendor/autoload.php';

            include_once SEZ_ABSPATH . '/includes/class-sez-install.php';
            include_once SEZ_ABSPATH . '/includes/sez-functions.php';
            include_once SEZ_ABSPATH . '/includes/class-sez-admin-page.php';
            include_once SEZ_ABSPATH . '/includes/class-sez-api-response.php';
            include_once SEZ_ABSPATH . '/includes/class-sez-remote-api.php';
            include_once SEZ_ABSPATH . '/includes/class-sez-api-controller.php';
            include_once SEZ_ABSPATH . '/includes/class-sez-map.php';
            include_once SEZ_ABSPATH . '/includes/class-sez-rules.php';
            include_once SEZ_ABSPATH . '/includes/class-sez-change.php';
            include_once SEZ_ABSPATH . '/includes/class-sez-settings.php';
            include_once SEZ_ABSPATH . '/includes/class-sez-merge-log.php';

            include_once SEZ_ABSPATH . '/includes/class-sez-sync.php';
            include_once SEZ_ABSPATH . '/includes/class-sez-sync-functions.php';
            include_once SEZ_ABSPATH . '/includes/class-sez-advanced-tools.php';

            include_once SEZ_ABSPATH . '/includes/expressions/class-sez-rule-expression.php';
            include_once SEZ_ABSPATH . '/includes/expressions/class-sez-rule-equality-expression.php';
            include_once SEZ_ABSPATH . '/includes/expressions/class-sez-rule-inequality-expression.php';
            include_once SEZ_ABSPATH . '/includes/expressions/class-sez-rule-like-expression.php';
        }

        function init_hooks(){
            global $wpdb;

            register_activation_hook( SEZ_PLUGIN_FILE, array( 'SEZ_Install', 'install' ) );

            add_action( "init", array( $this, "on_init" ) );
            add_action( "admin_notices", array( $this, "uploads_dir_permissions_notice" ) );

            add_action( "sez_after_change_execute", "sez_save_mapping", 20, 5 );
            add_filter( "sez_before_change_execute", "sez_adjust_primary_key_for_updates_deletes", 10, 4 );

            if ( SEZ_Rules::is_rule_enabled( "include_all_users" ) ){
                add_action( 'sez_perform_adjustments_' . $wpdb->usermeta, "sez_sync_usermeta", 20, 3 );
            }

            // Fixes comment sync issues.
            // LIFE-30, LIFE-31, LIFE-32
            if ( SEZ_Rules::is_rule_enabled( "include_all_comments" ) ){
                add_action( 'sez_perform_adjustments_' . $wpdb->comments, "sez_sync_comments", 20, 3 );
            }
        }


        function on_init(){
            $this->sync = new SEZ_Sync();
            $this->settings = SEZ_Settings::instance();
        }


        function uploads_dir_permissions_notice(){
            if ( function_exists( 'get_current_screen' ) ) {
                $screen = get_current_screen();

                if ( is_object( $screen ) && property_exists( $screen, 'id' ) && $screen->id === "tools_page_" . SyncEasy_Admin_Page::$handle ){
                    if ( false === wp_is_writable( wp_upload_dir()[ "basedir" ] ) ){
                    ?>
                        <div class="notice notice-error">
                            <p>Uploads directory does not have write permissions. EasySync requires the uploads directory to be writeable to function correctly.</p>
                        </div>
                    <?php
                    }
                }
            }
        }


        /**
         * What type of request is this?
         *
         * @param  string $type admin, ajax, cron or frontend.
         * @return bool
         */
        private function is_request( $type ) {
            switch ( $type ) {
                case 'admin':
                    return is_admin();
                case 'ajax':
                    return defined( 'DOING_AJAX' );
                case 'cron':
                    return defined( 'DOING_CRON' );
                case 'frontend':
                    return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' ) && ! $this->is_rest_api_request();
            }
        }


        /**
         * Define constant if not already set.
         *
         * @param string      $name  Constant name.
         * @param string|bool $value Constant value.
         */
        private function define( $name, $value ) {
            if ( ! defined( $name ) ) {
                define( $name, $value );
            }
        }
    }


?>