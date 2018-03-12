<?php
/**
 * @category	Fishpig
 * @package		Fishpig_Bolt
 * @license		http://fishpig.co.uk/license.txt
 * @author		Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Bolt_Adapter_Redis extends Fishpig_Bolt_Adapter_Abstract
{
	/**
	 * Cache tag used inside Redis
	 *
	 * @static var string
	 */
	const CACHE_TAG = 'FishPig_Bolt';
	
	/**
	 * Initialise the object
	 *
	 * @param array $options = array()
	 */
	public function __construct($options = array())
	{
		$this->_adapter = new Cm_Cache_Backend_Redis($options);
	}
	
	/**
	 * Get a value from the cache
	 *
	 * @param string $key
	 * @return string
	 */
	public function get($key)
	{
		return $this->_getAdapter()->load($key, true);
	}
	
	/**
	 * Set a value in the cache
	 *
	 * @param string $key
	 * @param string $value
	 * @param int $lifetime = 0
	 * @return $this
	 */
	public function set($key, $value, $lifetime = 0)
	{
		$this->_getAdapter()->save($value, $key, array(self::CACHE_TAG), $lifetime);
		
		return $this;
	}
	
	/**
	 * Flush the hole cache
	 * This is done by incrementing the prefix
	 *
	 * @return $this
	 */
	public function flush()
	{
		$this->_getAdapter()->clean(Zend_Cache::CLEANING_MODE_ALL, array(self::CACHE_TAG));
		
		return $this;
	}
	
	/**
	 * Actually delete cached value from Redis
	 *
	 * @param string $key
	 * @return $this
	 */
	public function delete($key)
	{
		$this->_getAdapter()->remove($key);
		
		return $this;
	}
}