<?php
/**
 * Check for update
 *
 * @author Team USVN <contact@usvn.info>
 * @link http://www.usvn.info
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2-en.txt CeCILL V2
 * @copyright Copyright 2007, Team USVN
 * @since 0.7
 * @package usvn
 *
 * This software has been written at EPITECH <http://www.epitech.net>
 * EPITECH, European Institute of Technology, Paris - FRANCE -
 * This project has been realised as part of
 * end of studies project.
 *
 * $Id$
 */
class USVN_Update
{
	/**
	 * @return string Path where available version number is stored
	 */
	static private function getVersionFilePath()
	{
		$config = Zend_Registry::get('config');
		return ($config->subversion->path . DIRECTORY_SEPARATOR . ".usvn-version");
	}
	
	/**
	 * @return bool True if we need to check update
	 */
	static public function itsCheckForUpdateTime()
	{
		$config = Zend_Registry::get('config');
		if (isset($config->update->lastcheckforupdate)) {
			if ($config->update->lastcheckforupdate > (time() - (60 * 60 * 24))) {
				return false;
			}
		}
		return true;
	}
	
	/**
	 * Return available version on http://www.usvn.info
	 * 
	 * @return string
	 */
	static public function getUSVNAvailableVersion()
	{
		$config = Zend_Registry::get('config');
		$version = @file_get_contents(USVN_Update::getVersionFilePath());
		if ($version === false) {
			return $config->version;
		}
		return $version;
	}
	
	/**
	 * Update file with USVN version number
	 */
	static public function updateUSVNAvailableVersionNumber()
	{
		if (USVN_Update::itsCheckForUpdateTime()) {
			$config = Zend_Registry::get('config');
			$config->update = array("lastcheckforupdate" => time());
			$client = new Zend_Http_Client('http://iceage.usvn.info/update/' . $config->version, array(
    					'maxredirects' => 0,
    					'timeout'      => 30));
        	$client->setParameterPost('sysinfo', USVN_Update::getInformationsAboutSystem());
			$response = $client->request('POST');
			if ($response->getStatus() == 200) {
				file_put_contents(USVN_Update::getVersionFilePath(), $response->getBody());
			}
		}
	}
	
	/**
	 * Return informations about the system into a XML string.
	 * 
	 * @return string XML 
	 */
	static public function getInformationsAboutSystem()
	{
		$config = Zend_Registry::get('config');
		$xml = new SimpleXMLElement("<informations></informations>");
		$os = $xml->addChild('host');
		$os->addChild('os', PHP_OS);
		$os->addChild('uname', php_uname());
		$subversion = $xml->addChild('subversion');
		$subversion->addChild('version', implode(".", USVN_SVNUtils::getSvnVersion()));
		$usvn = $xml->addChild('usvn');
		$usvn->addChild('version', $config->version);
		$usvn->addChild('translation', $config->translation->locale);
		$usvn->addChild('databaseadapter', $config->database->adapterName);
		$php = $xml->addChild('php');
		$php->addChild('version', phpversion());
		$ini = $php->addChild('ini');
		foreach(ini_get_all() as $var => $value) {
			$ini->addChild($var, htmlspecialchars((string)$value['local_value']));
		}
		foreach (get_loaded_extensions() as $ext) {
			$php->addChild('extension', $ext);
		}
		return $xml->asXml();
	}
}