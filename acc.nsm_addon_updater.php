<?php

require PATH_THIRD.'nsm_addon_updater/config.php';

/**
 * NSM Addon Updater Accessory
 *
 * @package			NsmAddonUpdater
 * @version			1.3.0
 * @author			Leevi Graham <http://leevigraham.com> - Technical Director, Newism
 * @copyright 		Copyright (c) 2007-2012 Newism <http://newism.com.au>
 * @license 		Commercial - please see LICENSE file included with this distribution
 * @link			http://ee-garage.com/nsm-example-addon
 * @see				http://expressionengine.com/public_beta/docs/development/accessories.html
 */

class Nsm_addon_updater_acc 
{
	/**
	 * The accessory name
	 *
	 * @var string
	 **/
	var $name	 			= NSM_ADDON_UPDATER_NAME;

	/**
	 * Version
	 *
	 * @var string
	 **/
	var $version	 		= NSM_ADDON_UPDATER_VERSION;

	/**
	 * Description
	 *
	 * @var string
	 **/
	var $description		= 'Accessory for NSM Addon Updater.';

	/**
	 * Sections
	 *
	 * @var array
	 **/
	var $sections	 		= array();

	/**
	 * Cache lifetime
	 *
	 * @var int
	 **/
	var $cache_lifetime		= 86400;

	/**
	 * Is the addon in test mode
	 *
	 * @var boolean
	 **/
	var $test_mode			= false;

	/**
	 * Hide up-to-date addons
	 *
	 * @var boolean
	 **/
	var $hide_uptodate		= false;

	/**
	 * Hide addons that are incompatible
	 *
	 * @var boolean
	 **/
	var $hide_incompatible	= true;


	/**
	 * The cache directory for the addon
	 *
	 * @var string
	 **/
	var $cache_path			= false;

	/**
	 * Constructor
	 *
	 * @return void
	 * @author Leevi Graham
	 **/
	function __construct()
	{
		$this->addon_id		= $this->id = NSM_ADDON_UPDATER_ADDON_ID;
		$this->cache_path	= APPPATH . 'cache/' . NSM_ADDON_UPDATER_ADDON_ID;
	}

	/**
	* Set the sections and content for the accessory
	*
	* @access	public
	* @return	void
	*/
	function set_sections()
	{
		$EE =& get_instance();

		$EE->cp->load_package_js("accessory_tab");
		$EE->cp->load_package_css("accessory_tab");

		$this->sections['Available Updates'] = $EE->load->view("/accessory/index", array(), true); ; 
	}

	/**
	* Set the sections and content for the accessory
	*
	* @access	public
	* @return	void
	*/
	function process_ajax_feeds()
	{
		$EE			=& get_instance();
		$versions	= false;
		
		$EE->load->helper('file');
		
		$this->_prepCacheDirectory();
		if ($feeds = $this->_updateFeeds()) {

			foreach ($feeds as $addon_id => $feed) {
				
				include PATH_THIRD . '/' . $addon_id . '/config.php';

				$latest_version = 0;
				$data			=  array(
					'addon_name' 		=> $config['name'],
					'installed_version' => $config['version'],
					'download' 			=> false,
					'extension_class' 	=> $addon_id,
					'error'				=> false,
					'row_class'			=> false,
					'latest_version' 	=> 0,
				);
				
				if(!$feed instanceof SimpleXMLElement) {
					$data					= array_merge($data, $feed);
					$versions[$addon_id]	= $data;
					continue;
				}
				
				// XML from here on
				$namespaces = $feed->getNameSpaces(true);

				if (!empty($feed->channel->item)) {
					foreach ($feed->channel->item as $version) {
						$ee_addon		= $version->children($namespaces['ee_addon']);
						$version_number	= (string) $ee_addon->version;
						
						// version is greater than installed version
						if (version_compare($version_number, $config['version'], '>=') && version_compare($version_number, $latest_version, '>')) {
						    $latest_version			= $version_number;
							$versions[$addon_id]	= array_merge($data, array(
								'title' 			=> (string) $version->title,
								'notes' 			=> (string) $version->description,
								'docs_url' 			=> (string) $version->link,
								'download' 			=> false,
								'created_at'		=> $version->pubDate,
								'extension_class' 	=> $addon_id,
								'latest_version' 	=> $version_number,
								'row_class'			=> 'info',
							));

							if ($version->enclosure) {
								$versions[$addon_id]['download'] = array(
									'url'	=> (string)$version->enclosure['url'],
									'type'	=>  (string)$version->enclosure['type'],
									'size'	=> (string)$version->enclosure['length']
								);

								if (isset($config['nsm_addon_updater']['custom_download_url'])) {
									$versions[$addon_id]['download']['url'] = call_user_func(
										$config['nsm_addon_updater']['custom_download_url'],
										$versions[$addon_id]
									);
								}
							}
						}
					}
				}
				
				// the search for the latest version should be complete now
				if (version_compare($config['version'], $latest_version, '>=')) {
					// don't hide uptodate addons? output the correct message
					if (!$this->hide_uptodate) {
						$versions[$addon_id]	= array_merge($data, array(
							'error' 			=> 'This add-on is up-to-date',
							'latest_version' 	=> $latest_version,
							'row_class'			=> 'success',
						));
					} else {
						// remove uptodate addon from list
						unset($versions[$addon_id]);
					}
				}
				// clear the config
				unset($config);
			}
		}

		$EE->cp->load_package_js("accessory_tab");
		$EE->cp->load_package_css("accessory_tab");

		echo $EE->load->view("/accessory/updates", array('versions' => $versions), true);
		exit;
	}

