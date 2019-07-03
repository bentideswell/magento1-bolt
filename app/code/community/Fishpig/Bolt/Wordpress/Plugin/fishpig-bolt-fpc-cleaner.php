<?php
/**
 * Plugin Name: FishPig's Bolt FPC Cleaner
 * Plugin URI:  https://fishpig.co.uk/magento/extensions/full-page-cache/
 * Description: Flushes the Bolt FPC cache for posts when saving them
 * Author: FishPig
 * Author URI:  https://fishpig.co.uk/
 * Version: 1.1.0
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
    $canRun = !defined('FISHPIG_BOLT_FPC_CLEANER_DISABLE')
      && (!empty($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST')
      && (empty($_GET['action']) || $_GET['action'] !== 'edit');


    if ($canRun) {
      add_action('save_post', array($this, 'flushPostById'));
    }
	}	
	
	/**
	 *
	**/
	public function flushPostById($postId)
	{
		$postUrls = array(
			get_permalink($postId),
			home_url(),
		);
		
		if ($categories = wp_get_post_categories($postId)) {
			foreach($categories as $category) {
				$postUrls[] = get_category_link($category);
			}
		}

		if ($postUrls) {
			foreach($postUrls as $postUrl) {
				$this->flushUrl($postUrl);
			}
			
			return true;
		}
		
		return false;
	}
	
	/**
	 *
	**/
	public function flushUrl($url)
	{
		try {
			$this->log($url);

			if (!($response = wp_remote_get($url . '?___refresh=bolt&___refresh_subpages=1'))) {
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
