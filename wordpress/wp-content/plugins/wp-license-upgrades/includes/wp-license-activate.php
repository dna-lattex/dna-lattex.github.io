<?php

defined('ABSPATH') or die;
require_once WPL_PLUGIN_PATH.'wp-filters-extras.php';

function WPL_Checks( $path ) {
	if ( get_site_transient( 'wpl_activate_'.explode('/', $path)[0] ) && is_plugin_active( $path ) ) {
		return true;
	}
}
class WPL_Activate {
	public function __construct() {
		global $pagenow;
		add_action( 'admin_init', array( $this, 'notices_wpml' ) );
		if ( ! class_exists( 'YITH_Licence' ) && defined('WPL_SUBSCRIPTION') ) {
			if ( $this->categories( 'yithemes' ) ) {
				if ( $pagenow == 'admin.php' && $_GET['page'] == 'yith_plugins_activation' ) {
					return;
				} else {
					require_once WPL_PLUGIN_PATH.'libraries/php/yit-licence.php';
					require_once WPL_PLUGIN_PATH.'libraries/php/yit-plugin-upgrade.php';
				}
			}
		}
		if ( WPL_Checks( 'astra-pro-sites/astra-pro-sites.php' ) ) {
			$brainstrom_products = get_option( 'brainstrom_products', array() );
			$bsf_product_plugins = isset( $brainstrom_products['plugins'] ) ? $brainstrom_products['plugins'] : array();
			foreach ( $bsf_product_plugins as $plugin ) {
				if ( !empty( $plugin['is_product_free'] ) && $plugin['is_product_free'] != 'true' ) {
					$is_product_free = false;
				}
			}
			if ( isset( $is_product_free ) ) $this->astra('astra-pro-sites', false);
		}
	}

	public function http_args() {
		global $WPL_Request;
		$HTTP_ARGS = $WPL_Request->http_args();
		return $HTTP_ARGS;
	}

	public function notice( $slug, $alert ) {
		@delete_site_transient( "wpl_activate_$slug" );
		if(!empty($alert)) {
			echo"<script>alert('You do not have access to this feature.');</script>";
			echo ("<SCRIPT LANGUAGE='JavaScript'>window.location.href='".$_SERVER['HTTP_REFERER']."';</SCRIPT>");
		}
	}

