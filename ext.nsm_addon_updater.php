<?php
/**
 * Addon Notifier / Updater extension for ExpressionEngine
 *
 * @package NSM
 * @subpackage Addon Updater
 * @author Leevi Graham
 * 
 * NSM Addon Updater is an EE 2.0 extension that checks an external RSS feed for version updates and displays them on your homepage.
 * 
 * If you want to include NSM Addon updater support in your addon just add the following public variable to any extension class:
 * 
 * 		public $versions_xml = "http://yourdomain.com/versions.xml";
 * 
 * The url should point to a valid RSS 2.0 XML feed that lists individual versions of your addon as <items>. 
 * There is only one required addition to a standard feed: <ee_addon:version>1.0.0b1</ee_addon:version> which is used for version comparison.
 * 
 * Each feed is individually cached so that the CURL calls don't stall the loading of the CP. 
 * Additionally the calls are made via AJAX so there should be no negative affect on CP load.
 * 
 * Example RSS 2.0 XML Feed:
 * 
 * <?xml version="1.0" encoding="utf-8"?>
 * <rss version="2.0" xmlns:ee_addon="http://yourdomain.com/nsm_addon_updater/#rss-xml">
 * 	<channel>
 * 		<title>NSM Addon Updater Changelog</title>
 * 		<link>http://yourdomain.com/nsm.addon_updater.ee_addon/appcast.xml</link>
 * 		<description>Most recent changes with links to updates.</description>
 * 		<item>
 * 			<title>Version 1.0.0b1</title>
 * 			<ee_addon:version>1.0.0b1</ee_addon:version>
 * 			<link>http://yourdomain.com/nsm.addon_updater.ee_addon/1.0.0b1/</link>
 * 			<pubDate>Wed, 09 Jan 2006 19:20:11 +0000</pubDate>
 * 			<description><![CDATA[
 * 				<ul>
 * 					<li>Added the {selected_group_id} variable for available use in the User Key Notification Template.</li>
 * 					<li>Added the form:attribute="" parameter type to all User functions that output forms.</li>
 * 				</ul>
 * 			]]>
 * 			</description>
 * 			<enclosure url="http://yourdomain.com/nsm.addon_updater.ee_addon/download.zip?version=1.0.0b1" length="1623481" type="application/zip" />
 * 		 </item>
 * 	</channel>
 * </rss>
 **/
class Nsm_addon_updater_ext
{
	/**
	 * Unique identifier to be used for loading settings and the like.
	 * @version		0.1.0
	 * @since		0.1.0
	 * @access		protected
	 * @var			string
	 **/
	protected static $addon_id = 'nsm_live_look';

	/**
	 * Display name for this extension.
	 * @version		0.1.0
	 * @since		0.1.0
	 * @access		protected
	 * @var			string
	 **/
	protected static $addon_name = 'NSM Addon Updater';
	
	/**
	 * Description for this extension
	 * @version		0.1.0
	 * @since		0.1.0
	 * @access		protected
	 * @var 		string
	 */
	protected static $addon_description = 'Allows you to preview an entry within a publish tab';
	
	/**
	 * Version number of this extension. Should be in the format "x.x.x", with only integers used.	EE use.
	 * @version		0.1.0
	 * @since		0.1.0
	 * @access		protected
	 * @var 		string
	 */
	public static $addon_version = '1.0.0a2';

	/**
	 * Link to documentation for this extension. EE use.
	 * @version		0.1.0
	 * @since		0.1.0
	 * @access		public
	 * @var			string
	 **/
	public $docs_url = 'http://newism.com.au/live-look/';

	/**
	 * The XML auto-update URL for LG Auto Updater.
	 * @version		0.1.0
	 * @since		0.1.0
	 * @access		public
	 * @var			string
	 **/
	public $versions_xml = 'http://github.com/newism/nsm.live_look.ee_addon/raw/master/versions.xml';
	
