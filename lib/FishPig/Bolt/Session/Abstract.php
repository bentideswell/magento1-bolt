<?php
/**
 * @category	Fishpig
 * @package		Fishpig_Bolt
 * @license		http://fishpig.co.uk/license.txt
 * @author		Ben Tideswell <help@fishpig.co.uk>
 */

abstract class Fishpig_Bolt_Session_Abstract
{
	/**
	 * Internal data array
	 *
	 * @var array
	 */
	static protected $_data = array();
	
	/**
	 * Determine whether the session type is active
	 *
	 * @var bool
	 */
	static protected $_active = false;
	
	/**
	 * Potential cookie names used to load session
	 *
	 * @var array
	 */
	static protected $_potentialCookieNames = array(
		'PHPSESSID', // Early session instantiation
		'frontend', // Proper Magento cookie
	);
		
	/**
	 * Determine whether the session adapter is active
	 *
	 * @return bool
	 */
	static public function isActive()
	{
		return self::$_active === true;
	}
	
	/**
	 * Initliase the session data
	 *
	 * @return bool
	 */
	static protected function _initSessionData($type)
	{
		if (self::$_active) {
			return self::$_active;
		}
		
		$adapterClassName = 'Fishpig_Bolt_Session_' . $type;

		if (($data = call_user_func(array($adapterClassName, '_getRawSessionData'))) === false) {
			return false;
		}

		$session = array();
	    $split = preg_split('/([a-z\_]{1,}\|*)\|/', $data,-1,PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
		    
	    $len = count($split);
		    
	    for ($i = 0; $i < $len; $i++, $i++) {
		    $session[$split[$i]] = @unserialize($split[$i+1]);
	    }

		 self::$_data = $session;
		 self::$_active = true;
		 
		 return true;
	}
	
	/**
	 * Get a data value
	 *
	 * @param string $key = null
	 * @return mixed
	 */
	static public function getData($key = null)
	{
		return Fishpig_Bolt_App::getArrayValue(self::$_data, $key);
	}
}