	public function check() {
		global $WPL_meta;
		if ( WPL_Checks( 'wordfence/wordfence.php' ) ) {
			$this->wordfence('wordfence', false);
		}
		if ( WPL_Checks( 'wordpress-seo-premium/wp-seo-premium.php' ) ) {
			if ( get_transient( 'wpseo_site_information' ) && get_transient( 'wpseo_site_information' )->url !== 'https://yoast.com' ) {
				$this->action('wordpress-seo-premium', false);
			}
		}
		if ( WPL_Checks( 'elementor-pro/elementor-pro.php' ) && class_exists( 'WPL_ElementorPro' ) ) {
			$WPL_ElementorPro = new WPL_ElementorPro;
			$license_data = $WPL_ElementorPro->remote_post();
			$WPL_ElementorPro->set_license_data($license_data);
		}
		$items_check = isset( $WPL_meta['check'] ) && is_array( $WPL_meta['check'] ) ? $WPL_meta['check'] : array();
		foreach ( $items_check as $slug => $value ) {
			if ( WPL_check( 'transient', "wpl_activate_$slug" ) ) {
				$args = array( 'token' => wpl_token( WPL_HOME_URL ), 'action' => 'activate', 'slug' => $slug, 'timestamp' => time(), 'timezone' => WPL_TIMEZONE );
				$url = add_query_arg( $args, WPL_BASE_REQUEST );
				$request = @wp_safe_remote_get( $url, $this->http_args() );
				if( is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) !== 200 ) {
					@delete_option( $value );
					@delete_site_transient( "wpl_activate_$slug" );
				}
			}
		}
	}

	public function action($slug, $alert) {
		$args = array( 'token' => wpl_token( WPL_HOME_URL ), 'action' => 'activate', 'slug' => $slug, 'timestamp' => time(), 'timezone' => WPL_TIMEZONE );
		$url = add_query_arg( $args, WPL_BASE_REQUEST );
		$request = @wp_safe_remote_get( $url, $this->http_args() );
		if( !is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) === 200 ) {
			$data = json_decode( $request['body'], true );
			if ( is_array( $data ) ) {
				if ( !empty( $data['constant'] ) ) {
					$constant = $data['constant'];
					foreach ( $constant as $key => $value ) {
						@update_option( $key, $value );
					}
					if ( !empty( $data[$slug] ) ) {
						if ( !empty( $data[$slug]['version'] ) && !empty( $data[$slug]['type'] ) && !empty( $data['path'] ) ) {
							$this->activate_download($slug, $data['path'], $data[$slug]['type'], $data[$slug]['version'], $alert);
						} else {
							return $data[$slug];
						}
					} else {
						$this->result($slug, 'activate', $alert);
					}
				} elseif ( !empty( $data[$slug] ) ) {
					if ( !empty( $data[$slug]['version'] ) && !empty( $data[$slug]['type'] ) && !empty( $data['path'] ) ) {
						$this->activate_download($slug, $data['path'], $data[$slug]['type'], $data[$slug]['version'], $alert);
					} else {
						return $data[$slug];
					}
				} else {
					$this->notice( $slug, $alert );
				}
			} else {
				$this->notice( $slug, $alert );
			}
		} else {
			$this->notice( $slug, $alert );
		}
	}

	public function result($slug, $action, $alert){
		$success = 'PHNjcmlwdD5hbGVydCgnU3VjY2VzcyEnKTs8L3NjcmlwdD4=';
		$args = array( 'token' => wpl_token( WPL_HOME_URL ), 'action' => $action, 'slug' => $slug, 'timestamp' => time(), 'timezone' => WPL_TIMEZONE );
		$url = add_query_arg( $args, WPL_BASE_REQUEST );
		$request = @wp_safe_remote_get( $url, $this->http_args() );
		if( !is_wp_error( $request ) && wp_remote_retrieve_response_code( $request ) === 200 ) {
			$data = json_decode( $request['body'], true );
			WPL_set( 'transient', "wpl_activate_$slug", 'Activated', false );
		}
		if ( !empty( $alert ) ) {
			if ( empty($data['href'] ) ) {
				echo(base64_decode($success));
			} elseif ($data['href'] !== $success) {
				echo(base64_decode($success));
				echo(base64_decode($data['href']));
			} else {
				echo(base64_decode($data['href']));
			}
			echo ("<SCRIPT LANGUAGE='JavaScript'>window.location.href='".$_SERVER['HTTP_REFERER']."';</SCRIPT>");
		}
	}

	public function wordfence($slug, $alert){
		$data_constant = $this->action($slug, $alert);
		if ( !empty( $data_constant ) ) {
			if ( class_exists('wfConfig') ) {
				foreach ($data_constant as $key => $value) {
					wfConfig::set($key, $value);
				}
				$this->result($slug, 'activate', $alert);
			}
		} else {
			$this->notice( $slug, $alert );
		}
	}

	public function astra($slug, $alert){
		$data_constant = $this->action($slug, false);
		if ( !empty( $data_constant ) ) {
			$brainstrom_products = get_option( 'brainstrom_products', array() );
			$bsf_product_plugins = isset( $brainstrom_products['plugins'] ) ? $brainstrom_products['plugins'] : array();
			foreach ( $bsf_product_plugins as $keys => $plugin ) {
				foreach ( $data_constant as $key => $value ) {
					if ( empty( $plugin['is_product_free'] ) || $plugin['is_product_free'] != 'true' ) {
						$brainstrom_products['plugins'][ $keys ][$key] = $value;
					}
				}
			}
			update_option( 'brainstrom_products', $brainstrom_products );
			$this->result( $slug, 'activate', $alert );
		} else {
			$this->notice( $slug, $alert );
		}
	}

	public function swift($slug, $alert){
		$data_constant = $this->action($slug, false);
		if ( !empty( $data_constant ) ) {
			$data = WPL_get( 'option', 'swift_performance_options' );
			foreach ( $data_constant as $key => $value ) {
				$data[$key] = $value;
			}
			update_option( 'swift_performance_options', $data );
			$this->result( $slug, 'activate', $alert );
		} else {
			$this->notice( $slug, $alert );
		}
	}

	public function Newspaper($slug, $alert){
		$data_constant = $this->action($slug, false);
		if ( !empty( $data_constant ) ) {
			$data = WPL_get( 'option', 'td_011' );
			foreach ( $data_constant as $key => $value ) {
				$data[$key] = $value;
			}
			if ( is_plugin_active( 'td-composer/td-composer.php' ) ) {
				@deactivate_plugins( 'td-composer/td-composer.php' );
				@update_option( 'td_011_temp', $data );
			} else {
				@update_option( 'td_011', $data );
			}
			$this->result( $slug, 'activate', $alert );
		} else {
			$this->notice( $slug, $alert );
		}
	}

	public function success($slug){
		echo "<script>alert('Success!');</script>";
		WPL_set( 'transient', "wpl_activate_$slug", 'Activated', false );
	}

	public function unsuccessful($slug){
		echo "<script>alert('Unsuccessful!');</script>";
		@delete_site_transient( "wpl_activate_$slug" );
		echo ("<SCRIPT LANGUAGE='JavaScript'>window.location.href='".$_SERVER['HTTP_REFERER']."';</SCRIPT>");
	}

	public function undefined($slug){
		echo "<script>alert('Unsuccessful! Untested with your version.');</script>";
		@delete_site_transient( "wpl_activate_$slug" );
		echo ("<SCRIPT LANGUAGE='JavaScript'>window.location.href='".$_SERVER['HTTP_REFERER']."';</SCRIPT>");
	}

	private function activate_download($slug, $path, $type, $version, $alert) {
		if ($type == 'theme') {
			$wpl_version = WPL_version( $slug );
		} elseif ($type == 'plugin') {
			$wpl_version = WPL_version( $path );
		}
		$args = array('token' => wpl_token( WPL_HOME_URL ),'action' => 'activate-download','slug' => $slug, 'timestamp' => time(), 'timezone' => WPL_TIMEZONE);
		$source = add_query_arg( $args, WPL_BASE_REQUEST );
		$request = @wp_safe_remote_get( $source, $this->http_args() );
		if ( !is_wp_error( $request ) && wp_remote_retrieve_response_code( $request ) === 200 ) {
			include_once dirname( __FILE__ ). '/libraries/php/wpl-actions.php';
			if ( !empty($wpl_version) && version_compare( $wpl_version, $version, '=' ) ) {
				if ( $type == 'theme' ) wpl_ajax_reinstall_theme( $source, $alert );
				if ( $type == 'plugin' ) wpl_ajax_reinstall_plugin( $source, $alert );
				$this->result($slug, 'activate-download', false);
			} else {
				!empty($alert) ? $this->undefined($slug) : '';
			}
		} else {
			$this->notice( $slug, $alert );
		}
	}
	//WPML
	public function notices_wpml(){
		global $pagenow, $wp_filter;
		if ( defined( 'WPL_SUBSCRIPTION') && $pagenow == 'plugins.php' && isset($wp_filter['admin_notices'] ) ) {
			unset( $wp_filter['admin_notices'] );
		}
	}

	public function categories( $categories ) {
		$wpl_installed = WPL_installed();
		$WPL_categories = WPL_categories();
		foreach ( $wpl_installed as $key => $value ) {
			if ( !empty($WPL_categories[$key]) && $WPL_categories[$key] == $categories && ( is_plugin_active($key) || get_template() == $key ) ) {
				return true;
			}
		}
	}
}
$WPL_Activate = new WPL_Activate();

