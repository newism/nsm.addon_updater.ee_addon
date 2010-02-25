<?php

$config['name']				= 'NSM Addon Updater';
$config['id']		 		= 'nsm_addon_updater';
$config['version']	 		= '1.0';
$config['description']		= 'Accessory for NSM Addon Updater.';

$config['nsm_addon_updater']['versions_xml'] 			= 'http://local.ee2/system/expressionengine/third_party/nsm_addon_updater/versions.xml';
$config['nsm_addon_updater']['custom_download_url'] 	= array('Nsm_addon_updater_acc', 'nsm_addon_updater_download_url');