<?php
/*
Plugin Name: Quick menu for Live Admin
Plugin URI: http://trenvo.com
Description: Quick actions admin menu for WP
Author: Mike Martel
Version: 1.0
Author URI: http://trenvo.com/
*/

// Exit if accessed directly
if (!defined('ABSPATH'))
    exit;

// Version number
if ( defined  ('WP_QUICK_MENU_VERSION' ) )
    return;
define ( 'WP_QUICK_MENU_VERSION', '1.0' );

/**
 * PATHs and URLs
 *
 * @since 0.1
 */
define ( 'WP_QUICK_MENU_DIR', plugin_dir_path ( __FILE__ ) );
define ( 'WP_QUICK_MENU_URL', plugin_dir_url ( __FILE__ ) );
define ( 'WP_QUICK_MENU_INC_URL', WP_QUICK_MENU_URL . '_inc/' );


if (!class_exists('WP_QuickMenu')) :

    class WP_QuickMenu    {

        protected $menu_items = array();

        /**
         * Creates an instance of the WP_QuickMenu class
         *
         * @return WP_QuickMenu object
         * @since 0.1
         * @static
        */
        public static function &init() {
            static $instance = false;

            if (!$instance) {
                load_plugin_textdomain('quick-menu', false, basename ( WP_QUICK_MENU_DIR ) . '/languages/');
                $instance = new WP_QuickMenu;
            }

            return $instance;
        }

        /**
         * Constructor
         *
         * @since 0.1
         */
        public function __construct() {
            $this->menu_pages();
            add_action('admin_print_styles-index.php', array ( &$this, 'enqueue_style' ) );

            add_action('wp_dashboard_setup', array ( &$this, 'widgets' ), 99 );
        }

            /**
             * PHP4
             *
             * @since 0.1
             */
            public function WP_QuickMenu() {
                $this->__construct();
            }

        // Create the function use in the action hook
        public function widgets() {
                wp_add_dashboard_widget('quick_menu_content_widget', __("Quick Menu Content", 'quick-menu'), array ( &$this, 'content_widget' ) );
                wp_add_dashboard_widget('quick_menu_website_widget', __("Quick Menu Website", 'quick-menu'), array ( &$this, 'website_widget' ) );

                global $wp_meta_boxes;

                //$quick_menu_content_backup = array('quick_menu_content_widget' => $wp_meta_boxes['dashboard']['normal']['core']['quick_menu_content_widget']);
                //$quick_menu_website_backup = array('quick_menu_website_widget' => $wp_meta_boxes['dashboard']['normal']['core']['quick_menu_website_widget']);
                $quick_menu_boxes = array(
                    'quick_menu_content_widget' => $wp_meta_boxes['dashboard']['normal']['core']['quick_menu_content_widget'],
                    'quick_menu_website_widget' => $wp_meta_boxes['dashboard']['normal']['core']['quick_menu_website_widget']
                );
                unset($wp_meta_boxes['dashboard']['normal']['core']['quick_menu_website_widget']);
                unset($wp_meta_boxes['dashboard']['normal']['core']['quick_menu_content_widget']);

                // Merge the two arrays together so our widget is at the beginning
                $wp_meta_boxes['dashboard']['normal']['core'] = array_merge($quick_menu_boxes, $wp_meta_boxes['dashboard']['normal']['core']);
        }

        public function enqueue_style() {
            wp_enqueue_style( 'quick-menu', WP_QUICK_MENU_INC_URL . 'css/quick-menu.css' );
        }

        public function menu_pages() {
            global $menu;

            $menu_items = array();
            foreach ( $menu as $i => $item ) {
                if ( isset ( $item[2] ) ) {
                    $menu_items[] = $item[2];
                }
            }
            $this->menu_items = $menu_items;
        }

        public function menu_is_active ( $item ) {
            return in_array ( $item, $this->menu_items );
        }

        public function content_widget() {

            ?>

            <ul class="quick-menu">

                    <?php if ( $this->menu_is_active( 'edit.php?post_type=page' ) ) : ?>

                    <li class="left-gray"><a href="<?php echo admin_url ('edit.php?post_type=page'); ?>"><div id="icon-edit-pages" class="icon32">&nbsp;</div><?php _e('Pages'); ?></a></li>

                    <?php endif; ?>
                    <?php if ( $this->menu_is_active( 'edit.php' ) ) : ?>

                    <li class="right-gray"><a href="<?php echo admin_url ('edit.php'); ?>"><div id="icon-edit" class="icon32">&nbsp;</div><?php _e ('Posts'); ?></a></li>

                    <!--<li class="left-gray"><a href="<?php echo admin_url ('media-new.php'); ?>"><div id="icon-upload" class="icon32 add">&nbsp;</div> Upload Media</a></li>-->
                    <?php endif; ?>
                    <?php if ( $this->menu_is_active( 'upload.php' ) ) : ?>

                    <li class="left-gray"><a href="<?php echo admin_url ('upload.php'); ?>"><div id="icon-upload" class="icon32">&nbsp;</div> Media Library</a></li>

                    <?php endif;?>
                    <?php if ( $this->menu_is_active( 'edit-comments.php' ) ) : ?>

                        <li class="right-gray"><a href="<?php echo admin_url ('edit-comments.php'); ?>"><div id="icon-edit-comments" class="icon32">&nbsp;</div> Comments</a></li>

                    <?php endif; ?>

            </ul>

            <br class="clear" />

            <?php
        }

        public function website_widget() {
            $themes_url = ( defined( 'WP_LTP_VERSION' ) && WP_LTP_VERSION ) ? 'themes.php?live_themes=1' : 'themes.php';

            ?>

            <ul class="quick-menu">

                    <li class="left-gray"><a href="<?php echo admin_url ( $themes_url ); ?>"><div id="icon-change" class="icon32">&nbsp;</div><?_e('Change theme'); ?></a></li>
                    <li class="right-gray"><a href="<?php echo wp_customize_url() ?>"><div id="icon-customize" class="icon32">&nbsp;</div><?php _e('Customize'); ?></a></li>

                    <li class="left-gray"><a href="<?php echo admin_url ('widgets.php'); ?>"><div id="icon-themes" class="icon32">&nbsp;</div> Widgets</a></li>
                    <li class="right-gray"><a href="<?php echo admin_url ('nav-menus.php'); ?>"><div id="icon-themes" class="icon32">&nbsp;</div> Menus</a></li>

                    <?php if ( ! is_multisite() && current_user_can( 'install_plugins' ) ) : ?>

                    <li class="left-gray"><a href="<?php echo admin_url ('plugin-install.php'); ?>"><div id="icon-plugins" class="icon32 add">&nbsp;</div> Install Plugin</a></li>

                    <?php endif; ?>

                    <?php if ( current_user_can( 'activate_plugins' ) ) : ?>

                    <li class="<?php if ( is_multisite() || ! current_user_can( 'install_plugins' ) ) echo 'left-gray '; ?>right-gray"><a href="<?php echo admin_url ('plugins.php'); ?>"><div id="icon-plugins" class="icon32">&nbsp;</div> Plugins</a></li>

                    <?php endif; ?>

                    <li class="left-gray right-gray"><a href="<?php echo admin_url ('options-general.php'); ?>"><div id="icon-options-general" class="icon32">&nbsp;</div> Settings</a></li>
            </ul>

            <br class="clear" />
            <?php
        }
    }
    add_action( 'wp_dashboard_setup', array( 'WP_QuickMenu', 'init' ), 1);
endif;