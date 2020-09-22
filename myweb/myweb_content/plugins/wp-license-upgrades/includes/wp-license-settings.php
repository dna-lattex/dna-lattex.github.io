<?php

class WPL_Page_Settings {
	public function __construct() {
		global $WPL_VERSION;
		if ( !isset($WPL_VERSION) || ( version_compare( $WPL_VERSION, WPL_VERSION,'<=' ) ) ) {
			add_action( 'admin_menu', array( $this, 'add_menu' ) );
		}
		add_action( 'wp_ajax_wpl_click_activate', array( $this, 'activate' ) );
		add_action( 'wp_ajax_nopriv_wpl_click_activate', array( $this, 'activate' ) );
		if ( WPL_check( 'option', 'td_011_temp' ) ) {
			@update_option( 'td_011', WPL_get( 'option', 'td_011_temp' ) );
			@activate_plugins( 'td-composer/td-composer.php' );
			@delete_option( 'td_011_temp' );
		}
	}

	public function activate() {
		global $WPL_Activate;
		$wpl_page_activate = WPL_get( 'transient', 'wpl_activate', array() );
		$activate_default = array();
		foreach ( $wpl_page_activate as $value ) {
			if ( !empty( $value["slug"] ) && !empty( $value["action"] ) && $value["action"] == "default" ) {
				$activate_default[] = $value["slug"];
			}
		}
		if ( isset( $_POST['slug'] ) && isset( $_POST['action'] ) && $_POST['action'] == 'wpl_click_activate' ) {
			if ( !in_array( $_POST['slug'], $activate_default ) ) {
				if ( $_POST['slug'] == 'wordfence' ) {
					$WPL_Activate->wordfence( $_POST['slug'], true );
				} elseif ( $_POST['slug'] == 'swift-performance' ) {
					$WPL_Activate->swift( $_POST['slug'], true );
				} elseif ( $_POST['slug'] == 'astra-pro-sites' ) {
					$WPL_Activate->astra( $_POST['slug'], true );
				} elseif ( $_POST['slug'] == 'Newspaper' ) {
					$WPL_Activate->Newspaper( $_POST['slug'], true );
				} else {
					$WPL_Activate->unsuccessful( $_POST['slug'] );
				}
			} else {
				$WPL_Activate->action( $_POST['slug'], true );
			}
		}
	}

	private function tab_settings( $name, $title, $slug, $note, $fee, $disabled ) {
		echo '<table class="form-table">';
		echo '<tr valign="top">';
		echo '<th scope="row"><label for='.$name.'>'.__("$title", 'wp-license-upgrades' ).'</label></th>';
		echo '<td><fieldset>';
		echo '<link rel="stylesheet" type="text/css" href="'.plugins_url('libraries/css/style_button.css',__FILE__).'">';
		echo '<label class="switch"><input type="checkbox" name="'.$slug.'"';
		if ( !empty( $disabled ) ) {
			?>onclick="alert('Unavailable. Under Maintenance!');checked = false"<?php
		}
		if ( !defined( 'WPL_SUBSCRIPTION' ) && !empty( $fee ) ) {
			?>onclick="alert('You must be a Vip member!');checked = false"<?php
		}
		if ( WPL_settings( str_ireplace( "wp_license_", '', $slug ) ) ) {
			echo 'checked';
		}
		echo '><span class="slider round"></span></label>';
		if ( WPL_check( 'option', "$slug" ) && WPL_get( 'option', "$slug" ) == 'enabled' ) {
			echo "<span class='dashicons dashicons-yes' style='color: #66ab03;vertical-align: text-top;'></span>";
		} else {
			echo "<span class='dashicons dashicons-no' style='color: #ca336c;vertical-align: text-top;'></span>";
		}
		echo "<span>$note</span>";
		echo '</fieldset></td>';
		echo '</tr>';
		echo '</table>';
		echo '<hr>';
	}

