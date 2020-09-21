<?php

define( 'WPL_TIMEZONE', date_default_timezone_get() );

class WPL_Request {
	public function __construct(){
		add_action( 'wp_ajax_wpl_click_reload', array( $this, 'connect_server' ) );
		add_action( 'wp_ajax_nopriv_wpl_click_reload', array( $this, 'connect_server' ) );
	}

	public function http_args( $username = WPL_USERNAME ) {
		$HTTP_ARGS = array(
			'timeout' => 15,
			'headers' => array(
				'Content-Type' => 'application/json',
				'Authorization' => $username.'/'.wpl_token( WPL_HOME_URL )
			),
			'user-agent' => 'WPLicense/'.WPL_VERSION,
		);
		return $HTTP_ARGS;
	}

	private function check_api(){
		$args = array( 'token' => wpl_token( WPL_HOME_URL ), 'timestamp' => time(), 'timezone' => WPL_TIMEZONE );
		$url = add_query_arg( $args, WPL_ORIGIN_REQUEST );
		$request = @wp_safe_remote_get( $url, $this->http_args() );
		if( !is_wp_error($request) || wp_remote_retrieve_response_code($request) === 200 ) {
			$content_type = $request['headers']['Content-Type'];
			if ( !empty($content_type) && $content_type === "application/json; charset=utf-8" ) {
				@delete_site_transient('wpl_request_api');
			}
		}
	}

	public function check_user( $action, $username ) {
		$response = array();
		$args = array( 'token' => wpl_token( WPL_HOME_URL ), 'action' => $action, 'username' => $username, 'timestamp' => time(), 'timezone' => WPL_TIMEZONE );
		$url = add_query_arg( $args, WPL_BASE_REQUEST );
		$request = @wp_safe_remote_get( $url, $this->http_args( $username ) );
		if ( !is_wp_error($request) && wp_remote_retrieve_response_code($request) === 200 ) {
			$body = json_decode($request['body'],true);
			$response = !empty($body) ? $body : array();
		}
		return $response;
	}

	public function check_domain(){
		$args = array( 'token' => wpl_token( WPL_HOME_URL ), 'action' => 'check_domain', 'timestamp' => time(), 'timezone' => WPL_TIMEZONE );
		$url = add_query_arg( $args, WPL_BASE_REQUEST );
		$request = @wp_safe_remote_get( $url, $this->http_args() );
		if ( !is_wp_error($request) && wp_remote_retrieve_response_code($request) === 200 ) {
			$response = json_decode( $request['body'], true );
			@set_site_transient( 'wpl_subscription', $response );
		}
	}

	public function meta_items( $username = WPL_USERNAME ) {
		$args = array( 'token' => wpl_token( WPL_HOME_URL ), 'timestamp' => time(), 'timezone' => WPL_TIMEZONE );
		$url = add_query_arg( $args, WPL_BASE_REQUEST );
		$request = @wp_safe_remote_get( $url, $this->http_args( $username ) );
		if( !is_wp_error($request) && wp_remote_retrieve_response_code($request) === 200) {
			$body = json_decode( $request['body'],true);
			$response = !empty($body) ? $body : array();
		}
		if (!empty($response)){
			update_option( 'wpl_plugins_themes', $response );
		} else {
			update_option( 'wpl_plugins_themes', 'false' );
		}
	}

	public function meta_data(){
		$response = array( 'meta' => array(), 'settings' => array(), 'activate' => array() );
		$args = array( 'token' => wpl_token( WPL_HOME_URL ), 'action' => 'meta_data', 'timestamp' => time(), 'timezone' => WPL_TIMEZONE );
		$url = add_query_arg( $args, WPL_BASE_REQUEST );
		$request = @wp_safe_remote_get( $url, $this->http_args() );
		if ( !is_wp_error($request) && wp_remote_retrieve_response_code($request) === 200 ) {
			$response = json_decode( $request['body'], true );
		}
		WPL_set( 'transient', 'wpl_meta', $response['meta'], array() );
		WPL_set( 'transient', 'wpl_settings', $response['settings'], array() );
		WPL_set( 'transient', 'wpl_activate', $response['activate'], array() );
	}

	public function connect_server(){
		global $WPL_Request;
		$wpl_items_fee = WPL_price();
		WPL_set( 'transient', 'wpl_home_url', str_ireplace( 'www.', '', wp_parse_url( home_url(), PHP_URL_HOST ) ), false );
		$this->check_update();
		$this->check_domain();
		$this->meta_data();
		$this->meta_items();
		require_once WPL_PLUGIN_PATH.'wp-license-activate.php';
		$WPL_Activate = new WPL_Activate();
		$WPL_Activate->check();
		if ( WPL_check( 'transient', 'wpl_request_api' ) ) $this->check_api();
		if ( defined( 'WPL_FEE' ) && empty( $wpl_items_fee ) ) {
			$response = $WPL_Request->check_user( 'check_domain', WPL_USERNAME );
			if ( $response != WPL_USERNAME ) {
				set_site_transient( 'wpl_username', WPL_USERNAME );
				update_option( 'wplicense_upgrades_username', null );
			}
		}
		update_option( 'wpl_updates_time', time() );
	}

