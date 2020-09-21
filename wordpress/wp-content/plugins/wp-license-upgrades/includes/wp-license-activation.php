<?php

if ( ! class_exists( 'WPLU_Plugin_Activation' ) ) {
	class WPLU_Plugin_Activation {
		const WPLICENSE_VERSION = '2.6.1';
		const WP_REPO_REGEX = '|^http[s]?://wordpress\.org/(?:extend/)?plugins/|';
		const IS_URL_REGEX = '|^http[s]?://|';
		public static $instance;
		public $plugins = array();
		protected $sort_order = array();
		protected $has_forced_activation = false;
		protected $has_forced_deactivation = false;
		public $id = 'wplicense';
		protected $menu = 'wpl-install';
		public $parent_slug = 'themes.php';
		public $capability = 'edit_theme_options';
		public $default_path = '';
		public $has_notices = true;
		public $dismissable = true;
		public $dismiss_msg = '';
		public $is_automatic = false;
		public $message = '';
		public $strings = array();
		public $wp_version;
		public $page_hook;
		public function __construct() {
			$this->wp_version = $GLOBALS['wp_version'];
			do_action_ref_array( 'wplicense_init', array( $this ) );
			add_action( 'init', array( $this, 'load_textdomain' ), 5 );
			add_filter( 'load_textdomain_mofile', array( $this, 'overload_textdomain_mofile' ), 10, 2 );
			add_action( 'init', array( $this, 'init' ) );
		}
		public function __set( $name, $value ) {
			return;
		}
		public function __get( $name ) {
			return $this->{$name};
		}
		public function init() {
			if ( true !== apply_filters( 'wplicense_load', ( is_admin() && ! defined( 'DOING_AJAX' ) ) ) ) {
				return;
			}
			$this->strings = array(
				'page_title'                      => __( 'Install Required Plugins', 'wplicense' ),
				'menu_title'                      => __( 'WPLicense Plugins', 'wplicense' ),
				'installing'                      => __( 'Installing Plugin: %s', 'wplicense' ),
				'updating'                        => __( 'Updating Plugin: %s', 'wplicense' ),
				'oops'                            => __( 'Something went wrong with the plugin API.', 'wplicense' ),
				'notice_can_install_required'     => _n_noop(
					'This theme requires the following plugin: %1$s.',
					'This theme requires the following plugins: %1$s.',
					'wplicense'
				),
				'notice_can_install_recommended'  => _n_noop(
					'This theme recommends the following plugin: %1$s.',
					'This theme recommends the following plugins: %1$s.',
					'wplicense'
				),
				'notice_ask_to_update'            => _n_noop(
					'The following plugin needs to be updated to its latest version to ensure maximum compatibility with this theme: %1$s.',
					'The following plugins need to be updated to their latest version to ensure maximum compatibility with this theme: %1$s.',
					'wplicense'
				),
				'notice_ask_to_update_maybe'      => _n_noop(
					'There is an update available for: %1$s.',
					'There are updates available for the following plugins: %1$s.',
					'wplicense'
				),
				'notice_can_activate_required'    => _n_noop(
					'The following required plugin is currently inactive: %1$s.',
					'The following required plugins are currently inactive: %1$s.',
					'wplicense'
				),
				'notice_can_activate_recommended' => _n_noop(
					'The following recommended plugin is currently inactive: %1$s.',
					'The following recommended plugins are currently inactive: %1$s.',
					'wplicense'
				),
				'install_link'                    => _n_noop(
					'Begin installing plugin',
					'Begin installing plugins',
					'wplicense'
				),
				'update_link'                     => _n_noop(
					'Begin updating plugin',
					'Begin updating plugins',
					'wplicense'
				),
				'activate_link'                   => _n_noop(
					'Begin activating plugin',
					'Begin activating plugins',
					'wplicense'
				),
				'return'                          => __( 'Return to Required Plugins Installer', 'wplicense' ),
				'dashboard'                       => __( 'Return to the Dashboard', 'wplicense' ),
				'plugin_activated'                => __( 'Plugin activated successfully.', 'wplicense' ),
				'activated_successfully'          => __( 'The following plugin was activated successfully:', 'wplicense' ),
				'plugin_already_active'           => __( 'No action taken. Plugin %1$s was already active.', 'wplicense' ),
				'plugin_needs_higher_version'     => __( 'Plugin not activated. A higher version of %s is needed for this theme. Please update the plugin.', 'wplicense' ),
				'complete'                        => __( 'All plugins installed and activated successfully. %1$s', 'wplicense' ),
				'dismiss'                         => __( 'Dismiss this notice', 'wplicense' ),
				'notice_cannot_install_activate'  => __( 'There are one or more required or recommended plugins to install, update or activate.', 'wplicense' ),
				'contact_admin'                   => __( 'Please contact the administrator of this site for help.', 'wplicense' ),
			);
			do_action( 'wplicense_register' );
			if ( empty( $this->plugins ) || ! is_array( $this->plugins ) ) {
				return;
			}
			if ( true !== $this->is_wplicense_complete() ) {
				array_multisort( $this->sort_order, SORT_ASC, $this->plugins );
				add_action( 'admin_menu', array( $this, 'admin_menu' ) );
				add_action( 'admin_head', array( $this, 'dismiss' ) );
				add_filter( 'install_plugin_complete_actions', array( $this, 'actions' ) );
				add_filter( 'update_plugin_complete_actions', array( $this, 'actions' ) );
				if ( $this->has_notices ) {
					add_action( 'admin_notices', array( $this, 'notices' ) );
					add_action( 'admin_init', array( $this, 'admin_init' ), 1 );
					add_action( 'admin_enqueue_scripts', array( $this, 'thickbox' ) );
				}
			}
			add_action( 'load-plugins.php', array( $this, 'add_plugin_action_link_filters' ), 1 );
			add_action( 'switch_theme', array( $this, 'flush_plugins_cache' ) );
			if ( $this->has_notices ) {
				add_action( 'switch_theme', array( $this, 'update_dismiss' ) );
			}
			if ( true === $this->has_forced_activation ) {
				add_action( 'admin_init', array( $this, 'force_activation' ) );
			}
			if ( true === $this->has_forced_deactivation ) {
				add_action( 'switch_theme', array( $this, 'force_deactivation' ) );
			}
		}

		public function load_textdomain() {
			if ( is_textdomain_loaded( 'wplicense' ) ) {
				return;
			}
			if ( false !== strpos( __FILE__, WP_PLUGIN_DIR ) || false !== strpos( __FILE__, WPMU_PLUGIN_DIR ) ) {
				add_action( 'load_textdomain_mofile', array( $this, 'correct_plugin_mofile' ), 10, 2 );
				load_theme_textdomain( 'wplicense', dirname( __FILE__ ) . '/languages' );
				remove_action( 'load_textdomain_mofile', array( $this, 'correct_plugin_mofile' ), 10 );
			} else {
				load_theme_textdomain( 'wplicense', dirname( __FILE__ ) . '/languages' );
			}
		}

		public function correct_plugin_mofile( $mofile, $domain ) {
			if ( 'wplicense' !== $domain ) {
				return $mofile;
			}
			return preg_replace( '`/([a-z]{2}_[A-Z]{2}.mo)$`', '/wplicense-$1', $mofile );
		}

		public function overload_textdomain_mofile( $mofile, $domain ) {
			if ( 'wplicense' !== $domain || false === strpos( $mofile, WP_LANG_DIR ) || @is_readable( $mofile ) ) {
				return $mofile;
			}
			if ( false !== strpos( $mofile, '/themes/' ) ) {
				return str_replace( '/themes/', '/plugins/', $mofile );
			} elseif ( false !== strpos( $mofile, '/plugins/' ) ) {
				return str_replace( '/plugins/', '/themes/', $mofile );
			} else {
				return $mofile;
			}
		}

		public function add_plugin_action_link_filters() {
			foreach ( $this->plugins as $slug => $plugin ) {
				if ( false === $this->can_plugin_activate( $slug ) ) {
//					add_filter( 'plugin_action_links_' . $plugin['file_path'], array( $this, 'filter_plugin_action_links_activate' ), 20 );
				}
				if ( true === $plugin['force_activation'] ) {
					add_filter( 'plugin_action_links_' . $plugin['file_path'], array( $this, 'filter_plugin_action_links_deactivate' ), 20 );
				}
				if ( false !== $this->does_plugin_require_update( $slug ) ) {
					add_filter( 'plugin_action_links_' . $plugin['file_path'], array( $this, 'filter_plugin_action_links_update' ), 20 );
				}
			}
		}

		public function filter_plugin_action_links_activate( $actions ) {
			unset( $actions['activate'] );
			return $actions;
		}

		public function filter_plugin_action_links_deactivate( $actions ) {
			unset( $actions['deactivate'] );
			return $actions;
		}

		public function filter_plugin_action_links_update( $actions ) {
			$actions['update'] = sprintf(
				'<a style="color: #39b54a; font-weight: 700;" href="%1$s" title="%2$s" class="edit">%3$s</a>',
//				esc_url( $this->get_wplicense_status_url( 'wpl-updates' ) ),//EDIT
				admin_url( 'admin.php?page=wpl-install&plugin_status=installed' ),//EDIT
				esc_attr__( 'New version available.', 'wplicense' ),
				esc_html__( 'Update', 'wplicense' )
			);
			return $actions;
		}

		public function admin_init() {
			if ( ! $this->is_wplicense_page() ) {
				return;
			}
			if ( isset( $_REQUEST['tab'] ) && 'plugin-information' === $_REQUEST['tab'] ) {
				require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
				wp_enqueue_style( 'plugin-install' );
				global $tab, $body_id;
				$body_id = 'plugin-information';
				$tab     = 'plugin-information';
				install_plugin_information();
				exit;
			}
		}

		public function thickbox() {
			if ( ! get_user_meta( get_current_user_id(), 'wplicense_dismissed_notice_' . $this->id, true ) ) {
				add_thickbox();
			}
		}

		public function admin_menu() {
			if ( ! current_user_can( 'install_plugins' ) ) {
				return;
			}
			$args = apply_filters(
				'wplicense_admin_menu_args',
				array(
					'parent_slug' => $this->parent_slug,                     
					'page_title'  => $this->strings['page_title'],           
					'menu_title'  => $this->strings['menu_title'],           
					'capability'  => $this->capability,                     
					'menu_slug'   => $this->menu,                           
					'function'    => array( $this, 'install_plugins_page' ), 
				)
			);
			$this->add_admin_menu( $args );
		}

		protected function add_admin_menu( array $args ) {
			if ( has_filter( 'wplicense_admin_menu_use_add_theme_page' ) ) {
				_deprecated_function( 'The "wplicense_admin_menu_use_add_theme_page" filter', '2.5.0', esc_html__( 'Set the parent_slug config variable instead.', 'wplicense' ) );
			}
			if ( 'themes.php' === $this->parent_slug ) {
				$this->page_hook = call_user_func( 'add_theme_page', $args['page_title'], $args['menu_title'], $args['capability'], $args['menu_slug'], $args['function'] );
			} else {
				$hook = $this->page_hook = call_user_func( 'add_submenu_page', 'admin.php?page=wpl_activation', 'WPLicense Install', 'Install', $args['capability'], $args['menu_slug'], $args['function'] );//NEW
			}
			add_action( "load-$hook", 'wpl_add_options_plugin' );//NEW
		}

		public function install_plugins_page() {
			global $pagenow, $wp_filesystem;
			if ( !defined( 'WPL_FEE' ) ) {
				$sumtime = 600000;
			} else {
				$sumtime = 3600000;
			}
			$plugin_table = new WPLICENSE_List_Table;
			if ( ( ( 'wplicense-bulk-install' === $plugin_table->current_action() || 'wplicense-bulk-update' === $plugin_table->current_action() ) && $plugin_table->process_bulk_actions() ) || $this->do_plugin_install() ) {
				return;
			}
			wp_clean_plugins_cache( false );
			?>
			<div class="wplicense wrap">
				<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
				<?php $plugin_table->prepare_items(); ?>
				<?php
				include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
				if ( $pagenow == 'admin.php' && $_GET['page'] == 'wpl-install' ) {
					include_once dirname( __FILE__ ). '/libraries/php/wpl-actions.php';
					if(!empty($_POST['installtheme'])){
						foreach($_POST['installtheme'] as $theme){
							$token = wpl_token_download( $theme );
							$theme_source = WPL_BASE_API. WPL_ACTIVATION_EMAIL_GET. $token. '&install=true';
							wpl_install_theme( $theme, $theme_source );
						}
					}
					if(!empty($_POST['installplugin'])){
						foreach($_POST['installplugin'] as $plugin){
							$token = wpl_token_download( $plugin );
							$plugin_source = WPL_BASE_API. WPL_ACTIVATION_EMAIL_GET. $token. '&install=true';
							wpl_install_plugin( $plugin, $plugin_source );
						}
					}
					if(!empty($_POST['updatetheme'])){
						foreach($_POST['updatetheme'] as $theme){
							wpl_update_theme( $theme );
						}
					}
					if(!empty($_POST['updateplugin'])){
						foreach($_POST['updateplugin'] as $plugin){
							wpl_update_plugin( $plugin );
						}
					}
					if(!empty($_POST['retheme'])){
						foreach($_POST['retheme'] as $theme){
							$token = wpl_token_download( $theme );
							$theme_source = WPL_BASE_API. WPL_ACTIVATION_EMAIL_GET. $token. '&install=true';
							wpl_reinstall_theme( $theme, $theme_source );
						}
					}
					if(!empty($_POST['replugin'])){
						foreach($_POST['replugin'] as $plugin){
							$token = wpl_token_download( $plugin );
							$plugin_source = WPL_BASE_API. WPL_ACTIVATION_EMAIL_GET. $token. '&install=true';
							wpl_reinstall_plugin( $plugin, $plugin_source );
						}
					}
				
					?>
					<hr>
					<div style="background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">
						<p>How to Install/Update a WordPress Theme/Plugin? Simply click on "Install/Update" button and the theme/plugin will get automatically installed/updated on your website.<br>
						Click <strong>Reload</strong> before Install/Update. <span style="color: #ff0000;">While installing or updating, do not close or reload this browser window.</span></p>
						<?php wpl_script_request('wpl_click_reload', 'wpl_click_reload', 'spinner_click_reload', 'Reload', false, true);?>
						<span id="reload"></span>
						<br><br>
						<script>
							var sumtime = <?php echo $sumtime; ?>;
							var countDownDate = <?php echo WPL_DATE_UPDATE * 1000; ?>;
							var x = setInterval(function() {
								var now = new Date().getTime();
								var distance = sumtime - (now - countDownDate);
								var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
								var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
								var seconds = Math.floor((distance % (1000 * 60)) / 1000);
								document.getElementById("reload").innerHTML = "in: " + hours + "h " + minutes + "m " + seconds + "s ";
								if (distance <= 0) {
									clearInterval(x);
									document.getElementById("reload").innerHTML = "reloading...";
								}
							}, 1000);
						</script>
					</div>
					<hr>
					<?php
				}
				if ( ! empty( $this->message ) && is_string( $this->message ) ) {
					echo wp_kses_post( $this->message );
				}
				?>
				<?php $plugin_table->views(); ?>

				<form id="wplicense-plugins" action="" method="post">
					<input type="hidden" name="wplicense-page" value="<?php echo esc_attr( $this->menu ); ?>" />
					<input type="hidden" name="plugin_status" value="<?php echo esc_attr( $plugin_table->view_context ); ?>" />
					<?php $plugin_table->search_box( 'Search', 'searchbox' ); ?>
					<?php $plugin_table->display(); ?>
				</form>
			</div>
			<?php
		}

		protected function do_plugin_install() {
			if ( empty( $_GET['plugin'] ) ) {
				return false;
			}
			$slug = $this->sanitize_key( urldecode( $_GET['plugin'] ) );
			if ( ! isset( $this->plugins[ $slug ] ) ) {
				return false;
			}
			if ( ( isset( $_GET['wplicense-install'] ) && 'install-plugin' === $_GET['wplicense-install'] ) || ( isset( $_GET['wplicense-update'] ) && 'update-plugin' === $_GET['wplicense-update'] ) ) {
				$install_type = 'install';
				if ( isset( $_GET['wplicense-update'] ) && 'update-plugin' === $_GET['wplicense-update'] ) {
					$install_type = 'update';
				}
				check_admin_referer( 'wplicense-' . $install_type, 'wplicense-nonce' );
				$url = wp_nonce_url(
					add_query_arg(
						array(
							'plugin'                 => urlencode( $slug ),
							'wplicense-' . $install_type => $install_type . '-plugin',
						),
						$this->get_wplicense_url()
					),
					'wplicense-' . $install_type,
					'wplicense-nonce'
				);
				$method = '';
				if ( false === ( $creds = request_filesystem_credentials( esc_url_raw( $url ), $method, false, false, array() ) ) ) {
					return true;
				}
				if ( ! WP_Filesystem( $creds ) ) {
					request_filesystem_credentials( esc_url_raw( $url ), $method, true, false, array() ); 
					return true;
				}
				$extra         = array();
				$extra['slug'] = $slug;
				$source        = $this->get_download_url( $slug );
				$api           = ( 'repo' === $this->plugins[ $slug ]['source_type'] ) ? $this->get_plugins_api( $slug ) : null;
				$api           = ( false !== $api ) ? $api : null;
				$url = add_query_arg(
					array(
						'action' => $install_type . '-plugin',
						'plugin' => urlencode( $slug ),
					),
					'update.php'
				);
				if ( ! class_exists( 'Plugin_Upgrader', false ) ) {
					require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
				}
				$title     = ( 'update' === $install_type ) ? $this->strings['updating'] : $this->strings['installing'];
				$skin_args = array(
					'type'   => ( 'bundled' !== $this->plugins[ $slug ]['source_type'] ) ? 'web' : 'upload',
					'title'  => sprintf( $title, $this->plugins[ $slug ]['name'] ),
					'url'    => esc_url_raw( $url ),
					'nonce'  => $install_type . '-plugin_' . $slug,
					'plugin' => '',
					'api'    => $api,
					'extra'  => $extra,
				);
				unset( $title );
				if ( 'update' === $install_type ) {
					$skin_args['plugin'] = $this->plugins[ $slug ]['file_path'];
					$skin                = new Plugin_Upgrader_Skin( $skin_args );
				} else {
					$skin = new Plugin_Installer_Skin( $skin_args );
				}
				$upgrader = new Plugin_Upgrader( $skin );
				add_filter( 'upgrader_source_selection', array( $this, 'maybe_adjust_source_dir' ), 1, 3 );
				if ( 'update' === $install_type ) {
					$to_inject                    = array( $slug => $this->plugins[ $slug ] );
					$to_inject[ $slug ]['source'] = $source;
					$this->inject_update_info( $to_inject );
					$upgrader->upgrade( $this->plugins[ $slug ]['file_path'] );
				} else {
					$upgrader->install( $source );
				}
				remove_filter( 'upgrader_source_selection', array( $this, 'maybe_adjust_source_dir' ), 1 );
				$this->populate_file_path( $slug );
				if ( $this->is_automatic && ! $this->is_plugin_active( $slug ) ) {
					$plugin_activate = $upgrader->plugin_info();
					if ( false === $this->activate_single_plugin( $plugin_activate, $slug, true ) ) {
						return true;
					}
				}
				$this->show_wplicense_version();
				if ( $this->is_wplicense_complete() ) {
					echo '<p>', sprintf( esc_html( $this->strings['complete'] ), '<a href="' . esc_url( self_admin_url() ) . '">' . esc_html__( 'Return to the Dashboard', 'wplicense' ) . '</a>' ), '</p>';
					echo '<style type="text/css">#adminmenu .wp-submenu li.current { display: none !important; }</style>';
				} else {
					echo '<p><a href="', esc_url( $this->get_wplicense_url() ), '" target="_parent">', esc_html( $this->strings['return'] ), '</a></p>';
				}
				return true;
			} elseif ( isset( $this->plugins[ $slug ]['file_path'], $_GET['wplicense-activate'] ) && 'activate-plugin' === $_GET['wplicense-activate'] ) {
				check_admin_referer( 'wplicense-activate', 'wplicense-nonce' );
				if ( false === $this->activate_single_plugin( $this->plugins[ $slug ]['file_path'], $slug ) ) {
					return true;
				}
			}
			return false;
		}

		public function inject_update_info( $plugins ) {
			$repo_updates = get_site_transient( 'update_plugins' );
			if ( ! is_object( $repo_updates ) ) {
				$repo_updates = new stdClass;
			}
			foreach ( $plugins as $slug => $plugin ) {
				$file_path = $plugin['file_path'];
				if ( empty( $repo_updates->response[ $file_path ] ) ) {
					$repo_updates->response[ $file_path ] = new stdClass;
				}
				$repo_updates->response[ $file_path ]->slug        = $slug;
				$repo_updates->response[ $file_path ]->plugin      = $file_path;
				$repo_updates->response[ $file_path ]->new_version = $plugin['version'];
				$repo_updates->response[ $file_path ]->package     = $plugin['source'];
				if ( empty( $repo_updates->response[ $file_path ]->url ) && ! empty( $plugin['external_url'] ) ) {
					$repo_updates->response[ $file_path ]->url = $plugin['external_url'];
				}
			}
			set_site_transient( 'update_plugins', $repo_updates );
		}

		public function maybe_adjust_source_dir( $source, $remote_source, $upgrader ) {
			if ( ! $this->is_wplicense_page() || ! is_object( $GLOBALS['wp_filesystem'] ) ) {
				return $source;
			}
			$source_files = array_keys( $GLOBALS['wp_filesystem']->dirlist( $remote_source ) );
			if ( 1 === count( $source_files ) && false === $GLOBALS['wp_filesystem']->is_dir( $source ) ) {
				return $source;
			}
			$desired_slug = '';
			if ( false === $upgrader->bulk && ! empty( $upgrader->skin->options['extra']['slug'] ) ) {
				$desired_slug = $upgrader->skin->options['extra']['slug'];
			} else {
				foreach ( $this->plugins as $slug => $plugin ) {
					if ( ! empty( $upgrader->skin->plugin_names[ $upgrader->skin->i ] ) && $plugin['name'] === $upgrader->skin->plugin_names[ $upgrader->skin->i ] ) {
						$desired_slug = $slug;
						break;
					}
				}
				unset( $slug, $plugin );
			}
			if ( ! empty( $desired_slug ) ) {
				$subdir_name = untrailingslashit( str_replace( trailingslashit( $remote_source ), '', $source ) );

				if ( ! empty( $subdir_name ) && $subdir_name !== $desired_slug ) {
					$from_path = untrailingslashit( $source );
					$to_path   = trailingslashit( $remote_source ) . $desired_slug;

					if ( true === $GLOBALS['wp_filesystem']->move( $from_path, $to_path ) ) {
						return trailingslashit( $to_path );
					} else {
						return new WP_Error( 'rename_failed', esc_html__( 'The remote plugin package does not contain a folder with the desired slug and renaming did not work.', 'wplicense' ) . ' ' . esc_html__( 'Please contact the plugin provider and ask them to package their plugin according to the WordPress guidelines.', 'wplicense' ), array( 'found' => $subdir_name, 'expected' => $desired_slug ) );
					}
				} elseif ( empty( $subdir_name ) ) {
					return new WP_Error( 'packaged_wrong', esc_html__( 'The remote plugin package consists of more than one file, but the files are not packaged in a folder.', 'wplicense' ) . ' ' . esc_html__( 'Please contact the plugin provider and ask them to package their plugin according to the WordPress guidelines.', 'wplicense' ), array( 'found' => $subdir_name, 'expected' => $desired_slug ) );
				}
			}
			return $source;
		}

		protected function activate_single_plugin( $file_path, $slug, $automatic = false ) {
			if ( $this->can_plugin_activate( $slug ) ) {
				$activate = activate_plugin( $file_path );
				if ( is_wp_error( $activate ) ) {
					echo '<div id="message" class="error"><p>', wp_kses_post( $activate->get_error_message() ), '</p></div>',
						'<p><a href="', esc_url( $this->get_wplicense_url() ), '" target="_parent">', esc_html( $this->strings['return'] ), '</a></p>';
					return false;
				} else {
					if ( ! $automatic ) {
						if ( ! isset( $_POST['action'] ) ) {
							echo '<div id="message" class="updated"><p>', esc_html( $this->strings['activated_successfully'] ), ' <strong>', esc_html( $this->plugins[ $slug ]['name'] ), '.</strong></p></div>';
						}
					} else {
						echo '<p>', esc_html( $this->strings['plugin_activated'] ), '</p>';
					}
				}
			} elseif ( $this->is_plugin_active( $slug ) ) {
				echo '<div id="message" class="error"><p>',
					sprintf(
						esc_html( $this->strings['plugin_already_active'] ),
						'<strong>' . esc_html( $this->plugins[ $slug ]['name'] ) . '</strong>'
					),
					'</p></div>';
			} elseif ( $this->does_plugin_require_update( $slug ) ) {
				if ( ! $automatic ) {
					if ( ! isset( $_POST['action'] ) ) {
						echo '<div id="message" class="error"><p>',
							sprintf(
								esc_html( $this->strings['plugin_needs_higher_version'] ),
								'<strong>' . esc_html( $this->plugins[ $slug ]['name'] ) . '</strong>'
							),
							'</p></div>';
					}
				} else {
					echo '<p>', sprintf( esc_html( $this->strings['plugin_needs_higher_version'] ), esc_html( $this->plugins[ $slug ]['name'] ) ), '</p>';
				}
			}
			return true;
		}

		public function notices() {
			if ( ( $this->is_wplicense_page() || $this->is_core_update_page() ) || get_user_meta( get_current_user_id(), 'wplicense_dismissed_notice_' . $this->id, true ) || ! current_user_can( apply_filters( 'wplicense_show_admin_notice_capability', 'publish_posts' ) ) ) {
				return;
			}
			$message = array();
			$install_link_count          = 0;
			$update_link_count           = 0;
			$activate_link_count         = 0;
			$total_required_action_count = 0;
			foreach ( $this->plugins as $slug => $plugin ) {
				if ( $this->is_plugin_active( $slug ) && false === $this->does_plugin_have_update( $slug ) ) {
					continue;
				}
				if ( ! $this->is_plugin_installed( $slug ) ) {
					if ( current_user_can( 'install_plugins' ) ) {
						$install_link_count++;
						if ( true === $plugin['required'] ) {
							$message['notice_can_install_required'][] = $slug;
						} else {
							$message['notice_can_install_recommended'][] = $slug;
						}
					}
					if ( true === $plugin['required'] ) {
						$total_required_action_count++;
					}
				} else {
					if ( ! $this->is_plugin_active( $slug ) && $this->can_plugin_activate( $slug ) ) {
						if ( current_user_can( 'activate_plugins' ) ) {
							$activate_link_count++;
							if ( true === $plugin['required'] ) {
								$message['notice_can_activate_required'][] = $slug;
							} else {
								$message['notice_can_activate_recommended'][] = $slug;
							}
						}
						if ( true === $plugin['required'] ) {
							$total_required_action_count++;
						}
					}
					if ( $this->does_plugin_require_update( $slug ) || false !== $this->does_plugin_have_update( $slug ) ) {
						if ( current_user_can( 'update_plugins' ) ) {
							$update_link_count++;
							if ( $this->does_plugin_require_update( $slug ) ) {
								$message['notice_ask_to_update'][] = $slug;
							} elseif ( false !== $this->does_plugin_have_update( $slug ) ) {
								$message['notice_ask_to_update_maybe'][] = $slug;
							}
						}
						if ( true === $plugin['required'] ) {
							$total_required_action_count++;
						}
					}
				}
			}
			unset( $slug, $plugin );
			if ( ! empty( $message ) || $total_required_action_count > 0 ) {
				krsort( $message );
				$rendered = '';
				$line_template = '<span style="display: block; margin: 0.5em 0.5em 0 0; clear: both;">%s</span>' . "\n";
				if ( ! current_user_can( 'activate_plugins' ) && ! current_user_can( 'install_plugins' ) && ! current_user_can( 'update_plugins' ) ) {
					$rendered  = esc_html( $this->strings['notice_cannot_install_activate'] ) . ' ' . esc_html( $this->strings['contact_admin'] );
					$rendered .= $this->create_user_action_links_for_notice( 0, 0, 0, $line_template );
				} else {
					if ( ! $this->dismissable && ! empty( $this->dismiss_msg ) ) {
						$rendered .= sprintf( $line_template, wp_kses_post( $this->dismiss_msg ) );
					}
					foreach ( $message as $type => $plugin_group ) {
						$linked_plugins = array();
						foreach ( $plugin_group as $plugin_slug ) {
							$linked_plugins[] = $this->get_info_link( $plugin_slug );
						}
						unset( $plugin_slug );
						$count          = count( $plugin_group );
						$linked_plugins = array_map( array( 'WPLICENSE_Utils', 'wrap_in_em' ), $linked_plugins );
						$last_plugin    = array_pop( $linked_plugins );
						$imploded       = empty( $linked_plugins ) ? $last_plugin : ( implode( ', ', $linked_plugins ) . ' ' . esc_html_x( 'and', 'plugin A *and* plugin B', 'wplicense' ) . ' ' . $last_plugin );
						$rendered .= sprintf(
							$line_template,
							sprintf(
								translate_nooped_plural( $this->strings[ $type ], $count, 'wplicense' ),
								$imploded,
								$count
							)
						);
					}
					unset( $type, $plugin_group, $linked_plugins, $count, $last_plugin, $imploded );
					$rendered .= $this->create_user_action_links_for_notice( $install_link_count, $update_link_count, $activate_link_count, $line_template );
				}
				add_settings_error( 'wplicense', 'wplicense', $rendered, $this->get_admin_notice_class() );
			}
			if ( 'options-general' !== $GLOBALS['current_screen']->parent_base ) {
				$this->display_settings_errors();
			}
		}

		protected function create_user_action_links_for_notice( $install_count, $update_count, $activate_count, $line_template ) {
			$action_links = array(
				'install'  => '',
				'update'   => '',
				'activate' => '',
				'dismiss'  => $this->dismissable ? '<a href="' . esc_url( wp_nonce_url( add_query_arg( 'wplicense-dismiss', 'dismiss_admin_notices' ), 'wplicense-dismiss-' . get_current_user_id() ) ) . '" class="dismiss-notice" target="_parent">' . esc_html( $this->strings['dismiss'] ) . '</a>' : '',
			);
			$link_template = '<a href="%2$s">%1$s</a>';
			if ( current_user_can( 'install_plugins' ) ) {
				if ( $install_count > 0 ) {
					$action_links['install'] = sprintf(
						$link_template,
						translate_nooped_plural( $this->strings['install_link'], $install_count, 'wplicense' ),
						esc_url( $this->get_wplicense_status_url( 'install' ) )
					);
				}
				if ( $update_count > 0 ) {
					$action_links['update'] = sprintf(
						$link_template,
						translate_nooped_plural( $this->strings['update_link'], $update_count, 'wplicense' ),
						esc_url( $this->get_wplicense_status_url( 'update' ) )
					);
				}
			}
			if ( current_user_can( 'activate_plugins' ) && $activate_count > 0 ) {
				$action_links['activate'] = sprintf(
					$link_template,
					translate_nooped_plural( $this->strings['activate_link'], $activate_count, 'wplicense' ),
					esc_url( $this->get_wplicense_status_url( 'activate' ) )
				);
			}
			$action_links = apply_filters( 'wplicense_notice_action_links', $action_links );
			$action_links = array_filter( (array) $action_links ); 
			if ( ! empty( $action_links ) ) {
				$action_links = sprintf( $line_template, implode( ' | ', $action_links ) );
				return apply_filters( 'wplicense_notice_rendered_action_links', $action_links );
			} else {
				return '';
			}
		}

		protected function get_admin_notice_class() {
			if ( ! empty( $this->strings['nag_type'] ) ) {
				return sanitize_html_class( strtolower( $this->strings['nag_type'] ) );
			} else {
				if ( version_compare( $this->wp_version, '4.2', '>=' ) ) {
					return 'notice-warning';
				} elseif ( version_compare( $this->wp_version, '4.1', '>=' ) ) {
					return 'notice';
				} else {
					return 'updated';
				}
			}
		}

		protected function display_settings_errors() {
			global $wp_settings_errors;
			settings_errors( 'wplicense' );
			foreach ( (array) $wp_settings_errors as $key => $details ) {
				if ( 'wplicense' === $details['setting'] ) {
					unset( $wp_settings_errors[ $key ] );
					break;
				}
			}
		}

		public function dismiss() {
			if ( isset( $_GET['wplicense-dismiss'] ) && check_admin_referer( 'wplicense-dismiss-' . get_current_user_id() ) ) {
				update_user_meta( get_current_user_id(), 'wplicense_dismissed_notice_' . $this->id, 1 );
			}
		}

		public function register( $plugin ) {
			if ( empty( $plugin['slug'] ) || empty( $plugin['name'] ) ) {
				return;
			}
			if ( empty( $plugin['slug'] ) || ! is_string( $plugin['slug'] ) || isset( $this->plugins[ $plugin['slug'] ] ) ) {
				return;
			}
			$defaults = array(
				'name'               => '',
				'slug'               => '',
				'categories'         => '',
				'url'     			 => '',
				'source'             => 'repo',
				'required'           => false,
				'version'            => '',
				'force_activation'   => false,
				'force_deactivation' => false,
				'external_url'       => '',
				'is_callable'        => '',
			);
			$plugin = wp_parse_args( $plugin, $defaults );
			$plugin['slug'] = $this->sanitize_key( $plugin['slug'] );
			$plugin['version']            = (string) $plugin['version'];
			$plugin['source']             = empty( $plugin['source'] ) ? 'repo' : $plugin['source'];
			$plugin['required']           = WPLICENSE_Utils::validate_bool( $plugin['required'] );
			$plugin['force_activation']   = WPLICENSE_Utils::validate_bool( $plugin['force_activation'] );
			$plugin['force_deactivation'] = WPLICENSE_Utils::validate_bool( $plugin['force_deactivation'] );
			$plugin['file_path']   = $this->_get_plugin_basename_from_slug( $plugin['slug'] );
			$plugin['source_type'] = $this->get_plugin_source_type( $plugin['source'] );
			$this->plugins[ $plugin['slug'] ]    = $plugin;
			$this->sort_order[ $plugin['slug'] ] = $plugin['name'];
			if ( true === $plugin['force_activation'] ) {
				$this->has_forced_activation = true;
			}
			if ( true === $plugin['force_deactivation'] ) {
				$this->has_forced_deactivation = true;
			}
		}

		protected function get_plugin_source_type( $source ) {
			if ( 'repo' === $source || preg_match( self::WP_REPO_REGEX, $source ) ) {
				return 'repo';
			} elseif ( preg_match( self::IS_URL_REGEX, $source ) ) {
				return 'external';
			} else {
				return 'bundled';
			}
		}

		public function sanitize_key( $key ) {
			$raw_key = $key;
			$key     = preg_replace( '`[^A-Za-z0-9_-]`', '', $key );
			return apply_filters( 'wplicense_sanitize_key', $key, $raw_key );
		}

		public function config( $config ) {
			$keys = array(
				'id',
				'default_path',
				'has_notices',
				'dismissable',
				'dismiss_msg',
				'menu',
				'parent_slug',
				'capability',
				'is_automatic',
				'message',
				'strings',
			);

			foreach ( $keys as $key ) {
				if ( isset( $config[ $key ] ) ) {
					if ( is_array( $config[ $key ] ) ) {
						$this->$key = array_merge( $this->$key, $config[ $key ] );
					} else {
						$this->$key = $config[ $key ];
					}
				}
			}
		}

		public function actions( $install_actions ) {
			if ( $this->is_wplicense_page() ) {
				return false;
			}
			return $install_actions;
		}

		public function flush_plugins_cache( $clear_update_cache = true ) {
			wp_clean_plugins_cache( $clear_update_cache );
		}

		public function populate_file_path( $plugin_slug = '' ) {
			if ( ! empty( $plugin_slug ) && is_string( $plugin_slug ) && isset( $this->plugins[ $plugin_slug ] ) ) {
				$this->plugins[ $plugin_slug ]['file_path'] = $this->_get_plugin_basename_from_slug( $plugin_slug );
			} else {
				foreach ( $this->plugins as $slug => $values ) {
					$this->plugins[ $slug ]['file_path'] = $this->_get_plugin_basename_from_slug( $slug );
				}
			}
		}

		protected function _get_plugin_basename_from_slug( $slug ) {
			$keys = array_keys( $this->get_plugins() );
			foreach ( $keys as $key ) {
				if ( preg_match( '|^' . $slug . '/|', $key ) ) {
					return $key;
				}
			}
			return $slug;
		}

		public function _get_plugin_data_from_name( $name, $data = 'slug' ) {
			foreach ( $this->plugins as $values ) {
				if ( $name === $values['name'] && isset( $values[ $data ] ) ) {
					return $values[ $data ];
				}
			}
			return false;
		}

		public function get_download_url( $slug ) {
			$dl_source = '';
			switch ( $this->plugins[ $slug ]['source_type'] ) {
				case 'repo':
					return $this->get_wp_repo_download_url( $slug );
				case 'external':
					return $this->plugins[ $slug ]['source'];
				case 'bundled':
					return $this->default_path . $this->plugins[ $slug ]['source'];
			}
			return $dl_source;
		}

		protected function get_wp_repo_download_url( $slug ) {
			$source = '';
			$api    = $this->get_plugins_api( $slug );
			if ( false !== $api && isset( $api->download_link ) ) {
				$source = $api->download_link;
			}
			return $source;
		}

		protected function get_plugins_api( $slug ) {
			static $api = array();
			if ( ! isset( $api[ $slug ] ) ) {
				if ( ! function_exists( 'plugins_api' ) ) {
					require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
				}
				$response = plugins_api( 'plugin_information', array( 'slug' => $slug, 'fields' => array( 'sections' => false ) ) );
				$api[ $slug ] = false;
				if ( is_wp_error( $response ) ) {
					wp_die( esc_html( $this->strings['oops'] ) );
				} else {
					$api[ $slug ] = $response;
				}
			}
			return $api[ $slug ];
		}

		public function get_info_link( $slug ) {
			if ( ! empty( $this->plugins[ $slug ]['external_url'] ) && preg_match( self::IS_URL_REGEX, $this->plugins[ $slug ]['external_url'] ) ) {
				$link = sprintf(
					'<a href="%1$s" target="_blank">%2$s</a>',
					esc_url( $this->plugins[ $slug ]['external_url'] ),
					esc_html( $this->plugins[ $slug ]['name'] )
				);
			} elseif ( 'repo' === $this->plugins[ $slug ]['source_type'] ) {
				$url = add_query_arg(
					array(
						'tab'       => 'plugin-information',
						'plugin'    => urlencode( $slug ),
						'TB_iframe' => 'true',
						'width'     => '640',
						'height'    => '500',
					),
					self_admin_url( 'plugin-install.php' )
				);
				$link = sprintf(
					'<a href="%1$s" class="thickbox">%2$s</a>',
					esc_url( $url ),
					esc_html( $this->plugins[ $slug ]['name'] )
				);
			} else {
				$link = esc_html( $this->plugins[ $slug ]['name'] );
			}
			return $link;
		}

		protected function is_wplicense_page() {
			return isset( $_GET['page'] ) && $this->menu === $_GET['page'];
		}

		protected function is_core_update_page() {
			if ( ! function_exists( 'get_current_screen' ) ) {
				return false;
			}
			$screen = get_current_screen();
			if ( 'update-core' === $screen->base ) {
				return true;
			} elseif ( 'plugins' === $screen->base && ! empty( $_POST['action'] ) ) {
				return true;
			} elseif ( 'update' === $screen->base && ! empty( $_POST['action'] ) ) {
				return true;
			}
			return false;
		}

		public function get_wplicense_url() {
			static $url;
			if ( ! isset( $url ) ) {
				$parent = $this->parent_slug;
				if ( false === strpos( $parent, '.php' ) ) {
					$parent = 'admin.php';
				}
				$url = add_query_arg(
					array(
						'page' => urlencode( $this->menu ),
					),
					self_admin_url( $parent )
				);
			}
			return $url;
		}

		public function get_wplicense_status_url( $status ) {
			return add_query_arg(
				array(
					'plugin_status' => urlencode( $status ),
				),
				$this->get_wplicense_url()
			);
		}

		public function is_wplicense_complete() {
			$complete = true;
			foreach ( $this->plugins as $slug => $plugin ) {
				if ( ! $this->is_plugin_active( $slug ) || false !== $this->does_plugin_have_update( $slug ) ) {
					$complete = false;
					break;
				}
			}
			return $complete;
		}

		public function is_plugin_installed( $slug ) {
			$installed_plugins = $this->get_plugins();
			return ( ! empty( $installed_plugins[ $this->plugins[ $slug ]['file_path'] ] ) );
		}

		public function is_plugin_active( $slug ) {
			return ( ( ! empty( $this->plugins[ $slug ]['is_callable'] ) && is_callable( $this->plugins[ $slug ]['is_callable'] ) ) || is_plugin_active( $this->plugins[ $slug ]['file_path'] ) );
		}

		public function can_plugin_update( $slug ) {
			if ( 'repo' !== $this->plugins[ $slug ]['source_type'] ) {
				return true;
			}
			$api = $this->get_plugins_api( $slug );
			if ( false !== $api && isset( $api->requires ) ) {
				return version_compare( $this->wp_version, $api->requires, '>=' );
			}
			return true;
		}

		public function is_plugin_updatetable( $slug ) {
			if ( ! $this->is_plugin_installed( $slug ) ) {
				return false;
			} else {
				return ( false !== $this->does_plugin_have_update( $slug ) && $this->can_plugin_update( $slug ) );
			}
		}

		public function can_plugin_activate( $slug ) {
			return ( ! $this->is_plugin_active( $slug ) && ! $this->does_plugin_require_update( $slug ) );
		}

		public function get_installed_version( $slug ) {
			$installed_plugins = $this->get_plugins();
			if ( ! empty( $installed_plugins[ $this->plugins[ $slug ]['file_path'] ]['Version'] ) ) {
				return $installed_plugins[ $this->plugins[ $slug ]['file_path'] ]['Version'];
			}
			return '';
		}

		public function does_plugin_require_update( $slug ) {
			$installed_version = $this->get_installed_version( $slug );
			$minimum_version   = $this->plugins[ $slug ]['version'];
			return version_compare( $minimum_version, $installed_version, '>' );
		}

		public function does_plugin_have_update( $slug ) {
			if ( 'repo' !== $this->plugins[ $slug ]['source_type'] ) {
				if ( $this->does_plugin_require_update( $slug ) ) {
					return $this->plugins[ $slug ]['version'];
				}
				return false;
			}
			$repo_updates = get_site_transient( 'update_plugins' );
			if ( isset( $repo_updates->response[ $this->plugins[ $slug ]['file_path'] ]->new_version ) ) {
				return $repo_updates->response[ $this->plugins[ $slug ]['file_path'] ]->new_version;
			}
			return false;
		}

		public function get_upgrade_notice( $slug ) {
			if ( 'repo' !== $this->plugins[ $slug ]['source_type'] ) {
				return '';
			}
			$repo_updates = get_site_transient( 'update_plugins' );
			if ( ! empty( $repo_updates->response[ $this->plugins[ $slug ]['file_path'] ]->upgrade_notice ) ) {
				return $repo_updates->response[ $this->plugins[ $slug ]['file_path'] ]->upgrade_notice;
			}
			return '';
		}

		public function get_plugins( $plugin_folder = '' ) {
			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			return get_plugins( $plugin_folder );
		}

		public function update_dismiss() {
			delete_metadata( 'user', null, 'wplicense_dismissed_notice_' . $this->id, null, true );
		}

		public function force_activation() {
			foreach ( $this->plugins as $slug => $plugin ) {
				if ( true === $plugin['force_activation'] ) {
					if ( ! $this->is_plugin_installed( $slug ) ) {
						continue;
					} elseif ( $this->can_plugin_activate( $slug ) ) {
						activate_plugin( $plugin['file_path'] );
					}
				}
			}
		}

		public function force_deactivation() {
			$deactivated = array();
			foreach ( $this->plugins as $slug => $plugin ) {
				if ( true === $plugin['force_deactivation'] && is_plugin_active( $plugin['file_path'] ) ) {
					deactivate_plugins( $plugin['file_path'] );
					$deactivated[ $plugin['file_path'] ] = time();
				}
			}
			if ( ! empty( $deactivated ) ) {
				update_option( 'recently_activated', $deactivated + (array) get_option( 'recently_activated' ) );
			}
		}

		public function show_wplicense_version() {
			echo '<p style="float: right; padding: 0em 1.5em 0.5em 0;display: none;"><strong><small>',
				esc_html(
					sprintf(
						__( 'WPLICENSE v%s', 'wplicense' ),
						self::WPLICENSE_VERSION
					)
				),
				'</small></strong></p>';
		}

		public static function get_instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof self ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}
	}

	if ( ! function_exists( 'load_wplu_plugin_activation' ) ) {
		function load_wplu_plugin_activation() {
			$GLOBALS['wplicense'] = WPLU_Plugin_Activation::get_instance();
		}
	}

	if ( did_action( 'plugins_loaded' ) ) {
		load_wplu_plugin_activation();
	} else {
		add_action( 'plugins_loaded', 'load_wplu_plugin_activation' );
	}
}

