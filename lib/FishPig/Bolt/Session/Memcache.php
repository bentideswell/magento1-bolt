<?php
/**
 * @category	Fishpig
 * @package		Fishpig_Bolt
 * @license		http://fishpig.co.uk/license.txt
 * @author		Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Bolt_Session_Memcache extends Fishpig_Bolt_Session_Abstract
{
	/**
	 * Store the Memcache adapter
	 *
	 * @var Memcache|false
	 */
	static protected $_memcache = null;

	/**
	 * Initialise the session object
	 *
	 * @return void
	 */
	static public function init()
	{
		Fishpig_Bolt_Session_Abstract::_initSessionData('Memcache');
	}
	
	/**
	 * Get the raw session data from Memcache
	 *
	 * @return string
	 */
	static protected function _getRawSessionData()
	{
		if (($memcache = self::_getMemcache()) === false) {
			return false;
		}

		$data = null;
		
		foreach(Fishpig_Bolt_Session_Abstract::$_potentialCookieNames as $cookieName) {
			if (!isset($_COOKIE[$cookieName])) {
				continue;
			}

			if ($data = $memcache->get($_COOKIE[$cookieName])) {
				if (strpos($data, 'is_bolt') !== false) {
					break;
				}
			}
		}
		
		return $data !== NULL ? $data : false;
	}
	
	/**
	 * Get the memcache object
	 *
	 * @return false|Memcache
	 */
	static protected function _getMemcache()
	{
		if (self::$_memcache !== NULL) {
			return self::$_memcache;
		}

		if (($sessionSavePath = Fishpig_Bolt_App::getConfig('session_save_path')) === '') {
			throw new Exception('Unable to read session_save_path from configuration.');
		}

		return self::$_memcache = new Fishpig_Bolt_Adapter_Memcache(array(array(
			'host' => $sessionSavePath,
			'port' => null
		)));
	}
}
