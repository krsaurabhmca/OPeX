<?php
session_start(); 
$CONFIG['token'] = session_id();
ini_set('max_execution_time', 300);
set_time_limit(300);
date_default_timezone_set('Asia/Kolkata');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$CONFIG['today'] =date('Y-m-d');
$CONFIG['current_date_time'] =date('Y-m-d H:i:s');
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


/* Live Configuration */
// $CONFIG['host_name'] ='localhost';
// $CONFIG['db_user'] ='u673864504_opex';
// $CONFIG['db_password'] ='@User_2001';
// $CONFIG['db_name'] ='u673864504_opex';

// $CONFIG['base_url'] ='https://opex.x2z.in/';



/* Test Configuration */
$CONFIG['host_name'] ='localhost';
$CONFIG['db_user'] ='root';
$CONFIG['db_password'] ='';
$CONFIG['db_name'] ='opex';
$CONFIG['base_url'] ='http://localhost/opex/';


/*-------End of Basic Details ---------*/

extract($CONFIG);

?>
