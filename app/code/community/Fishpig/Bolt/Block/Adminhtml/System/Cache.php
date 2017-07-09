<?php
/**
 * @category	Fishpig
 * @package		Fishpig_Bolt
 * @license		http://fishpig.co.uk/license.txt
 * @author		Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Bolt_Block_Adminhtml_System_Cache extends Mage_Adminhtml_Block_Template
{
	/**
	 *
	 *
	  * @return string
	  */
	public function getFormActionUrl()
	{
		return $this->getUrl('adminhtml/cache_bolt/refreshPage');
	}
	
	/**
	 * Get the default store ID
	 *
	  * @return int
	  */
	public function getDefaultStoreId()
	{
		return (int)Mage::app()->getWebsite(true)->getDefaultGroup()->getDefaultStoreId();
	}

	/**
	 *
	 *
	  * @return int
	  */
	public function getStoreElementSize()
	{
		$count = 0;
		
		foreach($this->getWebsites() as $website) {
			foreach($website->getGroups() as $group) {
				$count += 2 + count($group->getStores());
			}
		}
		
		return $count < 10 ? $count : 10;
	}
	
	/**
	 *
	 *
	  * @return array
	  */
	public function getWebsites()
	{
		$websites = Mage::app()->getWebsites();
		
		if ($websiteIds = $this->getWebsiteIds()) {
			foreach ($websites as $websiteId => $website) {
				if (!in_array($websiteId, $websiteIds)) {
					unset($websites[$websiteId]);
				}
			}
		}

		return $websites;
	}

	/**
	 *
	 *
	 * @param Mage_Core_Model_Store_Group|int
	  * @return array
	  */
	public function getStores($group)
	{
		if (!$group instanceof Mage_Core_Model_Store_Group) {
			$group = Mage::app()->getGroup($group);
		}

		$stores = $group->getStores();

		if ($storeIds = $this->getStoreIds()) {
			foreach ($stores as $storeId => $store) {
				if (!in_array($storeId, $storeIds)) {
					unset($stores[$storeId]);
				}
			}
		}

		return $stores;
	}
	
	/**
	 * Get the current Bolt version that is installed
	 *
	 * @return string
	 */
	public function getModuleVersion()
	{
		return (string)Mage::app()->getConfig()->getNode('modules/Fishpig_Bolt/version');
	}
}