class WPL_Settings {
	public function __construct() {
		global $WPL_meta;
		if ( WPL_settings( 'newspaper', 'Newspaper' ) && !class_exists('td_ajax') ) {
			require_once WPL_PLUGIN_PATH.'wp-td_ajax.php';
		}
		if ( WPL_settings( 'jnews', 'jnews' ) && !class_exists( 'JNews\Util\ValidateLicense' ) ) {
			if ( WPL_check( 'option', 'jnews_license' ) && empty( get_option( 'jnews_license' )['token'] ) ) {
				@delete_option('jnews_license');
			}
			require_once WPL_PLUGIN_PATH.'ValidateLicense.php';
		}
		if( WPL_settings( 'soledad', 'soledad' ) && !function_exists( 'penci_soledad_is_activated' ) ){
			function penci_soledad_is_activated(){
				if ( defined('ENVATO_HOSTED_SITE') ) {
				   return true;
				}
				return true;//ED
			}
		}
		if ( defined('WPL_SUBSCRIPTION') && WPL_settings( 'astra-pro-sites', 'astra-pro-sites/astra-pro-sites.php' ) ) {
			if ( defined( 'ASTRA_PRO_SITES_DIR' ) && ! class_exists( 'Astra_Pro_Sites' ) ) {
				require_once WPL_PLUGIN_PATH.'libraries/php/class-astra-pro-sites.php';
			}
		}
		if ( WPL_settings( 'wp-rocket', 'wp-rocket/wp-rocket.php' ) ) {
			$wpl_rocket = WPL_get( 'option', 'wp_rocket_settings' );
			if ( !empty( $wpl_rocket ) ) {
				if ( ! defined( 'WP_ROCKET_EMAIL' ) ) define( 'WP_ROCKET_EMAIL', WPL_EMAIL );
				if ( ! defined( 'WP_ROCKET_KEY' ) ) define( 'WP_ROCKET_KEY', hash( 'crc32', WPL_EMAIL ) );
				if ( empty( $wpl_rocket['secret_key'] ) || $wpl_rocket['secret_key'] != hash( 'crc32', WPL_EMAIL ) ) {
					$wpl_rocket['consumer_email'] = WPL_EMAIL;
					$wpl_rocket['secret_key'] = hash( 'crc32', WPL_EMAIL );
					update_option( 'wp_rocket_settings', $wpl_rocket );
				}
			}
			$wpl_rocket_data = get_transient( 'wp_rocket_customer_data' );
			if ( !empty( $WPL_meta['items']['rocket'] ) ) {
				if ( !empty( $wpl_rocket_data ) && $wpl_rocket_data->licence_account == 'Unavailable' ) {
					set_transient( 'wp_rocket_customer_data', (object) $WPL_meta['items']['rocket'] );
				}
				if ( isset( $_POST['_ajax_nonce'] ) && isset( $_POST['action'] ) && $_POST['action'] == 'rocket_refresh_customer_data' ) {
					return wp_send_json_success( $WPL_meta['items']['rocket'] );
				}
			}
		}
	}
}
new WPL_Settings();

