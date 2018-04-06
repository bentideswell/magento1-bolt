<?php
/**
 * @category	Fishpig
 * @package		Fishpig_Bolt
 * @license		http://fishpig.co.uk/license.txt
 * @author		Ben Tideswell <help@fishpig.co.uk>
 */
 
/**
 * Hole punch class
 *
 */
class Fishpig_Bolt_HolePunch
{
	/**
	 * Block apply options
	 *
	 * @const int
	 */
    const APPLY_ALWAYS = 1;
    const APPLY_WITH_CART = 2;
    const APPLY_WITH_CUSTOMER = 3;
    const APPLY_WITH_CART_OR_CUSTOMER = 4;
	
	/**
	 * Message passed via Exception
	 *
	 * @const string
	 */
	const EXCEPTION_MSG = 'Bolt is punching holes.';
	
	/**
	 * Array of hole punched blocks
	 * Saved here during exception
	 *
	 * @var static false|array
	 */    
    static protected $_exceptionTransport = false;

	/**
	 * Cache variable to store hole data
	 * Is false if cannot hole punch
	 *
	 * @null|false|array
	 */
	static protected $_validHoles = null;
	
	/**
	 * Default hole punch blocks
	 * This is initialised in self::__construct
	 * 
	 * @param array
	 */
	static protected $_defaultBlocks = array(
		'header' => self::APPLY_WITH_CART_OR_CUSTOMER,
	);
	
	/**
	 * The cache key for the hole punched request
	 *
	 * @var string
	 */
	static protected $_cacheKey = null;
	
	/**
	 * Error reporting level before throwing hole punch exception
	 *
	 * @var int
	 */
	static protected $_errorReportingLevel = null;
	
	/**
	 * Value of Mage::$_isDeveloperMode before throwing exception
	 *
	 * @var bool
	 */
	static protected $_isDeveloperMode = false;
	
	/**
	 * Punch holes in $html
	 *
	 * @param string $html
	 * @return void
	 */
	static public function punchHoles(&$html)
	{
		if (($holes = self::getValidHoles()) === false) {
			return false;
		}

		if (!preg_match('/<!--BOLT-(' . implode('|', $holes) . ')-->/U', $html)) {
			return false;
		}

		define('FISHPIG_BOLT_PUNCHING_HOLES', true);

		if (!defined('MAGENTO_ROOT')) {
			define('MAGENTO_ROOT', getcwd());
			require_once(Fishpig_Bolt_App::getDir('app' . DIRECTORY_SEPARATOR . 'Mage.php'));
		}

		$cacheEnabled = (int)Fishpig_Bolt_App::getConfig('holepunch/cache') === 1;
		$cacheKey = self::_getCacheKey();
		$holes = false;

		if ($cacheEnabled && ($holes = call_user_func(array(Fishpig_Bolt_App::getCache(), 'load'), $cacheKey)) !== false) {
			$holes = unserialize($holes);
			
foreach($holes as $k => $v) {
	$holes[$k] .= '<h1>CACHED ' . $cacheKey . '</h1>';
}
		}
		else {
			try {
				/**
				 * Hole punch isn't cached so we have to run Magento to generate the HP content
				 * This will throw an exception containing the blocks in JSON format
				 */
				Mage::app(Fishpig_Bolt_App::getConfig('store_code'))->getFrontController()->dispatch();
			}
			catch (Exception $e) {
				error_reporting(self::$_errorReportingLevel);

				if (self::$_isDeveloperMode) {
					Mage::setIsDeveloperMode(true);
				}

				if ($e->getMessage() !== self::EXCEPTION_MSG) {
					Mage::log($e->getMessage());
					Mage::logException($e);

					return false;
				}

				$holes = (array)self::$_exceptionTransport;

				if ($cacheEnabled) {
					call_user_func(array(Fishpig_Bolt_App::getCache(), 'save'), $cacheKey, serialize($holes), 21600);
				}
			}
		}
		
		if (!is_array($holes)) {
			return false;
		}

		$moveJsToBottom = Fishpig_Bolt_App::getConfig('move_js_to_bottom');
		$scripts = array();
		
		foreach((array)$holes as $alias => $data) {
			if ($moveJsToBottom && strpos($data, '<script') !== false) {
				if (preg_match_all('/<script[^>]{0,}>.*<\/script>/Us', $data, $matches)) {
					$data = str_replace($matches[0], '', $data);
					$scripts += $matches[0];
				}
			}
			
			$key = md5($data . microtime());
			$html = str_replace($key, $data, preg_replace('/\<\!--BOLT-' . $alias . '--\>.*\<\!--\/BOLT-' . $alias . '--\>/Uis', $key, $html));
		}

		if ($moveJsToBottom && count($scripts) > 0) {
			$html = str_replace('</body>', implode('', $scripts) . '</body>', $html);
		}

		if (strpos($html, '___SID=U') !== false) {
			$html = str_replace('?___SID=U&amp;', '?', $html);
			$html = str_replace(array('?___SID=U', '&amp;___SID=U'), '', $html);
		}
		
		return true;
	}
	
