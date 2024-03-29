<?php
/**
 * @category	Fishpig
 * @package		Fishpig_Bolt
 * @license		http://fishpig.co.uk/license.txt
 * @author		Ben Tideswell <help@fishpig.co.uk>
 */
class Fishpig_Bolt_Session_Redis extends Fishpig_Bolt_Session_Abstract
{
	/**
	 * Session key prefix
	 *
	 * @const string
	 */
    const SESSION_PREFIX = 'sess_';
    
	/**
	 * Store the Memcache adapter
	 *
	 * @var Memcache|false
	 */
	static protected $_redis = null;

	/**
	 * Initialise the session object
	 *
	 * @return void
	 */
	static public function init()
	{
		if (!(@include_once(Fishpig_Bolt_App::getDir('lib' . DIRECTORY_SEPARATOR . 'Credis' . DIRECTORY_SEPARATOR . 'Client.php')))) {
			return false;
		}

		self::$_redis = new Credis_Client(
			self::getRedisHost(),
			(int)Fishpig_Bolt_App::getConfig('session_redis/port'),
			(int)Fishpig_Bolt_App::getConfig('session_redis/timeout'),
			(int)Fishpig_Bolt_App::getConfig('session_redis/persistent'),
			(int)Fishpig_Bolt_App::getConfig('session_redis/db'),
			self::getRedisPassword()
		);

        self::$_redis->setCloseOnDestruct(false);

		if ($dbNum = (int)Fishpig_Bolt_App::getConfig('session_redis/db')) {
			self::$_redis->select($dbNum);
		}

		Fishpig_Bolt_Session_Abstract::_initSessionData('Redis');
	}

	/**
	 * Get the Redis host/server if this has been set
	 *
	 * @return string|null
	  */
	static public function getRedisHost()
	{
		foreach(array('host', 'server') as $key) {
			if ($value = Fishpig_Bolt_App::getConfig('session_redis/' . $key)) {
				return $value;
			}
		}
		
		return null;
	}
	
	/**
	 * Get the Redis password if this has been set
	 *
	 * @return string|null
     */
	static public function getRedisPassword()
	{
		foreach(array('password', 'pass') as $key) {
			if ($value = Fishpig_Bolt_App::getConfig('session_redis/' . $key)) {
				return $value;
			}
		}
		
		return null;
	}
	
	/**
	 * Get the raw session data from Memcache
	 *
	 * @return string
	 */
	static protected function _getRawSessionData()
	{
        // If Redis connection has failed and Magento using DB as fallback for session
        // This code connects to the DB and gets session data from there
        if (!self::$_redis->isConnected()) {
            return self::getSessionDataFromDbFallback();
        }
        
        $sessionFound = false;
        
        foreach(Fishpig_Bolt_Session_Abstract::$_potentialCookieNames as $cookieName) {
            if (!isset($_COOKIE[$cookieName])) {
                continue;
            }
        
            if ($data = self::$_redis->hGet(self::SESSION_PREFIX . $_COOKIE[$cookieName], 'data')) {
                $sessionFound = true;
                $data = self::_decodeData($data);
                
                if (strpos($data, 'is_bolt') !== false) {
                    return $data;
                }
            }
        }
        
        if ($sessionFound) {
            // Session has been found in Redis so lets not fall back to DB
            return false;
        }
        
        return self::getSessionDataFromDbFallback();
	}

    /**
     * @return false|array
     */
    static protected function getSessionDataFromDbFallback()
    {
        $localFile = Fishpig_Bolt_App::getDir('app/etc/local.xml');
      
        if (!is_file($localFile)) {
            throw new Exception('Unable to load local.xml. This is required to connect to DB to get Redis fallback (DB).');
        }
      
        $localXml  = simplexml_load_file($localFile);      
        $conn      = $localXml->global->resources->default_setup->connection;
        $mysqli    = new mysqli((string)$conn->host, (string)$conn->username, (string)$conn->password, (string)$conn->dbname);
        $tableName = preg_replace('/[^a-z0-9_]{1}/i', '', $localXml->global->resources->db->table_prefix . 'core_session');
      
        foreach(Fishpig_Bolt_Session_Abstract::$_potentialCookieNames as $cookieName) {
            if (!isset($_COOKIE[$cookieName])) {
                continue;
            }
        
            $sessionId      = preg_replace('/[^a-z0-9]/', '', $_COOKIE[$cookieName]);
            $sessionExpires = (int)time();

            $query = "SELECT session_data FROM {$tableName} WHERE session_id LIKE '%{$sessionId}' AND session_expires > {$sessionExpires}";

            if (($result = $mysqli->query($query)) && ($row = $result->fetch_assoc())) {
                $data = self::_decodeData($row['session_data']);

                if (strpos($data, 'is_bolt') !== false) {
                    $result->free();
                    $mysqli->close();
            
                    return $data;	
                }
            }
        }

        !empty($result) && $result->free();
        !empty($mysqli) && $mysqli->close();
      
        return false;
    }
    
    /**
     * Decode the data
     *
     * @param string $data
     * @return string
     */
    static public function _decodeData($data)
    {
        switch (substr($data, 0, 4)) {
            case ':sn:': return snappy_uncompress(substr($data, 4));
            case ':lz:': return lzf_decompress(substr($data, 4));
            case ':gz:': return gzuncompress(substr($data, 4));
        }

        return $data;
    }
}