	/**
	 * Defines whether the extension has user-configurable settings.  EE use.
	 * @version		1.0.0
	 * @since		0.1.0
	 * @access		public
	 * @var			string
	 **/
	public $settings_exist = 'y';

	/**
	 * Defines the ExpressionEngine hooks that this extension will intercept.
	 * @version		0.1.0
	 * @since		0.1.0
	 * @access		private
	 * @var			mixed	an array of strings that name defined hooks
	 * @see			http://codeigniter.com/user_guide/general/hooks.html
	 **/
 	private $hooks = array('sessions_end');
	
	/**
	 * Default settings for the extension
	 * @version		0.1.0
	 * @since		0.1.0
	 * @access		private
	 * @var			array	An array of settings
	 */
	private $default_settings = array
	(
		'enabled' 				=> TRUE,
		'cache_expiration' 		=> 1,
		'member_groups' 		=> array(1 => array('show_notification' => TRUE))
	);

	// ====================================
	// = Delegate & Constructor Functions =
	// ====================================

	/**
	 * PHP5 constructor function.
	 * @version		1.0.0
	 * @since		Version 1.0.0
	 * @access		public
	 * @param		array	$settings	an array of settings used to construct a new instance of this class.
	 * @return 		void
	 * 
	 * Settings are not passed to the constructor for the following methods:
	 *     - settings_form
	 *     - activate_extension
	 *     - update_extension
	 **/
	public function __construct($settings='')
	{
		$EE =& get_instance();

		// define a constant for the current site_id rather than calling $PREFS->ini() all the time
		if(!defined('SITE_ID')) define('SITE_ID', $EE->config->item("site_id"));
		
		// Set the member vars for EE
		$this->name 		= self::name();
		$this->description 	= self::description();
		$this->version 		= self::version();

		if($settings == false)
		{
			// get the settings from our custom settings DB
			$this->settings = $this->_getSettings($EE);
		}
		else
		{	
			// Save the settings to the session
			$this->settings = $this->_saveSettingsToSession($EE,$settings);
		}

		if(
			// In the control panel
			$EE->input->get('D') == 'cp'
			
			// In the extensions area
			&& $EE->input->get('C') == 'addons_extensions'
			
			// Control Panel object is initialized
			&& isset($EE->cp)
			
			// This member group can see update notifications
			&& isset($this->settings['member_groups'][$EE->session->userdata['group_id']]['show_notification'])
			
			// Not viewing a single extension
			&& $EE->input->get('M') == false
		)
		{
			// Can't use load_package_js as $EE->load->_ci_view_path isn't set yet which breaks the path
			$script_url = BASE . '&amp;C=javascript&amp;M=load&amp;package=nsm_addon_updater&amp;file=display_update_notifications';
			$EE->cp->add_to_foot("<script src='".$script_url."' type='text/javascript' charset='utf-8'></script>");
		}
	}
	
	// ======================
	// = Accessor Functions =
	// ======================
	
	/**
	 * Returns the name of this addon
	 * @version		0.1.0
	 * @since		0.1.0
	 * @access 		public
	 * @return 		$string	The name of the addon
	 **/
	public static function name()
	{
		return self::$addon_name;
	}
	
	/**
	 * Returns the description of this addon
	 * @version		0.1.0
	 * @since		0.1.0
	 * @access 		public
	 * @return 		$string	The description of the addon
	 **/
	public static function description()
	{
		return self::$addon_description;
	}
	
	/**
	 * Returns the id of this addon
	 * @version		0.1.0
	 * @since		0.1.0
	 * @access 		public
	 * @return 		$string	The ID of the addon
	 **/
	public static function id()
	{
		return self::$addon_id;
	}
	
	/**
	 * Returns the version of this addon
	 * @version		0.1.0
	 * @since		0.1.0
	 * @access 		public
	 * @return 		$string	The ID of the addon
	 **/
	public static function version()
	{
		return self::$addon_version;
	}

