<?php
/**
 * @category  Fishpig
 * @package  Fishpig_CrossLink
 * @license    http://fishpig.co.uk/license.txt
 * @author    Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Bolt_Block_System_Config_Form_Field_Cookies extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
	/**
	 * Prepare to render
	 * 
	 * @return void
	*/
	protected function _prepareToRender()
	{
		$this->addColumn('cookies', array(
			'label' => $this->__('Cookie Name'),
		));
	
		$this->_addAfter = false;
		$this->_addButtonLabel = $this->__('Add New');
	}
}
