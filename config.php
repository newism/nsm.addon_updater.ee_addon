<?php

/**
 * Config file for NSM Addon Updater
 *
 * @package			NsmAddonUpdater
 * @version			1.1.0
 * @author			Leevi Graham <http://leevigraham.com> - Technical Director, Newism
 * @copyright 		Copyright (c) 2007-2010 Newism <http://newism.com.au>
 * @license 		Commercial - please see LICENSE file included with this distribution
 * @link			http://ee-garage.com/nsm-addon-updater
 */


$config['name']				= 'NSM Addon Updater';
$config['version']	 		= '1.1.0';

$config['nsm_addon_updater']['versions_xml'] 			= 'http://ee-garage.com/nsm-addon-updater/release-notes/feed';
$config['nsm_addon_updater']['custom_download_url'] 	= array('Nsm_addon_updater_acc', 'nsm_addon_updater_download_url');

// Local test XML
// $config['nsm_addon_updater']['versions_xml'] = 'http://local.expressionengine-addons.com/system/expressionengine/third_party/nsm_addon_updater/test_versions.xml';