	public function check_update(){
		$url = 'https://wplicense.github.io/'.WPL_SLUG.'/update-check.json';
		$request = @wp_safe_remote_get( $url, array( 'timeout' => 15,'headers' => array( 'Content-Type' => 'application/json' ) ) );
		if ( !is_wp_error($request) || wp_remote_retrieve_response_code($request) === 200 ) {
			$response = json_decode($request['body'],true);
			$VERSION = !empty( $response['version'] ) ? $response['version'] : false;
			WPL_set( 'transient', 'wpl_version', $response['version'], false, true );
		}
		return $VERSION;
	}

	public function script_reload(){
		if ( is_admin() ) {
			ob_start();
			?><script src="../wp-includes/js/jquery/jquery.js"></script>
			<script>
			jQuery(document).ready(function(){
				jQuery.ajax({
					type : "post",
					dataType : "html",
					url : '<?php echo admin_url('admin-ajax.php');?>',
					data : {
						action: "wpl_click_reload",
					},
				});
			});
			</script><?php
		}
	}
}
$WPL_Request = new WPL_Request();

class WPL_Requests {
	public function __construct(){
		add_action( 'wp_ajax_wpl_check_version', array( $this, 'check_version' ) );
		add_action( 'wp_ajax_nopriv_wpl_check_version', array( $this, 'check_version' ) );
		add_action( 'wp_ajax_wpl_check_connect', array( $this, 'check_connect' ) );
		add_action( 'wp_ajax_nopriv_wpl_check_connect', array( $this, 'check_connect' ) );
		add_action( 'wp_ajax_wpl_request_api', array( $this, 'wpl_request_api' ) );
		add_action( 'wp_ajax_nopriv_wpl_request_api', array( $this, 'wpl_request_api' ) );
	}

	public function check_version(){
		global $WPL_Request, $WPL_VERSION;
		$args = array( 'token' => wpl_token( WPL_HOME_URL ), 'timestamp' => time(), 'timezone' => WPL_TIMEZONE );
		$url = add_query_arg( $args, WPL_BASE_REQUEST );
		$request = @wp_safe_remote_get( $url, $WPL_Request->http_args() );
		if( !is_wp_error($request) || wp_remote_retrieve_response_code($request) === 200) {
			$body = json_decode( $request['body'],true);
			$response = !empty($body) ? $body : array();
		}
		if (!empty($response)){
			foreach ( $response as $value){
				if ( $value['slug'] == WPL_SLUG && !empty($value['version']) ) {
					$VERSION = $value['version'];
				}
			}
		}
		if ( !empty( $VERSION ) ) {
			@set_site_transient( 'wpl_version', $VERSION );
		} else {
			$VERSION = $WPL_Request->check_update();
		}
		echo "<script>alert('Latest Version: $VERSION');</script>";
		if ( version_compare( $WPL_VERSION, $VERSION, '=' ) ) return;
		echo ("<SCRIPT LANGUAGE='JavaScript'>window.location.href='".$_SERVER['HTTP_REFERER']."';</SCRIPT>");
	}

	public function check_connect(){
		global $WPL_Request;
		$args = array( 'token' => wpl_token( WPL_HOME_URL ), 'timestamp' => time(), 'timezone' => WPL_TIMEZONE );
		$url = add_query_arg( $args, WPL_ORIGIN_REQUEST );
		$request = @wp_safe_remote_get( $url, $WPL_Request->http_args() );
		if( !is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) === 200 ) {
			$content_type = $request['headers']['Content-Type'];
			if ( !empty( $content_type ) && $content_type === "application/json; charset=utf-8" ) {
				echo "<script>alert('WPLicense.com is reachable!');</script>";
				if ( !WPL_check( 'transient', 'wpl_request_api' ) ) return;
				@delete_site_transient( 'wpl_request_api' );
				$WPL_Request->connect_server();
			} else {
				echo "<script>alert('Unable to reach WPLicense.com!');</script>";
				if ( WPL_check( 'transient', 'wpl_request_api' ) ) return;
				WPL_set( 'transient', 'wpl_request_api', array( 'api' => 'failed', 'json' => 'failed' ), false );
				$WPL_Request->connect_server();
			}
		} else {
			echo "<script>alert('Please try again!');</script>";
			WPL_set( 'transient', 'wpl_request_api', array( 'api' => 'failed', 'json' => 'failed' ), false );
			$WPL_Request->connect_server();
		}
		echo ("<SCRIPT LANGUAGE='JavaScript'>window.location.href='".$_SERVER['HTTP_REFERER']."';</SCRIPT>");
	}

	public function wpl_request_api(){
		global $WPL_Request, $WPL_VERSION;
		if ( version_compare( $WPL_VERSION, WPL_VERSION, '>' ) ) {
			set_site_transient( 'wpl_troubleshoot', true );
		}
		WPL_set( 'transient', 'wpl_request_api', array( 'api' => 'failed', 'json' => 'failed' ), false );
		$WPL_Request->connect_server();
	}
}