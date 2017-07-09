<?php
/**
 * @category  Fishpig
 * @package  Fishpig_CrossLink
 * @license    http://fishpig.co.uk/license.txt
 * @author    Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Bolt_Block_System_Config_Form_Field_Block extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
	/**
	 * Retrieve the rendering block class for the type field
	 *
	 * @return Fishpig_Bolt_Block_System_Config_Form_Field_Select
	 */
	public function getSelectRenderer($field)
	{
		$key = 'select_renderer_' . $field;
		
		if (!$this->hasData($key)) {
			$this->setData($key, $this->getLayout()->createBlock('bolt/system_config_form_field_select', '', array('is_render_to_js_template' => true))
				->setClass('select')
				->setOptions(
					$this->_getOptions()
				)
			);
		}
		
		return $this->_getData($key);
	}
	
	/**
	 * Prepare to render
	*/
	protected function _prepareToRender()
	{
		$this->addColumn('name', array(
			'label' => $this->__('Block Name'),
			'style' => 'max-width: 120px;',
		));

		$this->addColumn('apply', array(
			'label' => $this->__('Punch a hole'),
			'renderer' => $this->getSelectRenderer('apply'),
		));

		$this->_addAfter = false;
		$this->_addButtonLabel = $this->__('Add New');
	}
	
    /**
     * Prepare existing row data object
     *
     * @param Varien_Object
     */
    protected function _prepareArrayRow(Varien_Object $row)
    {
    	$fields = array(
    		'apply',
    	);
    	
    	foreach($fields as $field) {
			$row->setData(
				'option_extra_attr_' . $this->getSelectRenderer($field)->calcOptionHash($row->getData($field)),
				'selected="selected"'
			);
		}
    }

    /**
     * Get a key value array of options
     *
     * @return array
     */
    protected function _getOptions()
    {
	    $data = array(
	    	Fishpig_Bolt_HolePunch::APPLY_ALWAYS => $this->__('Always'),
	    	Fishpig_Bolt_HolePunch::APPLY_WITH_CART => $this->__('When items are in the cart'),
	    	Fishpig_Bolt_HolePunch::APPLY_WITH_CUSTOMER => $this->__('When the customer is logged in'),
	    	Fishpig_Bolt_HolePunch::APPLY_WITH_CART_OR_CUSTOMER => $this->__('When items are in the cart or the customer is logged in'),
	    );
	    
	    $options = array();
	    
	    foreach($data as $value => $label) {
		    $options[] = array('value' => $value, 'label' => $label);
	    }
	    
	    return $options;
    }
}