if ( ! function_exists( 'wplicense' ) ) {
	function wplicense( $plugins, $config = array() ) {
		$instance = call_user_func( array( get_class( $GLOBALS['wplicense'] ), 'get_instance' ) );
		foreach ( $plugins as $plugin ) {
			call_user_func( array( $instance, 'register' ), $plugin );
		}
		if ( ! empty( $config ) && is_array( $config ) ) {
			if ( isset( $config['notices'] ) ) {
				_deprecated_argument( __FUNCTION__, '2.2.0', 'The `notices` config parameter was renamed to `has_notices` in WPLICENSE 2.2.0. Please adjust your configuration.' );
				if ( ! isset( $config['has_notices'] ) ) {
					$config['has_notices'] = $config['notices'];
				}
			}
			if ( isset( $config['parent_menu_slug'] ) ) {
				_deprecated_argument( __FUNCTION__, '2.4.0', 'The `parent_menu_slug` config parameter was removed in WPLICENSE 2.4.0. In WPLICENSE 2.5.0 an alternative was (re-)introduced. Please adjust your configuration. For more information visit the website: http://tgmpluginactivation.com/configuration/#h-configuration-options.' );
			}
			if ( isset( $config['parent_url_slug'] ) ) {
				_deprecated_argument( __FUNCTION__, '2.4.0', 'The `parent_url_slug` config parameter was removed in WPLICENSE 2.4.0. In WPLICENSE 2.5.0 an alternative was (re-)introduced. Please adjust your configuration. For more information visit the website: http://tgmpluginactivation.com/configuration/#h-configuration-options.' );
			}
			call_user_func( array( $instance, 'config' ), $config );
		}
	}
}