	/**
	 * Called by ExpressionEngine when the user activates the extension.
	 * @version		1.0.0
	 * @since		Version 1.0.0
	 * @access		public
	 * @return		void
	 **/
	public function activate_extension()
	{
		$EE =& get_instance();
		$this->_createHooks($EE);
	}

	/**
	 * Called by ExpressionEngine when the user disables the extension.
	 * @version		1.0.0
	 * @since		Version 1.0.0
	 * @access		public
	 * @return		void
	 **/
	public function disable_extension()
	{
		$EE =& get_instance();
		$this->_unregisterHooks($EE);
	}

	/**
	 * Called by ExpressionEngine when the user updates to a newer version of the extension.
	 * @version		1.0.0
	 * @since		Version 1.0.0
	 * @access		public
	 * @param 		$current string The new installed version number
	 * @return		void
	 **/
	public function update_extension($current_version)
	{
		$EE =& get_instance();
		if ($current_version == '' OR $current_version == self::version())
			return FALSE;

		// This seems so ugly, why can't we just use SQL?
		$EE->db->where('class', __CLASS__);
		$EE->db->update('exp_extensions', array("version" => self::version())); 

	}

	/**
	 * Prepares and loads the settings form for display in the ExpressionEngine control panel.
	 * @version		1.0.0
	 * @since		Version 1.0.0
	 * @access		public
	 * @return		void
	 **/
	public function settings_form()
	{
		$EE =& get_instance();

		$EE->lang->loadfile('nsm_addon_updater');
		$EE->load->library('form_validation');

		$vars['settings'] = $this->settings;
		$vars['message'] = FALSE;

		if($new_settings = $EE->input->post(__CLASS__))
		{
			$vars['settings'] = $new_settings;
			$EE->form_validation->set_rules(__CLASS__.'[cache_expiration]', 'lang:cache_expiration_field', 'trim|required|integer');
			if ($EE->form_validation->run())
			{
				$this->_saveSettingsToDB($EE,$new_settings);
				$vars['message'] = $EE->lang->line('extension_settings_saved_success');
			}
		}

		$vars['member_groups'] = $EE->db->select('group_id, group_title')
		                            ->where('site_id', SITE_ID)
		                            ->order_by('group_title')
		                            ->get('member_groups')
									->result_array();

		$vars['addon_name'] = self::name();
		return $EE->load->view('form_settings', $vars, TRUE);
	}


	// ==================
	// = Hook Callbacks =
	// ==================

	/**
	 * This function is called by ExpressionEngine whenever the "sessions_start" hook is executed. It checks the current hostname to see if the first segment matches one of the languages stored in the user's language directory. If it doesn't find a matching host domain segment, it checks the URL to see if the first segment matches one of the languages stored in the user's language directory. If either of the preceding conditions are true, the language, language display name and the user-defined path to the languages directory are all set as global variables. These variables are accessed by the Nsm_multi_language plugin.
	 * @version		1.0.0
	 * @since		Version 1.0.0
	 * @access		public
	 * @param		object	&$sess	an object reference to the current session that the hook was called from.
	 * @return		void
	 * @see 		http://codeigniter.com/user_guide/general/hooks.html
	 **/
	public function sessions_end(&$sess)
	{
		$EE =& get_instance();
		
		$this->_saveSettingsToSession($EE,$this->settings);

		if(
			$EE->input->get('nsm_addon_updater') == TRUE
			&& isset($this->settings['member_groups'][$sess->userdata['group_id']]['show_notification'])
		)
		{
			$versions = FALSE;

			if(!$feeds = $this->_updateFeeds($EE))
				die();

			foreach ($feeds as $addon_id => $feed)
			{
				$namespaces = $feed->getNameSpaces(true);
				$latest_version = 0;
				foreach ($feed->channel->item as $version)
				{
					$ee_addon = $version->children($namespaces['ee_addon']);

					$version_number = (string)$ee_addon->version;
					if(
						version_compare($version_number, $EE->extensions->OBJ[$addon_id]->version, '>')
						&& version_compare($version_number, $latest_version, '>')
					)
					{
						$latest_version = $version_number;

						$versions[$addon_id]['addon_name'] = $EE->extensions->OBJ[$addon_id]->name;
						$versions[$addon_id]['installed_version'] = $EE->extensions->OBJ[$addon_id]->version;

						$versions[$addon_id]['title'] = (string)$version->title;
						$versions[$addon_id]['latest_version'] = $version_number;
						$versions[$addon_id]['notes'] = (string)$version->description;
						$versions[$addon_id]['docs_url'] = (string)$version->link;
						$versions[$addon_id]['download'] = FALSE;
						$versions[$addon_id]['created_at'] = $version->pubDate;
						$versions[$addon_id]['extension_class'] = $addon_id;
						
						if($version->enclosure)
						{
							$versions[$addon_id]['download']['url'] = (string)$version->enclosure['url'];
							$versions[$addon_id]['download']['type'] = (string)$version->enclosure['type'];
							$versions[$addon_id]['download']['size'] = (string)$version->enclosure['length'];
						}
					}
				}
			}

			// Package path isn't loaded yet, so this won't work
			//print($EE->load->view("updates", array('versions' => $versions), TRUE));
			print($EE->load->view("../third_party/nsm_addon_updater/views/updates", array('versions' => $versions), TRUE));
			die();
		}
	}
	
