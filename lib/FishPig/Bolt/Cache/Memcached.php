<?php
/**
 * @category	Fishpig
 * @package		Fishpig_Bolt
 * @license		http://fishpig.co.uk/license.txt
 * @author		Ben Tideswell <help@fishpig.co.uk>
 */
 
/**
 * Class handle all caching of html
 *
 */
class Fishpig_Bolt_Cache_Memcached extends Fishpig_Bolt_Cache_Abstract
{
	/**
	 * Store the Memcache adapter
	 *
	 * @var Memcache|false
	 */
	static protected $_memcache = null;

	/**
	 * Flush all contents in Memcache
	 * This doesn't actually flush everything
	 * but just increments the prefix
	 *
	 */
	static public function flush()
	{
		self::_getMemcache()->flush();
	}
	
	/**
	 * Delete a cache page by it's ID
	 *
	 * @param string $id
	 * @param bool $subpages = false
	 * @return void
	 */
	 static public function delete($key, $subpages = false)
	{
		self::_getMemcache()->delete($key);

		if ($subpages) {
			$metaKey = self::_getMetaKey($key);
			
			if ($value = self::_getMemcache()->get($metaKey)) {
				$value = unserialize($value);

				if (isset($value['children'])) {
					foreach($value['children'] as $childKey) {
						self::delete($childKey, true);
					}
				}
				
				self::_getMemcache()->delete($metaKey);
			}
		}
	}

	/**
	 * Retrieve a cached page by it's ID
	 *
	 * @param string $id
	 * @return string|false
	 */
	 static public function load($key)
	 {
		$value = trim(self::_getMemcache()->get($key));

		if (strlen($value) > Fishpig_Bolt_Cache_Abstract::MINIMUM_CACHED_FILE_LENGTH) {
			return $value;
		}
		
		return false;
	}
	
	/**
	 * Get an array of parent keys for $key
	 *
	 * @param string $key
	 * @return false|array
	 */
	static protected function _getParentKeys($key)
	{
		if (substr_count($key, '/') <= 3) {
			return false;
		}

		$key = substr($key, 0, strrpos($key, '/'));
		$parts = explode('/', $key);
		$base = implode('/', array_splice($parts, 0, 2));

		$keys = array();
		
		foreach($parts as $part) {
			$keys[] = $base = $base . '/' . $part;
		}
		
		return array_reverse($keys);
	}

	/**
	 * Cache a page
	 *
	 * @param string $id
	 * @param string $value
	 * @return bool
	 */
	 static public function save($key, $value, $lifetime = 0)
	{
		if (strlen($value) < Fishpig_Bolt_Cache_Abstract::MINIMUM_CACHED_FILE_LENGTH) {
			return false;
		}

		self::_getMemcache()->set($key, $value, $lifetime);

		if (($parentKeys = self::_getParentKeys($key)) !== false) {
			$previousKey = $key;

			foreach($parentKeys as $parentKey) {
				$metaKey = self::_getMetaKey($parentKey);
				
				if ($value = self::_getMemcache()->get($metaKey)) {
					$value = unserialize($value);
					
					if (!in_array($previousKey, $value['children'])) {
						$value['children'][] = $previousKey;
					}
				}
				else {
					$value = array(
						'key' => $parentKey,
						'children' => array($previousKey),
					);
				}

				self::_getMemcache()->set($metaKey, serialize($value), $lifetime);	
				
				$previousKey = $parentKey;
			}
		}

		return true;
	}
	
	/**
	 * Get a meta key based on $key
	 *
	 * @param string $key
	 * @return string
	 */
	static protected function _getMetaKey($key)
	{
		return $key . '.meta';
	}
	
	/**
	 *
	 * @return false|Memcache
	 */
	static protected function _getMemcache()
	{
		if (self::$_memcache !== NULL) {
			return self::$_memcache;
		}

		$servers = Fishpig_Bolt_App::getConfig('cache_options/servers');
		
		if (!is_array($servers)) {
			throw new Exception('There are no servers defined for Memcache');
		}

		return self::$_memcache = new Fishpig_Bolt_Adapter_Memcache($servers, '__fishpig_bolt_cache_prefix_key');
	}
	
	/**
	 * Determine whether $id has been cached
	 *
	 * @param string $id
	 * @return bool
	 */
	static public function exists($key)
	{
		return true;
	}
	
	/**
	 * Determine whether $id has expired
	 *
	 * @param string
	 * @return bool
	 */
	 static public function expired($key)
	{
		return false;
	}
}
