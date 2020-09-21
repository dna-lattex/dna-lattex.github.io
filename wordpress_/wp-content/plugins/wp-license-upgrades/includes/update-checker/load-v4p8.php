<?php
require dirname(__FILE__) . '/Puc/v4p8/Autoloader.php';
new WPLPuc_v4p8_Autoloader();

require dirname(__FILE__) . '/Puc/v4p8/Factory.php';
require dirname(__FILE__) . '/Puc/v4/Factory.php';

//Register classes defined in this version with the factory.
foreach (
	array(
		'Plugin_UpdateChecker' => 'WPLPuc_v4p8_Plugin_UpdateChecker',
		'Theme_UpdateChecker'  => 'WPLPuc_v4p8_Theme_UpdateChecker',

		'Vcs_PluginUpdateChecker' => 'WPLPuc_v4p8_Vcs_PluginUpdateChecker',
		'Vcs_ThemeUpdateChecker'  => 'WPLPuc_v4p8_Vcs_ThemeUpdateChecker',

		'GitHubApi'    => 'WPLPuc_v4p8_Vcs_GitHubApi',
		'BitBucketApi' => 'WPLPuc_v4p8_Vcs_BitBucketApi',
		'GitLabApi'    => 'WPLPuc_v4p8_Vcs_GitLabApi',
	)
	as $pucGeneralClass => $pucVersionedClass
) {
	WPL_Factory::addVersion($pucGeneralClass, $pucVersionedClass, '4.8');
	//Also add it to the minor-version factory in case the major-version factory
	//was already defined by another, older version of the update checker.
	WPLPuc_v4p8_Factory::addVersion($pucGeneralClass, $pucVersionedClass, '4.8');
}

