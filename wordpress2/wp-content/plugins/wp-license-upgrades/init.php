<?php

global $pagenow;
define( 'WPL_TIMEZONE_DEFAULT', @date_default_timezone_get() );
define( 'WPL_SPIN', admin_url('images/spinner-2x.gif') );

if ( empty( $wp_filesystem ) ) {
	require_once wp_normalize_path( ABSPATH . '/wp-admin/includes/file.php' );
	WP_Filesystem();
}
if ( !function_exists( 'wp_get_current_user' ) ) {
    include_once wp_normalize_path( ABSPATH . 'wp-includes/pluggable.php' ); 
}
include_once wp_normalize_path( ABSPATH . 'wp-includes/version.php' );

function WPL_set( $type, $name, $value, $values, $delete = false ) {
	if ( $type == 'transient' ) {
		if ( !empty( $value ) ) {
			@set_site_transient( $name, $value );
		} elseif ( $values != false ) {
			@set_site_transient( $name, $values );
		} elseif ( $delete == true ) {
			@delete_site_transient( $name );
		}
	} elseif ( $type == 'option' ) {
		if ( !empty( $value ) ) {
			@update_option( $name, $value );
		} elseif ( $values != false ) {
			@update_option( $name, $values );
		} elseif ( $delete == true ) {
			@update_option( $name );
		}
	}
}

function WPL_check( $type, $name ) {
	if ( $type == 'transient' ) {
		if ( get_site_transient( $name ) ) {
			return true;
		}
	} elseif ( $type == 'option' ) {
		if ( get_option( $name ) ) {
			return true;
		}
	}
}

function WPL_get( $type, $name, $value = '' ) {
	if ( $type == 'transient' ) {
		if ( WPL_check( $type, $name ) ) {
			$data = get_site_transient( $name );
			if ( is_array( $value ) && !is_array( $data ) ) $data = array();
		} else {
			$data = $value;
		}
	} elseif ( $type == 'option' ) {
		if ( WPL_check( $type, $name ) ) {
			$data = get_option( $name );
			if ( is_array( $value ) && !is_array( $data ) ) $data = array();
		} else {
			$data = $value;
		}
	}
	return $data;
}

$WPL_meta = WPL_get( 'transient', 'wpl_meta' );
$WPL_VERSION = WPL_get( 'transient', 'wpl_version', WPL_VERSION );
$WPL_request_api = WPL_get( 'transient', 'wpl_request_api' );
$WPL_free = WPL_price( 'free' );
$WPL_path = WPL_path();
$WPL_subscription = WPL_get( 'transient', 'wpl_subscription', array() );
define( 'WPL_USERNAME', WPL_get( 'option', 'wplicense_upgrades_username' ) );
if ( !empty( $WPL_subscription ) && WPL_USERNAME != '' ) {
	define( 'WPL_FEE', TRUE );
}
if ( defined( 'WPL_FEE' ) && in_array( 'all', $WPL_subscription ) ) {
	define( 'WPL_SUBSCRIPTION', TRUE );
}

function WPL_settings( $slug, $path = false ) {
	$wpl_settings = WPL_get( 'option', 'wpl_settings', array() );
	if ( empty( $wpl_settings ) ) {
		update_option( 'wpl_settings', array( 'wp_license_deactivation' ), '', 'yes' );
	}
	$wpl_page_settings = WPL_get( 'transient', 'wpl_settings', array() );
	foreach ( $wpl_page_settings as $value ) {
		if ( ( !empty( $value["disabled"] ) || !defined( 'WPL_SUBSCRIPTION' ) && !empty( $value["fee"] ) ) && in_array( $value["slug"], $wpl_settings ) ) {
			unset( $wpl_settings[ array_search( $value["slug"], $wpl_settings ) ] );
		}
	}
	if ( in_array( "wp_license_$slug", $wpl_settings ) ) {
		return true;
		if ( !empty( $path ) ) {
			if ( isset( explode('/', $path)[1] ) ) {
				if ( !is_plugin_active( $path ) ) {
					return false;
				}
			} elseif ( get_template() != explode('/', $path)[0] ) {
				return false;
			}
		}
	} else {
		return false;
	}
}

function update_plugin_wpl( $transient ) {
	global $WPL_VERSION, $WPL_meta;
	if ( version_compare( $WPL_VERSION, WPL_VERSION, '>' ) ) {
		empty($transient->response[WPL_MAIN_FILE] ) ? $transient->response[WPL_MAIN_FILE] = new stdClass : '';
		$transient->response[WPL_MAIN_FILE]->url = WPL_ORIGIN;
		$transient->response[WPL_MAIN_FILE]->slug = WPL_SLUG;
		$transient->response[WPL_MAIN_FILE]->new_version = $WPL_VERSION;
		$transient->response[WPL_MAIN_FILE]->package = !empty($WPL_meta['package']) ? $WPL_meta['package'] : WPL_PACKAGE.WPL_SLUG.".zip";
	}
	return $transient;
}

