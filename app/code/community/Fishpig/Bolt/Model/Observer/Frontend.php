<?php
/**
 * @category	Fishpig
 * @package		Fishpig_Bolt
 * @license		http://fishpig.co.uk/license.txt
 * @author		Ben Tideswell <help@fishpig.co.uk>
 * @Obfuscate
 */

class Fishpig_Bolt_Model_Observer_Frontend
{
	/**
	 * XML Attribute name that denotes a safe block
	 *
	 * @var const string
	 */
	const XML_BLOCK_SAFE = 'bolt_safe';
	
	/**
	 * Validated block name cache
	 *
	 * @var array
	 */
	protected $_validatedBlockNameCache = null;

	/**
	 * Apply a temporary form key if one isn't set
	 *
	 * @param Varien_Event_Observer $observer
	 * @return $this
	 */
	public function applyTempFormKeyObserver(Varien_Event_Observer $observer)
	{
		if (Mage::app()->getStore()->isAdmin() || !$this->isBolt()) {
			return $this;
		}

		if (Mage::app()->getRequest()->getMethod() === 'POST') {
			Mage::app()->getRequest()->setPost(
				$this->_fixFormKey(Mage::app()->getRequest()->getPost())
			);
		}
		
		if ($params = Mage::app()->getRequest()->getParams()) {
			if (strpos(serialize($params), 'form_key') !== false) {
				Mage::app()->getRequest()->setParams(
					$this->_fixFormKey($params)
				);
			}
			else {
				if (Mage::app()->getRequest()->getMethod() === 'POST') {
					Mage::app()->getRequest()->setParam('form_key', Mage::getSingleton('core/session')->getFormKey());
				}
			}
		}

		return $this;
	}
	
	/**
	 * Fix the form key
	 *
	 * @param array $data
	 * @return array
	 */
	protected function _fixFormKey($data)
	{
		if (is_array($data)) {
			foreach($data as $key => $value) {
				if (is_array($value)) {
					$data[$key] = $this->_fixFormKey($value);
				}
				else if ($key === 'form_key') {
					$data[$key] = Mage::getSingleton('core/session')->getFormKey();
				}
			}
		}
		
		return $data;
	}
	
	/**
	 * Try and cache the request
	 *
	 * @param Varien_Event_Observer $observer
	 * @return $this
	 */
	public function cacheResponseAfterSendingToBrowserObserver(Varien_Event_Observer $observer)
	{
		if (!$this->isBolt()) {
			return $this;
		}
		
		if (Mage::helper('bolt')->doSessionMessagesExist()) {
			return $this;	
		}
	
		if (Mage::getSingleton('customer/session')->isLoggedIn()) {
			return $this;
		}
		
		Fishpig_Bolt_App::cachePage(Mage::app());

		return $this;
	}
	
	/**
	 * Store the current store ID/code in the session
	 *
	 * @param Varien_Event_Observer $observer
	 * @return $this
	 */	
	public function setSessionDataObserver(Varien_Event_Observer $observer)
	{
		$helper = Mage::helper('bolt');

		if (!$helper->configFileExists()) {
			$helper->refreshConfig();
		}
		
		return $helper->setSessionData(Mage::app()->getStore());
	}
	
	/**
	 * Check whether hole punch server variable is set
	 * If so, generate hole punched content and pass to Bolt via an Exception
	 *
	 * @param Varien_Event_Observer $observer
	 */
	public function handleHolePunchObserver(Varien_Event_Observer $observer)
	{
		if (!$this->isBolt()) {
			return $this;
		}

		if (Fishpig_Bolt_HolePunch::isPunchingHoles()) {
			$blocks = array();

			if (($holes = Fishpig_Bolt_HolePunch::getValidHoles()) !== false) {
				$holes = $this->_validateBlockNames($holes);

				foreach($holes as $hole) {
					if (($block = $this->getLayout()->getBlock($hole)) !== false) {
						$blocks[$hole] = $block->toHtml();
					}
				}
			}

			Fishpig_Bolt_HolePunch::throwHolePunch($blocks);
		}
		else {
			if (($holes = Fishpig_Bolt_HolePunch::getHoles()) !== false) {
				$blockNames = $this->_validateBlockNames(array_keys($holes));	

				foreach($blockNames as $alias) {
					if (($block = $this->getLayout()->getBlock($alias)) !== false) {
						$block->setFrameTags('!--BOLT-' . $alias . '--', '!--/BOLT-' . $alias . '--');
					}
				}
			}
		}
		
		return $this;
	}
	
