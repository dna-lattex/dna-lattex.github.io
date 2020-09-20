<?php

require_once dirname( __FILE__ ). '/background-update-tester.php';

new WPL_Requests();

function wpl_add_menu_documentation() {
	add_submenu_page( 'admin.php?page=wpl_activation', 'WPLicense Documentation', 'Documentation', 'manage_options', 'wpl-documentation', 'wpl_documentation_page' );
}
add_action( 'admin_menu', 'wpl_add_menu_documentation' );

function wpl_documentation_page() {
	global $pagenow, $WPL_VERSION, $WPL_request_api, $WPL_items;
	echo '</pre><div class="wrap"><h2>WPLicense Documentation</h2>';
	?><hr>
	<div style="background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">
		<p>
		<h2>We at WPLicense offer WordPress software developed by third party developers. Our services are subject to the following Terms and Conditions:</h2><hr>
		&nbsp;&nbsp;1. We do not provide technical support and do not guarantee the proper functioning of the software.<br>
		&nbsp;&nbsp;2. As we do not modify any files or code for any of the available downloads, we do not bear any responsibility for any damage caused by installation/usage of downloaded item.<br>
		&nbsp;&nbsp;3. WPLicense reserves the right to re-publish or un-publish any of the listed download items at any time in the future.<br>
		&nbsp;&nbsp;4. All the users are required to abide by their install limits, any means of cheating or bypassing restrictions will result in ban from accessing the website resources.<br>
		&nbsp;&nbsp;5. These Terms and conditions are subject to change at any time without any notice to the website users.</p>
		<strong>Please note that if you do not accept the terms mentioned above, then you are requested to stop using our services.</strong>
		</p>
	</div><hr>
	<div style="background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">
		<p>
		<h2>How To?</h2><hr>
		<?php if ( WPL_GET_STATUS !== 'Activated' ) { ?>
		How to <strong>Activate</strong> the WPLicense Upgrades plugin: <strong><a href="admin.php?page=wpl_activation">Click here</a></strong><br>
		<?php } else { ?>
		How to <strong>Install</strong> a WordPress Theme/Plugin: <strong><a href="admin.php?page=wpl-install">Click here</a></strong><br>
		How to <strong>Update</strong> a WordPress Theme/Plugin: <strong><a href="update-core.php">Click here</a></strong> or <strong><a href="admin.php?page=wpl-install&plugin_status=installed">Click here</a></strong><br>
		How to <strong>ReInstall</strong> a WordPress Theme/Plugin: <strong><a href="admin.php?page=wpl-install&plugin_status=installed">Click here</a></strong><br>
		How to <strong>Activate</strong> other theme/plugin: <strong><a href="admin.php?page=wpl-settings">Click here</a></strong> & <strong><a href="admin.php?page=wpl-settings&tab=activate">Click here</a></strong><br>
		<strong><a href="https://wplicense.com/how-to-register/" target="_blank">How to register?</a></strong><br>
		<strong><a href="https://wplicense.com/faq/" target="_blank">Frequently Asked Questions</a></strong><br>
		<?php } ?>
		<hr><span style="color: #ff0000;">Cannot activate WPLicense Upgrades license? Deactivate the WPLicense Upgrades plugin then try again.<br>
		If it still does not work. Click the <strong>Check connection to server</strong> button below and then try again.</span>
		</p>
	</div><hr>
	<div style="background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">
		<h2>Need Assistance?</h2><hr>
		<p>Website: https://wplicense.net/<br>
		Website: https://wplicense.com/<br>
		Email: wplicense@gmail.com<br>
		Facebook: https://www.facebook.com/wplicense/ (<span style="color: #ff0000;">*recommended</span>)<br>
		Background Update Tester: <strong><a href="admin.php?page=wpl_background-updates-debugger">Click here</a></strong></p>
	</div><hr>
	<div style="background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">
		<p><h2>Information</h2><hr>
		<table class="form-table">
			<th scope="row"><label>
				Plugin:<br>
				Current Version:<br>
				Latest Version:<br>
				Web Server:<br>
				PHP Version:<br>
				Timezone:<br>
				Domain:<br>
				Status:<br>
			</label></th>
			<td><label><strong>
				<?php
				echo WPL_NAME.'<br>';
				echo WPL_VERSION.'<br>';
				echo $WPL_VERSION.'<br>';
				echo ( isset( $_SERVER['SERVER_SOFTWARE'] ) ? $_SERVER['SERVER_SOFTWARE'] : 'unknown' ).'<br>';
				if ( function_exists( 'phpversion' ) ) echo phpversion().'<br>';
				echo WPL_TIMEZONE_DEFAULT.'<br>';
				echo str_ireplace( 'www.', '', wp_parse_url( home_url(), PHP_URL_HOST ) );
				if ( !empty( $WPL_items ) && is_array( $WPL_items ) ) {
					if ( defined( 'WPL_FEE' ) ) {
						echo '&nbsp;(<span style="color: #39b54a;">*Registered</span>)<br>' ;
					} else {
						echo '&nbsp;(<span style="color: #ff0000;">*Unregistered</span>)<br>';
					}
				} else {
					echo '&nbsp;(<span style="color: #ff0000;">*unknown</span>)<br>';
				}
				if ( !empty( $WPL_request_api ) ) {
					echo '<span style="color: #ff0000;">Connecting...</span>';
				} else {
					echo '<span style="color: #39b54a;">Connected</span>';
				}
				?></strong>
			</label></td>
		</table>
		<?php
		if ( !empty( $WPL_items ) && is_array( $WPL_items ) && !defined( 'WPL_FEE' ) ) {
			echo '<span style="color: #ff0000;">*</span><strong><a href="https://wplicense.com/how-to-register/" target="_blank">How to register?</a> Did you sign up?</strong>';
			wpl_script_request('wpl_check_vips', 'wpl_click_reload', 'spinner_check_vips', 'Click here', false, true);
			echo '</p>';
		}
		wpl_script_request('wpl_request_api', 'wpl_request_api', 'spinner_request_api', 'Troubleshoot', false, false);
		wpl_script_request('wpl_check_connect', 'wpl_check_connect', 'spinner_check_connect', 'Check connection to server', true, true);
		wpl_script_request('wpl_check_version', 'wpl_check_version', 'spinner_check_version', 'Check new version', true, false);
		?>
		<br><br>
	</div>
	<?php
	echo '</div>';
}