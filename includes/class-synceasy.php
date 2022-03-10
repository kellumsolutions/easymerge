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
            $this->define( 'SEZ_LOG_LEVEL', 'DEBUG' );
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

            include_once SEZ_ABSPATH . '/includes/class-sez-sync.php';
            include_once SEZ_ABSPATH . '/includes/class-sez-sync-functions.php';

            include_once SEZ_ABSPATH . '/includes/expressions/class-sez-rule-expression.php';
            include_once SEZ_ABSPATH . '/includes/expressions/class-sez-rule-equality-expression.php';
            include_once SEZ_ABSPATH . '/includes/expressions/class-sez-rule-inequality-expression.php';
            include_once SEZ_ABSPATH . '/includes/expressions/class-sez-rule-like-expression.php';
        }

        function init_hooks(){
            register_activation_hook( SEZ_PLUGIN_FILE, array( 'SEZ_Install', 'install' ) );

            add_action( "sez_after_change_execute", "sez_save_mapping", 20, 5 );
            add_filter( "sez_before_change_execute", "sez_adjust_primary_key_for_updates_deletes", 10, 4 );

            add_action( "init", array( $this, "on_init" ) );
        }


        function on_init(){
            $this->sync = new SEZ_Sync();
            $this->settings = SEZ_Settings::instance();
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