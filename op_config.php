<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
} 
if (file_exists(__DIR__ . '/system/op_error_handler.php')) {
    require_once(__DIR__ . '/system/op_error_handler.php');
} 
$CONFIG['token'] = session_id();
ini_set('max_execution_time', 300);
set_time_limit(300);
date_default_timezone_set('Asia/Kolkata');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED & ~E_NOTICE);
$CONFIG['today'] =date('Y-m-d');
$CONFIG['current_date_time'] =date('Y-m-d H:i:s');
$api_key ='a9dcc62d6078b881d318aca0ead65ab5'; //MyAdvIndia@2020
/*-------Some Basic Details (Global Variables) ---------*/
if(isset($_SESSION['user_id']))
{
	$CONFIG['user_id'] = $_SESSION['user_id'];
}

$CONFIG['app_name'] ='OPeX';
$CONFIG['dev_company'] ="OfferPlant Technologies Private Limited";
$CONFIG['dev_by'] ="OfferPlant";
$CONFIG['dev_url'] ="http://offerplant.com";
$CONFIG['dev_email'] ="ask@offerplant.com";
$CONFIG['dev_contact'] ="9431426600";




/* Environmental Configuration */
// Determine protocol (http/https)
$protocol = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? (php_sapi_name() === 'cli' ? 'localhost' : '');
// Detect base directory dynamically
$script_name = $_SERVER['SCRIPT_NAME'] ?? '';
$script_dir = $script_name ? str_replace(basename($script_name), "", $script_name) : '/';
$CONFIG['base_url'] = $protocol . $host . $script_dir;

if ($host == 'localhost' || $host == '127.0.0.1') {
    /* Test / Local Configuration (Laragon) */
    $CONFIG['host_name'] = 'localhost';
    $CONFIG['db_user']     = 'root';   // Local MySQL user
    $CONFIG['db_password'] = '';       // Local MySQL password
    $CONFIG['db_name']     = 'u305984835_caseos'; 
	 $CONFIG['base_url']    = 'http://localhost/caseos/';
} else {
    /* Live Configuration */
    $CONFIG['host_name'] = 'localhost';
    $CONFIG['db_user']     = 'u305984835_caseos';
    $CONFIG['db_password'] = '@User_2001!';
    $CONFIG['db_name']     = 'u305984835_caseos';
    // If live has a fixed URL that doesn't follow script path, you can force it:
    $CONFIG['base_url']    = 'https://caseos.olaw.in/';
}




/*-------End of Basic Details ---------*/

/* Razorpay Settings */
define('RAZORPAY_KEY_ID', 'rzp_live_RUcX3vW4ZEbY47');
define('RAZORPAY_KEY_SECRET', 'HaH57gsIQcvVGK8PLmkdF61o');
define('RZP_CURRENCY', 'INR');

extract($CONFIG);

?>
