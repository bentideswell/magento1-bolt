<?php
/**
 * @category	Fishpig
 * @package		Fishpig_Bolt
 * @license		http://fishpig.co.uk/license.txt
 * @author		Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Bolt_Model_System_Config_Source_Useragent
{	
	/**
	 * Get the raw options array
	 *
	 * @return array
	 */
	public function getOptions()
	{
		return array(
			'ua_default' => 'Desktop',
			'ua_mobile' => 'Mobile',
			'ua_tablet' => 'Tablet',
		);
	}
	
	/**
	 * Get a option array of all options
	 *
	 * @return array
	 */
	public function toOptionArray()
	{
		$options = array();
		
		foreach($this->getOptions() as $value => $label) {
			$options[] = array(
				'value' => $value,
				'label' => Mage::helper('cachewarmer')->__($label)
			);
		}
		
		return $options;
	}
}