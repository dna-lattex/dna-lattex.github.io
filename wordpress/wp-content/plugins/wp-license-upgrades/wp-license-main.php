<?php
/*
Plugin Name: WPLicense Upgrades
Plugin URI: https://wplicense.com/wplicense-upgrades/
Description: Automatic updates for premium WordPress themes and plugins from your WordPress dashboard.
Version: 3.0.14
Author: WPLicense
Author URI: https://wplicense.com/
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: WPLicense
*/

if( !function_exists( 'get_plugin_data' ) ) {
	require_once wp_normalize_path( ABSPATH . 'wp-admin/includes/plugin.php' );
}

define( 'WPL_PATH_PLUGIN', plugin_dir_path( __DIR__ ) );
define( 'WPL_PATH_THEME', get_theme_root().'/' );
define( 'WPL_FILE_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPL_MAIN_FILE', plugin_basename( __FILE__ ) );
define( 'WPL_SLUG', plugin_basename( __DIR__ ) );
define( 'WPL_NAME', get_plugin_data( __FILE__ )['Name'] );
define( 'WPL_VERSION', get_plugin_data( __FILE__ )['Version'] );

require_once( WPL_FILE_PATH. 'init.php' );
require_once( WPL_FILE_PATH. 'load.php' );

if ( ! class_exists( 'WPL_Plugin_Upgrades' ) ) {
	require_once( WPL_FILE_PATH. 'includes/wp-license-api.php' );
	if ( WPL_ORIGIN === WPL_BASE ) {
		WPL_Plugin_Upgrades::instance( __FILE__, WPL_NAME, WPL_VERSION, 'plugin', WPL_ORIGIN );
	} else {
		WPL_Plugin_Upgrades::instance( __FILE__, WPL_NAME, WPL_VERSION, 'plugin', WPL_BASE_REQUEST );
	}
}

register_activation_hook( __FILE__, 'wpl_activation' );
register_deactivation_hook( __FILE__, 'wpl_deactivation' );
define( 'WPL_DATE_ACTIVATE', WPL_get( 'option', 'wpl_activate_time' ) );
require_once 'includes/update-checker/wpl-update-checker.php';

$wplhooks = "in_plugin_update_message-".WPL_MAIN_FILE;
add_action( $wplhooks, 'wpl_update_message_plugin', 10, 2 );
if ( !empty( $WPL_request_api ) || !empty( $WPL_troubleshoot ) ) {
	add_filter( 'pre_set_site_transient_update_plugins', 'update_plugin_wpl', 10000, 1 );
} else {
	$WPLChecker = WPL_Factory::buildChecker( WPL_BASE_API. WPL_EMAIL_GET. WPL_META. WPL_SLUG, WPL_PATH_PLUGIN. WPL_MAIN_FILE, WPL_SLUG, 2, WPL_PREFIX. WPL_SLUG );
}

if ( is_admin() ) {
	require_once WPL_PLUGIN_PATH.'documentation.php';
	require_once WPL_PLUGIN_PATH.'wp-notifications.php';
	if ( empty( $WPL_VERSION ) || ( version_compare( $WPL_VERSION, WPL_VERSION,'<=' ) ) ) {
		if ( $pagenow == 'admin.php' && $_GET['page'] == 'wpl_activation' && ( !isset( $_GET['tab'] ) || $_GET['tab'] == 'wplicense_upgrades_dashboard' ) ) {
			$WPL_Request->check_domain();
		}
		add_filter( "plugin_action_links_".WPL_MAIN_FILE, 'wpl_license_link' );
		if ( defined( 'WPL_ACTIVATED' ) ) {
			if ( defined( 'WPL_DATE_UPDATE' ) ) {
				if ( time() - WPL_DATE_UPDATE >= 3600 ) {
					$WPL_Request->connect_server();
				} elseif ( time() - WPL_DATE_UPDATE >= 600 ) {
					if ( !defined('WPL_FEE') || WPL_get( 'option', 'wpl_plugins_themes' ) === 'false' ) {
						$WPL_Request->connect_server();
					}
				}
			} elseif ( WPL_DATE_ACTIVATE > 0 ) {
				$WPL_Request->connect_server();
			}
			$WPL_items = WPL_get( 'option', 'wpl_plugins_themes' );
			if ( is_array( $WPL_items ) && !empty( $WPL_items ) ) {
				foreach ( $WPL_items as $value ) {
					$main_file = ( $value['type'] == 'plugin' ) ? $value['path'] : explode('/',$value['path'])[0];
					$wpl_slug_version[$main_file] = $value['version'];
				}
			} else {
				if ( $WPL_items == 'false' ) {
					WPL_set( 'transient', 'wpl_request_api', array( 'api' => 'failed', 'json' => 'failed' ), false );
					add_action( 'in_admin_header', 'wpl_admin_error', 100000 );
				} elseif ( WPL_DATE_ACTIVATE > 0 ) {
					$WPL_Request->connect_server();
				}
			}
			$WPL_fee = WPL_price();
			if ( !empty( $WPL_fee ) && empty($WPL_subscription) && defined('WPL_DATE_UPDATE') && ( time() - WPL_DATE_UPDATE ) >= 300 ) {
				$WPL_Request->meta_items();
			}
			add_filter( "plugin_action_links_".WPL_MAIN_FILE, 'wpl_settings_link' );
			if ( empty( $WPL_VERSION ) || version_compare( $WPL_VERSION, WPL_VERSION, '<=' ) ) {
				require_once WPL_PLUGIN_PATH. 'wp-license-plugin.php';
			}
			require_once WPL_PLUGIN_PATH.'wp-license-settings.php';
			require_once WPL_PLUGIN_PATH.'wp-license-activate.php';
			require_once WPL_PLUGIN_PATH.'wp-license-updates.php';
		} else {
			if ( WPL_check( 'option', 'wpl_plugins_themes' ) && WPL_get( 'option', 'wpl_plugins_themes' ) != array() ) {
				update_option( 'wpl_plugins_themes', array() );
			}
		}
	}
} else {
	if ( WPL_settings( 'automatic_updates' ) ) {
		add_filter( 'auto_update_plugin', 'wpl_auto_update', 10, 2 );
		add_filter( 'automatic_updates_send_debug_email', '__return_true' );
	}
}