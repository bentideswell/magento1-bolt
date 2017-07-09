<?php
/**
 * @category	Fishpig
 * @package		Fishpig_Bolt
 * @license		http://fishpig.co.uk/license.txt
 * @author		Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Bolt_Helper_Data extends Mage_Core_Helper_Abstract
{	
	/**
	 * Easy way to get the Fishpig_Bolt_App object
	 *
	 * @return Fishpig_Bolt_App|false
	 */
	public function getApp()
	{
		return defined('FISHPIG_BOLT')
			? Fishpig_Bolt_App::getSingleton()
			: false;
	}

	/**
	 * Store the current store ID/code in the session
	 *
	 * @param Mage_Core_Mode_Store|null $store
	 * @return $this
	 */	
	public function setSessionData($store = null)
	{
		if (is_null($store)) {
			$store = Mage::app()->getStore();
		}

		$_cart = Mage::getSingleton('checkout/cart');
		$_quote = $_cart->getQuote();

		Mage::getSingleton('core/session')
#			->setWebsiteId($store->getWebsiteId())
#			->setWebsiteCode($store->getWebsite()->getCode())
#			->setStoreGroupId($store->getGroupId())
			->setStoreCode($store->getCode())
#			->setStoreId($store->getId())
#			->setCurrencyCode($store->getCurrencyCode())
			->setCartItemCount($_cart->getItemsCount())
			->setQuoteHash(md5($_quote->getData('created_at') . $_quote->getData('updated_at') . $_cart->getItemsCount()))
			->setIsLoggedIn(Mage::getSingleton('customer/session')->isLoggedIn())
			->setCustomerGroupId(Mage::getSingleton('customer/session')->getCustomerGroupId())
			->setIsBolt(true);
		
		/**
		 * Potential new session system
		 */
		/*
		$sessionData = array(
			'website_id' => $store->getWebsiteId(),
			'store_code' => $store->getCode(),
			'cart_has_items' => (int)((int)Mage::getSingleton('checkout/cart')->getItemsCount() > 0),
			'is_logged_in' => (int)Mage::getSingleton('customer/session')->isLoggedIn(),
			'session_messages_exist' => (int)$this->doSessionMessagesExist(),
		);

		$sessionString = http_build_query($sessionData);
		*/

		return $this;
	}

	/**
	 * Refresh the config file
	 *
	 * @return array|false
	 */
	public function refreshConfig()
	{
		$file = Mage::getBaseDir('etc') . DS . 'bolt.config';

		if (Mage::app()->useCache('bolt')) {
			// If 2.0.0.12 of Opti is installed, we can move hole punched JS to the bottom
			$isOptiInstalled = (string)Mage::app()->getConfig()->getNode('modules/Fishpig_Opti/active') === 'true'
				&& version_compare((string)Mage::app()->getConfig()->getNode('modules/Fishpig_Opti/version'), '2.0.0.12', '>=');

			$container = array(
				'websites' => array(),
				'use_cache' => (int)Mage::app()->useCache('bolt'),
				'admin_frontName' => (string)Mage::getConfig()->getNode('admin/routers/adminhtml/args/frontName'),
				'session_save' => (string)Mage::getConfig()->getNode('global/session_save'),
				'session_save_path' =>(string)Mage::getConfig()->getNode('global/session_save_path'),
				'session_cache_limiter' => (string)Mage::getConfig()->getNode('global/session_cache_limiter'),
				'cache_type' => (string)Mage::getConfig()->getNode('global/cache/backend'),
			);
			
			if ($container['session_save'] === 'files') {
				$container['var_session_path'] = (string)Mage::getSingleton('core/session')->getSessionSavePath();
			}
			
			if ($container['cache_type'] === 'Cm_Cache_Backend_Redis') {
				$container['cache_options'] = (array)Mage::getConfig()->getNode('global/cache/backend_options')->asArray();
			}
			else if  ($container['cache_type']) {
				if ($cacheOptions = (array)Mage::getConfig()->getNode('global/cache/' . $container['cache_type'])) {
					$container['cache_options'] = json_decode(json_encode($cacheOptions), true);
				}
			}

			if ($redisSessionData = Mage::getConfig()->getNode('global/redis_session')) {
				$container['session_redis'] = (array)$redisSessionData->asArray();
			}

			$defaults = (array)Mage::app()->getConfig()->getNode('default/bolt')->asArray();
			$websites = Mage::getResourceModel('core/website_collection')->load();

			foreach($websites as $website) {
				$isDefaultStore = false;

				if (count(($groups = $website->getGroups())) > 0) {
					$container['websites'][$website->getId()] = array(
						'code' => $website->getCode(),
						'default_group_id' => $website->getDefaultGroupId(),
						'is_default' => $website->getIsDefault(),
						'groups' => array(),
					);
					
					if ($website->getIsDefault()) {
						$container['default_website_code'] = $website->getCode();
						$container['default_website_id'] = $website->getId();	
					}

					foreach($groups as $group) {
						$stores = Mage::getResourceModel('core/store_collection')
							->addFieldToFilter('group_id', $group->getId())
							->setOrder('sort_order', 'ASC')
							->load();

						if (count($stores) > 0) {
							$container['websites'][$website->getId()]['groups'][$group->getId()] = array(
								'default_store_id' => $group->getDefaultStoreId(),
								'stores' => array(),
							);
							
							foreach($stores as $store) {
								$buffer = array(
									'website_id' => $website->getId(),
									'website_code' => $website->getCode(),
									'store_group_id' => $group->getId(),
									'store_id' => $store->getId(),
									'store_code' => $store->getCode(),
									'base_url' => Mage::getUrl('', array('_store' => $store->getCode())),
									'currency_code' => $store->getCurrentCurrencyCode(),
									'is_default' => $group->getDefaultStoreId() === $store->getId(),
								);
								
								foreach($defaults as $area => $values) {
									foreach($values as $field => $value) {
										if (($configValue = trim(Mage::getStoreConfig('bolt/' . $area . '/' . $field, $store))) !== '') {
											$buffer[$area][$field] = $configValue;
										}
									}
								}
								
								if (isset($buffer['conditions']['excluded_uris'])) {
									$uris = array();
									
									foreach(unserialize($buffer['conditions']['excluded_uris']) as $uri) {
										$uris[] = array_shift($uri);
									}
									
									$buffer['exclude']['uris'] = serialize($uris);
								}

								$buffer['move_js_to_bottom'] = $isOptiInstalled ? Mage::helper('opti/minify_js')->isMoveToBottomAllowed($store->getId()) : false;
								
								$container['websites'][$website->getId()]['groups'][$group->getId()]['stores'][$store->getId()] = $buffer;
							}
						}
					}
					
					if (count($container['websites'][$website->getId()]['groups']) === 0) {
						unset($container['websites'][$website->getId()]);
					}
				}
			}

			@file_put_contents($file, serialize($container));

			return $container;
		}
		else {
			@unlink($file);
		}

		return false;
	}
	
	/**
	 * Retrieve the config file name and path
	 *
	 * @return string
	 */
	public function getConfigFile()
	{
		return Mage::getBaseDir('etc') . DS . 'bolt.config';
	}
	
	/**
	 * Determine whether the config file exists
	 *
	 * @return bool
	 */
	public function configFileExists()
	{
		return is_file($this->getConfigFile());
	}
	
	/**
	 * Determine whether session messages exist
	 *
	 * @return bool
	 */
	public function doSessionMessagesExist()
	{
		Mage::getSingleton('core/session')->setSessionMessagesExists(false);
		
		$sessionMessagesExist = false;
		$oldErrorLevel = error_reporting();
		error_reporting(0);
		
		foreach(array_keys($_SESSION) as $module) {
			try {
				if (strpos($module, '_') !== false) {
					$module = substr($module, 0, strpos($module, '_'));
				}
				
				if ($session = Mage::getSingleton($module . '/session')) {
					if (count($session->getMessages()->getItems()) > 0) {
						Mage::getSingleton('core/session')->setSessionMessagesExists(true);
						$sessionMessagesExist = true;
						break;
					}
				}
			}
			catch (Exception $e) {}
		}
		
		error_reporting($oldErrorLevel);
		
		return $sessionMessagesExist;
	}	
	
	/**
	 * Determine whether the request is for the API
	 *
	 * @return bool
	 */	
	public function isApiRequest()
	{
		return Mage::app()->getRequest()->getModuleName() === 'api';
	}
	
	/**
	 * Invalidate the cache
	 *
	 * @return bool
	 */
	public function invalidateCache()
	{
		Mage::app()->getCacheInstance()->invalidateType('bolt');
		
		return $this;
	}
}
