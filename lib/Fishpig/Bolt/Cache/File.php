<?php
/**
 * @category	Fishpig
 * @package		Fishpig_Bolt
 * @license		http://fishpig.co.uk/license.txt
 * @author		Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Bolt_Cache_File extends Fishpig_Bolt_Cache_Abstract
{
	/**
	 * Cache a page
	 *
	 * @param string $id
	 * @param string $value
	 * @return bool
	 */
	 static public function save($key, $value, $lifetime = null)
	 {
		if (strlen($value) < Fishpig_Bolt_Cache_Abstract::MINIMUM_CACHED_FILE_LENGTH) {
			return false;
		}
		
		$filename = self::_getCacheFilename($key);
		$path = dirname($filename);
		
		if (!is_dir($path)) {
			if (@mkdir($path, 0777, true) === false) {
				return false;
			}
		}

		return (int)file_put_contents($filename, (self::_isCompressionEnabled() ? gzcompress($value) : $value)) > 0;
	}
	
	/**
	 * Retrieve a cached page by it's ID
	 *
	 * @param string $id
	 * @return string|false
	 */
	 static public function load($key)
	 {
		if (self::exists($key)) {
			$value = @file_get_contents(self::_getCacheFilename($key));
			$value = trim(self::_isCompressionEnabled() ? gzuncompress($value) : $value);

			if (strlen($value) > Fishpig_Bolt_Cache_Abstract::MINIMUM_CACHED_FILE_LENGTH) {
				return $value;
			}
		}
		
		return false;
	}
	
	/**
	 * Delete a cache page by it's ID
	 *
	 * @param string $id
	 * @return void
	 */
	 static public function delete($key, $subpages = false)
	 {
		if (self::exists($key)) {
			@unlink(self::_getCacheFilename($key));
		}

		if ($subpages) {
			if ($path = self::_getPath($key)) {
				self::_recursivelyDelete($path);	
			}
		}
	}
	
	/**
	 * Flush the whole cache
	 *
	 * @return void
	 */
	static public function flush()
	{
		self::_recursivelyDelete(self::_getPath(''));
	}

	
	/**
	 * Determine whether $id has been cached
	 *
	 * @param string $id
	 * @return bool
	 */
	 static public function exists($key)
	{
		$filename = self::_getCacheFilename($key);
		
		if (!is_file($filename)) {
			return false;
		}

		return ($ttl = self::getExpiryTime()) === 0 || $ttl > (time() - filectime($filename));
	}
	
	/**
	 * Determine whether $id has expired
	 *
	 * @param string
	 * @return bool
	 */
	 static public function expired($key)
	{
		return self::exists($key);
	}
	
	/**
	 * Get the filename for $key's cache file
	 *
	 * @param string $key
	 * @return string
	 */
	static protected function _getCacheFilename($key)
	{
		return self::_getPath($key . '.cache');
	}
	
	/**
	 * Retrieve the relative path to the cache directory
	 *
	 * @return string
	 */
	 static protected function _getPath($file)
	 {
		return Fishpig_Bolt_App::getDir(
			'var' . DIRECTORY_SEPARATOR . 'bolt' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . $file
		);
	 }
	 
	 /**
	  * Recursively delete a directory
	  *
	  * @param string $dir
	  */
	 static protected function _recursivelyDelete($dir)
	 {
		$files = array_reverse(self::_recursivelyScan($dir));

		if (count($files) > 0) {
			foreach($files as $file) {
				if (is_file($file)) {
					unlink($file);
				}	
				else if (is_dir($file)) {
					rmdir($file);
				}
			}
		}
		
		if (is_dir($dir)) {
			rmdir($dir);
		}
		
		return is_dir($dir);
	 }
	 
	/**
	 * Scan $dir and return all directories and files in an array
	 *
	 * @param string $dir
	 * @return array
	 */
	static protected function _recursivelyScan($dir)
	{
		$files = array();
		
		if (is_dir($dir)) {
			foreach(scandir($dir) as $file) {
				if (trim($file, '.') === '') {
					continue;
				}
				
				$tmp = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $file;
				$files[] = $tmp;
		
				if (is_dir($tmp)) {
					$files = array_merge($files, self::_recursivelyScan($tmp));
				}
			}
		}

		return $files;
	}
}
