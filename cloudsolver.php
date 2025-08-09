<?php
//ini_set('display_errors', '1');
//ini_set('display_startup_errors', '1');
// BOOTSTRAP FreshRSS
require(__DIR__ . '/../../constants.php');
require(LIB_PATH . '/lib_rss.php'); //Includes class autoloader

FreshRSS_Context::initSystem();

// Load list of extensions and enable the "system" ones.
Minz_ExtensionManager::init();

// get the directory for the extension in case the folder name changed to something non-standard
$extention = Minz_ExtensionManager::findExtension("FreshRss FlareSolverr");

if(!$extention){
	die('Could not find extension');
}else{
	require_once($extention->getPath().'/lib.php');
	run_flaresolverr_extension();
}

