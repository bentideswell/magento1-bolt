<?php
/**
 * @category	Fishpig
 * @package		Fishpig_Bolt
 * @license		http://fishpig.co.uk/license.txt
 * @author		Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Bolt_Model_System_Config_Source_Conditions
{
	/**
	 * Options cache
	 *
	 * @return array
	 */
	protected $_options = null;
	
	/**
	 * Retrieve the option array of modules
	 *
	 * @return array
	 */
	public function toOptionArray()
	{
		if (!is_null($this->_options)) {
			return $this->_options;
		}

		$this->_options = array();

		foreach($this->_getOptions() as $value => $label) {
			$this->_options[] = array(
				'value' => $value,
				'label' => $label,
			);
		}

		return $this->_options;
	}
	
	/**
	 * Get a key value array of options
	 *
	 * @return array
	 */
	protected function _getOptions()
	{
		$helper = Mage::helper('bolt');

		return array(
			0 => $helper->__('Never'),
			Fishpig_Bolt_App::CONDITION_CACHE_FIRST_REQUEST => $helper->__('If First Request'),
			Fishpig_Bolt_App::CONDITION_CACHE_IF_CUSTOMER => $helper->__('If Customer Logged in'),
			Fishpig_Bolt_App::CONDITION_CACHE_IF_CART => $helper->__('If Products in Cart'),
		);
	}
}