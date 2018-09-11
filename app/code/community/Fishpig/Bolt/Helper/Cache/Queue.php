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
		$legacyLogFile = Mage::getBaseDir('var') . DS . 'bolt' . DS . 'flush-queue';
		
		if (is_file($legacyLogFile)) {
			@unlink($legacyLogFile);
		}
		
		$this->ensureDbTableExists();

		$table = $this->getTableName();
		$write = Mage::getSingleton('core/resource')->getConnection('core_write');
		$read  = Mage::getSingleton('core/resource')->getConnection('core_read');
		
		$logRecords = $read->fetchAll(
			$read->select()
				->from($table, array('entity_id', 'entity_type'))
				->order('created_at DESC')
				->limit(100)
		);
			
		if (!$logRecords) {
			return $this;
		}
		
		$helper = Mage::helper('bolt/cache');
		$models = array(
			self::TYPE_CATEGORY => Mage::getModel('catalog/category'),
			self::TYPE_PRODUCT => Mage::getModel('catalog/product'),
			self::TYPE_PRODUCT_CATEGORIES => Mage::getModel('catalog/product'),
			self::TYPE_CMS_PAGE => Mage::getModel('cms/page'),
		);
		
		foreach($logRecords as $line) {
			$id   = (int)$line['entity_id'];
			$type = $line['entity_type'];
			
			if ($id === 0 || !isset($models[$type])) {
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
			
			$write->delete($table, $write->quoteInto('entity_id=' . (int)$id . ' AND entity_type=?', $type));
		}

		return $this;
	}

	/**
	 *
	 * @param  Mage_Catalog_Model_Product $product
	 * @param  bool $includeCategories = true
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
	 * @param  Mage_Catalog_Model_Category $category
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
	 * @param  Mage_Cms_Model_Page $page
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
	 * @param  int $id
	 * @param  string $type
	 * @return $this
	 */
	protected function _saveCacheFlushRequest($id, $type)
	{
		$this->ensureDbTableExists();

		try {
			Mage::getSingleton('core/resource')->getConnection('core_write')
				->insert(
					$this->getTableName(),
					array(
						'entity_id'   => $id,
						'entity_type' => $type,
						'created_at'  => now(),
					)
				);
		}
		catch (Exception $e) {
			// Do nothing. Record already exists	
		}
		
		return $this;
	}
	
	/*
	 * Ensure that the DB table exists
	 *
	 * @return $this
	 */
	protected function ensureDbTableExists()
	{
		$resource = Mage::getSingleton('core/resource');
		$read     = $resource->getConnection('core_read');
		$table    = $this->getTableName();
		
		try {
			$read->fetchOne(
				$read->select()->from($table, '*')->limit(1)
			);

		}
		catch (Exception $e) {
			$resource->getConnection('core_write')->query("
				CREATE TABLE IF NOT EXISTS {$table} (
					`entity_id` int(11) unsigned NOT NULL default 0,
					`entity_type` varchar(32) NOT NULL default '',
					`created_at` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
					PRIMARY KEY (`entity_id`, `entity_type`)
				) ENGINE=MYISAM DEFAULT CHARSET=utf8 COMMENT='Queue for Bolt Auto Refresh';
			");
		}
		
		return $this;
	}
	
	/*
	 * Get the DB table
	 *
	 * @return string
	 */
	protected function getTableName()
	{
		return Mage::getSingleton('core/resource')->getTableName('bolt_flush_queue');
	}
}
