<?php
/**
 * Installation operations
 *
 * @author Team USVN <contact@usvn.info>
 * @link http://www.usvn.info
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2-en.txt CeCILL V2
 * @copyright Copyright 2007, Team USVN
 * @since 0.5
 * @package install
 *
 * This software has been written at EPITECH <http://www.epitech.net>
 * EPITECH, European Institute of Technology, Paris - FRANCE -
 * This project has been realised as part of
 * end of studies project.
 *
 * $Id$
 */
class Install
{
	private static function _loadConfig($config_file)
	{
		return new USVN_Config_Ini($config_file, 'general', array("create" => true));
	}

	/**
	* This method will test connection to the database, load database schemas
	* and finally write the config file.
	*
	* Throw an exception in case of problems.
	*
	* @param string Path to the USVN config file
	* @param string Path to the SQL files
	* @param string Database host
	* @param string Database user
	* @param string Database password
	* @param string Database name
	* @param string Database table prefix (ex: usvn_)
	* @param string Database type (mysql or sqlite)
	* @param boolean Create the database before installing
	* @throw USVN_Exception
	*/
	static public function installDb($config_file, $path_sql, $host, $user, $password, $database, $prefix, $adapter, $createdb)
	{
		$params = array ('host' => $host,
		'username' => $user,
		'password' => $password,
		'dbname'   => $database);

		if ($createdb && ($adapter == 'PDO_MYSQL' || $adapter == 'MYSQLI')) {
			try {
				$tmp_params = $params;
				$tmp_params['dbname'] = "mysql";
				$db = Zend_Db::factory($adapter, $tmp_params);
				if ($adapter == 'PDO_MYSQL') {
					$db->query("CREATE DATABASE `{$database}`;");
				} else {
					/* @var $cnx mysqli */
					$cnx = $db->getConnection();
					$cnx->query("CREATE DATABASE `{$database}`;");
				}
				$db->closeConnection();
			} catch (Exception $e) {
				throw new USVN_Exception(T_("Can't create database\n") . $e->getMessage());
			}
		}

		try {
			$db = Zend_Db::factory($adapter, $params);
			$db->getConnection();
		}
		catch (Exception $e) {
			throw new USVN_Exception(T_("Can't connect to database.\n") ." ". $e->getMessage());
		}
		Zend_Db_Table::setDefaultAdapter($db);
		USVN_Db_Table::$prefix = $prefix;

		try {
			if ($adapter == "PDO_MYSQL" || $adapter == "MYSQLI") {
				USVN_Db_Utils::loadFile($db, $path_sql . "/mysql.sql");
			}
			else if ($adapter == "PDO_SQLITE") {
				USVN_Db_Utils::loadFile($db, $path_sql . "/sqlite.sql");
			}
            else {
                throw new USVN_Exception(T_("Invalid adapter %s.\n") . $adapter);
            }
		}
		catch (Exception $e) {
			try {
				USVN_Db_Utils::deleteAllTablesPrefixed($db, $prefix);
			}
			catch (Exception $e2) {
			}
			$db->closeConnection();
			throw new USVN_Exception(T_("Can't load SQL file.\n") . $e->getMessage());
		}
		$db->closeConnection();
		try {
			$config = Install::_loadConfig($config_file);
			/* @var $config USVN_Config */
			$array = array (
				"adapterName" => $adapter,
				"prefix" => $prefix,
				"options" => array (
					"host" => $host,
					"username" => $user,
					"password" => $password,
					"dbname" => $database
				)
			);
			if ($adapter == "PDO_SQLITE") {
				unset($array['options']['host']);
				unset($array['options']['username']);
				unset($array['options']['password']);
			}
			$config->database = $array;
			$config->save();
		}
		catch (Exception $e) {
			USVN_Db_Utils::deleteAllTablesPrefixed($db, $prefix);
			$db->closeConnection();
			throw new USVN_Exception(T_("Can't write config file %s.\n") ." ". $e->getMessage(),  $config_file);
		}
	}

	/**
	 * This method will  write the choosen language into config file.
	 *
	 * Throw an exception in case of problems.
	 *
	 * @param string Path to the USVN config file
	 * @param string Language
	 * @throw USVN_Exception
	 */
	static public function installLanguage($config_file, $language)
	{
		if (in_array($language, USVN_Translation::listTranslation())) {
			$config = Install::_loadConfig($config_file);
			$config->translation = array("locale"  => $language);
			$config->save();
		}
		else {
			throw new USVN_Exception(T_("Invalid language"));
		}
	}

	/**
	 * This method will write the choosen timezone into config file.
	 *
	 * Throw an exception in case of problems.
	 *
	 * @param string Path to the USVN config file
	 * @param string Language
	 * @throw USVN_Exception
	 */
	static public function installTimezone($config_file, $timezone)
	{
		$availableTimeZones = Zend_Locale_Data::getContent("en", "timezonestandard");
		if (array_key_exists($timezone, $availableTimeZones)) {
			$config = Install::_loadConfig($config_file);
			$config->timezone = $timezone;
			$config->save();
		}
		else {
			throw new USVN_Exception(T_("Invalid timezone"));
		}
	}

