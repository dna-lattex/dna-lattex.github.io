<?php

//ajax
function wpl_ajax_install_plugin( $plugin_source ) {
	include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
	include_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
	$skin     = new WP_Ajax_Upgrader_Skin();
	$upgrader = new Plugin_Upgrader( $skin );
	$upgrader->install( $plugin_source );
}

function wpl_ajax_update_plugin( $plugin ) {
	include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
	include_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
	wp_update_plugins();
	$skin     = new WP_Ajax_Upgrader_Skin();
	$upgrader = new Plugin_Upgrader( $skin );
	$upgrader->bulk_upgrade( array( $plugin ) );
}

function wpl_ajax_reinstall_plugin( $plugin_source, $alert ) {
	include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
	include_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
	include_once dirname( __FILE__ ). '/wpl-class-upgrader.php';
	$skin     = new WP_Ajax_Upgrader_Skin();
	$upgrader = new WPL_Plugin_Upgrader( $skin );
	$result   = $upgrader->install( $plugin_source );
	if ( $alert != true ) return;
	if ( $result || is_wp_error( $result ) ) {
		echo "<script>alert('Success!');window.location.href='';</script>";
	} else {
		echo "<script>alert('Unsuccessful!');</script>";
	}
}

function wpl_ajax_install_theme( $theme_source ) {
	include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
	include_once( ABSPATH . 'wp-admin/includes/theme.php' );
	$skin     = new WP_Ajax_Upgrader_Skin();
	$upgrader = new Theme_Upgrader( $skin );
	$upgrader->install( $theme_source );
}

function wpl_ajax_update_theme( $theme ) {
	include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
	include_once( ABSPATH . 'wp-admin/includes/theme.php' );
	$current = get_site_transient( 'update_themes' );
	if ( empty( $current ) ) {
		wp_update_themes();
	}
	$skin     = new WP_Ajax_Upgrader_Skin();
	$upgrader = new Theme_Upgrader( $skin );
	$upgrader->bulk_upgrade( array( $theme ) );
}

function wpl_ajax_reinstall_theme( $theme_source, $alert ) {
	include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
	include_once( ABSPATH . 'wp-admin/includes/theme.php' );
	include_once dirname( __FILE__ ). '/wpl-class-upgrader.php';
	$skin     = new WP_Ajax_Upgrader_Skin();
	$upgrader = new WPL_Theme_Upgrader( $skin );
	$result   = $upgrader->install( $theme_source );
	if ( $alert != true ) return;
	if ( $result || is_wp_error( $result ) ) {
		echo "<script>alert('Success!');window.location.href='';</script>";
	} else {
		echo "<script>alert('Unsuccessful!');</script>";
	}
}

//Non Ajax
function wpl_install_plugin( $plugin, $plugin_source ) {
	include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
	include_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
	$title        = __( 'Plugin Installation' );
	$parent_file  = 'plugins.php';
	$submenu_file = 'plugin-install.php';
	$title = sprintf( __( 'Installing Plugin: %s' ), $plugin );
	$nonce = 'install-plugin_' . $plugin;
	$url   = 'update.php?action=install-plugin&plugin=' . urlencode( $plugin );
	$type = 'web';
	$upgrader = new Plugin_Upgrader( new Plugin_Installer_Skin( compact( 'title', 'url', 'nonce', 'plugin' ) ) );
	$upgrader->install( $plugin_source );
}

function wpl_update_plugin( $plugin ) {
	include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
	include_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
	wp_update_plugins();
	$title        = __( 'Update Plugin' );
	$parent_file  = 'plugins.php';
	$submenu_file = 'plugins.php';
	$nonce = 'upgrade-plugin_' . $plugin;
	$url   = 'update.php?action=upgrade-plugin&plugin=' . urlencode( $plugin );
	$upgrader = new Plugin_Upgrader( new Plugin_Upgrader_Skin( compact( 'title', 'nonce', 'url', 'plugin' ) ) );
	$upgrader->upgrade( $plugin );
}

function wpl_reinstall_plugin( $plugin, $plugin_source ) {
	include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
	include_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
	include_once dirname( __FILE__ ). '/wpl-class-upgrader.php';
	$title        = __( 'Plugin Installation' );
	$parent_file  = 'plugins.php';
	$submenu_file = 'plugin-install.php';
	$title = sprintf( __( 'Installing Plugin: %s' ), $plugin );
	$nonce = 'install-plugin_' . $plugin;
	$url   = 'update.php?action=install-plugin&plugin=' . urlencode( $plugin );
	$type = 'web';
	$upgrader = new WPL_Plugin_Upgrader( new Plugin_Installer_Skin( compact( 'title', 'url', 'nonce' ) ) );
	$upgrader->install( $plugin_source );
}

function wpl_install_theme( $theme, $theme_source ) {
	include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
	include_once( ABSPATH . 'wp-admin/includes/theme.php' );
	$title        = __( 'Install Themes' );
	$parent_file  = 'themes.php';
	$submenu_file = 'themes.php';
	$title = sprintf( __( 'Installing Theme: %s' ), $theme );
	$nonce = 'install-theme_' . $theme;
	$url   = 'update.php?action=install-theme&theme=' . urlencode( $theme );
	$type  = 'web';
	$upgrader = new Theme_Upgrader( new Theme_Installer_Skin( compact( 'title', 'url', 'nonce' ) ) );
	$upgrader->install( $theme_source );
}

function wpl_update_theme( $theme ) {
	include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
	include_once( ABSPATH . 'wp-admin/includes/theme.php' );
	$title        = __( 'Update Theme' );
	$parent_file  = 'themes.php';
	$submenu_file = 'themes.php';
	$nonce = 'upgrade-theme_' . $theme;
	$url   = 'update.php?action=upgrade-theme&theme=' . urlencode( $theme );
	$upgrader = new Theme_Upgrader( new Theme_Upgrader_Skin( compact( 'title', 'nonce', 'url', 'theme' ) ) );
	$upgrader->upgrade( $theme );
}

function wpl_reinstall_theme( $theme, $theme_source ) {
	include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
	include_once( ABSPATH . 'wp-admin/includes/theme.php' );
	include_once dirname( __FILE__ ). '/wpl-class-upgrader.php';
	$title        = __( 'Install Themes' );
	$parent_file  = 'themes.php';
	$submenu_file = 'themes.php';
	$title = sprintf( __( 'Installing Theme: %s' ), $theme );
	$nonce = 'install-theme_' . $theme;
	$url   = 'update.php?action=install-theme&theme=' . urlencode( $theme );
	$type  = 'web';
	$upgrader = new WPL_Theme_Upgrader( new Theme_Installer_Skin( compact( 'title', 'url', 'nonce' ) ) );
	$upgrader->install( $theme_source );
}