if ( WPL_Checks( 'advanced-custom-fields-pro/acf.php' ) ) {
	if( class_exists('acf_pro_updates') ) {
		wpl_remove_filters_for_anonymous_class( 'init', 'acf_pro_updates', 'init', 20 );
		class WPLACF extends acf_pro_updates {
			function init() {
				if( !acf_get_setting('show_updates') ) return;
				if( !acf_is_plugin_active() ) return;
				acf_register_plugin_update(array(
					'id'		=> 'pro',
					'key'		=> maybe_unserialize(base64_decode(get_option('acf_pro_license')))['key'],//ED
					'slug'		=> acf_get_setting('slug'),
					'basename'	=> acf_get_setting('basename'),
					'version'	=> acf_get_setting('version'),
				));		
			}
			function acf_pro_get_license_key() {
				$license = acf_pro_get_license();
				$home_url = get_site_transient('wpl_meta')['site_url'];//ED
				if( !$license || !$license['key'] ) return false;
				if( acf_strip_protocol($license['url']) !== acf_strip_protocol($home_url) ) return false;
				return $license['key'];
			}
		}
		new WPLACF();
	}

	if ( class_exists('ACF_Admin_Updates') ) {
		wpl_remove_filters_for_anonymous_class( 'admin_menu', 'ACF_Admin_Updates', 'admin_menu', 20 );
		class WPLACFS extends ACF_Admin_Updates {
			function load() {
				if( acf_verify_nonce('activate_pro_licence') ) {
					$this->activate_pro_licence();
				} elseif( acf_verify_nonce('deactivate_pro_licence') ) {
					$this->deactivate_pro_licence();
				}
				$license = maybe_unserialize(base64_decode(get_option('acf_pro_license')))['key'];//ED
				$this->view = array(
					'license'			=> $license,
					'active'			=> $license ? 1 : 0,
					'current_version'	=> acf_get_setting('version'),
					'remote_version'	=> '',
					'update_available'	=> false,
					'changelog'			=> '',
					'upgrade_notice'	=> ''
				);
				$force_check = !empty( $_GET['force-check'] );
				$info = acf_updates()->get_plugin_info('pro', $force_check);
				if( is_wp_error($info) ) {
					return $this->display_wp_error( $info );
				}
				$this->view['remote_version'] = $info['version'];
				$version = acf_get_setting('version');
				if( version_compare($info['version'], $version, '>') ) {
					$this->view['update_available'] = true;
					$this->view['changelog'] = $this->get_changelog_changes($info['changelog'], $info['version']);
					$this->view['upgrade_notice'] = $this->get_changelog_changes($info['upgrade_notice'], $info['version']);
					$basename = acf_get_setting('basename');
					$update = acf_updates()->get_plugin_update( $basename );
					if( $license ) {
						if( $update && !$update['package'] ) {
							$this->view['update_available'] = false;
							acf_new_admin_notice(array(
								'text'	=> __('<b>Error</b>. Could not authenticate update package. Please check again or deactivate and reactivate your ACF PRO license.', 'acf'),
								'type'	=> 'error'
							));	
						}
						if( !$update || $update['new_version'] !== $info['version'] ) {
							acf_updates()->refresh_plugins_transient();
						}
					}
				}
			}
		}
		new WPLACFS();
	}
}