	/**
	 * Validate block names for hole punch
	 *
	 * @param array $blockNames
	 * @return array
	 */
	public function _validateBlockNames(array $blockNames)
	{
		$blockTypes = $blockNames;
		
		foreach($blockTypes as $key => $blockType) {
			if (strpos($blockType, '/') !== false) {
				$types[] = "@type='".$blockType."'";
				unset($blockNames[$key]);
			}
			else {
				unset($blockTypes[$key]);
			}
		}
		
		if ($this->_validatedBlockNameCache === NULL) {
			$this->_validatedBlockNameCache = array();
			
			if ($blockTypes && ($blocks = Mage::getSingleton('core/layout')->getNode()->xpath("//block[" . implode(' or ', $blockTypes) . "]"))) {
				foreach($blocks as $block) {
					if (!isset($this->_validatedBlockNameCache[(string)$block['type']])) {
						$this->_validatedBlockNameCache[(string)$block['type']] = array();
					}
					
					$this->_validatedBlockNameCache[(string)$block['type']][] = (string)$block['name'];
				}
			}
		}

		foreach($blockTypes as $key => $blockType) {
			if (isset($this->_validatedBlockNameCache[$blockType])) {
				$blockNames += $this->_validatedBlockNameCache[$blockType];
			}
		}

		return array_unique($blockNames);
	}
	
	/**
	 * Minify the layout tree so that the hole punch request can be processed quicker
	 *
	 * @param Varien_Event_Observer $observer
	 * @return $this
	 */
	public function minifyXmlTreeForHolePunchObserver(Varien_Event_Observer $observer)
	{
		if (!$this->isBolt()) {
			return $this;
		}

		if (!defined('FISHPIG_BOLT_PUNCHING_HOLES')) {
			return false;
		}
		
		$holePunch = Fishpig_Bolt_App::getHolePunch();

		if (($holes = Fishpig_Bolt_HolePunch::getValidHoles()) === false) {
			return false;
		}

		$holes = $this->_validateBlockNames($holes);

		$root = $observer->getEvent()->getLayout()->getNode();

		foreach($holes as $hole) {
			$this->_protectNodes($root->xpath('//block[@name="' . $hole . '"]'), true);
			$this->_protectNodes($root->xpath('//reference[@name="' . $hole . '"]'));
			$this->_protectNodes($root->xpath('//reference[@' . self::XML_BLOCK_SAFE . '="1"]/block'), true);
		}

		if (preg_match_all('/name="([^"]{1,})"[^>]{1,}' . self::XML_BLOCK_SAFE . '="1"[ \/]{0,2}>/s', $root->asNiceXml(), $matches)) {
			$matches[1] = array_unique($matches[1]);

			foreach($matches[1] as $blockName) {
				$this->_protectNodes($root->xpath('//block[@name="' . $blockName . '"]'), true);
				$this->_protectNodes($root->xpath('//reference[@name="' . $blockName . '"]'), true);
			}
		}

		if ($nodes = $root->xpath('//block[not(@' . self::XML_BLOCK_SAFE . '="1")]')) {
			$this->_unsetNodes($nodes);
		}
		
		if ($nodes = $root->xpath('//reference[not(@' . self::XML_BLOCK_SAFE . '="1")]')) {
			$this->_unsetNodes($nodes);
		}

		return true;
	}
	
	/**
	 * Unset nodes
	 *
	 * @param $nodes
	 * @return $this
	 */
	protected function _unsetNodes(&$nodes)
	{
		if ($nodes) {
			foreach($nodes as $node) {
				if (@count($node[0]->{0})) {
					unset($node[0]->{0});
				}
			}
		}
		
		return $this;
	}
	
	/**
	 * Add the safe = 1 attribute to $nodes
	 *
	 * @param $nodes
	 * @param int $protectFamily = 0
	 * @return $this
	 */
	protected function _protectNodes($nodes, $protectFamily = false)
	{
		$safe = self::XML_BLOCK_SAFE;

		foreach($nodes as $node) {
			if ($node->getName() !== 'action') {
				if (!$node->getAttribute($safe)) {
					$node->addAttribute($safe, "1");
				}
			}
			
			if ($protectFamily) {
				if ($node->hasChildren()) {
					foreach($node->children() as $child) {
						if ($child->getName() !== 'action') {
							$this->_protectNodes(array($child), $protectFamily);
						}
					}
				}
				
				if ($node->getName() !== 'reference') {
					while($node = @$node->getParent()) {
						if ($node->getName() !== 'action') {
							if (!$node->getAttribute($safe)) {
								$node->addAttribute($safe, "1");
							}
						}
					}
				}
			}
		}
		
		return $this;
	}
	
	/**
	 * Retrieve the Magento layout model
	 *
	 * @return Mage_Core_Model_Layout
	 */
	public function getLayout()
	{
		return Mage::getSingleton('core/layout');
	}
	
	/**
	 * Determine whether Bolt is loaded
	 *
	 * @return bool
	 */
	public function isBolt()
	{
		return defined('FISHPIG_BOLT');
	}
}
