<?php

$config['name']				= 'NSM Addon Updater';
$config['version']	 		= '0.1.1';

$config['nsm_addon_updater']['versions_xml'] 			= 'http://github.com/newism/nsm.addon_updater.ee_addon/raw/master/versions.xml';
$config['nsm_addon_updater']['custom_download_url'] 	= array('Nsm_addon_updater_acc', 'nsm_addon_updater_download_url');

// Local test XML
// $config['nsm_addon_updater']['versions_xml'] = 'http://local.expressionengine-addons.com/system/expressionengine/third_party/nsm_addon_updater/test_versions.xml';
