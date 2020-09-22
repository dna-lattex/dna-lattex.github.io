<?php

require_once dirname( __FILE__ ). '/wp-license-activation.php';

add_action( 'wp_ajax_wpl_action_reinstall', 'wpl_action_reinstall' );
add_action( 'wp_ajax_nopriv_wpl_action_reinstall', 'wpl_action_reinstall' );

function wpl_action_reinstall(){
	include_once dirname( __FILE__ ). '/libraries/php/wpl-actions.php';
	if ( isset( $_POST['slug'] ) && isset( $_POST['type'] ) && isset( $_POST['action'] ) && $_POST['action'] == 'wpl_action_reinstall' ) {
		$token = wpl_token_download( $_POST['slug'] );
		$source = WPL_BASE_API. WPL_ACTIVATION_EMAIL_GET. $token. '&install=true';
		if ( $_POST['type'] == 'theme' ) {
			wpl_ajax_reinstall_theme( $source, true );
		} elseif ( $_POST['type'] == 'plugin' ) {
			wpl_ajax_reinstall_plugin( $source, true );
		}
	}
}

function wpl_install_update( $id, $slug, $type, $spin, $name ) {
	$id = strtolower($id);
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
						action: "wpl_action_reinstall",
						type: "<?php echo $type;?>",
						slug: "<?php echo $slug;?>",
					},
					beforeSend: function(){
						window.onbeforeunload = function(event) {
						  event.returnValue = "Actions may not complete if you navigate away from this page.";
						};
						jQuery('#<?php echo $id;?>').hide();
						jQuery('#<?php echo $spin;?>').show();
					},
					success: function(response){
						window.onbeforeunload = null;
						jQuery('.display-result').html(response);
						jQuery('#<?php echo $spin;?>').hide();
						jQuery('#<?php echo $id;?>').show();
					},
					error: function( jqXHR, textStatus, errorThrown ){
						jQuery('#<?php echo $spin;?>').hide();
						jQuery('#<?php echo $id;?>').show();
					}
				});
			}
		});
	});
	</script>
	<button type="button" style="min-width: 73.5px;" value="<?php echo $slug ?>" class="button-primary" id="<?php echo $id ?>"><?php echo $name ?></button>
	<img id="<?php echo $spin ?>" src="<?php echo esc_attr(WPL_SPIN); ?>" alt="..." style="vertical-align:bottom; max-height: 30px; display:none">
	<span class="display-result" style="display:none"></span>
	<?php
}

function wpl_filter_action_links( $action_links, $item_slug, $item, $view_context ) {
	$api = !empty( $item['api'] ) ? $item['api'] : '';
	$type = !empty( $item['itype'] ) ? $item['itype'] : '';
	$slug = !empty( $item['slug'] ) ? $item['slug'] : '';
	$path = !empty( $item['path'] ) ? $item['path'] : '';
	$price = !empty( $item['price'] ) ? $item['price'] : '';
	echo '<script type="text/javascript" src="'.plugins_url('libraries/js/confirm.js',__FILE__).'"></script>';
	if ( !empty( $api ) ) {
		if ( !empty( $price ) ) {
			if ( $type === 'theme' && !is_dir( WPL_PATH_THEME. $slug ) ) {
				$action_links = array('wplicense_premium_registration' => wpl_install_update( 'install_'.$slug, $slug, $type, 'install_spin_'.$slug, 'Install' ) );
			}
			if ( $type === 'plugin' && !is_dir( WPL_PATH_PLUGIN. $slug) ) {
				$action_links = array('wplicense_premium_registration' => wpl_install_update( 'install_'.$slug, $slug, $type, 'install_spin_'.$slug, 'Install' ) );
			}
		} else {
			$action_links = array('wplicense_premium_registration' => '<button type="button" class="button-primary" onclick="return wplPremium()">Premium</button>' );
		}
	} else {
		$action_links = array('<button style="min-width: 73.5px; cursor: not-allowed;" class="button-primary" disabled>Install</button>');
	}
	if ( is_dir( WPL_PATH_PLUGIN. $slug ) || is_dir( WPL_PATH_THEME. $slug ) ) {
		if ( !empty( $price ) ) {
			if ( $type === 'theme' && is_dir( WPL_PATH_THEME. $slug ) ) {
				$wp_theme = wp_get_theme( $slug );
				if ( version_compare( $wp_theme->get('Version'), $item['available_version'], '<' ) ) {
					$action_links = array('wplicense_premium_registration' => wpl_install_update( 'update_'.$slug, $slug, $type, 'update_spin_'.$slug, 'Update' ) );
				} else {
					$action_links = array('<button style="min-width: 73.5px; cursor: not-allowed;" class="button-primary" disabled>Installed</button>');
				}
			}
			if ( $type === 'plugin' && is_dir( WPL_PATH_PLUGIN. $slug ) ) {
				if ( version_compare( $item['installed_version'], $item['available_version'], '<' ) ) {
					$action_links = array('wplicense_premium_registration' => wpl_install_update( 'update_'.$slug, $slug, $type, 'update_spin_'.$slug, 'Update' ));
				} else {
					$action_links = array('<button style="min-width: 73.5px; cursor: not-allowed;" class="button-primary" disabled>Installed</button>');
				}
			}
		} else {
			$action_links = array('wplicense_premium_registration' => '<button type="button" class="button-primary" onclick="return wplPremium()">Premium</button>' );
		}
	}
	return $action_links;
}

add_action( 'wplicense_register', 'wpl_required_themes_plugins' );

function wpl_required_themes_plugins() {
	$WPL_items = WPL_get( 'option', 'wpl_plugins_themes', array() );
	foreach ( $WPL_items as $value ) {
		$token = wpl_token_download( $value['slug'] );
		if ( !empty( $value['name'] ) && !empty( $value['slug'] ) && !empty( $value['api'] ) ) {
			$plugins[] = array(
				'name' 			=> $value['name'],
				'slug' 			=> $value['slug'],
				'url' 			=> !empty($value['url']) ? $value['url'] : '',
				'version' 		=> $value['version'],
				'type' 			=> $value['type'],
				'price' 		=> !empty($value['price']) ? $value['price'] : '',
				'path' 			=> $value['path'],
				'api' 			=> $value['api'],
				'categories' 	=> $value['categories'],
				'time' 			=> !empty($value['time']) ? $value['time'] : '',
				'source' 		=> WPL_BASE_API. $value['api']. $token. '&install=true',
			);
		}
	}
	if ( empty( $plugins ) ) {
		$plugins = array( array( 'name' => ' ', 'slug' => 'empty' ) );
	}
	$config = array(
		'id'           => 'wplicense',
		'default_path' => '',
		'menu'         => 'wpl-install',
		'parent_slug'  => 'admin.php',
		'capability'   => 'manage_options',
		'has_notices'  => false,
		'dismissable'  => true,
		'dismiss_msg'  => '',
		'is_automatic' => false,
		'message'      => '',
	);
	wplicense( $plugins, $config );
}