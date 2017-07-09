<?php
/**
 * @category  Fishpig
 * @package  Fishpig_Bolt
 * @author    Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Bolt_Block_System_Config_Form_Field_Select extends Mage_Core_Block_Html_Select
{
	/**
	 * Set the input name
	 *
	 * @param string $name
	 * @return $this
	 */
	public function setInputName($name)
	{
		return $this->setName($name);
	}
}
