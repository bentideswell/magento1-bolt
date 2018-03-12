<?php
/**
 * @category	Fishpig
 * @package		Fishpig_Bolt
 * @license		http://fishpig.co.uk/license.txt
 * @author		Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Bolt_Session_Files extends Fishpig_Bolt_Session_Abstract
{
	/**
	 * Initialise the session object
	 *
	 * @return void
	 */
	static public function init()
	{
		Fishpig_Bolt_Session_Abstract::_initSessionData('Files');
	}

	/**
	 * Get the raw session data from the file system
	 *
	 * @return false|string
	 */
	static protected function _getRawSessionData()
	{
		$data = null;
		$baseSessionDir = Fishpig_Bolt_App::getConfig('var_session_path')  . DIRECTORY_SEPARATOR;
		$matchFound = false;
		
		// If PHPSESSID is used, early session instantiation has occured
		foreach(Fishpig_Bolt_Session_Abstract::$_potentialCookieNames as $cookieName) {
			if (!isset($_COOKIE[$cookieName])) {
				continue;
			}
			
			$sessionFiles = array(
				'magento' => $baseSessionDir . 'sess_' . $_COOKIE[$cookieName],
				'tmp' => session_save_path() . DIRECTORY_SEPARATOR . 'sess_' . $_COOKIE[$cookieName],
			);
			
			foreach($sessionFiles as $sessionFile) {
				if (($data = Fishpig_Bolt_App::getFile($sessionFile)) !== false) {
					if (strpos($data, 'is_bolt') !== false) {
						$matchFound = true;
						break;
					}
				}
				
				if ($matchFound) {
					break;
				}
			}
		}

		return $data !== NULL ? $data : false;
	}
}
