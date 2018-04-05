<?php
/*
 *
 */
class Fishpig_Bolt_Model_Cron
{
	/*
	 *
	 */
	public function flushCacheQueue()
	{
		if (Mage::getStoreConfigFlag('bolt/autorefresh/cron_use_magento')) {
			return Mage::helper('bolt/cache_queue')->flush();
		}
	}
}