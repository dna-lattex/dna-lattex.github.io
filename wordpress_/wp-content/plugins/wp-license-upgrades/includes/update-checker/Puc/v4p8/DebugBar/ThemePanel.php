<?php

if ( !class_exists('WPLPuc_v4p8_DebugBar_ThemePanel', false) ):

	class WPLPuc_v4p8_DebugBar_ThemePanel extends WPLPuc_v4p8_DebugBar_Panel {
		/**
		 * @var WPLPuc_v4p8_Theme_UpdateChecker
		 */
		protected $updateChecker;

		protected function displayConfigHeader() {
			$this->row('Theme directory', htmlentities($this->updateChecker->directoryName));
			parent::displayConfigHeader();
		}

		protected function getUpdateFields() {
			return array_merge(parent::getUpdateFields(), array('details_url'));
		}
	}

endif;
