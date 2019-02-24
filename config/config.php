<?php
return array_merge(array(
/*
|--------------------------------------------------------------------------
|  Configuration
|--------------------------------------------------------------------------
*/

	'node' =>'http://192.168.1.3',
	'limit' => 150,
	'public_key' =>'',
	'private_key'=>'',
	'address'=>'',

	'min_pay'=>100,
	// If you configure PHP environment variables and allow them to be invoked globally directly by PHP CGI mode, you can leave them blank. If it is a quick installation script install, please keep it unchanged
	'php_path'=>'/usr/local/php/bin/',
	
),include(__DIR__.'/config_db.php'));

?>