<?php
/**
 * @category	Fishpig
 * @package		Fishpig_Bolt
 * @license		http://fishpig.co.uk/license.txt
 * @author		Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Bolt_Helper_Cache extends Mage_Core_Helper_Abstract
{	
	/**
	 * Refresh a URL
	 *
	 * @param string $url
	 * @param array|int $storeIds
	 * @param bool $subpages = false
	 * @return $this
	 */
	public function refreshUrl($url, $storeIds, $subpages = true)
	{
		if ($_boltApp = $this->getApp()) {
			if (!is_array($storeIds)) {
				$storeIds = array($storeIds);
			}

			$useragents = array('ua_default', 'ua_mobile', 'ua_tablet');
			$protocols = array('http', 'https');
			
			foreach($storeIds as $storeId) {
				foreach($useragents as $useragent) {
					foreach($protocols as $protocol) {
						call_user_func(array($_boltApp, 'delete'), (int)$storeId, $useragent, $protocol, $url, $subpages);
					}
				}
			}
		}
		
		return $this;
	}

	/**
	 * Refresh a product . Can also refresh related category URLs
	 * All sub pages are automatically refreshed
	 *
	 * @param Mage_Catalog_Model_Product $product
	 * @param bool $includeCategories = true
	 * @return $this
	 */
	public function refreshProduct(Mage_Catalog_Model_Product $product, $includeCategories = true)
	{
		if ($product && $product->getId()) {
			$productIds = array($product->getId());
			
			if ($parentIds = $this->_getParentProductIds($product->getId())) {
				$productIds = array_merge($productIds, $parentIds);
			}

			if ($productUris = $this->_getAllProductUrls($productIds)) {
				foreach($productUris as $uri => $storeIdString) {
					$this->refreshUrl($uri, explode(',', $storeIdString));
				}
			}
			
			if ($includeCategories) {
				if ($categoryIds = $this->_getAllProductCategoryIds($productIds)) {					
					if ($categoryUris = $this->_getAllCategoryUrls($categoryIds)) {
						foreach($categoryUris as $uri => $storeIdString) {
							$this->refreshUrl($uri, explode(',', $storeIdString));
						}
					}
				}
			}
		}

		return $this;
	}
	
	/**
	 * Refresh a category cache record
	 * All sub pages are automatically refreshed
	 *
	 * @param Mage_Catalog_Model_Category $category
	 * @return $this
	 */
	public function refreshCategory(Mage_Catalog_Model_Category $category)
	{
		if ($categoryUris = $this->_getAllCategoryUrls($category->getId())) {
			foreach($categoryUris as $uri => $storeIdString) {
				$this->refreshUrl($uri, explode(',', $storeIdString));
			}
		}

		return $this;
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
		if ($pageUris = $this->_getAllCmsPageUrls($page)) {
			foreach($pageUris as $uri => $storeIdString) {
				$this->refreshUrl($uri, explode(',', $storeIdString));
			}
		}

		return $this;
	}

	/**
	 * Send a flush message to the cache adapter
	 *
	 * @return $this
	 */
	public function flush()
	{
		return ($_boltApp = $this->getApp()) ? call_user_func(array($_boltApp, 'flush')) : false;
	}

	/**
	 * Fetch all category IDs belonging to $productIds
	 *
	 * @param array|int $productIds
	 * @return array
	**/
	protected function _getAllProductCategoryIds($productIds)
	{
		$resource = Mage::getSingleton('core/resource');
		$db = $resource->getConnection('core_read');

		$select = $db->select()
			->distinct()
			->from(array('e' => $resource->getTableName('catalog/category')), 'path')
			->join(
				array('p' => $resource->getTableName('catalog_category_product')),
				'p.category_id = e.entity_id',
				null
			);
		
		if (is_array($productIds)) {
			$select->where('p.product_id IN (?)', $productIds);
		}
		else {
			$select->where('p.product_id=?', $productIds);
		}

		$categoryIds = array();
		
		if ($results = $db->fetchCol($select)) {
			foreach($results as $result) {
				$parts = explode('/', trim($result, '/'));
				
				if (count($parts) > 2) {
					array_shift($parts);
					array_shift($parts);
					
					$categoryIds += $parts;
				}
			}
		}
		
		return array_unique($categoryIds);
	}
	
	/**
	 * Retrieve all product URL's categorised by store for a specific product
	 *
	 * @param int|array $product
	 * @return false|array
	 */
	protected function _getAllProductUrls($productIds)
	{
		$resource = Mage::getSingleton('core/resource');
		$db = $resource->getConnection('core_read');
		
		$select = $db->select()
			->distinct()
			->from($resource->getTableName('core/url_rewrite'), array('request_path', 'store_ids' => new Zend_Db_Expr('GROUP_CONCAT(store_id)')))
			->where('request_path <> ?', '')
			->where('options IS NULL')
			->group('request_path');
			
		if (is_array($productIds)) {
			$select->where('product_id IN (?)', $productIds);
		}
		else {
			$select->where('product_id=?', $productIds);
		}

		return $db->fetchPairs($select);
	}

	/**
	 * Retrieve all product URL's categorised by store for a specific product
	 *
	 * @param Mage_Catalog_Model_Product $product
	 * @return false|array
	 */
	protected function _getAllCategoryUrls($categoryId)
	{
		$resource = Mage::getSingleton('core/resource');
		$db = $resource->getConnection('core_read');
		
		$select = $db->select()
			->distinct()
			->from($resource->getTableName('core/url_rewrite'), array('request_path', 'store_ids' => new Zend_Db_Expr('GROUP_CONCAT(store_id)')))
			->where('request_path <> ?', '')
			->where('options IS NULL')
			->group('request_path');
		
		if (is_array($categoryId)) {
			$select->where('category_id IN (?)', $categoryId);
		}
		else {
			$select->where('category_id=?', $categoryId);
		}
			
		return $db->fetchPairs($select);
	}

	/**
	 * Retrieve all URL's for $page
	 *
	 * @param Mage_Cms_Model_Page $page
	 * @return false|array
	 */
	protected function _getAllCmsPageUrls(Mage_Cms_Model_Page $page)
	{
		if (!$page->getStores()) {
			return false;
		}

		$storeIds = $page->getStores();
			
		if (count($storeIds) === 1 && $storeIds[0] == 0) {
			$storeIds = array_keys(Mage::app()->getStores());
		}

		$storeIdString = implode(',', $storeIds);
		
		$urls = array(
			$page->getIdentifier() => $storeIdString,
		);

		foreach($storeIds as $storeId) {
			if ($page->getIdentifier() == Mage::getStoreConfig('web/default/cms_home_page', $storeId)) {
				$urls['/'] = $storeIdString;
			}
		}

		return $urls;
	}
	
	/**
	 * Get the parent product IDs
	 *
	 * @param int $productId
	 * @return array
	 */
	protected function _getParentProductIds($productId)
	{

		$resource = Mage::getSingleton('core/resource');
		$db = $resource->getConnection('core_read');

		return $db->fetchCol(
			$db->select()->distinct()
				->from($resource->getTableName('catalog_product_super_link'), 'parent_id')
				->where('product_id = ?', $productId)
		);
	}
	
	/**
	 * Get the App model
	 *
	 * @return false|Fishpig_Bolt_App
	 */
	public function getApp()
	{
		return 'Fishpig_Bolt_App';
		return defined('FISHPIG_BOLT') ? 'Fishpig_Bolt_App' : false;
	}
}