if ( ! class_exists( 'WPL_List_Table' ) ) {
	require_once WPL_PLUGIN_PATH.'libraries/php/class-wp-list-table.php';
}

if ( ! class_exists( 'WPLICENSE_List_Table' ) ) {
	class WPLICENSE_List_Table extends WPL_List_Table {
		protected $wplicense;
		public $view_context = 'all';
		protected $view_totals = array(
			'all'      => 0,
			'install'  => 0,
			'update'   => 0,
			'activate' => 0,
		);
		public function __construct() {
			$this->wplicense = call_user_func( array( get_class( $GLOBALS['wplicense'] ), 'get_instance' ) );
			parent::__construct(
				array(
					'singular' => 'plugin',
					'plural'   => 'plugins',
					'ajax'     => false,
				)
			);
			if ( WPL_check( 'option', 'wpl_plugins_themes' ) ) {//NEW
				$WPL_items = WPL_get( 'option', 'wpl_plugins_themes', array() );
				foreach ( $WPL_items as $value){
					$categories[] = $value['categories'];
				}
				if (isset($categories)) {
					$_categories = array_merge(array_unique($categories),array( 'install', 'installed', 'free', 'themes', 'plugins', 'available', 'update', 'activate' ));
				}
			}
			add_action( 'admin_head', array( &$this, 'admin_header' ) );//NEW
			if ( isset( $_REQUEST['plugin_status'] ) && isset($_categories) && in_array( $_REQUEST['plugin_status'], $_categories, true ) ) {
				$this->view_context = sanitize_key( $_REQUEST['plugin_status'] );
			}
			add_filter( 'wplicense_table_data_items', array( $this, 'sort_table_items' ) );
		}
//NEW
		function admin_header() {
			$page = ( isset($_GET['page'] ) ) ? esc_attr( $_GET['page'] ) : false;
			if( 'wpl-install' != $page )
			return;
			echo '<style type="text/css">';
			echo '.widefat td {vertical-align: middle;}';
			echo '.widefat tbody th.check-column {padding: 16px 0 0 3px;}';
			echo '.wp-list-table .column-name { width: 31%; }';
			echo '.wp-list-table .column-slug { width: 30%; }';
			echo '.wp-list-table .column-version { width: 7%;}';
			echo '.wp-list-table .column-itype { width: 6%;}';
			echo '.wp-list-table .column-time { width: 9%;}';
			echo '.wp-list-table .column-install { width: 8.5%;}';
			echo '.wp-list-table .column-reinstall { width: 8.5%;}';
			echo '</style>';
		}
//NEW
		public function get_table_classes() {
			return array( 'widefat', 'fixed' );
		}

		protected function _gather_plugin_data() {
			$this->wplicense->admin_init();
			$this->wplicense->thickbox();
			$plugins = $this->categorize_plugins_to_views();
			$this->set_view_totals( $plugins );
			$table_data = array();
			$i          = 0;
			if ( empty( $plugins[ $this->view_context ] ) ) {
				$this->view_context = 'all';
			}
			foreach ( $plugins[ $this->view_context ] as $slug => $plugin ) {
				$table_data[ $i ]['sanitized_plugin']	= $plugin['name'];
				$table_data[ $i ]['slug']				= $slug;
				$table_data[ $i ]['plugin']				= sprintf( __( '<strong><a style="color: #555;" target="_bank" rel="nofollow" href="%s">' . $this->wplicense->get_info_link( $slug ) . '</a></strong>'), $plugin['url'] );
				$table_data[ $i ]['categories']			= $plugin['categories'];
				$table_data[ $i ]['url']				= $plugin['url'];
				if (isset($plugin['path'])){
					$table_data[ $i ]['path']			= $plugin['path'];
				} else {
					$table_data[ $i ]['path']			= '';
				}
				if (isset($plugin['path'])){
					$table_data[ $i ]['itype']			= $plugin['type'];
				} else {
					$table_data[ $i ]['itype']			= '';
				}
				if (isset($plugin['price'])){
					$table_data[ $i ]['price']			= $plugin['price'];//NEW
				} else {
					$table_data[ $i ]['price']			= '';//NEW
				}
				if (isset($plugin['api'])){
					$table_data[ $i ]['api']			= $plugin['api'];
				} else {
					$table_data[ $i ]['api']			= '';
				}
				if (isset($plugin['time'])){
					$table_data[ $i ]['time']			= $plugin['time'];
				} else {
					$table_data[ $i ]['time']			= '';
				}
				$table_data[ $i ]['source']				= $this->get_plugin_source_type_text( $plugin['source_type'] );
				$table_data[ $i ]['type']				= $this->get_plugin_advise_type_text( $plugin['required'] );
				$table_data[ $i ]['status']				= $this->get_plugin_status_text( $slug );
				$table_data[ $i ]['installed_version']	= $this->wplicense->get_installed_version( $slug );
				$table_data[ $i ]['minimum_version']	= $plugin['version'];
				$table_data[ $i ]['available_version']	= $this->wplicense->does_plugin_have_update( $slug );//NEW
				$upgrade_notice = $this->wplicense->get_upgrade_notice( $slug );
				if ( ! empty( $upgrade_notice ) ) {
					$table_data[ $i ]['upgrade_notice'] = $upgrade_notice;

					add_action( "wplicense_after_plugin_row_{$slug}", array( $this, 'wp_plugin_update_row' ), 10, 2 );
				}
				$table_data[ $i ] = apply_filters( 'wplicense_table_data_item', $table_data[ $i ], $plugin );
				$i++;
			}
			return $table_data;
		}

		protected function categorize_plugins_to_views() {
			$plugins = array(
				'all'      		=> array(),
				'themes'    	=> array(),
				'plugins'    	=> array(),
				'available'   	=> array(),
				'free'    		=> array(),
				'install'  		=> array(),
				'installed' 	=> array(),
				'update'   		=> array(),
				'activate' 		=> array(),
			);
            foreach ( $this->wplicense->plugins as $slug => $plugin ) {
				$plugins['all'][ $slug ] = $plugin;
                if ( $this->wplicense->is_plugin_installed( $slug ) ) {
					if ( isset( $plugin['type'] ) && $plugin['type'] == 'plugin' ) {
						$plugins['installed'][ $slug ] = $plugin;
						$plugins['plugins'][ $slug ] = $plugin;
					}
                } else {
					if ( is_dir( WPL_PATH_THEME.$plugin['slug'] ) ) {
						if ( isset( $plugin['type'] ) && $plugin['type'] == 'theme' ) {
							$plugins['installed'][ $slug ] = $plugin;
							$plugins['themes'][ $slug ] = $plugin;
						}
					}
                }
//=
				if ( $plugin['categories'] != '' ) {
					$plugins[$plugin['categories']][ $slug ] = $plugin;
				}
				if ( isset( $plugin['price'] ) && $plugin['price'] == 'free' ) {
					$plugins['free'][ $slug ] = $plugin;
				}
				if ( isset( $plugin['type'] ) && $plugin['type'] == 'theme' ) {
					$plugins['themes'][ $slug ] = $plugin;
				}
				if ( isset( $plugin['type'] ) && $plugin['type'] == 'plugin' ) {
					$plugins['plugins'][ $slug ] = $plugin;
				}
				if ( isset( $plugin['price'] ) && $plugin['price'] != 'free' && $plugin['price'] != '' ) {
					$plugins['available'][ $slug ] = $plugin;
				}
//=
            }
			return $plugins;
		}

		protected function set_view_totals( $plugins ) {
			foreach ( $plugins as $type => $list ) {
				$this->view_totals[ $type ] = count( $list );
			}
		}

		protected function get_plugin_advise_type_text( $required ) {
			if ( true === $required ) {
				return __( 'Required', 'wplicense' );
			}
			return __( 'Recommended', 'wplicense' );
		}

		protected function get_plugin_source_type_text( $type ) {
			$string = '';
			switch ( $type ) {
				case 'repo':
					$string = __( 'WordPress Repository', 'wplicense' );
					break;
				case 'external':
					$string = __( 'External Source', 'wplicense' );
					break;
				case 'bundled':
					$string = __( 'Pre-Packaged', 'wplicense' );
					break;
			}
			return $string;
		}

		protected function get_plugin_status_text( $slug ) {
			if ( ! $this->wplicense->is_plugin_installed( $slug ) ) {
				if (!file_exists( WPL_PATH_THEME.$slug ) ) {//NEW
					return __( 'Not Installed', 'wplicense' );
				} else {//NEW
					if (get_template() != $slug){//NEW
						return __( 'Installed But Not Activated', 'wplicense' );//NEW
					} else {//NEW
						return __( 'Active', 'wplicense' );//NEW
					}//NEW
				}//NEW
			}
			if ( ! $this->wplicense->is_plugin_active( $slug ) ) {
				$install_status = __( 'Installed But Not Activated', 'wplicense' );
			} else {
				$install_status = __( 'Active', 'wplicense' );
			}
			$update_status = '';
			if ( $this->wplicense->does_plugin_require_update( $slug ) && false === $this->wplicense->does_plugin_have_update( $slug ) ) {
				$update_status = __( 'Required Update not Available', 'wplicense' );
			} elseif ( $this->wplicense->does_plugin_require_update( $slug ) ) {
				$update_status = __( 'Requires Update', 'wplicense' );
			} elseif ( false !== $this->wplicense->does_plugin_have_update( $slug ) ) {
				$update_status = __( 'Update recommended', 'wplicense' );
			}
			if ( '' === $update_status ) {
				return $install_status;
			}
			return sprintf(
				_x( '%1$s, %2$s', 'Install/Update Status', 'wplicense' ),
				$install_status,
				$update_status
			);
		}

		public function sort_table_items( $items ) {
			$type = array();
			$name = array();
			foreach ( $items as $i => $plugin ) {
				$type[ $i ] = $plugin['type'];
				$name[ $i ] = $plugin['time'];//ED:sanitized_plugin
			}
			array_multisort( $type, SORT_DESC, $name, SORT_DESC, $items );//ED
			return $items;
		}

		public function get_views() {
			$status_links = array();
			foreach ( $this->view_totals as $type => $count ) {
				if ( $count < 1 ) {
					continue;
				}
				switch ( $type ) {
					case 'all':
						$text = _nx( 'All <span class="count">(%s)</span>', 'All <span class="count">(%s)</span>', $count, 'plugins', 'wplicense' );
						break;
					case 'install':
						$text = _n( 'To Install <span class="count">(%s)</span>', 'To Install <span class="count">(%s)</span>', $count, 'wplicense' );
						break;
					case 'installed':
						$text = _n( 'Installed <span class="count">(%s)</span>', 'Installed <span class="count">(%s)</span>', $count, 'wplicense' );
						break;
					case 'free':
						$text = _n( 'Free <span class="count">(%s)</span>', 'Free <span class="count">(%s)</span>', $count, 'wplicense' );
						break;
					case 'themes':
						$text = _n( 'Themes <span class="count">(%s)</span>', 'Themes <span class="count">(%s)</span>', $count, 'wplicense' );
						break;
					case 'plugins':
						$text = _n( 'Plugins <span class="count">(%s)</span>', 'Plugins <span class="count">(%s)</span>', $count, 'wplicense' );
						break;
					case 'available':
						$text = _n( 'My Products <span class="count">(%s)</span>', 'My Products <span class="count">(%s)</span>', $count, 'wplicense' );
						break;
					case 'update':
						$text = _n( 'Update Available <span class="count">(%s)</span>', 'Update Available <span class="count">(%s)</span>', $count, 'wplicense' );
						break;
					case 'activate':
						$text = _n( 'To Activate <span class="count">(%s)</span>', 'To Activate <span class="count">(%s)</span>', $count, 'wplicense' );
						break;
//=
					case $type:
						$text = _n( $type.'<span class="count">(%s)</span>', $type.'<span class="count">(%s)</span>', $count, 'wplicense' );
						break;
//=
					default:
						$text = '';
						break;
				}
				if ( ! empty( $text ) ) {
					$status_links[ $type ] = sprintf(
						'<a href="%s"%s>%s</a>',
						esc_url( $this->wplicense->get_wplicense_status_url( $type ) ),
						( $type === $this->view_context ) ? ' class="current"' : '',
						sprintf( $text, number_format_i18n( $count ) )
					);
				}
			}
			return $status_links;
		}

		public function column_default( $item, $column_name ) {
			return $item[ $column_name ];
		}

		public function column_cb( $item ) {
			return sprintf(
				'<input type="checkbox" name="%1$s[]" value="%2$s" id="%3$s" />',
				esc_attr( $this->_args['singular'] ),
				esc_attr( $item['slug'] ),
				esc_attr( $item['sanitized_plugin'] )
			);
		}

		public function column_install( $item ) {
			return sprintf('%1$s',$this->row_actions( $this->get_row_actions( $item ), true ));
		}

		public function column_reinstall( $item ) {
			$wpl_items_fee = WPL_price();
			$wpl_items_free = WPL_price( 'free' );
			if ( in_array( $item['slug'], array_merge( $wpl_items_free,$wpl_items_fee ) ) ) {
				if ($item['itype'] === 'theme') {
					return sprintf( wpl_install_update( 're_'.$item['slug'], $item['slug'], $item['itype'], 're_spin_'.$item['slug'], 'Re-install' ) );
				} elseif ($item['itype'] === 'plugin'){
					return sprintf( wpl_install_update( 're_'.$item['slug'], $item['slug'], $item['itype'], 're_spin_'.$item['slug'], 'Re-install' ) );
				} else {
					return '<button style="cursor: not-allowed;" class="button-primary" disabled>Re-install</button>';
				}
			} else {
				return '<button style="cursor: not-allowed;" class="button-primary" disabled>Re-install</button>';
			}
		}

		public function column_version( $item ) {
			$output = array();
			if ( $this->wplicense->is_plugin_installed( $item['slug'] ) ) {
				$installed = ! empty( $item['installed_version'] ) ? $item['installed_version'] : _x( 'unknown', 'as in: "version nr unknown"', 'wplicense' );
				$color = 'font-weight: bold';
				if ( ! empty( $item['minimum_version'] ) && $this->wplicense->does_plugin_require_update( $item['slug'] ) ) {
					$color = ' color: #ff0000; font-weight: bold;';
				}
				$output[] = sprintf(
					'<p><span style="min-width: 32px; text-align: left; float: left;%1$s">%2$s</span>'. '</p>',
					$color,
					$installed
				);
			} else {
				if ( ! empty( $item['available_version'] ) ) {
					$color = 'font-weight: bold';
					$available = $item['available_version'];
					if (is_dir(WPL_PATH_THEME.$item['slug'])){
						$wp_theme = wp_get_theme($item['slug']);
						if ( version_compare( $wp_theme->get('Version'), $item['available_version'], '<' ) ) {
							$color = ' color: #ff0000; font-weight: bold;';
							$available = $wp_theme->get('Version');
						}
					} else {
						if ( ! empty( $item['minimum_version'] ) && version_compare( $item['available_version'], $item['minimum_version'], '>=' ) ) {
							$color = ' color: #71C671; font-weight: bold;';
						}
					}
					$output[] = sprintf(
						'<p><span style="min-width: 32px; text-align: left; float: left;%1$s">%2$s</span>' . '</p>',
						$color,
						$available
					);
				}
			}
			if ( empty( $output ) ) {
				return '&nbsp;'; 
			} else {
				return implode( "\n", $output );
			}
		}

		public function no_items() {
			echo esc_html__( 'No plugins to install, update or activate.', 'wplicense' ) . ' <a href="' . esc_url( self_admin_url() ) . '"> ' . esc_html__( 'Return to the Dashboard', 'wplicense' ) . '</a>';
			echo '<style type="text/css">#adminmenu .wp-submenu li.current { display: none !important; }</style>';
		}

		public function get_columns() {
			$columns = array(
				'cb'     		=> '<input type="checkbox" />',
				'plugin' 		=> __( 'Name', 'wplicense' ),
				'slug' 	 		=> __( 'Slug', 'wplicense' ),//NEW
				'version' 	 	=> __( 'Version', 'wplicense' ),//NEW
				'time' 	 		=> __( 'Updated', 'wplicense' ),//NEW
				'itype'   		=> __( 'Type', 'wplicense' ),
				'install'  		=> __( 'Actions', 'wplicense' ),//NEW
			);

//			if ( 'all' === $this->view_context || 'update' === $this->view_context ) {//OLD
			if ( 'update' === $this->view_context ) {//NEW
				$columns['version'] = __( 'Version', 'wplicense' );
//				$columns['status']  = __( 'Status', 'wplicense' );
			}
			if ( 'installed' === $this->view_context ) {//NEW
				$columns['reinstall'] = __( 'Re-install', 'wplicense' );
			}
			return apply_filters( 'wplicense_table_columns', $columns );
		}

		protected function get_default_primary_column_name() {
			return 'plugin';
		}

		protected function get_primary_column_name() {
			if ( method_exists( 'WPL_List_Table', 'get_primary_column_name' ) ) {
				return parent::get_primary_column_name();
			} else {
				return $this->get_default_primary_column_name();
			}
		}

		protected function get_row_actions( $item ) {
			$actions      = array();
			$action_links = array();
//=
			if ( WPL_check( 'option', 'wpl_plugins_themes' ) ) {
				$WPL_items = WPL_get( 'option', 'wpl_plugins_themes', array() );
				foreach ( $WPL_items as $key => $value ){
					if (isset( $value['price'] ) && $value['price'] != '') {
						$wpl_get_slug[] = $value['slug'];
					}
				}
			}
//=
			if ( ! $this->wplicense->is_plugin_installed( $item['slug'] ) && isset($wpl_get_slug) && in_array( $item['slug'],$wpl_get_slug ) && defined( 'WPL_ACTIVATED' ) ) {
				$actions['install'] = __( 'Install %2$s', 'wplicense' );
			} else {
/*
				if ( false !== $this->wplicense->does_plugin_have_update( $item['slug'] ) && $this->wplicense->can_plugin_update( $item['slug'] ) && defined( 'WPL_ACTIVATED' ) ) {
					$actions['update'] = __( 'Update %2$s', 'wplicense' );
				}
				if ( $this->wplicense->can_plugin_activate( $item['slug'] ) && defined( 'WPL_ACTIVATED' ) ) {
					$actions['activate'] = __( 'Activate %2$s', 'wplicense' );
				}
*/
			}
			foreach ( $actions as $action => $text ) {
				$nonce_url = wp_nonce_url(
					add_query_arg(
						array(
							'plugin'           => urlencode( $item['slug'] ),
							'wplicense-' . $action => $action . '-plugin',
						),
						$this->wplicense->get_wplicense_url()
					),
					'wplicense-' . $action,
					'wplicense-nonce'
				);
				$action_links[ $action ] = sprintf(
					'<a href="%1$s">' . esc_html( $text ) . '</a>',
					esc_url( $nonce_url ),
					'<span class="screen-reader-text">' . esc_html( $item['sanitized_plugin'] ) . '</span>'
				);
			}
			$prefix = ( defined( 'WP_NETWORK_ADMIN' ) && WP_NETWORK_ADMIN ) ? 'network_admin_' : '';
			return apply_filters( "wplicense_{$prefix}plugin_action_links", array_filter( $action_links ), $item['slug'], $item, $this->view_context );
		}
		public function single_row( $item ) {
			parent::single_row( $item );
			do_action( "wplicense_after_plugin_row_{$item['slug']}", $item['slug'], $item, $this->view_context );
		}

		public function wp_plugin_update_row( $slug, $item ) {
			if ( empty( $item['upgrade_notice'] ) ) {
				return;
			}
			echo '
				<tr class="plugin-update-tr">
					<td colspan="', absint( $this->get_column_count() ), '" class="plugin-update colspanchange">
						<div class="update-message">',
							esc_html__( 'Upgrade message from the plugin author:', 'wplicense' ),
							' <strong>', wp_kses_data( $item['upgrade_notice'] ), '</strong>
						</div>
					</td>
				</tr>';
		}

		public function extra_tablenav( $which ) {
			if ( 'bottom' === $which ) {
				$this->wplicense->show_wplicense_version();
			}
		}

		public function get_bulk_actions() {
			$actions = array();
			if ( 'update' !== $this->view_context && 'activate' !== $this->view_context ) {
				if ( current_user_can( 'install_plugins' ) ) {
//					$actions['wplicense-bulk-installs'] = __( 'Install', 'wplicense' );//EDIT|NEW
				}
			}
			if ( 'install' !== $this->view_context ) {
				if ( current_user_can( 'update_plugins' ) ) {
					$actions['wplicense-bulk-update'] = __( 'Update', 'wplicense' );
				}
				if ( current_user_can( 'activate_plugins' ) ) {
//					$actions['wplicense-bulk-activate'] = __( 'Activate', 'wplicense' );
				}
			}
			return $actions;
		}

		public function process_bulk_actions() {
			if ( 'wplicense-bulk-install' === $this->current_action() || 'wplicense-bulk-update' === $this->current_action() ) {
				check_admin_referer( 'bulk-' . $this->_args['plural'] );
				$install_type = 'install';
				if ( 'wplicense-bulk-update' === $this->current_action() ) {
					$install_type = 'update';
				}
				$plugins_to_install = array();
				if ( empty( $_POST['plugin'] ) ) {
					if ( 'install' === $install_type ) {
						$message = __( 'No plugins were selected to be installed. No action taken.', 'wplicense' );
					} else {
						$message = __( 'No plugins were selected to be updated. No action taken.', 'wplicense' );
					}
					echo '<div id="message" class="error"><p>', esc_html( $message ), '</p></div>';
					return false;
				}
				if ( is_array( $_POST['plugin'] ) ) {
					$plugins_to_install = (array) $_POST['plugin'];
				} elseif ( is_string( $_POST['plugin'] ) ) {
					$plugins_to_install = explode( ',', $_POST['plugin'] );
				}
				$plugins_to_install = array_map( 'urldecode', $plugins_to_install );
				$plugins_to_install = array_map( array( $this->wplicense, 'sanitize_key' ), $plugins_to_install );
				foreach ( $plugins_to_install as $key => $slug ) {
					if ( ! isset( $this->wplicense->plugins[ $slug ] ) ) {
						unset( $plugins_to_install[ $key ] );
						continue;
					}
					if ( 'install' === $install_type && true === $this->wplicense->is_plugin_installed( $slug ) ) {
						unset( $plugins_to_install[ $key ] );
					}
					if ( 'update' === $install_type && false === $this->wplicense->is_plugin_updatetable( $slug ) ) {
						unset( $plugins_to_install[ $key ] );
					}
				}

				if ( empty( $plugins_to_install ) ) {
					if ( 'install' === $install_type ) {
						$message = __( 'No plugins are available to be installed at this time.', 'wplicense' );
					} else {
						$message = __( 'No plugins or themes are available to be updated at this time.', 'wplicense' );//EDIT
					}
					echo '<div id="message" class="error"><p>', esc_html( $message ), '</p></div>';
					return false;
				}
				$url = wp_nonce_url(
					$this->wplicense->get_wplicense_url(),
					'bulk-' . $this->_args['plural']
				);
				$_POST['plugin'] = implode( ',', $plugins_to_install );
				$method = ''; 
				$fields = array_keys( $_POST ); 
				if ( false === ( $creds = request_filesystem_credentials( esc_url_raw( $url ), $method, false, false, $fields ) ) ) {
					return true; 
				}
				if ( ! WP_Filesystem( $creds ) ) {
					request_filesystem_credentials( esc_url_raw( $url ), $method, true, false, $fields );
					return true;
				}
				$names      = array();
				$sources    = array(); 
				$file_paths = array(); 
				$to_inject  = array(); 
				foreach ( $plugins_to_install as $slug ) {
					$name   = $this->wplicense->plugins[ $slug ]['name'];
					$source = $this->wplicense->get_download_url( $slug );
					if ( ! empty( $name ) && ! empty( $source ) ) {
						$names[] = $name;
						switch ( $install_type ) {
							case 'install':
								$sources[] = $source;
								break;
							case 'update':
								$file_paths[]                 = $this->wplicense->plugins[ $slug ]['file_path'];
								$to_inject[ $slug ]           = $this->wplicense->plugins[ $slug ];
								$to_inject[ $slug ]['source'] = $source;
								break;
						}
					}
				}
				unset( $slug, $name, $source );
				$installer = new WPLICENSE_Bulk_Installer(
					new WPLICENSE_Bulk_Installer_Skin(
						array(
							'url'          => esc_url_raw( $this->wplicense->get_wplicense_url() ),
							'nonce'        => 'bulk-' . $this->_args['plural'],
							'names'        => $names,
							'install_type' => $install_type,
						)
					)
				);

				echo '<div class="wplicense">',
					'<h2 style="font-size: 23px; font-weight: 400; line-height: 29px; margin: 0; padding: 9px 15px 4px 0;">', esc_html( get_admin_page_title() ), '</h2>
					<div class="update-php" style="width: 100%; height: 98%; min-height: 850px; padding-top: 1px;">';
				add_filter( 'upgrader_source_selection', array( $this->wplicense, 'maybe_adjust_source_dir' ), 1, 3 );
				if ( 'wplicense-bulk-update' === $this->current_action() ) {
					$this->wplicense->inject_update_info( $to_inject );
					$installer->bulk_upgrade( $file_paths );
				} else {
					$installer->bulk_install( $sources );
				}
				remove_filter( 'upgrader_source_selection', array( $this->wplicense, 'maybe_adjust_source_dir' ), 1 );
				echo '</div></div>';
				return true;
			}

			if ( 'wplicense-bulk-activate' === $this->current_action() ) {
				check_admin_referer( 'bulk-' . $this->_args['plural'] );
				if ( empty( $_POST['plugin'] ) ) {
					echo '<div id="message" class="error"><p>', esc_html__( 'No plugins were selected to be activated. No action taken.', 'wplicense' ), '</p></div>';
					return false;
				}
				$plugins = array();
				if ( isset( $_POST['plugin'] ) ) {
					$plugins = array_map( 'urldecode', (array) $_POST['plugin'] );
					$plugins = array_map( array( $this->wplicense, 'sanitize_key' ), $plugins );
				}
				$plugins_to_activate = array();
				$plugin_names        = array();
				foreach ( $plugins as $slug ) {
					if ( $this->wplicense->can_plugin_activate( $slug ) ) {
						$plugins_to_activate[] = $this->wplicense->plugins[ $slug ]['file_path'];
						$plugin_names[]        = $this->wplicense->plugins[ $slug ]['name'];
					}
				}
				unset( $slug );
				if ( empty( $plugins_to_activate ) ) {
					echo '<div id="message" class="error"><p>', esc_html__( 'No plugins are available to be activated at this time.', 'wplicense' ), '</p></div>';

					return false;
				}
				$activate = activate_plugins( $plugins_to_activate );
				if ( is_wp_error( $activate ) ) {
					echo '<div id="message" class="error"><p>', wp_kses_post( $activate->get_error_message() ), '</p></div>';
				} else {
					$count        = count( $plugin_names ); 
					$plugin_names = array_map( array( 'WPLICENSE_Utils', 'wrap_in_strong' ), $plugin_names );
					$last_plugin  = array_pop( $plugin_names ); 
					$imploded     = empty( $plugin_names ) ? $last_plugin : ( implode( ', ', $plugin_names ) . ' ' . esc_html_x( 'and', 'plugin A *and* plugin B', 'wplicense' ) . ' ' . $last_plugin );
					printf( 
						'<div id="message" class="updated"><p>%1$s %2$s.</p></div>',
						esc_html( _n( 'The following plugin was activated successfully:', 'The following plugins were activated successfully:', $count, 'wplicense' ) ),
						$imploded
					);
					$recent = (array) get_option( 'recently_activated' );
					foreach ( $plugins_to_activate as $plugin => $time ) {
						if ( isset( $recent[ $plugin ] ) ) {
							unset( $recent[ $plugin ] );
						}
					}
					update_option( 'recently_activated', $recent );
				}
				unset( $_POST ); 
				return true;
			}
			return false;
		}

		public function prepare_items() {
			$per_page    = $this->get_items_per_page( 'wpl_plugins_per_page', 15 );
			$current_page = $this->get_pagenum();
			$total_items = count( $this->wplicense->plugins );
			$this->set_pagination_args( array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil($total_items/$per_page)
			) );
			$columns               = $this->get_columns(); 
			$hidden                = array(); 
			$sortable              = array(); 
			$primary               = $this->get_primary_column_name(); 
			$this->_column_headers = array( $columns, $hidden, $sortable, $primary ); 
			if ( 'wplicense-bulk-activate' === $this->current_action() ) {
				$this->process_bulk_actions();
			}
//====================
			$data = apply_filters( 'wplicense_table_data_items', $this->_gather_plugin_data() );
			$searchkey = (!empty($_REQUEST['s'])) ? htmlentities(strtolower($_REQUEST['s'])) : '';
			$result_data=array();
			if($searchkey!=''){
				foreach($data as $key=>$value){
					$temp=array();
					foreach($value as $keys=>$values){
						array_push($temp,strtolower($values));
					}
					$input = preg_quote($searchkey, '~'); // don't forget to quote input string!
					$result_filter_s = preg_grep('~' . $input . '~', $temp);
					if($result_filter_s!=null){
						array_push($result_data,$value);
					}
				}
				$data =$result_data;
			}
//====================
			$this->items = array_slice($data,(($current_page-1)*$per_page),$per_page);//NEW&ED
		}

		protected function _get_plugin_data_from_name( $name, $data = 'slug' ) {
			_deprecated_function( __FUNCTION__, 'WPLICENSE 2.5.0', 'WPLU_Plugin_Activation::_get_plugin_data_from_name()' );
			return $this->wplicense->_get_plugin_data_from_name( $name, $data );
		}
	}
}


