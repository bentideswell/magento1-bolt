<?php
/*
 *
 */

	ini_set('display_errors', 1);
	error_reporting(E_ALL);
	
	include(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Mage.php');
	umask(0);
	Mage::app();
	
	Mage::helper('bolt/cache_queue')->flush();
