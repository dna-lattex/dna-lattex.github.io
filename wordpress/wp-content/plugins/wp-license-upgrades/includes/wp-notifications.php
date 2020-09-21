<?php

defined('ABSPATH') or die;
require_once WPL_PLUGIN_PATH.'wp-filters-extras.php';

class WPL_Notification {
	public function __construct() {
		global $pagenow;
		$wpl_pagenow = array('plugins.php', 'themes.php', 'update-core.php');
		$wpl_submenu = array('wpl_activation', 'wpl-documentation', 'wpl-settings', 'wpl-updates', 'wpl-install');
		if ( !in_array( $pagenow, $wpl_pagenow ) ) {
			add_action( 'in_admin_header', array( $this, 'wpl_update_notice' ), 100000 );
		}
		if ( in_array( $pagenow, $wpl_pagenow ) ) {
			add_action( 'in_admin_header', array( $this, 'wpl_update_alert' ), 100000 );
		}
		if ( $pagenow == 'admin.php' && in_array( $_GET['page'], $wpl_submenu ) ) {
			add_action( 'in_admin_header', array( $this, 'wpl_skip_notices' ), 100000 );
		}
		$WPL_subscription = WPL_get( 'transient', 'wpl_subscription' );
		if ( !defined('WPL_FEE') && empty( $WPL_subscription ) ) {
			if ( $pagenow == 'admin.php' && in_array( $_GET['page'], $wpl_submenu ) || in_array( $pagenow, $wpl_pagenow ) ) {
				add_action( 'in_admin_header', array( $this, 'wpl_license_notice' ), 100000 );
			}
		} else {
			$wpl_username = WPL_get( 'option', 'wplicense_upgrades_username' );
			$WPL_items = WPL_get( 'option', 'wpl_plugins_themes' );
			if ( !empty( $WPL_subscription ) && empty( $wpl_username ) && $WPL_items != 'false' && defined( 'WPL_ACTIVATED' ) ) {
				if ( $pagenow == 'admin.php' && in_array( $_GET['page'], $wpl_submenu ) ) {
					add_action( 'in_admin_header', array( $this, 'wpl_username' ), 100000 );
				}
			}
		}
		if ( defined( 'WPL_ACTIVATED' ) ) {
			add_action( 'admin_head', array( $this, 'wpl_yoast_metabox' ) );
			add_action( 'admin_head', array( $this, 'wpl_hide_notices' ) );
		}
	}

	public function wpl_skip_notices(){
		global $wp_filter;
		if ( is_network_admin() && isset( $wp_filter["network_admin_notices"] ) ) {
			unset( $wp_filter['network_admin_notices'] ); 
		} elseif ( is_user_admin() && isset( $wp_filter["user_admin_notices"] ) ) {
			unset( $wp_filter['user_admin_notices'] ); 
		} else {
			if ( isset( $wp_filter["admin_notices"] ) ) {
				unset( $wp_filter['admin_notices'] ); 
			}
		}
		if ( isset( $wp_filter["all_admin_notices"] ) ) {
			unset( $wp_filter['all_admin_notices'] ); 
		}
	}

	public function wpl_update_notice(){
		global $WPL_VERSION;
		if ( isset( $WPL_VERSION ) && version_compare( $WPL_VERSION, WPL_VERSION, '>' ) ) {
			$update_url = self_admin_url( 'update-core.php' );
			$message = '<p><span style="color: #ff0000;"><strong>There is a new version of WPLicense Upgrades available. This plugin requires the latest version.</strong></span>';
			$message .= sprintf( '<a href="%s" class="button-primary">Update Now</a>', $update_url );
			echo '<div class="notice notice-error is-dismissible">' . $message . '</div>';
		}
	}

