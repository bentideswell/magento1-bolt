<?php
/**
 * @category	Fishpig
 * @package		Fishpig_Bolt
 * @license		http://fishpig.co.uk/license.txt
 * @author		Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Bolt_Model_System_Config_Source_Holepunch_Cachekey
{
	/**
	 * Options cache
	 *
	 * @return array
	 */
	protected $options = array(
		'is_logged_in'    => 'Is Logged In',
		'customer_id'     => 'Customer ID',
		'cart_item_count' => 'Cart Qty',
		'quote_hash'      => 'Quote Hash',
	);

	/*
	 * Retrieve the option array of modules
	 *
	 * @return array
	 */
	public function toOptionArray()
	{
		$options = array();

		foreach($this->options as $value => $label) {
			$options[] = array('value' => $value, 'label' => $label);
		}

		return $options;
	}
}
