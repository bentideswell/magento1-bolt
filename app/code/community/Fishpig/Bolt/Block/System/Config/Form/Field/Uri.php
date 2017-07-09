<?php
/**
 * @category  Fishpig
 * @package  Fishpig_CrossLink
 * @license    http://fishpig.co.uk/license.txt
 * @author    Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Bolt_Block_System_Config_Form_Field_Uri extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
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
	 *
	 * @return void
	*/
	protected function _prepareToRender()
	{
		$this->addColumn('uri', array(
			'label' => $this->__('URI'),
		));
		
		$this->addColumn('type', array(
			'label' => $this->__('Type'),
			'renderer' => $this->getSelectRenderer('type'),
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
    		'type',
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
//	    	Fishpig_Bolt_App::CONDITION_URI_TYPE_ROUTE => $this->__('Magento Route'),
	    	Fishpig_Bolt_App::CONDITION_URI_TYPE_STRING => $this->__('String Match'),
	    	Fishpig_Bolt_App::CONDITION_URI_TYPE_REGEX => $this->__('Regex'),
	    );
	    
	    $options = array();
	    
	    foreach($data as $value => $label) {
		    $options[] = array('value' => $value, 'label' => $label);
	    }
	    
	    return $options;
    }
}
