<?php

class WPL_Page_Updates {
	public function __construct(){
		global $WPL_VERSION;
		if ( !isset($WPL_VERSION) || ( isset($WPL_VERSION) && version_compare( $WPL_VERSION, WPL_VERSION,'<=' ) ) ) {
			add_action( 'admin_menu', array( $this, 'add_menu' ) );
		}
	}
	public function updates_page(){
		global $WPL_path;
		$wpl_installed = WPL_installed();
		$items = array();
		if ( ($_SERVER['REQUEST_METHOD'] === 'POST') && isset($_POST['wpl_item_check_updates']) && wp_verify_nonce($_POST['wpl_item_check_updates'],'wpl_item_check_updates') ) {
			$wpl_main_file = array();
			foreach ( $_POST as $key => $val ) {
				if ( strpos( $key,'_php' ) ) {
					$wpl_main_file[] = str_replace( '_php', '.php', $key );
				}
				if ( in_array( $key, $WPL_path ) ) {
					$wpl_main_file[] = $key;
				}
			}
			update_option( 'wpl_check_updates', $wpl_main_file, '', 'yes' );
		}
		$wpl_checked_update = WPL_get( 'option', 'wpl_check_updates', array() );
		echo '<div class="wrap">';
		echo '<h2>WPLicense Updates</h2><p>';
		?>
		<hr>
		<div style="background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">
			<p><span style="color: #ff0000;">Selected items will not be automatically updated by the <strong>WPLicense Updates</strong> plugin.</span></p>
		</div>
		<hr>
		<script>
			function confirm_save(){
				var x = confirm("Selected items will not be automatically updated by the WPLicense Updates plugin?");
				if (x)
					return true;
				else
					return false;
			}
		</script>
		<?php
		echo '<div style="background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">';
		echo '<table class="form-table">';
		echo '<tr valign="top">';
		echo '<td><fieldset>';
		echo '<form action="admin.php?page=wpl-updates" method="post">';
		foreach ($wpl_installed as $key => $val) {
			if ( in_array( $key, $WPL_path ) ) {
				$items[] = $key;
			}
		}
		echo '<strong>All ( '. count( $wpl_checked_update ). '/'. count( $items ). ' ) items selected</strong></p>';
		echo '(Name=>Slug=>Version)</p><hr>';
		echo '<link rel="stylesheet" type="text/css" href="'.plugins_url('libraries/css/style_button.css',__FILE__).'">';
		echo '<script type="text/javascript" src="'.plugins_url('libraries/js/style_button.js',__FILE__).'"></script>';
		echo '<label class="switch"><input type="checkbox" onClick="toggle(this)"';
		if ( count( $wpl_checked_update ) == count( $items ) ) {
			echo 'checked';
		}
		echo '><span class="slider round"></span></label><hr>';
		foreach ( $wpl_installed as $key => $val ) {
			if ( in_array( $key, $WPL_path ) ) {
				echo '<input type="checkbox" name="'.$key.'"';
				if (!empty($wpl_checked_update) && in_array ($key,$wpl_checked_update)){
					echo 'checked ';
				}
				echo '><label for="'.$key.'">';
				echo $val["Name"]." => ".explode('/',$key)[0]." => ".$val["Version"];
				echo '</label><br>';
			}
		}
		echo '</fieldset></td>';
		echo '</tr>';
		echo '</table>';
		echo '<hr>';
		wp_nonce_field('wpl_item_check_updates','wpl_item_check_updates');
		echo '<span class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes" onclick="return confirm_save();"/></span>';
		echo '</form> ';
		echo '<p>';
		echo '</div>';
		echo '</div>';
	}

	public function add_menu(){
		add_submenu_page( 'admin.php?page=wpl_activation', 'WPLicense Updates', 'Updates', 'manage_options', 'wpl-updates', array( $this, 'updates_page' ) );
	}
}
new WPL_Page_Updates();

