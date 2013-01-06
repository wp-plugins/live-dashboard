<?php
// Exit if accessed directly
if ( !defined ( 'ABSPATH' ) )
    exit;

if ( ! class_exists ( 'WP_LiveDashboard_Template' ) ) :

    class WP_LiveDashboard_Template extends WP_LiveAdmin
    {

        public function __construct() {
            if ( isset ( $_REQUEST['current-page' ] ) && ! empty ( $_REQUEST['current-page' ] ) ) {
                $iframe_url = $_REQUEST['current-page'];
                if ( ! strpos ( $iframe_url, get_bloginfo('url') ) )
                    $iframe_url = get_bloginfo('wpurl') . '/' . $iframe_url;

                $this->iframe_url = $iframe_url;
            }

            $this->menu = true;
            $this->screen_options = true;
            $this->remember_sidebar_state = true;
            //$this->collapsed = true;

            $this->pointers = array(
                    array(
                        'id' => 'live_dash',
                        'screen' => 'dashboard',
                        'target' => '#show-dash',
                        'title' => __ ( 'Live Dashboard' ),
                        'content' => __( "Click the button below to show or hide your dashboard.", 'live-dashboard' ),
                        'position' => array(
                                'edge' => 'bottom',
                                'align' => 'left'
                            )
                        )
                    );

            $this->info_notice = sprintf( __( 'You are administring %s', 'live-admin' ), '<strong class="site-name">' . get_bloginfo('name') . '</strong>' );
            $quick_action = '';
            if ( post_type_exists( 'post' ) ) $quick_action .= '<li class="left-gray"><a href="' . admin_url ('post-new.php') . '"><div id="icon-edit" class="icon32"></div>New Post</a></li>';
            if ( post_type_exists( 'page' ) ) $quick_action .= '<li class="right-gray"><a href="' . admin_url ('post-new.php?post_type=page') . '"><div id="icon-edit-pages" class="icon32"></div>Add Page</a></li>';

            $this->info_content =
                        '<ul class="quick-actions">' . $quick_action .
                            //<li class="left-gray"><a href="' . admin_url ('post-new.php') . '"><div id="icon-edit" class="icon32"></div>New Post</a></li>
                            //<li class="right-gray"><a href="' . admin_url ('post-new.php?post_type=page') . '"><div id="icon-edit-pages" class="icon32"></div>Add Page</a></li>
                        '</ul>

                        <div>
                            <a href="' . $this->switch_url() . '" style="float:left">Switch interface</a>

                            <a href="' . admin_url ('edit.php') . '" class="edit-current-post button-primary" style="float:right">
                                Edit current post
                            </a>
                        </div>';

            $this->add_button ( $this->my_account_button(), 20 );

            $this->enqueue_styles_and_scripts();

            add_action('before_live_admin_preview', array ( &$this, 'do_welcome_panel' ) );
            add_action('live_admin_after_collapse_sidebar', array ( &$this, 'show_dash_button' ) );

        }

            public function wp_livedashboard_template() {
                $this->_construct();
            }

        protected function enqueue_styles_and_scripts() {
            wp_enqueue_style( 'live-dashboard', LIVE_DASHBOARD_INC_URL .'css/live-dashboard.css', array ("customize-controls"), "0.1" );
            wp_enqueue_script( 'live-dashboard', LIVE_DASHBOARD_INC_URL .'js/live-dashboard.js', array ('jquery') );
        }


        public function do_start() {
            global $title, $parent_file, $admin_title, $handle;

            // Globals
            $title = __('Dashboard');
            $parent_file = 'index.php';
            $admin_title = sprintf( __( '%1$s &lsaquo; %2$s &#8212; WordPress' ), "Live Admin", get_bloginfo( 'name' ) );
            $handle = 'index.php';

            // Setup dashboard
            require_once(ABSPATH . 'wp-admin/includes/dashboard.php');
            set_current_screen('dashboard');
            wp_dashboard_setup();

            wp_enqueue_script( 'dashboard' );


            if ( current_user_can( 'install_plugins' ) )
                wp_enqueue_script( 'plugin-install' );
            if ( current_user_can( 'upload_files' ) )
                wp_enqueue_script( 'media-upload' );

            add_thickbox();

            if ( wp_is_mobile() )
                wp_enqueue_script( 'jquery-touch-punch' );

        }

        public function do_footer_actions() {
            echo $this->logout_button();
        }

        public function do_controls() {
            ?>
            <ul id="dashboard-widgets">
                <?php
                /**
                 * @todo Redirect QuickPress to (Live) Post Editor
                 */
                $this->do_meta_boxes( 'dashboard', 'normal', '' );
                $this->do_meta_boxes( 'dashboard', 'side', '' );
                $this->do_meta_boxes( 'dashboard', 'column-3', '' );
                $this->do_meta_boxes( 'dashboard', 'column-4', '' );
                ?>
			</ul>
            <?php
        }

        public function show_dash_button() {
            ?>
                <span class="button hide-when-expanded" id="show-dash">Show dashboard</span>
            <?php
        }



       /**
         * Meta-Box template function
         *
         * @since 2.5.0
         *
         * @param string|object $screen Screen identifier
         * @param string $context box context
         * @param mixed $object gets passed to the box callback function as first parameter
         * @return int number of meta_boxes
         */
        public function do_meta_boxes( $screen, $context, $object ) {
            global $wp_meta_boxes, $live_admin;
            static $already_sorted = false;

            if ( empty( $screen ) )
                $screen = get_current_screen();
            elseif ( is_string( $screen ) )
                $screen = convert_to_screen( $screen );

            $page = $screen->id;

            $hidden = get_hidden_meta_boxes( $screen );

            //printf('<div id="%s-sortables" class="meta-box-sortables">', htmlspecialchars($context));
            printf('<div id="%s-sortables" class="meta-box-sortables-live">', htmlspecialchars($context));

            $i = 0;
            do {
                // Grab the ones the user has manually sorted. Pull them out of their previous context/priority and into the one the user chose
                if ( !$already_sorted && $sorted = get_user_option( "meta-box-order_$page" ) ) {
                    foreach ( $sorted as $box_context => $ids ) {
                        foreach ( explode(',', $ids ) as $id ) {
                            if ( $id && 'dashboard_browser_nag' !== $id )
                                add_meta_box( $id, null, null, $screen, $box_context, 'sorted' );
                        }
                    }
                }
                $already_sorted = true;

                if ( !isset($wp_meta_boxes) || !isset($wp_meta_boxes[$page]) || !isset($wp_meta_boxes[$page][$context]) )
                    break;

                foreach ( array('high', 'sorted', 'core', 'default', 'low') as $priority ) {
                    if ( isset($wp_meta_boxes[$page][$context][$priority]) ) {
                        foreach ( (array) $wp_meta_boxes[$page][$context][$priority] as $box ) {
                            if ( false == $box || ! $box['title'] )
                                continue;
                            $i++;
                            //$style = '';
                            $hidden_class = in_array($box['id'], $hidden) ? ' hide-if-js' : '';

                            //$open_class = ( $box['id'] == 'submitdiv' ) ? ' open' : '';
                            //echo "<li class='control-section customize-section'>";
                            //echo '<li id="' . $box['id'] . '" class="control-section customize-section' . $open_class . postbox_classes($box['id'], $page) . $hidden_class . '" ' . '>' . "\n";
                            echo '<li id="' . $box['id'] . '" class="control-section customize-section ' . $live_admin->postbox_class . postbox_classes($box['id'], $page) . $hidden_class . '" ' . '>' . "\n";
                            //if ( 'dashboard_browser_nag' != $box['id'] )
                            //	echo '<div class="handlediv" title="' . esc_attr__('Click to toggle') . '"><br /></div>';
                            echo "<h3 class='customize-section-title'><span>{$box['title']}</span></h3>\n";
                            echo "<ul class='customize-section-content'>";
                                echo "<li>";
                                    echo '<div class="inside">' . "\n";
                                    call_user_func($box['callback'], $object, $box);
                                    echo "</div>\n";
                                echo "</li>";
                            echo "</ul>";
                            //echo "</div>\n";
                            echo "</li>";
                        }
                    }
                }
            } while(0);

            echo "</div>";

            return $i;

        }

        public function do_welcome_panel() {
            if ( current_user_can( 'edit_theme_options' ) ) :

                $classes = 'welcome-panel';

                $option = get_user_meta( get_current_user_id(), 'show_welcome_panel', true );
                // 0 = hide, 1 = toggled to show or single site creator, 2 = multisite site owner
                $hide = 0 == $option || ( 2 == $option && wp_get_current_user()->user_email != get_option( 'admin_email' ) );
                if ( $hide )
                    $classes .= ' hidden'; ?>



                <div id='welcome-panel' class="<?php echo esc_attr( $classes ); ?>">

                    <?php wp_nonce_field( 'welcome-panel-nonce', 'welcomepanelnonce', false ); ?>
                    <a class="welcome-panel-close" href="<?php echo esc_url( admin_url( '?welcome=0' ) ); ?>"><?php _e( 'Dismiss' ); ?></a>
                    <?php do_action( 'welcome_panel' ); ?>

                </div><?php

            endif;
        }
    }
    live_admin_register_extension ( 'WP_LiveDashboard_Template' );
endif;