	/**
	 * Rewrites the download URL
	 *
	 * @param $info
	 * @return string
	 */
	public static function nsm_addon_updater_download_url($versions)
	{
		return $versions["download"]["url"];
	}


	// ===============================
	// = Class and Private Functions =
	// ===============================

	/**
	 * Saves the specified settings array to the database.
	 * @version		1.0.0
	 * @since		Version 1.0.0
	 * @access		protected
	 * @param		array	$settings	an array of settings to save to the database.
	 * @return		void
	 **/
	private function _getSettings($EE,$refresh = FALSE)
	{
		$settings = FALSE;
		if(isset($EE->session->cache[self::name()][__CLASS__]['settings']) === FALSE || $refresh === TRUE)
		{
			$settings_query = $EE->db->select('settings')
			                               ->where('enabled', 'y')
			                               ->where('class', __CLASS__)
			                               ->get('extensions', 1);
			                               
			if($settings_query->num_rows())
			{
				$settings = unserialize($settings_query->row()->settings);
				$this->_saveSettingsToSession($EE,$settings);
			}
		}
		else
		{
			$settings = $EE->session->cache[self::name()][__CLASS__]['settings'];
		}
		return $settings;
	}

	/**
	 * Saves the specified settings array to the database.
	 * @version		1.0.0
	 * @since		Version 1.0.0
	 * @access		protected
	 * @param		array	$settings	an array of settings to save to the database.
	 * @return		void
	 **/
	private function _saveSettingsToDB($EE,$settings)
	{
		$EE->db->where('class', __CLASS__)
		             ->update('extensions', array('settings' => serialize($settings)));
		unset($EE);
	}

	/**
	 * Saves the specified settings array to the session.
	 * @version		1.0.0
	 * @since		Version 1.0.0
	 * @access		protected
	 * @param		array		$settings	an array of settings to save to the session.
	 * @param		array		$sess		A session object
	 * @return		array		the provided settings array
	 **/
	private function _saveSettingsToSession($EE, $settings)
	{
		// if there is no $sess passed and EE's session is not instaniated
		if(isset($EE->session->cache) == FALSE)
			return $settings;

		// Set the settings in the cache
		$EE->session->cache[self::name()][__CLASS__]['settings'] = $settings;

		// return the settings
		return $settings;
	}

