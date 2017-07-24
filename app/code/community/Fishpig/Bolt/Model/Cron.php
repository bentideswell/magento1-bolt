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
		return Mage::helper('bolt/cache_queue')->flush();
	}
}