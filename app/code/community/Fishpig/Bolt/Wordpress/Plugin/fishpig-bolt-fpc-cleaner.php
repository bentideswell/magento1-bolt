<?php
/**
 * Plugin Name: FishPig's Bolt FPC Cleaner
 * Plugin URI:  https://fishpig.co.uk/magento/extensions/full-page-cache/
 * Description: Flushes the Bolt FPC cache for posts when saving them
 * Author: FishPig
 * Author URI:  https://fishpig.co.uk/
 * Version: 1.0.0
 * Text Domain: fishpig
 */

if (!defined( 'ABSPATH' )) {
	exit;
}

/**
 *
**/
class FishPig_Bolt_FPC_Cleaner
{
	/**
	 *
	**/
	static protected $_instance = null;

	/**
	 *
	**/
	static public function getInstance()
	{
		if (self::$_instance === null) {
			self::$_instance = new FishPig_Bolt_FPC_Cleaner;
		}
		
		return self::$_instance;
	}
	
	/**
	 *
	**/
	protected function __construct()
	{
		add_action('save_post', array($this, 'flushPostById'));
	}	
	
	/**
	 *
	**/
	public function flushPostById($postId)
	{
		if ($permalink = get_permalink($postId)) {
			return $this->flushUrl($permalink);
		}
		
		return false;
	}
	
	/**
	 *
	**/
	public function flushUrl($url)
	{
		try {
			if (!($response = wp_remote_get($url . '?___refresh=bolt'))) {
				throw new Exception('Unable to flush ' . $url);
			}

			return true;
		}
		catch (Exception $e) {
			$this->log($e->getMessage());
		}
		
		return false;
	}
	
	/**
	 *
	**/
	public function log($msg)
	{
		@file_put_contents(ABSPATH . 'bolt-fpc-flusher.log', $msg . "\n", FILE_APPEND);
		
		return $this;
	}
}

// Singleton. This will construct the object
FishPig_Bolt_FPC_Cleaner::getInstance();
