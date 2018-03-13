<?php
/**
 * @category	Fishpig
 * @package		Fishpig_Bolt
 * @license		http://fishpig.co.uk/license.txt
 * @author		Ben Tideswell <ben@fishpig.co.uk>
 * @SkipObfuscation
 */
define('FISHPIG_BOLT', true);
define('FISHPIG_BOLT_DIR', getcwd());

/**
 * To enable debug mode, enter an md5 hash of your IP address in the FISHPIG_BOLT_DEBUG_IP_HASH constant
 * When debug mode is enabled, Bolt will only run for users accessing via the IP specified
 * Other users will see the site without Bolt, allowing you to test Bolt without affecting other users
 */
define('FISHPIG_BOLT_DEBUG_IP_HASH', false);

if (include('lib' . DIRECTORY_SEPARATOR . 'Fishpig' . DIRECTORY_SEPARATOR . 'Bolt' . DIRECTORY_SEPARATOR . 'App.php')) {
  	if (Fishpig_Bolt_App::isDebug()) {
		ini_set('display_errors', 1);
		error_reporting(E_ALL | E_STRICT);
	}
	
	try {

		Fishpig_Bolt_App::run();
		/**
		 * If code execution gets here, Bolt could not load a cached request
		 * The request will now fall to Magento (index.php) and the request will be handled as normal
		 * Bolt will try and cache the response that Magento sends (if possible)
		 */
	}
	catch (Exception $e) {
		Fishpig_Bolt_App::handleException($e);
	}
}