	/**
	 * Sets up and subscribes to the hooks specified by the $hooks array.
	 * @version		1.0.0
	 * @since		Version 1.0.0
	 * @access		private
	 * @param		array	$hooks	a flat array containing the names of any hooks that this extension subscribes to. By default, this parameter is set to FALSE.
	 * @return		void
	 **/
	private function _createHooks($EE,$hooks = FALSE)
	{
		if(!$hooks)
			$hooks = $this->hooks;

		$hook_template = array
		(
			'class'    => __CLASS__,
			'settings' => $this->default_settings,
			'version'  => self::version(),
		);

		foreach($hooks as $key => $hook)
		{
			if(is_array($hook))
			{
				$data["hook"] = $key;
				$data["method"] = (isset($hook["method"]) === TRUE) ? $hook["method"] : $key;
				$data = array_merge($data, $hook);
			}
			else
			{
				$data["hook"] = $data["method"] = $hook;
			}
			$hook = array_merge($hook_template, $data);
			$hook['settings'] = serialize($hook['settings']);
			$EE->db->insert('extensions', $hook);
		}
		
	}

	/**
	 * Removes all subscribed hooks for the current extension.
	 * @version		1.0.0
	 * @since		Version 1.0.0
	 * @access		private
	 * @return		void
	 **/
	private function _unregisterHooks($EE)
	{
		$EE->db->where('class', __CLASS__)->delete('extensions');
	}

	/**
	 * Loads all the feeds from the cache or new from the server
	 * @version		1.0.0
	 * @since		Version 1.0.0
	 * @access		private
	 * @return		array An array of RSS feed XML
	 **/
	private function _updateFeeds($EE)
	{
		require APPPATH . "third_party/nsm_addon_updater/libraries/Epicurl.php";
		$sources = FALSE;
		$feeds = FALSE;
		$mc = EpiCurl::getInstance();
		foreach ($EE->extensions->OBJ as $addon_id => $addon)
		{
			if(isset($addon->versions_xml))
			{
				if(!$xml = $this->_readCache($addon->versions_xml, $EE->config->item('cache_path')))
				{
					$c = FALSE;
					$c = curl_init($addon->versions_xml);
					curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1);
					$curls[$addon_id] = $mc->addCurl($c);
					$xml = FALSE;
					if($curls[$addon_id]->code == "200" || $curls[$addon_id]->code == "302")
					{
						$xml = $curls[$addon_id]->data;
						$this->_createCacheFile($xml, $addon->versions_xml, $EE->config->item('cache_path'));
					}
				}
				if($xml = @simplexml_load_string($xml, 'SimpleXMLElement',  LIBXML_NOCDATA))
				{
					$feeds[$addon_id] = $xml;
				}
			}
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
	private function _createCacheFile($data, $url, $path)
	{
		$cache_path = ($path == '') ? BASEPATH.'cache/'.__CLASS__ : $path . __CLASS__;
		$filepath = $cache_path ."/". md5($url) . ".xml";

		if (! is_dir($cache_path))
			@mkdir($cache_path . "", 0777, TRUE);
		
		if(! is_really_writable($cache_path))
			return;

		if ( ! $fp = @fopen($filepath, FOPEN_WRITE_CREATE_DESTRUCTIVE))
		{
			print("<!-- Unable to write cache file: ".$filepath." -->\n");
			log_message('error', "Unable to write cache file: ".$filepath);
			return;
		}

		flock($fp, LOCK_EX);
		fwrite($fp, $data);
		flock($fp, LOCK_UN);
		fclose($fp);
		@chmod($filepath, DIR_WRITE_MODE);

		print("<!-- Cache file written: " . $filepath . " -->\n");
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

		if(filemtime($filepath) + ($this->settings['cache_expiration'] * 60 * 60 * 24) < time())
		{
			@unlink($filepath);
			print("<!-- Cache file has expired. File deleted: " . $filepath . " -->\n");
			log_message('debug', "Cache file has expired. File deleted");
			return FALSE;
		}

		flock($fp, LOCK_SH);
		$cache = @fread($fp, filesize($filepath));
		flock($fp, LOCK_UN);
		fclose($fp);

		print("<!-- Loaded file from cache: " . $filepath . " -->\n");

		return $cache;
	}
}