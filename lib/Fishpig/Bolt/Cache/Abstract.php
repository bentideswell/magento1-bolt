<?php
/**
 * @category	Fishpig
 * @package		Fishpig_Bolt
 * @license		http://fishpig.co.uk/license.txt
 * @author		Ben Tideswell <help@fishpig.co.uk>
 */

abstract class Fishpig_Bolt_Cache_Abstract
{
	/**
	 * The minimum number of characters found in a cache file
	 * for it to be considered valid
	 *
	 * @const int
	 */
	const MINIMUM_CACHED_FILE_LENGTH = 100;

	/**
	 * Save an entry into the cache
	 *
	 * @param string $key
	 * @param string $html
	 * @param int $lifetime = null
	 * @return $this
	 */
	#static abstract public function save($key, $html, $lifetime = null);
	
	/**
	 * Load an entry from the cache
	 *
	 * @param string $key
	 * @return string|false
	 */
	#static abstract public function load($key);

	/**
	 * Delete a cache page by it's ID
	 *
	 * @param string $id
	 * @return void
	 */
	 #static abstract public function delete($key, $subpages = false);
	
	/**
	 * Determine whether $key has been cached
	 *
	 * @param string $id
	 * @return bool
	 */
	 #static abstract public function exists($key);
	
	/**
	 * Determine whether $key has expired
	 *
	 * @param string
	 * @return bool
	 */
	 #static abstract public function expired($key);
	
	/**
	 * Flush message. Fully clears the cache
	 *
	 * @return $this
	 */
	#static abstract public function flush();

	/**
	 * Get the expiry time (TTL / Lifetime) value
	 *
	 * @return int
	 */
	static public function getExpiryTime()
	{
		return (int)Fishpig_Bolt_App::getConfig('settings/lifetime');
	}
	
	/**
	 * Determine whether compression is enabled
	 *
	 * @return bool
	 */
	static protected function _isCompressionEnabled()
	{
		return (int)Fishpig_Bolt_App::getConfig('settings/compression') === 1;
	}
}