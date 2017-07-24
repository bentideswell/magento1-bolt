<?php
/**
 * @category	Fishpig
 * @package		Fishpig_Bolt
 * @license		http://fishpig.co.uk/license.txt
 * @author		Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Bolt_Helper_Cache_Queue extends Mage_Core_Helper_Abstract
{	
	/*
	 *
	 * @var string
	 *
	 */
	const TYPE_CATEGORY = 'catalog_category';
	const TYPE_PRODUCT = 'catalog_product';
	const TYPE_PRODUCT_CATEGORIES = 'catalog_product_categories';
	const TYPE_CMS_PAGE = 'cms_page';
	
	/*
	 *
	 * @return $this
	 */
	public function flush()
	{
		$logFile = $this->_getLogFile();
		
		if (!is_file($logFile)) {
			return $this;
		}
		
		if (($queue = trim(@file_get_contents($logFile))) === '') {
			return $this;
		}
		
		$buffer = array();
		
		foreach(explode("\n", $queue) as $line) {
			if (strpos($line, ':') !== false) {
				list($id, $type) = explode(':', $line);
				
				if (!isset($buffer[$id])) {
					$buffer[$id] = array();
				}
				
				$buffer[$id][$type] = $type;
			}
		}

		$helper = Mage::helper('bolt/cache');
		$models = array(
			self::TYPE_CATEGORY => Mage::getModel('catalog/category'),
			self::TYPE_PRODUCT => Mage::getModel('catalog/product'),
			self::TYPE_PRODUCT_CATEGORIES => Mage::getModel('catalog/product'),
			self::TYPE_CMS_PAGE => Mage::getModel('cms/page'),
		);

		foreach($buffer as $id => $types) {
			foreach($types as $type) {
				if (!isset($models[$type])) {
					continue;
				}
				
				$model = $models[$type]->setId($id);

				if ($type === self::TYPE_CATEGORY) {
					$helper->refreshCategory($model);
				}
				else if ($type === self::TYPE_PRODUCT_CATEGORIES) {
					$helper->refreshProduct($model, true);
				}
				else if ($type === self::TYPE_PRODUCT) {
					$helper->refreshProduct($model, false);
				}
				else if ($type === self::TYPE_CMS_PAGE) {
					$helper->refreshCmsPage($model);
				}
			}
		}
		
		return $this;
	}

	
	/**
	 *
	 * @param Mage_Catalog_Model_Product $product
	 * @param bool $includeCategories = true
	 * @return $this
	 */
	public function refreshProduct(Mage_Catalog_Model_Product $product, $includeCategories = true)
	{
		if (!$product || !$product->getId()) {
			return $this;
		}

		return $this->_saveCacheFlushRequest(
			$product->getId(), 
			($includeCategories ? self::TYPE_PRODUCT_CATEGORIES : self::TYPE_PRODUCT)
		);
	}
	
	/**
	 *
	 * @param Mage_Catalog_Model_Category $category
	 * @return $this
	 */
	public function refreshCategory(Mage_Catalog_Model_Category $category)
	{
		if (!$category || !$category->getId()) {
			return $this;
		}

		$this->_saveCacheFlushRequest($category->getId(), self::TYPE_CATEGORY);
	}
	
	/**
	 * Refresh a CMS page cache record
	 * All sub pages are automatically refreshed
	 *
	 * @param Mage_Cms_Model_Page $page
	 * @return $this
	 */
	public function refreshCmsPage(Mage_Cms_Model_Page $page)
	{
		if (!$page || !$page->getId()) {
			return $this;
		}

		$this->_saveCacheFlushRequest($page->getId(), self::TYPE_CMS_PAGE);
	}
	
	/*
	 *
	 * @param int $id
	 * @param string $type
	 * @return $this
	 */
	protected function _saveCacheFlushRequest($id, $type)
	{
		$logFile = $this->_getLogFile();
		
		if (!is_dir(dirname($logFile))) {
			@mkdir(dirname($logFile));
		}
		
		@file_put_contents($logFile, $id . ':' . $type . "\n", FILE_APPEND | LOCK_EX);
		
		return $this;
	}
	
	/*
	 *
	 * @return string
	 */
	protected function _getLogFile()
	{
		return Mage::getBaseDir('var') . DS . 'bolt' . DS . 'flush-queue';
	}
}
