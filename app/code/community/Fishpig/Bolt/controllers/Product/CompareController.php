<?php
/**
 * @category	Fishpig
 * @package		Fishpig_Bolt
 * @license		http://fishpig.co.uk/license.txt
 * @author		Ben Tideswell <help@fishpig.co.uk>
 */

 	require_once(Mage::getModuleDir('controllers', 'Mage_Catalog') . DS . 'Product' . DS . 'CompareController.php');
 
class Fishpig_Bolt_Product_CompareController extends Mage_Catalog_Product_CompareController
{
	/**
	 * Allow compare to work even without cookies being set
	 * Cookie wil be set as compare is created so there are no issues
	 *
	 * @var array
	 **/
    protected $_cookieCheckActions = array();
}