	public function script_activate( $slug, $id, $spin, $name ) {
		?><script>
		jQuery(document).ready(function(){
			jQuery('#<?php echo $id;?>').click(function(){
				var x = confirm("Are you sure?");
				if (x == true) {
					jQuery.ajax({
						type : "POST",
						dataType : "html",
						url : '<?php echo admin_url('admin-ajax.php');?>',
						data : {
							action: "wpl_click_activate",
							slug: "<?php echo $slug;?>",
						},
						beforeSend: function(){
							window.onbeforeunload = function(event) {
							  event.returnValue = "Actions may not complete if you navigate away from this page.";
							};
							jQuery('#<?php echo $spin;?>').show();
						},
						success: function(response){
							window.onbeforeunload = null;
							jQuery('#<?php echo $spin;?>').hide();
							jQuery('.display-result').html(response);
						},
						error: function( jqXHR, textStatus, errorThrown ){
							jQuery('#<?php echo $spin;?>').hide();
						}
					});
				}
			});
		});
		</script>
		<button type="submit" name="submit" value="<?php echo $slug ?>" class="button-primary" id="<?php echo $id ?>"><?php echo $name ?></button>
		<img id="<?php echo $spin ?>" src="<?php echo esc_attr(WPL_SPIN); ?>" alt="..." style="vertical-align:bottom; max-height: 30px; display:none">
		<span class="display-result" style="display:none"></span>
		<?php
	}

	private function tab_activates( $id, $name, $slug, $get, $path, $paths, $required, $note, $disabled ) {
		echo '<table class="form-table">';
		echo '<tr valign="top">';
		echo '<th scope="row"><label>'.$name.'</label></th>';
		echo '<td><fieldset>';
		if ( empty( $disabled ) ) {
			if ( !empty( $path ) ) {
				if ( empty( $paths ) ) {
					if ( strpos( $path,'.php' ) && is_plugin_active( $path ) || strpos( $path,'.css' ) && get_template() == $slug ) {
						$this->script_activate( $slug, $id, 'spin_'.$id, 'Activate' );
					} else {
						?><button type="button" class="button-primary" onclick="alert('<?php echo"$required : must be installed and enabled!"; ?>')">Activate</button>
						<img id="<?php echo 'spin_'.$id ?>" src="<?php echo esc_attr(WPL_SPIN); ?>" alt="..." style="vertical-align:bottom; max-height: 30px; display:none"><?php
					}
				} else {
					if ( ( strpos( $path,'.php' ) && is_plugin_active( $path ) || strpos( $path,'.css' ) && get_template() == $slug ) && is_plugin_active( $paths ) ) {
						$this->script_activate( $slug, $id, 'spin_'.$id, 'Activate' );
					} else {
						?><button type="button" class="button-primary" onclick="alert('<?php echo"$required : must be installed and enabled!"; ?>')">Activate</button>
						<img id="<?php echo 'spin_'.$id ?>" src="<?php echo esc_attr(WPL_SPIN); ?>" alt="..." style="vertical-align:bottom; max-height: 30px; display:none"><?php
					}
				}
			} else {
				$this->script_activate( $slug, $id, 'spin_'.$id, 'Activate' );
			}
		} else {
			?><button type="button" class="button-primary" onclick="alert('Unavailable. Under Maintenance!')">Activate</button>
			<img id="<?php echo 'spin_'.$id ?>" src="<?php echo esc_attr(WPL_SPIN); ?>" alt="..." style="vertical-align:bottom; max-height: 30px; display:none"><?php
		}
		if ( WPL_check( 'transient', $get ) ) {
			echo "<span class='dashicons dashicons-yes' style='color: #66ab03;vertical-align: text-top;'></span>";
		} else {
			echo "<span class='dashicons dashicons-no' style='color: #ca336c;vertical-align: text-top;'></span>";
		}
		echo '<span>'.$note.'</span>';
		echo '</fieldset></td>';
		echo '</tr>';
		echo '</table>';
		echo '<hr>';
	}

	private function admin_tab( $current = 'settings' ) {
		$tabs = array( 'settings' => 'Settings', 'activate' => 'Activate' ); 
		echo '<h2 class="nav-tab-wrapper">';
		foreach( $tabs as $tab => $name ) {
			$class = ( $tab == $current ) ? ' nav-tab-active' : '';
			echo "<a class='nav-tab$class' href='?page=wpl-settings&tab=$tab'>$name</a>";
		}
		echo '</h2>';
	}

