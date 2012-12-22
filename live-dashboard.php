<?php
/*
  Plugin Name: Live Dashboard
  Plugin URI: http://trenvo.com/wordpress-live-dashboard
  Description: Manage your website while you're browsing it.
  Version: 0.1
  Author: Mike Martel
  Author URI: http://trenvo.com
 */

// Exit if accessed directly
if ( !defined ( 'ABSPATH' ) )
    exit;

/**
 * Version number
 *
 * @since 0.1
 */
define ( 'LIVE_DASHBOARD_VERSION', '0.1' );

/**
 * PATHs and URLs
 *
 * @since 0.1
 */
define ( 'LIVE_DASHBOARD_DIR', plugin_dir_path ( __FILE__ ) );
define ( 'LIVE_DASHBOARD_URL', plugin_dir_url ( __FILE__ ) );
define ( 'LIVE_DASHBOARD_INC_URL', LIVE_DASHBOARD_URL . '_inc/' );

if ( ! class_exists ( 'WP_LiveDashboard' ) ) :
    class WP_LiveDashboard
    {

        /**
         * Creates an instance of the WP_LiveAdmin class
         *
         * @return WP_LiveDashboard object
         * @since 0.1
         * @static
        */
        public static function &init() {
            global $live_dashboard;

            if ( ! $live_dashboard ) {
                load_plugin_textdomain ( 'live-dashboard', false, LIVE_DASHBOARD_DIR . '/languages/' );
                $live_dashboard = new WP_LiveDashboard;
            }

            return $live_dashboard;
        }

        /**
         * Constructor
         *
         * @since 0.1
         */
        public function __construct() {
            if ( !is_user_logged_in () )
                return;

            /**
             * Requires and includes
             */
            require_once ( LIVE_DASHBOARD_DIR . 'lib/live-admin/live-admin.php' );
            $this->settings = new WP_LiveAdmin_Settings( 'dashboard', __('Live Dashboard', 'live-dashboard'), __('Combine browsing and administring your website with your full dashboard in a sidebar to your website','live-dashboard'), 'false', 'index.php' );

            // The settings screen is the only business @ network admin
            if (is_network_admin() )
                return

            $this->maybe_set_as_default();

            if ( $this->settings->is_active() ) {
                require ( LIVE_DASHBOARD_DIR . 'live-dashboard-template.php' );
            } elseif ( ! $this->settings->is_default() )
                add_action ( 'wp_dashboard_setup', array ( &$this, 'add_dashboard_widget' ) );
        }

            /**
             * PHP4
             *
             * @since 0.1
                 */
            public function WP_LiveAdmin() {
                $this->__construct ();
            }

        protected function maybe_set_as_default() {
            if ( isset ( $_POST['set_as_default'] ) ) {
                if ( ! wp_verify_nonce( $_POST['set_as_default'], 'live_dashboard_set_as_default' ) )
                    wp_die();

                $this->settings->save_user_setting( 'dashboard', 'true');
                wp_redirect( admin_url() );
            }
        }

        public function add_dashboard_widget() {
            wp_add_dashboard_widget( 'dashboard_live_dash', __( 'Live Dashboard', 'live-dashboard' ), array ( &$this, 'dashboard_widget' ) );

            // Globalize the metaboxes array, this holds all the widgets for wp-admin

            global $wp_meta_boxes;

            // Get the regular dashboard widgets array
            // (which has our new widget already but at the end)

            $normal_dashboard = $wp_meta_boxes['dashboard']['side']['core'];

            // Backup and delete our new dashbaord widget from the end of the array

            $dashboard_live_dash_backup = array('dashboard_live_dash' => $wp_meta_boxes['dashboard']['normal']['core']['dashboard_live_dash']);
            unset($normal_dashboard['dashboard_live_dash']);

            // Merge the two arrays together so our widget is at the beginning

            $sorted_dashboard = array_merge($dashboard_live_dash_backup, $normal_dashboard);

            // Save the sorted array back into the original metaboxes

            $wp_meta_boxes['dashboard']['side']['core'] = $sorted_dashboard;
        }

        public function dashboard_widget() {
            $switch_url = $this->settings->switch_url();
            $set_as_default_url = esc_html( add_query_arg( 'set_as_default', wp_create_nonce( 'live_dashboard_set_as_default' ) ) );

            ?>
            <p>Welcome to your WordPress dashboard. You have installed Live Dashboard, but not set it as your default dashboard. Using Live Dashboard you can conveniently access your WP admin while browsing your site.</p>
            <div style="float:right">
                <a href="<?php echo $switch_url ?>">Try it first</a>
                <form method="post" style='display:inline'>
                    <?php wp_nonce_field ( 'live_dashboard_set_as_default', 'set_as_default' ); ?>
                    <input type="submit" class="button-primary" value="Set as Default">
                </form>
            </div>
            <div class="clear"></div>
            <?php
        }

    }

    add_action ( 'admin_init', array ( 'WP_LiveDashboard', 'init' ), 999 );
endif;