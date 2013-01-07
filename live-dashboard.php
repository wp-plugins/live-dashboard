<?php
/*
  Plugin Name: Live Dashboard
  Plugin URI: http://trenvo.com/wordpress-live-dashboard
  Description: Manage your website while you're browsing it.
  Version: 0.2
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
define ( 'LIVE_DASHBOARD_VERSION', '0.2' );

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

        protected $dont_change_home_url = false;

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
                load_plugin_textdomain ( 'live-dashboard', false, basename ( LIVE_DASHBOARD_DIR ) . '/languages/' );
                $live_dashboard = new WP_LiveDashboard;
            }

            return $live_dashboard;
        }

        /**
         * Separate loader for front-end actions
         *
         * @since 0.1.1
         */
        public static function &frontend_init() {
            if ( is_admin_bar_showing() && ! is_admin() ) {
                add_filter('admin_url', array ( 'WP_LiveDashboard', 'change_admin_link' ), 10, 2 );
            }
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

            if ( $this->settings->is_default() ) {
                wp_enqueue_script( 'live-dashboard-links', LIVE_DASHBOARD_INC_URL . 'js/live-dashboard-links.js', array ('jquery'), 0.1, true );
                wp_localize_script( 'live-dashboard-links', 'liveDashboardLinks', array(
                    "site_url"  => get_bloginfo('wpurl'),
                    "admin_url" => admin_url()
                ));
            }

            // The settings screen is the only business @ network admin
            if (is_network_admin() )
                return;

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

                $url = admin_url();
                if ( isset ( $_REQUEST['current-page'] ) && ! empty ( $_REQUEST['current-page'] ) ) {
                    $url = add_query_arg ( array ( "current-page" =>  $_REQUEST['current-page'] ), $url );
                }
                wp_redirect( $url );
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

            ?>
            <a href="http://wordpress.org/extend/plugins/live-dashboard/" target="_new"><img src="<?php echo LIVE_DASHBOARD_INC_URL . 'images/dashboard_logo.png'; ?>" style="float:left;margin-right:10px;width:84px;height:84px;"></a>
            <p><?php _e('Welcome to your WordPress dashboard. You have installed Live Dashboard, but not set it as your default dashboard. Using Live Dashboard you can conveniently access your WP admin while browsing your site.', 'live-dashboard'); ?></p>
            <div style="float:right">
                <a href="<?php echo $switch_url ?>">Try it first</a>
                <form method="post" style='display:inline'>
                    <?php wp_nonce_field ( 'live_dashboard_set_as_default', 'set_as_default' ); ?>

                    <?php if ( isset ( $_REQUEST['current-page'] ) && !empty( $_REQUEST['current-page'] ) ) : ?>
                        <input type="hidden" name="current-page" value="<?php echo $_REQUEST['current-page'] ?>">
                    <?php endif; ?>

                    <input type="submit" class="button-primary" value="Set as Default">
                </form>
            </div>
            <div class="clear"></div>
            <?php
        }

        /**
         * Changes all requests for admin_link (without param)
         * to include a request param for current-page
         *
         * @param str $url
         * @param str $path
         * @return str
         */
        public static function change_admin_link( $url, $path ) {
            if ( empty ( $path )
                    && empty( $GLOBALS['_wp_switched_stack'] )
                    && strlen ( $_SERVER['REQUEST_URI'] ) > 1
                )
                $url = add_query_arg ( array ( "current-page" => urlencode ( substr ( $_SERVER['REQUEST_URI'], 1 ) ) ),$url);
            return $url;
        }

    }
    add_action ( 'admin_init', array ( 'WP_LiveDashboard', 'init' ), 999 );
    add_action ( 'init', array ( 'WP_LiveDashboard', 'frontend_init') );
endif;