if ( ! class_exists( 'TGM_Bulk_Installer' ) ) {
	class TGM_Bulk_Installer {
	}
}
if ( ! class_exists( 'TGM_Bulk_Installer_Skin' ) ) {
	class TGM_Bulk_Installer_Skin {
	}
}

add_action( 'admin_init', 'wplicense_load_bulk_installer' );
if ( ! function_exists( 'wplicense_load_bulk_installer' ) ) {
	function wplicense_load_bulk_installer() {
		if ( ! isset( $GLOBALS['wplicense'] ) ) {
			return;
		}
		$wplicense_instance = call_user_func( array( get_class( $GLOBALS['wplicense'] ), 'get_instance' ) );
		if ( isset( $_GET['page'] ) && $wplicense_instance->menu === $_GET['page'] ) {
			if ( ! class_exists( 'Plugin_Upgrader', false ) ) {
				require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			}
			if ( ! class_exists( 'WPLICENSE_Bulk_Installer' ) ) {
				class WPLICENSE_Bulk_Installer extends Plugin_Upgrader {
					public $result;
					public $bulk = false;
					protected $wplicense;
					protected $clear_destination = false;
					public function __construct( $skin = null ) {
						$this->wplicense = call_user_func( array( get_class( $GLOBALS['wplicense'] ), 'get_instance' ) );
						parent::__construct( $skin );
						if ( isset( $this->skin->options['install_type'] ) && 'update' === $this->skin->options['install_type'] ) {
							$this->clear_destination = true;
						}
						if ( $this->wplicense->is_automatic ) {
							$this->activate_strings();
						}
						add_action( 'upgrader_process_complete', array( $this->wplicense, 'populate_file_path' ) );
					}

					public function activate_strings() {
						$this->strings['activation_failed']  = __( 'Plugin activation failed.', 'wplicense' );
						$this->strings['activation_success'] = __( 'Plugin activated successfully.', 'wplicense' );
					}

					public function run( $options ) {
						$result = parent::run( $options );
						if ( $this->wplicense->is_automatic ) {
							if ( 'update' === $this->skin->options['install_type'] ) {
								$this->upgrade_strings();
							} else {
								$this->install_strings();
							}
						}
						return $result;
					}

					public function bulk_install( $plugins, $args = array() ) {
						add_filter( 'upgrader_post_install', array( $this, 'auto_activate' ), 10 );
						$defaults    = array(
							'clear_update_cache' => true,
						);
						$parsed_args = wp_parse_args( $args, $defaults );
						$this->init();
						$this->bulk = true;
						$this->install_strings(); 
						$this->skin->header();
						$res = $this->fs_connect( array( WP_CONTENT_DIR, WP_PLUGIN_DIR ) );
						if ( ! $res ) {
							$this->skin->footer();
							return false;
						}
						$this->skin->bulk_header();
						$maintenance = ( is_multisite() && ! empty( $plugins ) );
						if ( $maintenance ) {
							$this->maintenance_mode( true );
						}
						$results = array();
						$this->update_count   = count( $plugins );
						$this->update_current = 0;
						foreach ( $plugins as $plugin ) {
							$this->update_current++;
							$result = $this->run(
								array(
									'package'           => $plugin, 
									'destination'       => WP_PLUGIN_DIR,
									'clear_destination' => false, 
									'clear_working'     => true,
									'is_multi'          => true,
									'hook_extra'        => array(
										'plugin' => $plugin,
									),
								)
							);
							$results[ $plugin ] = $this->result;
							if ( false === $result ) {
								break;
							}
						} 
						$this->maintenance_mode( false );
						do_action( 'upgrader_process_complete', $this, array(
							'action'  => 'install', 
							'type'    => 'plugin',
							'bulk'    => true,
							'plugins' => $plugins,
						) );
						$this->skin->bulk_footer();
						$this->skin->footer();
						remove_filter( 'upgrader_post_install', array( $this, 'auto_activate' ), 10 );
						wp_clean_plugins_cache( $parsed_args['clear_update_cache'] );
						return $results;
					}

					public function bulk_upgrade( $plugins, $args = array() ) {
						add_filter( 'upgrader_post_install', array( $this, 'auto_activate' ), 10 );
						$result = parent::bulk_upgrade( $plugins, $args );
						remove_filter( 'upgrader_post_install', array( $this, 'auto_activate' ), 10 );
						return $result;
					}

					public function auto_activate( $bool ) {
						if ( $this->wplicense->is_automatic ) {
							wp_clean_plugins_cache();
							$plugin_info = $this->plugin_info();
							if ( ! is_plugin_active( $plugin_info ) ) {
								$activate = activate_plugin( $plugin_info );
								$this->strings['process_success'] = $this->strings['process_success'] . "<br />\n";
								if ( is_wp_error( $activate ) ) {
									$this->skin->error( $activate );
									$this->strings['process_success'] .= $this->strings['activation_failed'];
								} else {
									$this->strings['process_success'] .= $this->strings['activation_success'];
								}
							}
						}
						return $bool;
					}
				}
			}

			if ( ! class_exists( 'WPLICENSE_Bulk_Installer_Skin' ) ) {
				class WPLICENSE_Bulk_Installer_Skin extends Bulk_Upgrader_Skin {
					public $plugin_info = array();
					public $plugin_names = array();
					public $i = 0;
					protected $wplicense;
					public function __construct( $args = array() ) {
						$this->wplicense = call_user_func( array( get_class( $GLOBALS['wplicense'] ), 'get_instance' ) );
						$defaults = array(
							'url'          => '',
							'nonce'        => '',
							'names'        => array(),
							'install_type' => 'install',
						);
						$args     = wp_parse_args( $args, $defaults );
						$this->plugin_names = $args['names'];
						parent::__construct( $args );
					}

					public function add_strings() {
						if ( 'update' === $this->options['install_type'] ) {
							parent::add_strings();
							$this->upgrader->strings['skin_before_update_header'] = __( 'Updating Plugin %1$s (%2$d/%3$d)', 'wplicense' );
						} else {
							$this->upgrader->strings['skin_update_failed_error'] = __( 'An error occurred while installing %1$s: <strong>%2$s</strong>.', 'wplicense' );
							$this->upgrader->strings['skin_update_failed'] = __( 'The installation of %1$s failed.', 'wplicense' );
							if ( $this->wplicense->is_automatic ) {
								$this->upgrader->strings['skin_upgrade_start'] = __( 'The installation and activation process is starting. This process may take a while on some hosts, so please be patient.', 'wplicense' );
								$this->upgrader->strings['skin_update_successful'] = __( '%1$s installed and activated successfully.', 'wplicense' ) . ' <a href="#" class="hide-if-no-js" onclick="%2$s"><span>' . esc_html__( 'Show Details', 'wplicense' ) . '</span><span class="hidden">' . esc_html__( 'Hide Details', 'wplicense' ) . '</span>.</a>';
								$this->upgrader->strings['skin_upgrade_end']       = __( 'All installations and activations have been completed.', 'wplicense' );
								$this->upgrader->strings['skin_before_update_header'] = __( 'Installing and Activating Plugin %1$s (%2$d/%3$d)', 'wplicense' );
							} else {
								$this->upgrader->strings['skin_upgrade_start'] = __( 'The installation process is starting. This process may take a while on some hosts, so please be patient.', 'wplicense' );
								$this->upgrader->strings['skin_update_successful'] = esc_html__( '%1$s installed successfully.', 'wplicense' ) . ' <a href="#" class="hide-if-no-js" onclick="%2$s"><span>' . esc_html__( 'Show Details', 'wplicense' ) . '</span><span class="hidden">' . esc_html__( 'Hide Details', 'wplicense' ) . '</span>.</a>';
								$this->upgrader->strings['skin_upgrade_end']       = __( 'All installations have been completed.', 'wplicense' );
								$this->upgrader->strings['skin_before_update_header'] = __( 'Installing Plugin %1$s (%2$d/%3$d)', 'wplicense' );
							}
						}
					}

					public function before( $title = '' ) {
						if ( empty( $title ) ) {
							$title = esc_html( $this->plugin_names[ $this->i ] );
						}
						parent::before( $title );
					}

					public function after( $title = '' ) {
						if ( empty( $title ) ) {
							$title = esc_html( $this->plugin_names[ $this->i ] );
						}
						parent::after( $title );
						$this->i++;
					}

					public function bulk_footer() {
						parent::bulk_footer();
						wp_clean_plugins_cache();
						$this->wplicense->show_wplicense_version();
						$update_actions = array();
						if ( $this->wplicense->is_wplicense_complete() ) {
							echo '<style type="text/css">#adminmenu .wp-submenu li.current { display: none !important; }</style>';
							$update_actions['dashboard'] = sprintf(
								esc_html( $this->wplicense->strings['complete'] ),
								'<a href="' . esc_url( self_admin_url() ) . '">' . esc_html__( 'Return to the Dashboard', 'wplicense' ) . '</a>'
							);
						} else {
							$update_actions['wplicense_page'] = '<a href="' . esc_url( $this->wplicense->get_wplicense_url() ) . '" target="_parent">' . esc_html( $this->wplicense->strings['return'] ) . '</a>';
						}
						$update_actions = apply_filters( 'wplicense_update_bulk_plugins_complete_actions', $update_actions, $this->plugin_info );
						if ( ! empty( $update_actions ) ) {
							$this->feedback( implode( ' | ', (array) $update_actions ) );
						}
					}

					public function before_flush_output() {
						_deprecated_function( __FUNCTION__, 'WPLICENSE 2.5.0', 'Bulk_Upgrader_Skin::flush_output()' );
						$this->flush_output();
					}

					public function after_flush_output() {
						_deprecated_function( __FUNCTION__, 'WPLICENSE 2.5.0', 'Bulk_Upgrader_Skin::flush_output()' );
						$this->flush_output();
						$this->i++;
					}
				}
			}
		}
	}
}