	public function settings_page() {
		global $pagenow, $WPL_Request;
		?>
		<div class="wrap">
			<h2>WPLicense Settings</h2>
			<?php if ( isset ( $_GET['tab'] ) ) $this->admin_tab( $_GET['tab'] ); else $this->admin_tab( 'settings' ); ?>
			<div id="poststuff">
				<?php
				if ( $pagenow == 'admin.php' && $_GET['page'] == 'wpl-settings' ) {
					if ( isset ( $_GET['tab'] ) ) $tab = $_GET['tab']; 
					else $tab = 'settings'; 
					echo '<table class="form-table">';
					switch ( $tab ) {
						case 'settings' :
							if ( ( $_SERVER['REQUEST_METHOD'] === 'POST' ) && isset( $_POST['wp_license_settings'] ) && wp_verify_nonce( $_POST['wp_license_settings'], 'wp_license_settings' ) ) {
								$wpl_settings = array();
								foreach ( $_POST as $key => $val ) {
									if ( $key != 'wp_license_settings' && $key != '_wp_http_referer' && $key != 'submit' ) {
										$wpl_settings[] = $key;
									}
								}
								update_option( 'wpl_settings', $wpl_settings, '', 'yes' );
							}
							?>
							<hr>
							<div style="background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">
								<p> <strong>Select the function you want to use then click Save Changes.</strong></p>
							</div>
							<hr>
							<?php
							echo '<form action="admin.php?page=wpl-settings" method="post">';
							if ( WPL_check( 'transient', 'wpl_settings' ) ) {
								$wpl_page_settings = WPL_get( 'transient', 'wpl_settings' );
								if ( !empty( $wpl_page_settings ) ) {
									foreach ( $wpl_page_settings as $value ) {
										if ( isset($value["name"]) && isset($value["title"]) && isset($value["slug"]) && isset($value["note"]) && isset($value["fee"]) && isset($value["disabled"]) ) {
											$this->tab_settings( $value["name"], $value["title"], $value["slug"], base64_decode( $value["note"] ), $value["fee"], $value["disabled"] );
										}
									}
								} else {
									echo '<p><span style="color: #ff0000;"><strong>Can not connect to server.</strong></span><hr></p>';
								}
							} elseif ( ( time() - WPL_DATE_UPDATE ) >= 300 ) {
								$WPL_Request->meta_data();
							} else {
								echo '<p><span style="color: #ff0000;"><strong>Can not connect to server.</strong></span><hr></p>';
							}
							wp_nonce_field( 'wp_license_settings', 'wp_license_settings' );
							echo '<span class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes" /></span>';
							echo '</form> ';
							echo '<p>';
							echo '<hr>';
							break;
						case 'activate' :
							?>
							<hr>
							<div style="background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">
								<p><strong>How to Activate the License? Simply click on "Activate" button.</strong><br>
								<span style="color: #ff0000;">(Before proceeding make sure you have the required theme/plugin installed and activated.)</span></p>
							</div>
							<hr>
							<?php
							if ( WPL_check( 'transient', 'wpl_activate' ) ) {
								$wpl_page_activate = WPL_get( 'transient', 'wpl_activate' );
								if ( !empty( $wpl_page_activate ) ) {
									echo '<style>.form-table th {width: 225px;}</style>';
									foreach ( $wpl_page_activate as $value ) {
										$paths = isset( $value["paths"] ) ? $value["paths"] : '';
										if ( isset($value["name"]) && isset($value["slug"]) && isset($value["get"]) && isset($value["path"]) && isset($value["required"]) && isset($value["note"]) && isset($value["disabled"]) ) {
											$this->tab_activates( $value["slug"], $value["name"], $value["slug"], $value["get"], $value["path"], $paths, $value["required"], $value["note"], $value["disabled"] );
										}
									}
								} else {
									echo '<p><span style="color: #ff0000;"><strong>Can not connect to server.</strong></span><hr></p>';
								}
							} elseif ( ( time() - WPL_DATE_UPDATE ) >= 300 ) {
								$WPL_Request->meta_data();
							} else {
								echo '<p><span style="color: #ff0000;"><strong>Can not connect to server.</strong></span><hr></p>';
							}
						break;
					}
					echo '</table>';
				}
				?>
			</div>
		</div>
		<?php
	}

	public function add_menu() {
		add_submenu_page( 'admin.php?page=wpl_activation', 'WPLicense Settings', 'Settings', 'manage_options', 'wpl-settings', array( $this, 'settings_page' ) );
	}
}
new WPL_Page_Settings();