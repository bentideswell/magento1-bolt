<?php
/**
 * @category    Fishpig
 * @package    Fishpig_Bolt
 * @license      http://fishpig.co.uk/license.txt
 * @author       Ben Tideswell <ben@fishpig.co.uk>
 */

class Fishpig_Bolt_Adminhtml_Cache_BoltController extends Mage_Adminhtml_Controller_Action
{
	/**
	 * Determine ACL permissions
	 * Everyone can refresh for now
	 *
	 * @return bool
	 */
	protected function _isAllowed()
	{
		return true;
	}
	
	/**
	 * Process requested page refresh
	 *
	 * @return void
	 */
	public function refreshPageAction()
	{
		try {
			
			Mage::helper('bolt/license')->validate();

			if (!($bolt = $this->getRequest()->getPost('bolt'))) {
				throw new Exception('No data found.');
			}
			
			if (!is_array($bolt['store_id'])) {
				throw new Exception('You must provide at least 1 store ID');
			}
			
			Mage::helper('bolt/cache')->refreshUrl($bolt['uri'], $bolt['store_id'], $bolt['subpages']);
			
			Mage::getSingleton('adminhtml/session')->addSuccess($this->__('The cache was cleared using the options you provided.'));
		}
		catch (Exception $e) {
			Mage::getSingleton('adminhtml/session')->addError($this->__($e->getMessage()));
		}
		
		return $this->_redirect('adminhtml/cache');
	}
}
