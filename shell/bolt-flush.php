<?php
/*
 * @SkipObfuscation
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(0);

// This is required incase extension is installed using modman
// Would usually use dirname(__DIR__) however is this file in a symlink
// This will point to the parent directory of the actual file and not the symlink
$dirsToTry = array(
	__DIR__, 						// Calling from the Magento directory
	dirname(__DIR__), 	// Calling from the shell directory
	getcwd(), 					// Calling from the Magento directory
	dirname(getcwd()), 	// Calling from the shell directory
);

$ds = DIRECTORY_SEPARATOR;

foreach($dirsToTry as $dir) {
	$appMageFile = $dir . $ds . 'app' . $ds . 'Mage.php';
	
	if (is_file($appMageFile)) {
		chdir($dir);
		include($appMageFile);
		umask(0);
		Mage::app();
		break;
	}
}

if (!class_exists('Mage')) {
	echo 'Unable to find Magento installation.';
	exit;
}

Mage::helper('bolt/cache_queue')->flush();