	// =======================
	// = XML Feeds Functions =
	// =======================

	/**
	 * Loads all the feeds from the cache or new from the server
	 *
	 * @version		1.0.0
	 * @since		Version 1.0.0
	 * @access		private
	 * @return		array An array of RSS feed XML
	 **/
	public function _updateFeeds()
	{
		$EE =& get_instance();
		libxml_use_internal_errors(true);

		require_once PATH_THIRD . NSM_ADDON_UPDATER_ADDON_ID . "/libraries/Epicurl.php";

		$sources	= false;
		$feeds		= false;
		$mc			= EpiCurl::getInstance();

		foreach ($EE->addons->_packages as $addon_id => $addon) {
			
			$config_file = PATH_THIRD . '/' . $addon_id . '/config.php';

			if (!file_exists($config_file)) {
				continue;
			}

			include $config_file;

			$data = false;

			# Is there a file with the xml url?
			if (isset($config['nsm_addon_updater']['versions_xml'])) {

				$url = $config['nsm_addon_updater']['versions_xml'];

				# Get the XML again if it isn't in the cache
				if ($this->test_mode || ! $response = $this->_readCache(md5($url))) {

					log_message('debug', "Checking for updates via CURL: {$addon_id}");

					$c = false;
					$c = curl_init($url);
					
					curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
					@curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1);
					
					$curls[$addon_id]	= $mc->addCurl($c);
					$response			= $curls[$addon_id]->data;
					
					$this->_createCacheFile($response, md5($url));
					
					// if theres an error with the curl request set an error
					if (!in_array($curls[$addon_id]->code, array(200, 301, 302))) {
						$data = array(
							'error' => 'Could not find changelog for add-on',
							'row_class' => 'error'
						);
					}
				}
				if (!isset($data['error'])) {
					# If there isn't an error with the XML
					try {
						$xml	= @simplexml_load_string($response, 'SimpleXMLElement',  LIBXML_NOCDATA);
						$data	= $xml;
					} catch (Exception $e) {
						// problem with data
						$data = false;
					}
				}
				// data still false? mark as an error
				if (!$data) {
					$data	= array(
						'error'		=> "There was a problem processing the <a href='{$config['nsm_addon_updater']['versions_xml']}' target='_blank'>versions.xml</a> file for this add-on",
						'row_class' => 'error'
					);
				}
				
			} else {
				if (!$this->hide_incompatible) {
					$data = array(
						'error'		=> 'Addon doesn\'t have a NSM Addon Updater URL',
						'row_class' => ''
					);
				}
			}
			
			if ($data) {
				$feeds[$addon_id] = $data;
			}
			
			unset($config);
		}
		return $feeds;
	}

	/**
	 * Creates a cache file populated with data based on a URL
	 *
	 * @version		1.0.0
	 * @since		Version 1.0.0
	 * @access		private
	 * @param		$data string The data we need to cache
	 * @param		$url string A URL used as a unique identifier
	 * @return		void
	 **/
	private function _createCacheFile($data, $key)
	{
		$filepath = $this->cache_path ."/". $key . ".txt";
		$cache = write_file($filepath, $data);
		if (!$cache) {
			$this->throwError('Cannot create cache file');
		}
	}
	
	private function _prepCacheDirectory()
	{
		if (!is_dir($this->cache_path)) {
			if (!mkdir($this->cache_path."", 0777, true)) {
				$this->throwError('Cannot create the cache directory');
			}
		}
		if (!is_really_writable($this->cache_path)) {
			$this->throwError('Cannot write to the cache directory');
		}
	}
	

	private function throwError($error){
		$EE =& get_instance();
		
		$EE->cp->load_package_js("accessory_tab");
		$EE->cp->load_package_css("accessory_tab");
		echo $EE->load->view("/accessory/error", array('error' => $error), true);
		exit;
	}

	/**
	 * Modify the download URL
	 *
	 * @version		1.0.0
	 * @since		Version 1.0.0
	 * @access		private
	 * @param		$versions array 
	 * @return		array Modified versions URL
	 **/
	private function _readCache($key)
	{
		$cache			= false;
		$filepath		= $this->cache_path ."/". $key . ".txt";
		$cache_timeout	= $this->cache_lifetime + (rand(0,10) * 3600);
		$cache			= read_file($filepath);
		// cache exist?
		if (!$cache) {
			return false;
		}
		// cache timed out?
		if ((filemtime($filepath) + $cache_timeout) < time()) {
			@unlink($filepath);
			return false;
		}
		return $cache;
	}

	/**
	 * Modify the download URL
	 *
	 * @author your name
	 * @param $param
	 * @return return type
	 */
	public static function nsm_addon_updater_download_url($versions)
	{
		return $versions['download']['url'];
	}

}