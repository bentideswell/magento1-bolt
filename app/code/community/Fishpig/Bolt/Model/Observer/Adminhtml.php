<?php
/**
 * @category	Fishpig
 * @package		Fishpig_Bolt
 * @license		http://fishpig.co.uk/license.txt
 * @author		Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Bolt_Model_Observer_Adminhtml
{
	/**
	 * HTML Template used for displaying notice messages
	 *
	 * @const string
	 */
	const HTML_MSG_TEMPLATE = '<ul class="messages"><li class="error-msg"><ul><li><span>%s</span></li></ul></li></ul>';
	
	/**
	 * A cache of product IDs that have been refreshed
	 *
	 * @array static
	 */
	static protected $_productIdsFlushed = array();
	
	/**
	 * Invalidate the Bolt cache
	 *
	 * @param Varien_Event_Observer $observer
	 * @return $this
	 */
	public function invalidateCacheObserver(Varien_Event_Observer $observer)
	{
		return Mage::helper('bolt')->invalidateCache();
	}

	/**
	 * Flush the cache
	 *
	 * @param Varien_Event_Observer $observer
	 * @return $this
	 */
	public function flushCacheObserver(Varien_Event_Observer $observer)
	{
		$globalEvents = array(
			'adminhtml_cache_flush_all',
			'adminhtml_cache_flush_system',
			'controller_action_postdispatch_adminhtml_cache_cleanImages',
			'controller_action_postdispatch_adminhtml_cache_cleanMedia',
		);
		
		if (in_array($observer->getEvent()->getName(), $globalEvents)
			|| $observer->getEvent()->getType() === 'bolt') {
			Mage::helper('bolt/cache')->flush();
		}
		
		return $this;
	}
	
	/**
	 * Check for problems and inject errors
	 *
	 * @param Varien_Event_Observer $observer
	 * @return $this
	 */
	public function injectWarningMessagesObserver(Varien_Event_Observer $observer)
	{
		if (!Mage::getSingleton('admin/session')->isLoggedIn()) {
			return $this;
		}

		$response = $observer
			->getEvent()
				->getFront()
					->getResponse();
					
		if ($errorMsg = $this->_getErrorMessage()) {
			$html = str_replace('<div id="messages">', '<div id="messages">' . sprintf(self::HTML_MSG_TEMPLATE, $errorMsg), $response->getBody());
			
			$response->setBody($html);
		}
		else if ((string)Mage::app()->getConfig()->getNode('modules/Fishpig_Wordpress/active') === 'true') {
			$pluginHelper = Mage::helper('wordpress/plugin');
			$pluginName = 'fishpig-bolt-fpc-cleaner.php';
			$wpPath = Mage::helper('wordpress')->getWordPressPath();

			if ($wpPath) {
				$target = $wpPath . 'wp-content' . DS . 'plugins' . DS . $pluginName;
				$source = Mage::getModuleDir('', 'Fishpig_Bolt') . DS . 'Wordpress' . DS . 'Plugin' . DS . $pluginName;

				if (!$pluginHelper->install($target, $source, true)) {
  				/*
					$html = str_replace('<div id="messages">', '<div id="messages">' . sprintf(self::HTML_MSG_TEMPLATE, 'Unable to install FPC cleaner plugin in WordPress. Copy ' . $source . ' to ' . $target . ' and enable the plugin in the WordPress Admin.'), $response->getBody());
			
					$response->setBody($html);
					*/
				}
			}
		}
		
		return $this;
	}

	/**
	 * Check the installation for common problems
	 *
	 * @return string|false
	 */
	protected function _getErrorMessage()
	{
		$helper = Mage::helper('bolt');

		if (!$this->isBolt()) {
			return $helper->__("bolt.php is not included. Add %s above Mage::run() in index.php.", "include __DIR__ . DS . 'bolt.php';");
		}
		
		if (ini_get('suhosin.session.encrypt')) {
			return $helper->__("Your session files are encrypted.  Add 'php_flag suhosin.session.encrypt off' to your php.ini or .htaccess file.");
		}
		
		$configDir = Mage::getBaseDir('app') . DS . 'etc';
		$configFile = $configDir . DS . 'bolt.config';
		
		if ((is_file($configFile) && !is_writable($configFile)) || (!is_file($configFile) && !is_writable($configDir))) {
			return $helper->__("Unable to write Bolt config file to %s.", $configFile);
		}

		if ((string)Mage::getConfig()->getNode('global/cache/backend') === '') {
			$cacheDir = Mage::getBaseDir('var') . DS . 'bolt' . DS . 'cache';
			
			if (!is_dir($cacheDir)) {
				@mkdir($cacheDir, 0777, true);
				
				if (!is_dir($cacheDir)) {
					return $helper->__("Cache directory (%s) is not writable.", $cacheDir);
				}
				
				@rmdir($cacheDir);
			}
		}
		
		return false;
	}
	
	/**
	 * After a product has been saved, clear it's fpc files
	 *
	 * @param Varien_Event_Observer $observer
	 * @return $this
	 */
	public function catalogProductSaveAfterObserver(Varien_Event_Observer $observer)
	{
		if ($this->_initAutoRefresh('catalog_product_save_after')) {
			$_product = $observer->getEvent()->getProduct();

			if ($_product && !isset(self::$_productIdsFlushed[$_product->getId()])) {
				self::$_productIdsFlushed[$_product->getId()] = true;
				
				$helper = Mage::helper('bolt/cache_queue')->refreshProduct($_product, true);
			}
		}

		return $this;
	}

	/**
	 * After a product has been saved, clear it's fpc files
	 *
	 * @param Varien_Event_Observer $observer
	 * @return $this
	 */
	public function catalogCategorySaveAfterObserver(Varien_Event_Observer $observer)
	{
		if ($this->_initAutoRefresh('catalog_category_save_after')) {
			$helper = Mage::helper('bolt/cache_queue')->refreshCategory(
				$observer->getEvent()->getCategory()
			);
		}

		return $this;
	}
		
	/**
	 * After a stock item has been saved, clear it's fpc files
	 *
	 * @param Varien_Event_Observer $observer
	 * @return $this
	 */
	public function cataloginventoryStockItemSaveAfterObserver(Varien_Event_Observer $observer)
	{
		if ($this->_initAutoRefresh('cataloginventory_stock_item_save_after')) {
			$_product = $observer->getEvent()->getItem()->getProduct();

			if ($_product && !isset(self::$_productIdsFlushed[$_product->getId()])) {
				self::$_productIdsFlushed[$_product->getId()] = true;
				
				Mage::helper('bolt/cache_queue')->refreshProduct($_product, true);
			}
		}

		return $this;
	}

	/**
	 * Automatically clean the CMS page cache
	 *
	 * @param Varien_Event_Observer $observer
	 * @return $this
	 */
	public function cmsPageSaveAfterObserver(Varien_Event_Observer $observer)
	{
		if ($this->_initAutoRefresh('cms_page_save_after')) {
			$helper = Mage::helper('bolt/cache_queue')->refreshCmsPage(
				$observer->getEvent()->getObject()
			);
		}

		return $this;
	}
	
	/**
	 * Determine whether we can autorefresh for $key
	 *
	 * @param string $key
	 * @return bool
	 */
	protected function _initAutoRefresh($key)
	{
		return $this->isBolt() 
			&& (int)Mage::app()->getCacheInstance()->canUse('bolt')
			&& Mage::getStoreConfigFlag('bolt/autorefresh/' . $key);
		
			/*&& !Mage::helper('bolt')->isApiRequest();*/
	}
	
	/**
	 * Refresh the config file
	 *
	 * @param $observer = null
	 * @return array|false
	 */
	public function refreshConfig($observer = null)
	{
		return Mage::helper('bolt')->refreshConfig();
	}

	/**
	 * Determine whether Bolt is loaded
	 *
	 * @return bool
	 */
	public function isBolt()
	{
		return defined('FISHPIG_BOLT');
	}
}
