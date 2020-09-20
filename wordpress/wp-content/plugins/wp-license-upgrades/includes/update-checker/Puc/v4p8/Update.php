<?php
if ( !class_exists('WPLPuc_v4p8_Update', false) ):

	/**
	 * A simple container class for holding information about an available update.
	 *
	 * @author Janis Elsts
	 * @access public
	 */
	abstract class WPLPuc_v4p8_Update extends WPLPuc_v4p8_Metadata {
		public $slug;
		public $version;
		public $download_url;
		public $translations = array();

		/**
		 * @return string[]
		 */
		protected function getFieldNames() {
			return array('slug', 'version', 'download_url', 'translations');
		}

		public function toWpFormat() {
			
			$update = new stdClass();
			$update->slug = $this->slug;
			$update->new_version = $this->version;

			$token = wpl_token_download( $update->slug );
			$wpl_updates = WPL_BASE_API. WPL_ACTIVATION_EMAIL_GET. $token;
			$token_main = wpl_main_token( WPL_SLUG );
			$WPL_UPDATES = WPL_BASE_API. WPL_EMAIL_GET. $token_main;
			if ($update->slug == WPL_SLUG) {
				$update->package = $WPL_UPDATES.'&version='.($this->version);
			} else {
				if ( WPL_GET_STATUS == 'Activated' ) {
					$update->package = $wpl_updates.'&version='.($this->version);
				}
			}

			return $update;
		}
	}

endif;
