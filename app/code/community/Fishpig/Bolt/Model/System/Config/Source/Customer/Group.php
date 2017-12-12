<?php
/**
 *
 */
class Fishpig_Bolt_Model_System_Config_Source_Customer_Group extends Mage_Adminhtml_Model_System_Config_Source_Customer_Group
{
	/*
	 * Get the customer groups
	 *
	 * @return array
	 */
	public function toOptionArray()
	{
		$options = parent::toOptionArray();
		
		array_shift($options);
		
		return $options;
	}
}
