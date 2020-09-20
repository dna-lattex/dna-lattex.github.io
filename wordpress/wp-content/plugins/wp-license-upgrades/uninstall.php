<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if ( WPL_check( 'transient', 'wpl_meta' ) ) $WPL_meta = WPL_get( 'transient', 'wpl_meta' );
$wpl_items_check = isset( $WPL_meta['check'] ) && is_array( $WPL_meta['check'] ) ? $WPL_meta['check'] : array();
foreach ($wpl_items_check as $slug => $value) {
	if ( WPL_check( 'transient', "wpl_activate_$slug" ) ) {
		@delete_option( $value );
		@delete_site_transient( "wpl_activate_$slug" );
	}
}

$wpl_settings = array(
	'wplicense_upgrades_data',
	'wplicense_upgrades_product_id',
	'wplicense_upgrades_instance',
	'wplicense_upgrades_deactivate_checkbox',
	'wplicense_upgrades_activated',
	'wpl_activate_time',
	'wpl_updates_time',
	'wpl_plugins_themes',
	'wpl_settings',
	'wpl_check_updates',
	'wpl_activate',
	'wpl_request_api',
	'wpl_home_url',
	'wpl_version',
	'wpl_troubleshoot',
	);
foreach ($wpl_settings as $value) {
	@delete_option( $value );
	@delete_site_transient( $value );
}

$wpl_exists_plugins = get_plugins();
$wpl_exists_themes = wp_get_themes();
$wpl_exists_themes_plugins = array_merge($wpl_exists_themes,$wpl_exists_plugins);
foreach ( $wpl_exists_themes_plugins as $key => $value ) {
	$WPL_SLUG = explode( '/',$key )[0];
	if ( get_option( "wpl_checker_for-$WPL_SLUG" ) ) {
		@delete_option( "wpl_checker_for-$WPL_SLUG" );
	}
}