	public function wpl_update_alert(){
		global $WPL_items, $WPL_path;
		$plugins_installed = get_plugins();
		foreach ($plugins_installed as $key => $val) {
			$item_version = !empty( $WPL_items[$key] ) ? $WPL_items[$key]['version'] : '';
			$wpl_version = $val['Version'];
			if ( in_array( $key, $WPL_path ) && is_plugin_active( $key ) && !WPL_checked( $key ) ) {
				if ( !empty($item_version) && !empty($wpl_version) && version_compare( $wpl_version, $item_version, '<' )) {
					$items[] = $key;
				}
			}
		}
		$themes_installed = wp_get_themes();
		foreach ($themes_installed as $key => $val) {
			$item_version = !empty( $WPL_items[$key] ) ? $WPL_items[$key]['version'] : '';
			$wpl_version = $val->get('Version');
			if ( in_array( get_template(), $WPL_path ) && get_template() == $key && !WPL_checked( $key ) ) {
				if ( !empty($item_version) && !empty($wpl_version) && version_compare( $wpl_version, $item_version, '<' ) ) {
					$items[] = $key;
				}
			}
		}
		if ( !empty( $items ) ) {
			$update_url = self_admin_url( 'admin.php?page=wpl-install&plugin_status=installed' );
			$message = '<p><span style="color: #ff0000;"><strong>Some themes and plugins need to be upgraded </strong></span>';
			$message .= sprintf( '<a href="%s" class="button-primary">Update Now</a>', $update_url );
			echo '<div class="notice notice-error is-dismissible">' . $message . '</div>';
		}
	}

	public function wpl_license_notice(){
		global $WPL_VERSION, $WPL_meta;
		if ( isset($WPL_meta['notice']) && defined( 'WPL_ACTIVATED' ) && WPL_get( 'option', 'wpl_plugins_themes' ) !== 'false' && version_compare( $WPL_VERSION, WPL_VERSION, '<=' ) ) {
			wpl_script_request('wpl_check_vip', 'wpl_click_reload', 'spinner_check_vip', false, false, true);
			?>
			<div class="notice notice-error is-dismissible">
				<?php echo $WPL_meta['notice']; ?>
				<button class="button-primary" id="wpl_check_vip">Click here</button>&nbsp;
				<img id="spinner_check_vip" src="<?php echo esc_attr(WPL_SPIN); ?>" alt="..." style="vertical-align:bottom; max-height: 30px; display:none">
			</div>
			<?php
		}
	}

	public function wpl_username(){
		global $pagenow;
		$wpl_username = WPL_get( 'option', 'wplicense_upgrades_username' );
		$wpl_username_temp = WPL_get( 'transient', 'wpl_username' );
		$update_url = self_admin_url( 'admin.php?page=wpl_activation' );
		if ( empty( $wpl_username ) && !empty( $wpl_username_temp ) ) {
			$message = '<p><span style="color: #ff0000;"><strong>WPLicense.com username is incorrect. </strong></span>';
		} else {
			$message = '<p><span style="color: #ff0000;"><strong>WPLicense.com username cannot be blank. </strong></span>';
		}
		if ( $pagenow == 'admin.php' && ( $_GET['page'] != 'wpl_activation' || isset( $_GET['tab'] ) && $_GET['tab'] == 'wplicense_upgrades_deactivation' ) ) {
			$message .= sprintf( '<a href="%s" class="button-primary">Update Now</a>', $update_url );
		}
		echo '<div class="notice notice-error is-dismissible">' . $message . '</div>';
	}

	public function wpl_yoast_metabox(){
		global $WPL_meta;
		if ( WPL_settings( 'yoast', 'wordpress-seo-premium/wp-seo-premium.php' ) && isset($WPL_meta['items']['yoast']) && defined( 'WPL_ACTIVATED' ) ) {
			echo $WPL_meta['items']['yoast'];
		}
	}

	public function wpl_hide_notices(){
		global $wp_filter;
		if ( WPL_settings( 'hide_notifications' ) ) {
			unset($wp_filter['admin_notices']);
		}
		if ( function_exists( 'bsf_notices' ) ) {
			remove_action( 'admin_notices', 'bsf_notices', 1000 );
			remove_action( 'network_admin_notices', 'bsf_notices', 1000 );
		}
		if ( class_exists( 'YIT_Plugin_Licence' ) ) {
			wpl_remove_filters_for_anonymous_class( 'admin_notices', 'YIT_Plugin_Licence', 'activate_license_notice', 15 );
		}
	}
}
new WPL_Notification();