function wpl_token( $string ) {
	date_default_timezone_set("UTC");
	if ( date( 'T', time() ) != 'UTC' ) {
		date_default_timezone_set("GMT");
	}
	$guid = md5( 'WPL'. date( "Y/m/d/H" ). $string );
	$token = substr( $guid, 0, 8 ). '-'.
	substr( $guid, 8, 4 ). '-'.
	substr( $guid, 12, 4 ). '-'.
	substr( $guid, 16, 4 ). '-'.
	substr( $guid, 20, 12 );
	return $token;
}

function wpl_token_guid( $guid ) {
	$token = substr($guid, 0, 8). '-'.
	substr( $guid, 8, 4 ). '-'.
	substr( $guid, 12, 4 ). '-'.
	substr( $guid, 16, 4 ). '-'.
	substr( $guid, 20, 12 );
	return $token;
}

function wpl_main_token( $slug ) {
	global $WPL_request_api;
	date_default_timezone_set("UTC");
	if ( date( 'T', time() ) != 'UTC' ) {
		date_default_timezone_set("GMT");
	}
	$token = wpl_token_guid( md5( 'WPL'. date( "Y/m/d" ). 'Activated'. $slug ) );
	$token_main = "/?action=get_download_code$token&slug=$slug";
	if ( !empty( $WPL_request_api ) && count( $WPL_request_api ) > 1 ) {
		$token_main = "/request.php?action=get_download_code$token&slug=$slug";
	}
	return $token_main;
}

function wpl_token_download( $slug ) {
	global $WPL_request_api, $WPL_free;
	date_default_timezone_set("UTC");
	if ( date( 'T', time() ) != 'UTC' ) {
		date_default_timezone_set("GMT");
	}
	if ( !empty( $WPL_free ) && in_array( $slug, $WPL_free ) ) {
		$token = wpl_token_guid( md5( 'WPL'. date("Y/m/d"). WPL_GET_STATUS. $slug ) );
	} else {
		$token = wpl_token_guid( md5( 'WPL'. date("Y/m/d"). WPL_GET_STATUS. $slug. WPL_HOME_URL ) );
	}
	$token_download = "/?action=get_download_code$token&slug=$slug";
	if ( !empty( $WPL_request_api ) && count( $WPL_request_api ) > 1 ) {
		$token_download = "/request.php?action=get_download_code$token&slug=$slug";
	}
	return $token_download;
}

function WPL_price( $price = 'fee' ) {
	$WPL_items = WPL_get( 'option', 'wpl_plugins_themes', array() );
	$WPL_price = array();
	foreach ( $WPL_items as $value ) {
		if ( !empty( $value['price'] ) ) {
			if ( $price == 'fee' ) {
				if ( $value['price'] == 'fee' ) {
					$WPL_price[] = $value['slug'];
				}
			} elseif ( $price == 'free' ) {
				if ( $value['price'] == 'free' ) {
					$WPL_price[] = $value['slug'];
				}
			}
		}
	}
	return $WPL_price;
}

function WPL_version( $path ) {
	if ( isset( explode( '/', $path )[1] ) ) {
		$plugins_installed = get_plugins();
		$version = !empty($plugins_installed[$path]['Version']) ? $plugins_installed[$path]['Version'] : '';
	} else {
		$themes_installed = wp_get_themes();
		$version = !empty($themes_installed[$path]) ? $themes_installed[$path]->get('Version') : '';
	}
	return $version;
}

function WPL_path(){
	$WPL_path = array();
	$WPL_items = WPL_get( 'option', 'wpl_plugins_themes', array() );
	foreach ( $WPL_items as $value ) {
		if ( $value['type'] == 'plugin' ) {
			$main_file = $value['path'];
		} elseif (  $value['type'] == 'theme' ) {
			$main_file = explode( '/', $value['path'] )[0];
		}
		if ( !empty( $value['price'] ) && !empty( $main_file ) ) {
			$WPL_path[] = $main_file;
		}
	}
	return $WPL_path;
}

