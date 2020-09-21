<?php

$wpl_home_url = WPL_get( 'transient', 'wpl_home_url' );
if ( !empty( $wpl_home_url ) ) {
	define( 'WPL_HOME_URL', $wpl_home_url );
} else {
	define( 'WPL_HOME_URL', str_ireplace( 'www.', '', wp_parse_url( home_url(), PHP_URL_HOST ) ) );
}

define( 'WPL_ORIGIN', 'https://wplicense.com/' );
define( 'WPL_PACKAGE', 'https://wplicense.s3.amazonaws.com/update/' );
define( 'WPL_ORIGIN_API', WPL_ORIGIN. 'api/' );
define( 'WPL_EMAIL', 'wplicense@gmail.com' );
define( 'WPL_EMAIL_GET', explode( '@', WPL_EMAIL )[0] );
define( 'WPL_CODE_URL', wpl_token( false ) );
date_default_timezone_set( WPL_TIMEZONE_DEFAULT );

$WPL_troubleshoot = WPL_get( 'transient', 'wpl_troubleshoot' );
if ( version_compare( $WPL_VERSION, WPL_VERSION, '<=' ) && !empty( $WPL_troubleshoot ) ) {
	delete_site_transient( 'wpl_troubleshoot' );
}

if ( !empty( $WPL_request_api ) && count( $WPL_request_api ) > 1 ) {
	define( 'WPL_BASE', 'https://apionline.net/' );
	define( 'WPL_META', '/request.php?action=get_item_code'. WPL_CODE_URL. '&slug=' );
} else {
	define( 'WPL_BASE', WPL_ORIGIN );
	define( 'WPL_META', '/?action=get_item_code'. WPL_CODE_URL. '&slug=' );
}

if ( ! defined( 'WPL_GET_STATUS' ) && WPL_check( 'option', 'wplicense_upgrades_activated' ) ) {
	define( 'WPL_GET_STATUS', WPL_get( 'option', 'wplicense_upgrades_activated' ) );
}
if ( get_option( 'wplicense_upgrades_data' )['activation_email'] ) {
	define( 'WPL_ACTIVATION_EMAIL', get_option( 'wplicense_upgrades_data' )['activation_email'] );
} else {
	define( 'WPL_ACTIVATION_EMAIL', WPL_EMAIL );
}
if ( WPL_ACTIVATION_EMAIL == WPL_EMAIL ) {
	define( 'WPL_ACTIVATION_EMAIL_GET', WPL_EMAIL_GET );
} else {
	define( 'WPL_ACTIVATION_EMAIL_GET', str_ireplace( array( '@', '.' ), '-', WPL_ACTIVATION_EMAIL ) );
}
define( 'WPL_BASE_API', WPL_BASE. 'api/' );
define( 'WPL_ORIGIN_REQUEST', WPL_ORIGIN_API. WPL_ACTIVATION_EMAIL_GET. '/request.php' );
define( 'WPL_BASE_REQUEST', WPL_BASE_API. WPL_ACTIVATION_EMAIL_GET. '/request.php' );
define( 'WPL_PREFIX', 'wpl_checker_for-' );
if ( WPL_check( 'option', 'wpl_updates_time' ) ) define( 'WPL_DATE_UPDATE', WPL_get( 'option', 'wpl_updates_time' ) );

require_once( WPL_FILE_PATH. 'includes/wp-license-request.php' );