	/**
	 * This method will write the system local into config file.
	 *
	 * @param string Path to the USVN config file
	 */
	static public function installLocale($config_file)
	{
		$config = Install::_loadConfig($config_file);
		if (0 === strpos(PHP_OS, 'WIN')) {
			$config->system = array("locale" => 'en_US.UTF-8');
			$config->save();
			return;
		}
		exec("locale -a", $locales);
		foreach ($locales as $locale) {
			if (preg_match("/utf-?8/i", $locale)) {
				$config->system = array("locale" => $locale);
				$config->save();
				return ;
			}
		}
		throw new USVN_Exception(T_("Invalide locale\nPlease, install UTF-8 locale."));
	}

	/**
	 * This method will save into config file if user allow check for
	 * update.
	 *
	 * @param string Path to the USVN config file
	 * @param bool True if user allow collect of informations
	 */
	static public function installCheckForUpdate($config_file, $check)
	{
		$config = Install::_loadConfig($config_file);
		$config->update = array("checkforupdate" => $check, "lastcheckforupdate" => 0);
		$config->save();
	}

	/**
	* This method will add subversion path
	*
	* @param string Path to the USVN config file
	* @param string Path to subversion directory
	* @param string Path to subversion password file
	* @param string Path to subversion access file
	* @param string Url of subversion repository
	*/
	static public function installSubversion($config_file, $path, $passwd, $authz, $url)
	{
		if (substr($path, -1) != DIRECTORY_SEPARATOR && substr($path, -1) != '/') {
			$path .= DIRECTORY_SEPARATOR;
		}
		if (substr($url, -1) != '/') {
			$url .= '/';
		}
		$path = str_replace(DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $path);
		$config = Install::_loadConfig($config_file);
		if (file_exists($path) && is_writable($path)
		&& (file_exists($path . DIRECTORY_SEPARATOR . 'svn') || mkdir($path . DIRECTORY_SEPARATOR . 'svn'))) {
			$config->subversion = array(
			"path" => $path,
			"passwd" => $passwd,
			"authz" => $authz,
			"url" => $url
			);
			$config->save();
			if (!@touch($authz)) {
				throw new USVN_Exception(T_("Can't write access file %s.\n"),  $authz);
			}
		}
		else {
			throw new USVN_Exception(T_("Invalid subversion path \"%s\", please check if directory exist and is writable."), $path);
		}
	}

	/**
	* This method will write htaccess and config file with urls informations
	*
	* Throw an exception in case of problems.
	*
	* @param string Path to the USVN config file
	* @param string Path to the USVN htaccess file
	* @param string Url of usvn with or without /install
	* @param string Server host
	* @param boolean Is https?
	* @throw USVN_Exception
	*/
	static public function installUrl($config_file, $htaccess_file, $usvn_url, $server_host, $https)
	{
		if (preg_match("#(.*)/install.*#", $usvn_url, $regs)) {
			$path = $regs[1];
		}
		else {
			$path = $usvn_url;
		}
		if (substr($path, strlen($path) - 1, strlen($path)) == "/") {
			$path = rtrim($path, "/");
		}
		$config = Install::_loadConfig($config_file);
		if (!isset($config->url)) {
			$config->url = array();
		}
		$config->url->base = $path;
		$config->save();
		$content = <<<EOF
<Files *.ini>
Order Allow,Deny
Deny from all
</Files>
RewriteEngine on
RewriteCond %{REQUEST_URI} !/install*
RewriteBase {$path}/
RewriteRule !\.(js|ico|gif|jpg|png|css)$ index.php

EOF;
		if (@file_put_contents($htaccess_file, $content) === false) {
			throw new USVN_Exception(T_("Can't write htaccess file %s.\n"),  $htaccess_file);
		}
		@chmod($htaccess_file, 0644);
		if (php_sapi_name() != "cli") {
			if ($https) {
				$method = "https";
			}
			else {
				$method = "http";
			}
			$url = "{$method}://{$server_host}{$path}/login/";

			try {
				$client = new Zend_Http_Client($url, array(
					'maxredirects' => 0,
					'timeout'      => 30));
				$response = $client->request();

				if ($response->getStatus() == 404) {
					throw new USVN_Exception(T_("AllowOverride seems to be missing.\nPlease check your configuration settings and come back.\n$url"));
				}
			}
			catch (Zend_Http_Client_Adapter_Exception $e) {
				;
			}
		}
	}

