<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');

// required files
require_once 'classes/instagram.class.php';
require_once 'classes/gump.class.php';
require_once 'classes/functions.class.php';
require_once 'classes/TwitterAPIExchange.php';

/**
 * 
 *  some global vars and objects
 * 
 */
date_default_timezone_set('America/Chicago');
//date_default_timezone_set('America/Los_Angeles');
define('SITE_NAME', 'Social Media Scraper');
define('SITE_TITLE', SITE_NAME);
define('START_DATE', '2015-04-29');
define('END_DATE', '2015-07-30'); 
$Gump = new GUMP();
$Func = new functions();


/**
 * 
 * Database stuffs
 * 
 */
define('HOST','');
define('DB_NAME', '');
define('USER', '');
define('PASS', '');
define('DSN', 'mysql:host=' . HOST . ';dbname=' . DB_NAME . ';charset=utf8');

// turn off prepare emulation since we are > 5.1 
// and can set the errmode here instead of using $DBH->setAttribute()
try {
    $DBH = new PDO(DSN, USER, PASS, array(PDO::ATTR_EMULATE_PREPARES => false, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
} catch (PDOException $e) {
    echo "Cannot connect to database " . DB_NAME . " for reason : " . $e->getMessage();
    exit();
}




/**
 * 
 * Logic to determine entry periods and appropriate tags
 * 
 */
$now = date('Y-m-d H:i:s');
$range = $Func->get_current_range($DBH, $now);

if (!empty($range)) {
    $terms = array('term1','term2');
    define('TERM1','term1');
    define('TERM2','term2');
} else {
    $terms = array();
}



/**
 * 
 * Social Media Global vars / keys
 * 
 */
define('TWITTER_PREFIX', 'q=%23');

// twitter keys
define("TWITTER_CONSUMER_KEY", "");
define("TWITTER_CONSUMER_SECRET", "");
define("TWITTER_ACCESS_TOKEN", "");
define("TWITTER_ACCESS_TOKEN_SECRET", "");

// Instagram keys
define("IG_CLIENT_ID", "");
define("IG_SECRET", "");

