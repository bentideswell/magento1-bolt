<?php
/**
 * @category	Fishpig
 * @package		Fishpig_Bolt
 * @license		http://fishpig.co.uk/license.txt
 * @author		Ben Tideswell <help@fishpig.co.uk>
 */

abstract class Fishpig_Bolt_Adapter_Abstract
{
	/**
	 * Store the Memcache adapter
	 *
	 * @var Memcache|false
	 */
	protected $_adapter = null;

	/**
	 * Retrieve a cached page by it's ID
	 *
	 * @param string $id
	 * @return string|false
	 */
	 abstract public function get($key);

	/**
	 * Add a value to the cache
	 *
	 * @param string $key
	 * @param string $value
	 * @param int $lifetime = null
	 */
	abstract public function set($key, $value, $lifetime = 0);

	/**
	 * Actually delete item from server
	 *
	 * @param string $key
	 * @return $this
	 */
	abstract public function delete($key);	
	
	/**
	 * Flush the hole cache
	 * This is done by incrementing the prefix
	 *
	 * @return $this
	 */
	abstract public function flush();

	/**
	 *
	 * @return Memcache
	 */
	protected function _getAdapter()
	{
		return $this->_adapter;
	}
}