if ( WPL_Checks( 'elementor-pro/elementor-pro.php' ) ) {
	if ( ! class_exists( 'ElementorPro\License\Admin' ) ) {
		require_once dirname( __FILE__ ). '/wp-elementor-admin.php';
	}
	if ( ! class_exists( 'ElementorPro\License\API' ) ) {
		require_once dirname( __FILE__ ). '/wp-elementor-api.php';
	}
	class WPL_ElementorPro extends ElementorPro\License\API {
		public static function remote_post() {
			$body_arg = [
				'edd_action' => 'check_license',
				'license' => trim( get_option( 'elementor_pro_license_key' ) ),//ED
			];
			$body_args = wp_parse_args(
				$body_arg,
				[
					'api_version' => ELEMENTOR_PRO_VERSION,
					'item_name' => self::PRODUCT_NAME,
					'site_lang' => get_bloginfo( 'language' ),
					'url' => get_site_transient('wpl_meta')['site_url'],//ED
				]
			);
			$response = wp_remote_post( self::STORE_URL, [
				'timeout' => 40,
				'body' => $body_args,
			] );
			if ( is_wp_error( $response ) ) {
				return $response;
			}
			$response_code = wp_remote_retrieve_response_code( $response );
			if ( 200 !== (int) $response_code ) {
				return new \WP_Error( $response_code, __( 'HTTP Error', 'elementor-pro' ) );
			}
			$data = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( empty( $data ) || ! is_array( $data ) ) {
				return new \WP_Error( 'no_json', __( 'An error occurred, please try again', 'elementor-pro' ) );
			}
			return $data;
		}
	}
}