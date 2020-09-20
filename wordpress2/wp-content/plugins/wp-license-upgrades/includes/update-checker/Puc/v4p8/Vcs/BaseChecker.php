<?php
if ( !interface_exists('WPLPuc_v4p8_Vcs_BaseChecker', false) ):

	interface WPLPuc_v4p8_Vcs_BaseChecker {
		/**
		 * Set the repository branch to use for updates. Defaults to 'master'.
		 *
		 * @param string $branch
		 * @return $this
		 */
		public function setBranch($branch);

		/**
		 * Set authentication credentials.
		 *
		 * @param array|string $credentials
		 * @return $this
		 */
		public function setAuthentication($credentials);

		/**
		 * @return WPLPuc_v4p8_Vcs_Api
		 */
		public function getVcsApi();
	}

endif;
