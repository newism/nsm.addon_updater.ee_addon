<?php

class Nsm_addon_updater_acc 
{
	var $name	 		= 'NSM Add-on Updater';
	var $id	 			= 'nsm_addon_updater';
	var $version	 	= '0.1.0';
	var $description	= 'Accessory for NSM Addon Updater.';
	var $sections	 	= array();
	var $cache_lifetime	= 86400;
	var $test_mode		= FALSE;

	/**
	* Set Sections
	*
	* Set content for the accessory
	*
	* @access	public
	* @return	void
	*/
	function set_sections()
	{
		$EE =& get_instance();
		$versions = FALSE;

		if($feeds = $this->_updateFeeds($EE))
		{
			foreach ($feeds as $addon_id => $feed)
			{
				$namespaces = $feed->getNameSpaces(true);
				$latest_version = 0;

				include PATH_THIRD . '/' . $addon_id . '/config.php';

				foreach ($feed->channel->item as $version)
				{
					$ee_addon = $version->children($namespaces['ee_addon']);
					$version_number = (string)$ee_addon->version;

					if(version_compare($version_number, $config['version'], '>') && version_compare($version_number, $latest_version, '>') )
					{
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
		$EE->cp->load_package_js("accessory_tab");
		$EE->cp->load_package_css("accessory_tab");

		$this->sections['Available Updates'] = $EE->load->view("updates", array('versions' => $versions), TRUE); 
	}
	
	// =======================
	// = XML Feeds Functions =
	// =======================

	/**
	 * Loads all the feeds from the cache or new from the server
	 * @version		1.0.0
	 * @since		Version 1.0.0
	 * @access		private
	 * @return		array An array of RSS feed XML
	 **/
	public function _updateFeeds($EE)
	{
		require APPPATH . "third_party/nsm_addon_updater/libraries/Epicurl.php";
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
				if($this->test_mode || !$xml = $this->_readCache($url, $EE->config->item('cache_path')))
				{
					$c = FALSE;
					$c = curl_init($url);
					curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1);
					$curls[$addon_id] = $mc->addCurl($c);
					$xml = FALSE;
					if($curls[$addon_id]->code == "200" || $curls[$addon_id]->code == "302")
					{
						$xml = $curls[$addon_id]->data;
						$this->_createCacheFile($xml, md5($url), $EE->config->item('cache_path'));
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
	 * @version		1.0.0
	 * @since		Version 1.0.0
	 * @access		private
	 * @param		$data string The data we need to cache
	 * @param		$url string A URL used as a unique identifier
	 * @return		void
	 **/
	private function _createCacheFile($data, $filename, $path)
	{
		$cache_path = ($path == '') ? BASEPATH.'expressionengine/cache/'.__CLASS__ : $path . __CLASS__;
		$filepath = $cache_path ."/". $filename . ".xml";

		if (! is_dir($cache_path))
			@mkdir($cache_path . "", 0777, TRUE);
		
		if(! is_really_writable($cache_path))
			return;

		if ( ! $fp = @fopen($filepath, FOPEN_WRITE_CREATE_DESTRUCTIVE))
		{
			//print("<!-- Unable to write cache file: ".$filepath." -->\n");
			log_message('error', "Unable to write cache file: ".$filepath);
			return;
		}

		flock($fp, LOCK_EX);
		fwrite($fp, $data);
		flock($fp, LOCK_UN);
		fclose($fp);
		@chmod($filepath, DIR_WRITE_MODE);

		//print("<!-- Cache file written: " . $filepath . " -->\n");
		log_message('debug', "Cache file written: " . $filepath);
	}

	/**
	 * Reads data from a file cache
	 * @version		1.0.0
	 * @since		Version 1.0.0
	 * @access		private
	 * @param		$url string A URL used as a unique identifier
	 * @return		string The cached data
	 **/
	private function _readCache($url,$path)
	{
		$cache_path = ($path == '') ? BASEPATH.'cache/'.__CLASS__ : $path . "nsm_addon_updater".__CLASS__;
		$filepath = $cache_path ."/". md5($url) . ".xml";

		if ( ! @file_exists($filepath))
			return FALSE;
	
		if ( ! $fp = @fopen($filepath, FOPEN_READ))
			return FALSE;

		if( filemtime($filepath) + $this->cache_lifetime < time() )
		{
			@unlink($filepath);
			//print("<!-- Cache file has expired. File deleted: " . $filepath . " -->\n");
			log_message('debug', "Cache file has expired. File deleted");
			return FALSE;
		}

		flock($fp, LOCK_SH);
		$cache = @fread($fp, filesize($filepath));
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