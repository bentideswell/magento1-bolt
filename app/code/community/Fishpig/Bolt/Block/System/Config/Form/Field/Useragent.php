<?php
/**
 * @category  Fishpig
 * @package  Fishpig_CrossLink
 * @license    http://fishpig.co.uk/license.txt
 * @author    Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Bolt_Block_System_Config_Form_Field_Useragent extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
	/**
	 * Prepare to render
	 *
	 * @return void
	*/
	protected function _prepareToRender()
	{
		$this->addColumn('name', array(
			'label' => $this->__('Group'),
			'style' => 'max-width: 120px;',
		));

		$this->addColumn('regex', array(
			'label' => $this->__('Regex'),
		));

		$this->_addAfter = false;
		$this->_addButtonLabel = $this->__('Add New');
	}	
}
