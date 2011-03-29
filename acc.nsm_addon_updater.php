<?php

/**
 * NSM Addon Updater Accessory
 *
 * @package			NsmAddonUpdater
 * @version			1.1.0
 * @author			Leevi Graham <http://leevigraham.com> - Technical Director, Newism
 * @copyright 		Copyright (c) 2007-2010 Newism <http://newism.com.au>
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
	var $name	 		= 'NSM Add-on Updater';

	/**
	 * Version
	 *
	 * @var string
	 **/
	var $version	 	= '1.1.0';

	/**
	 * Description
	 *
	 * @var string
	 **/
	var $description	= 'Accessory for NSM Addon Updater.';

	/**
	 * Sections
	 *
	 * @var array
	 **/
	var $sections	 	= array();

	/**
	 * Cache lifetime
	 *
	 * @var int
	 **/
	var $cache_lifetime	= 86400;

	/**
	 * Is the addon in test mode
	 *
	 * @var boolean
	 **/
	var $test_mode		= FALSE;

	/**
	 * Constructor
	 *
	 * @return void
	 * @author Leevi Graham
	 **/
	function __construct()
	{
		$this->addon_id = $this->id = strtolower(substr(__CLASS__,0,-4));
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
		$versions = FALSE;

		if($feeds = $this->_updateFeeds())
		{
			foreach ($feeds as $addon_id => $feed)
			{
				$namespaces = $feed->getNameSpaces(true);
				$latest_version = 0;

				include PATH_THIRD . '/' . $addon_id . '/config.php';

				if(!empty($feed->channel->item))
				{
					foreach ($feed->channel->item as $version)
					{
						$ee_addon = $version->children($namespaces['ee_addon']);
						$version_number = (string)$ee_addon->version;

						if(version_compare($version_number, $config['version'], '>') && version_compare($version_number, $latest_version, '>') )
						{
						    $latest_version = $version_number;
							$versions[$addon_id] = array(
								'addon_name' 		=> $config['name'],
								'installed_version' => $config['version'],
								'title' 			=> (string)$version->title,
								'latest_version' 	=> $version_number,
								'notes' 			=> (string)$version->description,
								'docs_url' 			=> (string)$version->link,
								'download' 			=> FALSE,
								'created_at'		=> $version->pubDate,
								'extension_class' 	=> $addon_id
							);

							if($version->enclosure)
							{
								$versions[$addon_id]['download'] = array(
									'url' => (string)$version->enclosure['url'],
									'type' =>  (string)$version->enclosure['type'],
									'size' => (string)$version->enclosure['length']
								);

								if(isset($config['nsm_addon_updater']['custom_download_url']))
								{
									$versions[$addon_id]['download']['url'] = call_user_func($config['nsm_addon_updater']['custom_download_url'], $versions[$addon_id]);
								}
							}
						}
					}
				}
			}
		}

		$EE->cp->load_package_js("accessory_tab");
		$EE->cp->load_package_css("accessory_tab");

		$this->sections['Available Updates'] = $EE->load->view("/accessory/updates", array('versions' => $versions), TRUE); 
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

		require PATH_THIRD . "nsm_addon_updater/libraries/Epicurl.php";

		$sources = FALSE;
		$feeds = FALSE;
		$mc = EpiCurl::getInstance();

		foreach($EE->addons->_packages as $addon_id => $addon)
		{
			$config_file = PATH_THIRD . '/' . $addon_id . '/config.php';

			if(!file_exists($config_file))
				continue;

			include $config_file;

			# Is there a file with the xml url?
			if(isset($config['nsm_addon_updater']['versions_xml']))
			{
				$url = $config['nsm_addon_updater']['versions_xml'];

				# Get the XML again if it isn't in the cache
				if($this->test_mode || ! $xml = $this->_readCache(md5($url)))
				{

					log_message('debug', "Checking for updates via CURL: {$addon_id}");

					$c = FALSE;
					$c = curl_init($url);
					curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
					@curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1);
					$curls[$addon_id] = $mc->addCurl($c);
					$xml = FALSE;
					if($curls[$addon_id]->code == "200" || $curls[$addon_id]->code == "302")
					{
						$xml = $curls[$addon_id]->data;
						$this->_createCacheFile($xml, md5($url));
					}
				}
			}

			# If there isn't an error with the XML
			if($xml = @simplexml_load_string($xml, 'SimpleXMLElement',  LIBXML_NOCDATA))
			{
				$feeds[$addon_id] = $xml;
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
		$cache_path = APPPATH.'cache/' . __CLASS__;
		$filepath = $cache_path ."/". $key . ".xml";
	
		if (! is_dir($cache_path))
			mkdir($cache_path . "", 0777, TRUE);
		
		if(! is_really_writable($cache_path))
			return;

		if ( ! $fp = fopen($filepath, FOPEN_WRITE_CREATE_DESTRUCTIVE))
		{
			// print("<!-- Unable to write cache file: ".$filepath." -->\n");
			log_message('error', "Unable to write cache file: ".$filepath);
			return;
		}

		flock($fp, LOCK_EX);
		fwrite($fp, $data);
		flock($fp, LOCK_UN);
		fclose($fp);
		chmod($filepath, DIR_WRITE_MODE);

		// print("<!-- Cache file written: " . $filepath . " -->\n");
		log_message('debug', "Cache file written: " . $filepath);
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
		$cache = FALSE;
		$cache_path = APPPATH.'cache/' . __CLASS__;
		$filepath = $cache_path ."/". $key . ".xml";

		if ( ! file_exists($filepath))
			return FALSE;
		if ( ! $fp = fopen($filepath, FOPEN_READ))
			return FALSE;

		if( filemtime($filepath) + $this->cache_lifetime < time() )
		{
			unlink($filepath);
			// print("<!-- Cache file has expired. File deleted: " . $filepath . " -->\n");
			log_message('debug', "Cache file has expired. File deleted");
			return FALSE;
		}

		flock($fp, LOCK_SH);
		$cache = fread($fp, filesize($filepath));
		flock($fp, LOCK_UN);
		fclose($fp);

		//print("<!-- Loaded file from cache: " . $filepath . " -->\n");

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