	/**
	* This method will write create an admin
	*
	* Throw an exception in case of problems.
	*
	* @param string Path to the USVN config file
	* @param string Admin login
	* @param string Admin password
	* @param string Admin first name
	* @param string Admin last name
	* @param string Admin email
	* @throw USVN_Exception
	*/
	static public function installAdmin($config_file, $login, $password, $firstname, $lastname, $email)
	{
		if (empty($password)) {
			throw new USVN_Exception(T_("Password empty"));
		}
		$userTable = new USVN_Db_Table_Users();
		$user = $userTable->createRow();
		$user->login = $login;
		$user->password = $password;
		$user->firstname = $firstname;
		$user->lastname = $lastname;
		$user->email = $email;
		$user->is_admin = true;
		$user->secret_id = md5(time().mt_rand());
		$user->save();
	}

	/**
	* Mark USVN install
	*
	* @param string Path to the USVN config file
	* @throw USVN_Exception
	*/
	static public function installEnd($config_file)
	{
		$config = Install::_loadConfig($config_file);
		$config->version = "0.7 RC2";
		$config->save();
	}

	/**
	* Return true if USVN is not already install.
	*
	* @param string Path to the USVN config file
	* @return boolean
	* @throw USVN_Exception
	*/
	static public function installPossible($config_file)
	{
		if (!file_exists($config_file)) {
			return true;
		}
		$config = Install::_loadConfig($config_file);
		if (!isset($config->version)) {
			return true;
		}
		return false;
	}

	/**
	* Some configurations informations
	*
	* @param string Path to the USVN config file
	* @param string USVN page title
	* @return boolean
	* @throw USVN_Exception
	*/
	static public function installConfiguration($config_file, $title)
	{
		$title = rtrim($title);
		if (strlen($title) == 0) {
			throw new USVN_Exception(T_("Need a title."));
		}
		$config = Install::_loadConfig($config_file);
		$config->template = array("name" => "default");
		if (!isset($config->site)) {
			$config->site = array();
		}
		$config->site->title = strip_tags($title);
		$config->site->ico = "medias/default/images/USVN.ico";
		$config->site->logo = "medias/default/images/USVN-logo.png";
		if (!isset($config->url)) {
			$config->url = array();
		}
		$config->save();
	}

	/**
	* Get apache configuration
	*
	* @param string Path to the USVN config file
	* @return string apache config
	*/
	static public function getApacheConfig($config_file)
	{
		$config = Install::_loadConfig($config_file);
		$path = str_replace(DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $config->subversion->path . DIRECTORY_SEPARATOR);
		$location = preg_replace("#http[s]?://[^/]*#", "", $config->subversion->url);
		$location = str_replace("//", "/", $location);
		$res = "<Location $location>\n";
		$res .= "\tErrorDocument 404 default\n";
		$res .= "\tDAV svn\n";
		if (substr($config->subversion->url, 0, 8) == "https://") {
			$res .= "\tSSLRequireSSL\n";
		}
		$res .= "\tRequire valid-user\n";
		$res .= "\tSVNParentPath " . $path . "svn\n";
		$res .= "\tSVNListParentPath off\n";
		$res .= "\tAuthType Basic\n";
		$res .= "\tAuthName \"" . $config->site->title . "\"\n";
		$res .= "\tAuthUserFile " . $config->subversion->passwd . "\n";
		$res .= "\tAuthzSVNAccessFile " . $config->subversion->authz . "\n";
		$res .= "</Location>\n";
		return $res;
	}

	/**
	 * Check if subversion is install on the computer. Else throw exception
	 *
	 * @trhow USVN_Exception
	 */
	static public function checkSystem()
	{
		if (ini_get("safe_mode")) {
			throw new USVN_Exception(T_("USVN can't run with php's safe mode."));
		}

		if (USVN_ConsoleUtils::runCmd('svn  --config-dir /USVN/fake --version')) {
			throw new USVN_Exception(T_("Subversion is not install on your system. If you are under Windows install ") . "http://subversion.tigris.org/files/documents/15/39559/svn-1.4.5-setup.exe" . T_(" and after restart your system (WARNING it's mandatory). \n\nOtherwise under UNIX you probably need to install a package named subversion."));
		}

		if (isset($_SERVER['HTTPS'])) {
			$method = "https";
		}
		else {
			$method = "http";
		}
		$image = dirname($method . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']) . "/../medias/default/images/USVN-logo.png";
		if (php_sapi_name() != "cli") {
			if (function_exists("apache_get_modules") && !in_array("mod_rewrite", apache_get_modules())) {
				throw new  USVN_Exception(T_("mod_rewrite seems not to be loaded"));
			}
			else {
				try {
					$client = new Zend_Http_Client($image);
					$response = $client->request();

					if ($response->getStatus() != 200) {
						throw new USVN_Exception(T_("mod_rewrite seems not to be loaded"));
					}
				}
				catch (Zend_Http_Client_Adapter_Exception $e) {
					;
				}
			}
		}
		if (function_exists("apache_get_modules") && !in_array("mod_dav_svn", apache_get_modules())) {
			throw new  USVN_Exception(T_("mod_dav_svn seems not to be loaded"));
		}
	}
}
