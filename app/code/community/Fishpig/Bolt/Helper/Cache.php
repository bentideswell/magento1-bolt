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
   * @var array
   */
  protected $urlSuffixes = array();
  
  /**
   * @var array
   */
  protected $allStoreIds;
  
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

			$protocols = array('http', 'https');

			foreach($storeIds as $storeId) {
				$userAgents =  Mage::getStoreConfigFlag('bolt/advanced/multiple_themes', $storeId)
					? array('ua_default', 'ua_mobile', 'ua_tablet')
					: array('ua_default');
					
				foreach($userAgents as $userAgent) {
					foreach($protocols as $protocol) {
  					$this->log(' FLUSH: ' . $storeId . '/' . $userAgent . '/' . $protocol . '/' . $url);
  					
						call_user_func(array($_boltApp, 'delete'), (int)$storeId, $userAgent, $protocol, $url, $subpages);
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
  				$storeIds = explode(',', $storeIdString);

          if ($this->isEnterprise() && strpos($storeIdString, '0') !== false) {
            $storeIds = $this->_getAllStoreIds();
          }
          
          if ($storeIds) {
    				foreach($storeIds as $storeId) {
    					$this->refreshUrl($uri . ($this->isEnterprise() ? $this->_getUrlSuffix('product', $storeId) : ''), $storeId);
    				}
  				}
				}
			}
			
			if ($includeCategories) {
				if ($categoryIds = $this->_getAllProductCategoryIds($productIds)) {					
					if ($categoryUris = $this->_getAllCategoryUrls($categoryIds)) {
						foreach($categoryUris as $uri => $storeIdString) {  						
      				$storeIds = explode(',', $storeIdString);
    
              if ($this->isEnterprise() && strpos($storeIdString, '0') !== false) {
                $storeIds = $this->_getAllStoreIds();
              }

              if ($storeIds) {
                foreach($storeIds as $storeId) {
                  $this->refreshUrl($uri . ($this->isEnterprise() ? $this->_getUrlSuffix('product', $storeId) : ''), $storeId);
                }
              }
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
				$storeIds = explode(',', $storeIdString);

        if ($this->isEnterprise() && strpos($storeIdString, '0') !== false) {
          $storeIds = $this->_getAllStoreIds();
        }

        if ($storeIds) {
          foreach($storeIds as $storeId) {
            $this->refreshUrl($uri . ($this->isEnterprise() ? $this->_getUrlSuffix('category', $storeId) : ''), $storeId);
          }
        }
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
			->from(array('e' => $resource->getTableName('catalog/category')), 'entity_id')
			->where('level > ?', 1)
			->join(
				array('p' => $resource->getTableName('catalog_category_product_index')),
				'p.category_id = e.entity_id',
				null
			);
		
		if (is_array($productIds)) {
			if (count($productIds) > 1) {
				$select->where('p.product_id IN (?)', $productIds);
			}
			else {
				$select->where('p.product_id=?', array_shift($productIds));
			}
		}
		else {
			$select->where('p.product_id=?', $productIds);
		}
		
		return $db->fetchCol($select);
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
		
		$table = $this->isEnterprise() ? $resource->getTableName('enterprise_url_rewrite') : $resource->getTableName('core/url_rewrite');

		$select = $db->select()
			->distinct()
			->from($table, array('request_path', 'store_ids' => new Zend_Db_Expr('GROUP_CONCAT(store_id)')))
			->where('request_path <> ?', '')
			->group('request_path');

    if (!$this->isEnterprise()) {
  		$select->where('options IS NULL');
      $select->where('target_path LIKE ?', 'catalog/product/view/id/%');
      
      is_array($productIds) ? $select->where('product_id IN (?)', $productIds) : $select->where('product_id=?', $productIds);
    }
    else {
    	$targetPaths = array();
  	
      foreach((array)$productIds as $productId) {
        $targetPaths[] = 'catalog/product/view/id/' . $productId;
      }

      $select->where('target_path IN (?)', $targetPaths);
  		$select->where('is_system=?', 1);
    }

    return $db->fetchPairs($select);
	}

	/**
	 * Retrieve all product URL's categorised by store for a specific product
	 *
	 * @param Mage_Catalog_Model_Product $product
	 * @return false|array
	 */
	protected function _getAllCategoryUrls($categoryIds)
	{
		$resource = Mage::getSingleton('core/resource');
		$db = $resource->getConnection('core_read');
		
		$table = $this->isEnterprise() ? $resource->getTableName('enterprise_url_rewrite') : $resource->getTableName('core/url_rewrite');
		
		$select = $db->select()
			->distinct()
			->from($table, array('request_path', 'store_ids' => new Zend_Db_Expr('GROUP_CONCAT(store_id)')))
			->where('request_path <> ?', '')
			->group('request_path');

		if (!$this->isEnterprise()) {
  		$select->where('options IS NULL');
      $select->where('target_path LIKE ?', 'catalog/category/view/id/%');

      is_array($categoryIds) ? $select->where('product_id IN (?)', $categoryIds) : $select->where('product_id=?', $categoryIds);
    }
    else {
    	$targetPaths = array();
  	
      foreach((array)$categoryIds as $categoryId) {
        $targetPaths[] = 'catalog/category/view/id/' . $categoryId;
      }

      $select->where('target_path IN (?)', $targetPaths);
  		$select->where('is_system=?', 1);
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
  	$page->load($page->getId());

		if (!$storeIds = $page->getStores()) {
  		if (!$storeIds = $page->getStoreId()) {
        return false;
  		}
		}

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
	
	/**
   * @return bool
   */
	protected function isEnterprise()
	{
    return Mage::EDITION_ENTERPRISE === Mage::getEdition();
	}
	
	/**
   * @return string
   */
	protected function _getUrlSuffix($type, $storeId)
	{
  	if (!isset($this->urlSuffixes[$storeId])) {
    	$this->urlSuffixes[$$storeId] = array();
  	}

  	if (!isset($this->urlSuffixes[$storeId][$type])) {
    	$this->urlSuffixes[$storeId][$type] = rtrim('.' . Mage::getStoreConfig('catalog/seo/' . $type . '_url_suffix', $storeId), '.');
    }
    
    return $this->urlSuffixes[$storeId][$type];
	}
	
	/**
   * @return array
   */
	protected function _getAllStoreIds()
	{
  	if (!$this->allStoreIds) {
    	$this->allStoreIds = array();
    	
      foreach(Mage::app()->getStores() as $store) {
        $this->allStoreIds[] = (int)$store->getId();
      }
  	}

    return $this->allStoreIds;
	}
	
	/**
   * @param string $msg
   * @return $this
   */
	public function log($msg)
	{
  	$logDir = Mage::getBaseDir('var') . DS . 'log';
  	
  	if (!is_dir($logDir)) {
    	@mkdir($logDir);
  	}
  	
    @file_put_contents($logDir . DS . 'bolt.log', $msg . PHP_EOL, FILE_APPEND);
    
    return $this;
	}
}
