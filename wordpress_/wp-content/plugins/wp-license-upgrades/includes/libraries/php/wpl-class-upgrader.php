<?php

include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );

class WPL_Plugin_Upgrader extends Plugin_Upgrader {
	public function install( $package, $args = array() ) {

		$defaults    = array(
			'clear_update_cache' => true,
		);
		$parsed_args = wp_parse_args( $args, $defaults );

		$this->init();
		$this->install_strings();

		add_filter( 'upgrader_source_selection', array( $this, 'check_package' ) );
		if ( $parsed_args['clear_update_cache'] ) {
			// Clear cache so wp_update_plugins() knows about the new plugin.
			add_action( 'upgrader_process_complete', 'wp_clean_plugins_cache', 9, 0 );
		}

		$this->run(
			array(
				'package'           => $package,
				'destination'       => WP_PLUGIN_DIR,
				'clear_destination' => true, // Do not overwrite files.
				'clear_working'     => true,
				'hook_extra'        => array(
					'type'   => 'plugin',
					'action' => 'install',
				),
			)
		);

		remove_action( 'upgrader_process_complete', 'wp_clean_plugins_cache', 9 );
		remove_filter( 'upgrader_source_selection', array( $this, 'check_package' ) );

		if ( ! $this->result || is_wp_error( $this->result ) ) {
			return $this->result;
		}

		// Force refresh of plugin update information
		wp_clean_plugins_cache( $parsed_args['clear_update_cache'] );

		return true;
	}
	public function install_strings() {
		$this->strings['no_package'] = __( 'Installation package not available.' );
		/* translators: %s: package URL */
		$this->strings['downloading_package'] = sprintf( __( 'Downloading installation package from %s&#8230;' ), '<span class="code">%s</span>' );
		$this->strings['unpack_package']      = __( 'Unpacking the package&#8230;' );
		$this->strings['installing_package']  = __( 'Installing the plugin&#8230;' );
		$this->strings['remove_old']          = __( 'Removing the old version of the plugin&#8230;' );
		$this->strings['no_files']            = __( 'The plugin contains no files.' );
		$this->strings['process_failed']      = __( 'Plugin installation failed.' );
		$this->strings['process_success']     = __( 'Plugin installed successfully.' );
	}
}

class WPL_Theme_Upgrader extends Theme_Upgrader {
	public function install( $package, $args = array() ) {

		$defaults    = array(
			'clear_update_cache' => true,
		);
		$parsed_args = wp_parse_args( $args, $defaults );

		$this->init();
		$this->install_strings();

		add_filter( 'upgrader_source_selection', array( $this, 'check_package' ) );
		add_filter( 'upgrader_post_install', array( $this, 'check_parent_theme_filter' ), 10, 3 );
		if ( $parsed_args['clear_update_cache'] ) {
			// Clear cache so wp_update_themes() knows about the new theme.
			add_action( 'upgrader_process_complete', 'wp_clean_themes_cache', 9, 0 );
		}

		$this->run(
			array(
				'package'           => $package,
				'destination'       => get_theme_root(),
				'clear_destination' => true, //Do not overwrite files.
				'clear_working'     => true,
				'hook_extra'        => array(
					'type'   => 'theme',
					'action' => 'install',
				),
			)
		);

		remove_action( 'upgrader_process_complete', 'wp_clean_themes_cache', 9 );
		remove_filter( 'upgrader_source_selection', array( $this, 'check_package' ) );
		remove_filter( 'upgrader_post_install', array( $this, 'check_parent_theme_filter' ) );

		if ( ! $this->result || is_wp_error( $this->result ) ) {
			return $this->result;
		}

		// Refresh the Theme Update information
		wp_clean_themes_cache( $parsed_args['clear_update_cache'] );

		return true;
	}
	public function install_strings() {
		$this->strings['no_package'] = __( 'Installation package not available.' );
		/* translators: %s: package URL */
		$this->strings['downloading_package'] = sprintf( __( 'Downloading installation package from %s&#8230;' ), '<span class="code">%s</span>' );
		$this->strings['unpack_package']      = __( 'Unpacking the package&#8230;' );
		$this->strings['installing_package']  = __( 'Installing the theme&#8230;' );
		$this->strings['remove_old']          = __( 'Removing the old version of the theme&#8230;' );
		$this->strings['no_files']            = __( 'The theme contains no files.' );
		$this->strings['process_failed']      = __( 'Theme installation failed.' );
		$this->strings['process_success']     = __( 'Theme installed successfully.' );
		/* translators: 1: theme name, 2: version */
		$this->strings['process_success_specific'] = __( 'Successfully installed the theme <strong>%1$s %2$s</strong>.' );
		$this->strings['parent_theme_search']      = __( 'This theme requires a parent theme. Checking if it is installed&#8230;' );
		/* translators: 1: theme name, 2: version */
		$this->strings['parent_theme_prepare_install'] = __( 'Preparing to install <strong>%1$s %2$s</strong>&#8230;' );
		/* translators: 1: theme name, 2: version */
		$this->strings['parent_theme_currently_installed'] = __( 'The parent theme, <strong>%1$s %2$s</strong>, is currently installed.' );
		/* translators: 1: theme name, 2: version */
		$this->strings['parent_theme_install_success'] = __( 'Successfully installed the parent theme, <strong>%1$s %2$s</strong>.' );
		/* translators: %s: theme name */
		$this->strings['parent_theme_not_found'] = sprintf( __( '<strong>The parent theme could not be found.</strong> You will need to install the parent theme, %s, before you can use this child theme.' ), '<strong>%s</strong>' );
	}
}