if ( ! class_exists( 'WPLICENSE_Utils' ) ) {
	class WPLICENSE_Utils {
		public static $has_filters;
		public static function wrap_in_em( $string ) {
			return '<em>' . wp_kses_post( $string ) . '</em>';
		}
		public static function wrap_in_strong( $string ) {
			return '<strong>' . wp_kses_post( $string ) . '</strong>';
		}
		public static function validate_bool( $value ) {
			if ( ! isset( self::$has_filters ) ) {
				self::$has_filters = extension_loaded( 'filter' );
			}
			if ( self::$has_filters ) {
				return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
			} else {
				return self::emulate_filter_bool( $value );
			}
		}

		protected static function emulate_filter_bool( $value ) {
			static $true  = array(
				'1',
				'true', 'True', 'TRUE',
				'y', 'Y',
				'yes', 'Yes', 'YES',
				'on', 'On', 'ON',
			);
			static $false = array(
				'0',
				'false', 'False', 'FALSE',
				'n', 'N',
				'no', 'No', 'NO',
				'off', 'Off', 'OFF',
			);

			if ( is_bool( $value ) ) {
				return $value;
			} elseif ( is_int( $value ) && ( 0 === $value || 1 === $value ) ) {
				return (bool) $value;
			} elseif ( ( is_float( $value ) && ! is_nan( $value ) ) && ( (float) 0 === $value || (float) 1 === $value ) ) {
				return (bool) $value;
			} elseif ( is_string( $value ) ) {
				$value = trim( $value );
				if ( in_array( $value, $true, true ) ) {
					return true;
				} elseif ( in_array( $value, $false, true ) ) {
					return false;
				} else {
					return false;
				}
			}
			return false;
		}
	} 
}