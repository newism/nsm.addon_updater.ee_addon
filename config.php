<?php

/**
 * Config file for NSM Addon Updater
 *
 * @package			NsmAddonUpdater
 * @version			1.3.0
 * @author			Leevi Graham <http://leevigraham.com> - Technical Director, Newism
 * @copyright 		Copyright (c) 2007-2012 Newism <http://newism.com.au>
 * @license 		Commercial - please see LICENSE file included with this distribution
 * @link			http://ee-garage.com/nsm-addon-updater
 */

if(!defined('NSM_ADDON_UPDATER_VERSION')) {
	define('NSM_ADDON_UPDATER_VERSION', '1.3.0');
	define('NSM_ADDON_UPDATER_NAME', 'NSM Addon Updater');
	define('NSM_ADDON_UPDATER_ADDON_ID', 'nsm_addon_updater');
}

$config['name'] 	= NSM_ADDON_UPDATER_NAME;
$config["version"] 	= NSM_ADDON_UPDATER_VERSION;

$config['nsm_addon_updater']['versions_xml'] 			= 'http://ee-garage.com/nsm-addon-updater/release-notes/feed';
$config['nsm_addon_updater']['custom_download_url'] 	= array('Nsm_addon_updater_acc', 'nsm_addon_updater_download_url');

// Local test XML
// $config['nsm_addon_updater']['versions_xml'] = 'http://local.expressionengine-addons.com/system/expressionengine/third_party/nsm_addon_updater/test_versions.xml';