function WPL_checked( $path ) {
	$WPL_checked = WPL_get( 'option', 'wpl_check_updates', array() );
	if ( in_array( $path, $WPL_checked) ) {
		return true;
	}
}
function WPL_categories() {
	$WPL_items = WPL_get( 'option', 'wpl_plugins_themes' );
	if ( is_array( $WPL_items ) ) {
		foreach ( $WPL_items as $value ) {
			if ( !empty( $value['path'] ) && !empty( $value['categories'] ) ) {
				$WPL_categories[$value['path']] = $value['categories'];
			}
		}
	} else {
		$WPL_categories = array();
	}
	return $WPL_categories;
}

function WPL_installed() {
	$plugins_installed = get_plugins();
	$themes_installed = wp_get_themes();
	$WPL_installed = array_merge( $themes_installed, $plugins_installed );
	if ( !is_array( $WPL_installed ) ) $WPL_installed = array();
	return $WPL_installed;
}

function wpl_add_options_plugin(){
	global $pluginListTable;
	$option = 'per_page';
	$args = array(
		'label' => 'Number of items per page:',
		'default' => 15,
		'option' => 'wpl_plugins_per_page'
		);
	add_screen_option( $option, $args );
	$pluginListTable = new WPLICENSE_List_Table();
}

function wpl_update_message_plugin( $plugin_data, $r ){
	echo ' <strong>Thanks for using the WPLicense service.</strong>';
}

function wpl_activation() {
	@delete_site_transient('wpl_request_api');
	@delete_site_transient('wpl_subscription');
	@update_option( 'wpl_plugins_themes', array() );
	@update_option( 'wpl_activate_time', time() );
}

function wpl_deactivation() {
	@delete_site_transient('wpl_subscription');
	@update_option( 'wpl_plugins_themes', array() );
	@delete_option( 'wpl_activate_time' );
}

function wpl_auto_update ( $update, $item ) {
	$plugins = array ( WPL_SLUG );
	if ( in_array( $item->slug, $plugins ) ) {
		return true;
	} else {
		return $update;
	}
}

function wpl_license_link( $links ) {
	$license = '<a href="admin.php?page=wpl_activation">'.__( 'License', 'wp-license-upgrades' ).'</a>'; 
	array_unshift( $links, $license ); 
	return $links; 
}

function wpl_settings_link( $links ) {
	$settings = '<a href="admin.php?page=wpl-settings">'.__( 'Settings', 'wp-license-upgrades' ).'</a>'; 
	array_unshift( $links, $settings ); 
	return $links; 
}

function wpl_script_request( $id, $action, $spin, $name, $result, $alert ) {
	?><script>
	jQuery(document).ready(function(){
		jQuery('#<?php echo $id;?>').click(function(){
			jQuery.ajax({
				type : "post",
				dataType : "html",
				url : '<?php echo admin_url('admin-ajax.php');?>',
				data : {
					action: "<?php echo $action;?>",
				},
				beforeSend: function(){
					jQuery('#<?php echo $spin;?>').show();
				},
				success: function(response){
					jQuery('#<?php echo $spin;?>').hide();
					<?php if ($result == true){ ?> jQuery('.display-result').html(response); <?php }else{ ?> window.location.href= ""; <?php } ?>
				},
				error: function( jqXHR, textStatus, errorThrown ){
					jQuery('#<?php echo $spin;?>').hide();
					<?php if ($alert == true) ?> alert('Please try again!');
					console.log( 'The following error occured: ' + textStatus, errorThrown );
				}
			});
		});
	});
	</script><?php
	if ( empty( $name ) ) return;
	?>
	<button class="button-primary" id="<?php echo $id ?>"><?php echo $name ?></button>
	<img id="<?php echo $spin ?>" src="<?php echo esc_attr(WPL_SPIN); ?>" alt="..." style="vertical-align:bottom; max-height: 30px; display:none">
	<span class="display-result" style="display:none"></span>
	<?php
}

function wpl_admin_error(){
	wpl_script_request('wpl_check_vip', 'wpl_click_reload', 'spinner_check_vip', false, false, true);
	?><div class="notice notice-error is-dismissible"><p><span style="color: #ff0000;"><strong>Can not connect to server </strong></span><button class="button-primary" id="wpl_check_vip">Click here to try again</button>&nbsp;
	<img id="spinner_check_vip" src="<?php echo esc_attr(WPL_SPIN); ?>" alt="..." style="vertical-align:bottom; max-height: 30px; display:none"><br></p></div><?php
}

if ( defined( 'WPL_SUBSCRIPTION' ) && WPL_settings( 'thrive' ) ) {
	if ( ! class_exists( 'TVE_Dash_Product_LicenseManager' ) ) {
		require_once dirname( __FILE__ ). '/includes/LicenseManager.php';
	}
}