class WPL_Updates {
	public function __construct(){
		global $WPL_VERSION;
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'wpl_updates_plugin' ), 10000, 1 );
		add_filter( 'pre_set_site_transient_update_themes', array( $this, 'wpl_updates_theme' ), 10000, 1 );
		if ( empty( $WPL_VERSION ) || version_compare( $WPL_VERSION, WPL_VERSION, '<=' ) ) {
			add_action( 'admin_init', array( $this, 'wpl_check_updates' ) );
		}
		add_filter( 'wp_prepare_themes_for_js' , array( $this, 'wpl_update_message_theme' ) );
	}

	public function wpl_updates_theme( $transient ) {
		global $WPL_items, $WPL_path;
		$WPL_categories = WPL_categories();
		foreach( $WPL_categories as $main_file => $value ) {
			$token = wpl_token_download( $main_file );
			if ( !empty( $WPL_items[$main_file]['version'] ) ) {
				$item_version = $WPL_items[$main_file]['version'];
			}
			$item_url = !empty( $WPL_items[$main_file]['url'] ) ? $WPL_items[$main_file]['url'] : '';
			$wpl_version = WPL_version( $main_file );
			if ( in_array( $main_file, $WPL_path ) && !WPL_checked( $main_file ) ) {
				if ( !empty($item_version) && !empty($wpl_version) ) {
					if ( version_compare( $wpl_version, $item_version, '>=' ) ) {
						if ( isset( $transient->response[$main_file] ) ) unset( $transient->response[$main_file] );
					} elseif ( version_compare( $wpl_version, $item_version, '<' ) ) {
						$transient->response[$main_file]['url'] = $item_url;
						$transient->response[$main_file]['new_version'] = $item_version;
						$transient->response[$main_file]['package'] = WPL_BASE_API. WPL_ACTIVATION_EMAIL_GET. $token. '&version='. $item_version;
					}
				}
			}
		}
		return $transient;
	}

	public function wpl_updates_plugin( $transient ) {
		global $WPL_items, $WPL_path;
		$WPL_categories = WPL_categories();
		foreach( $WPL_categories as $main_file => $value ) {
			$slug = explode( '/',$main_file )[0];
			$token = wpl_token_download( $slug );
			if ( !empty( $WPL_items[$main_file]['version'] ) ) {
				$item_version = $WPL_items[$main_file]['version'];
			}
			$item_url = !empty( $WPL_items[$main_file]['url'] ) ? $WPL_items[$main_file]['url'] : '';
			$wpl_version = WPL_version( $main_file );
			if ( in_array( $main_file, $WPL_path ) && !WPL_checked( $main_file ) ) {
				if ( !empty( $item_version ) && !empty( $wpl_version ) ) {
					if ( version_compare( $wpl_version, $item_version, '>=' ) ) {
						if ( isset( $transient->response[$main_file] ) ) unset( $transient->response[$main_file] );
					} elseif ( version_compare( $wpl_version, $item_version, '<' ) ) {
						empty( $transient->response[$main_file] ) ? $transient->response[$main_file] = new stdClass : '';
						$transient->response[$main_file]->url = $item_url;
						$transient->response[$main_file]->slug = $slug;
						$transient->response[$main_file]->new_version = $item_version;
						$transient->response[$main_file]->package = WPL_BASE_API. WPL_ACTIVATION_EMAIL_GET. $token. '&version='. $item_version;
					}
				}
			}
		}
		return $transient;
	}

	public function wpl_check_updates(){
		global $WPL_path;
		$wpl_installed = WPL_installed();
		foreach ( $wpl_installed as $key => $val ) {
			if ( is_dir( WPL_PATH_THEME. $key ) ) {
				$WPL_PATH = WPL_PATH_THEME;
			} else {
				$WPL_PATH = dirname(plugin_dir_path( __DIR__ ) ).'/';
			}
			$WPL_SLUG = explode( '/', $key )[0];
			if ( in_array( $key, $WPL_path ) && !WPL_checked( $key ) ) {
				$wplhook = "in_plugin_update_message-$key";
				add_action( $wplhook, 'wpl_update_message_plugin', 10, 2 );
				$WPLChecker = WPL_Factory::buildChecker( WPL_BASE_API. WPL_ACTIVATION_EMAIL_GET. WPL_META. $WPL_SLUG, $WPL_PATH. $key, $WPL_SLUG, 2, WPL_PREFIX. $WPL_SLUG );
			}
		}
	}

	public function wpl_update_message_theme( $themes ) {
		global $wpl_slug_version, $WPL_path;
		$themes_installed = wp_get_themes();
		foreach ( $themes_installed as $key => $val ) {
			if ( is_dir( WPL_PATH_THEME. $key ) && !empty( $wpl_slug_version[$key] ) ) {
				$wp_theme = wp_get_theme($key);
				if ( version_compare( $wp_theme->get('Version'), $wpl_slug_version[$key], '<' ) ) {
					$out = sprintf(
					'<a style="color: #39b54a; font-weight: 700; text-decoration: none" href="%1$s" title="%2$s" class="edit">%3$s</a>',
					admin_url( 'admin.php?page=wpl-install&plugin_status=installed' ),
					esc_attr__( 'New version available.', 'wplicense' ),
					esc_html__( 'Update now', 'wplicense' ));
				} else {
					$out = '';
				}
				if ( in_array( $key, $WPL_path ) && !WPL_checked( $key ) ) {
					$outs = '<strong>Thanks for using the WPLicense service.</strong><p/>';
				} else {
					$outs = '';
				}
				$OUT = !empty($out) ? '<p/><div class="notice notice-success notice-large">'.$out.'</div>' : '';
				$OUTS = !empty($outs) ? '<p/>'.$outs : '';
				if ( ! isset( $themes[ $key ]['tags'] ) ) {
					$themes[ $key ]['tags'] = '';
				}
				if ( ! isset( $themes[ $key ]['update'] ) ) {
					$themes[ $key ]['update'] = '';
				}
				$themes[ $key ]['tags'] .= $OUT;
				$themes[ $key ]['update'] .= $OUTS;
			}
		}
		return $themes;
	}
}
new WPL_Updates();