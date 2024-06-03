<?php
/**
 * Plugin Name: Give - Paynimo
 * Description: Process online donations via the Paynimo payment gateway.
 * Text Domain: give-paynimo
 * Version: 1.0.0
 * Author: Mithilesh More, Aarti Navalu, Amar Sawant, Bhavin Shah
 */


if ( ! class_exists( 'Give_Paynimo_Gateway' ) ) {
    /**
     * Class Give_Paynimo_Gateway
     *
     * @since 1.0.0
     */
    final class Give_Paynimo_Gateway {

        /**
         * @since  1.0
         * @access static
         * @var Give_Paynimo_Gateway $instance
         */
        static private $instance;

        /**
         * Notices (array)
         *
         * @since 1.0.0
         *
         * @var array
         */
        public $notices = array();

        /**
         * Get instance
         *
         * @since  1.0.0
         * @access static
         * @return Give_Paynimo_Gateway|static
         */
        static function get_instance() {
            if ( is_null( self::$instance ) ) {
                self::$instance = new self();
                self::$instance->setup();
            }

            return self::$instance;
        }

        /**
         * Setup Give Paynimo.
         *
         * @since  1.0.0
         * @access private
         */
        private function setup() {

            // Setup constants.
            $this->setup_constants();

            // Give init hook.
            add_action( 'give_init', array( $this, 'init' ), 10 );
            add_action( 'admin_init', array( $this, 'check_environment' ), 999 );
            add_action( 'admin_notices', array( $this, 'admin_notices' ), 15 );
        }

        /**
         * Setup constants.
         *
         * @since  1.0.0
         * @access public
         * @return Give_Paynimo_Gateway
         */
        public function setup_constants() {
            // Global Params.
            define( 'GIVE_PAYNIMO_VERSION', '1.0.0' );
            define( 'GIVE_PAYNIMO_MIN_GIVE_VER', '2.4.5' );
            define( 'GIVE_PAYNIMO_BASENAME', plugin_basename( __FILE__ ) );
            define( 'GIVE_PAYNIMO_URL', plugins_url( '/', __FILE__ ) );
            define( 'GIVE_PAYNIMO_DIR', plugin_dir_path( __FILE__ ) );

            return self::$instance;
        }

        /**
         * Load files.
         *
         * @since  1.0.0
         * @access public
         * @return Give_Paynimo_Gateway
         */
        public function init() {
            if ( ! $this->get_environment_warning() ) {
                return;
            }

            $this->activation_banner();

            require_once GIVE_PAYNIMO_DIR . 'includes/admin/plugin-activation.php';

            // Load helper functions.
            require_once GIVE_PAYNIMO_DIR . 'includes/functions.php';

            // Load plugin settings.
            require_once GIVE_PAYNIMO_DIR . 'includes/admin/admin-settings.php';

            // Process payments.
            require_once GIVE_PAYNIMO_DIR . 'includes/payment-processing.php';

            require_once GIVE_PAYNIMO_DIR . 'includes/lib/class-give-paynimo-api.php';

            require_once GIVE_PAYNIMO_DIR . 'includes/actions.php';

            require_once GIVE_PAYNIMO_DIR . 'includes/TransactionRequestBean.php';

            require_once GIVE_PAYNIMO_DIR . 'includes/TransactionResponseBean.php';

            return self::$instance;
        }

        /**
         * Load scripts.
         *
         * @since  1.0.0
         * @access public
         */
        function enqueue_scripts( $hook ) {
            if (
                'gateways' === give_get_current_setting_tab()
                && 'paynimo' === give_get_current_setting_section()
            ) {
                wp_enqueue_script( 'paynimo-admin-settings' );
            }
        }

        /**
         * Check plugin environment.
         *
         * @since  1.0.0
         * @access public
         *
         * @return bool
         */
        public function check_environment() {
            // Flag to check whether plugin file is loaded or not.
            $is_working = true;

            // Load plugin helper functions.
            if ( ! function_exists( 'is_plugin_active' ) ) {
                require_once ABSPATH . '/wp-admin/includes/plugin.php';
            }

            /*
             Check to see if Give is activated, if it isn't deactivate and show a banner. */
            // Check for if give plugin activate or not.
            $is_give_active = defined( 'GIVE_PLUGIN_BASENAME' ) ? is_plugin_active( GIVE_PLUGIN_BASENAME ) : false;

            if ( empty( $is_give_active ) ) {
                // Show admin notice.
                $this->add_admin_notice( 'prompt_give_activate', 'error', sprintf( __( '<strong>Activation Error:</strong> You must have the <a href="%s" target="_blank">Give</a> plugin installed and activated for Give - Paynimo to activate.', 'give-paynimo' ), 'https://givewp.com' ) );
                $is_working = false;
            }

            return $is_working;
        }

        /**
         * Check plugin for Give environment.
         *
         * @since  1.0.0
         * @access public
         *
         * @return bool
         */
        public function get_environment_warning() {
            // Flag to check whether plugin file is loaded or not.
            $is_working = true;

            // Verify dependency cases.
            if (
                defined( 'GIVE_VERSION' )
                && version_compare( GIVE_VERSION, GIVE_PAYNIMO_MIN_GIVE_VER, '<' )
            ) {

                /*
                 Min. Give. plugin version. */
                // Show admin notice.
                $this->add_admin_notice( 'prompt_give_incompatible', 'error', sprintf( __( '<strong>Activation Error:</strong> You must have the <a href="%1$s" target="_blank">Give</a> core version %2$s for the Give - Paynimo add-on to activate.', 'give-paynimo' ), 'https://givewp.com', GIVE_PAYNIMO_MIN_GIVE_VER ) );

                $is_working = false;
            }

            return $is_working;
        }

        /**
         * Allow this class and other classes to add notices.
         *
         * @since 1.0.0
         *
         * @param $slug
         * @param $class
         * @param $message
         */
        public function add_admin_notice( $slug, $class, $message ) {
            $this->notices[ $slug ] = array(
                'class'   => $class,
                'message' => $message,
            );
        }

        /**
         * Display admin notices.
         *
         * @since 1.0.0
         */
        public function admin_notices() {

            $allowed_tags = array(
                'a'      => array(
                    'href'  => array(),
                    'title' => array(),
                    'class' => array(),
                    'id'    => array(),
                ),
                'br'     => array(),
                'em'     => array(),
                'span'   => array(
                    'class' => array(),
                ),
                'strong' => array(),
            );

            foreach ( (array) $this->notices as $notice_key => $notice ) {
                echo "<div class='" . esc_attr( $notice['class'] ) . "'><p>";
                echo wp_kses( $notice['message'], $allowed_tags );
                echo '</p></div>';
            }

        }

        /**
         * Show activation banner for this add-on.
         *
         * @since 1.0.0
         */
        public function activation_banner() {

            // Check for activation banner inclusion.
            if (
                ! class_exists( 'Give_Addon_Activation_Banner' )
                && file_exists( GIVE_PLUGIN_DIR . 'includes/admin/class-addon-activation-banner.php' )
            ) {
                include GIVE_PLUGIN_DIR . 'includes/admin/class-addon-activation-banner.php';
            }

            // Initialize activation welcome banner.
            if ( class_exists( 'Give_Addon_Activation_Banner' ) ) {

                // Only runs on admin.
                $args = array(
                    'file'              => __FILE__,
                    'name'              => esc_html__( 'Paynimo Gateway', 'give-paynimo' ),
                    'version'           => GIVE_PAYNIMO_VERSION,
                    'settings_url'      => admin_url( 'edit.php?post_type=give_forms&page=give-settings&tab=gateways&section=paynimo' ),
                    'support_url'       => 'https://givewp.com/support/',
                    'testing'           => false, // Never leave true.
                );
                new Give_Addon_Activation_Banner( $args );
            }
        }
    }

    function Give_Paynimo_Gateway() {
        return Give_Paynimo_Gateway::get_instance();
    }

    /**
     * Returns class object instance.
     *
     * @since 1.0.0
     *
     * @return Give_Paynimo_Gateway bool|object
     */
    Give_Paynimo_Gateway();
}