	/**
	 * Get the cache key for the holepunch data
	 *
	 * @return string
	 */
	static protected function _getCacheKey()
	{
		if (!is_null(self::$_cacheKey)) {
			return self::$_cacheKey;
		}
		
		$sessionAdapter = Fishpig_Bolt_App::getSession();
		$cacheKeyFieldNames = self::_getCacheKeyFields();
		
		$cacheKeyFieldValues = array(
			'base' => 'holepunch',	
			'customer_id' => (int)call_user_func(array($sessionAdapter, 'getData'), 'customer_base/id'),
			'cart_item_count' => (int)call_user_func(array($sessionAdapter, 'getData'), 'core/cart_item_count'),
			'quote_hash' => call_user_func(array($sessionAdapter, 'getData'), 'core/quote_hash'),
		);
		
		$cacheKeyFieldValues['is_logged_in'] = (int)($cacheKeyFieldValues['customer_id'] > 0);
		
		foreach($cacheKeyFieldValues as $field => $value) {
			if (!in_array($field, $cacheKeyFieldNames)) {
				unset($cacheKeyFieldValues[$field]);
			}
		}

		self::$_cacheKey = Fishpig_Bolt_App::generateCacheKey(
			(int)Fishpig_Bolt_App::getConfig('store_id'), 
			Fishpig_Bolt_App::getUserAgentGroup(),
			Fishpig_Bolt_App::getRequestProtocol(),
			'HolePunch-'  . md5(implode(DIRECTORY_SEPARATOR, $cacheKeyFieldValues))
		);
		
		return self::$_cacheKey;
	}
	
	/*
	 *
	 * @return array
	 */
	static protected function _getCacheKeyFields()
	{
		if (!($cacheKeyFields = trim(Fishpig_Bolt_App::getConfig('holepunch/cache_key_fields'), ','))) {
			$cacheKeyFields = 'is_logged_in,customer_id,quote_hash';
		}
		
		$cacheKeyFields = explode(',', $cacheKeyFields);
		
		return $cacheKeyFields;
	}
	
	/**
	 * Retrieve all holes valid for current request
	 *
	 * @return false|array
	 */
	static public function getValidHoles()
	{
		if (is_array(self::$_validHoles)) {
			return self::$_validHoles;
		}
		else if (self::$_validHoles === false) {
			return false;
		}
		
		self::$_validHoles = false;
		
		if (($holes = self::getHoles()) === false) {
			return false;
		}

		$isLoggedIn = Fishpig_Bolt_App::isCustomerLoggedIn();
		$hasCart = Fishpig_Bolt_App::hasCartItems();

		$holesToPunch = array();

		foreach($holes as $blockName => $punchType) {
			if ((int)$punchType === self::APPLY_ALWAYS) {
				$holesToPunch[] = $blockName;
			}	
			else if ((int)$punchType === self::APPLY_WITH_CART) {
				if ($hasCart) {
					$holesToPunch[] = $blockName;
				}
			}	
			else if ((int)$punchType === self::APPLY_WITH_CUSTOMER) {
				if ($isLoggedIn) {
					$holesToPunch[] = $blockName;
				}
			}	
			else if ((int)$punchType === self::APPLY_WITH_CART_OR_CUSTOMER) {
				if ($hasCart || $isLoggedIn) {
					$holesToPunch[] = $blockName;
				}
			}
		}

		if ($holesToPunch) {
			self::$_validHoles = $holesToPunch;
		}

		return self::$_validHoles;
	}
	
	/**
	 * Retrieve all hole data
	 *
	 * @return array|false
	 */
	static public function getHoles()
	{
		if (Fishpig_Bolt_App::getConfig('holepunch/enabled') !== '1') {
			return false;
		}
		
		$holes = self::$_defaultBlocks;
		
		if ($customHoles = Fishpig_Bolt_App::getConfig('holepunch/blocks')) {
			if (($customHoles = @unserialize($customHoles)) !== false) {
				foreach($customHoles as $customHole) {
					$holes[$customHole['name']] = isset($customHole['apply']) ? $customHole['apply'] : self::APPLY_WITH_CART_OR_CUSTOMER;
				}
			}
		}

		return $holes ? $holes : false;
	}
	
	/**
	 * Determine whether the request is a hole punch request
	 *
	 * @return bool
	 */
	static public function isPunchingHoles()
	{
		return defined('FISHPIG_BOLT_PUNCHING_HOLES');
	}
	
	/**
	 * Determine whether the hole punch is enabled
	 *
	 * @return bool
	 */
	static public function isEnabled()
	{
		return self::getHoles() !== false;
	}
	
	/**
	 * Throw an exception containing the hole punch content
	 * This is the only way to escape the Magento execution and pass back to Bolt
	 *
	 * @param array $blocks
	 * @return void
	 */
	static public function throwHolePunch(array $blocks)
	{
		// Ensure Exception isn't logged	
		if ((self::$_isDeveloperMode = Mage::getIsDeveloperMode()) === true) {
			Mage::setIsDeveloperMode(false);
		}
		
		if (Mage::app()->getStore()->getConfig('dev/log/active')) {
			Mage::app()->getStore()->setConfig('dev/log/active', false);
		}
		
		self::$_errorReportingLevel = error_reporting(0);

/*		
		foreach($blocks as $block => $html) {
			if (trim($html) === '') {
				unset($blocks[$block]);
			}
		}
*/

		if (count($blocks) > 0) {
			self::$_exceptionTransport = $blocks;
		}

		throw new Exception(self::EXCEPTION_MSG);
	}
}