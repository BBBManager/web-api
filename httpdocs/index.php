<?php

set_time_limit(0);
function debug($content,$die = true) {
	echo '<pre>';
	print_r($content);
	echo '</pre>';
	if($die) die;
}

ini_set('display_errors', 'on');
ini_set('error_reporting', E_ALL);

try {
	defined('APPLICATION_PATH') || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../private/application'));	
	defined('APPLICATION_ENV') || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'development'));
	set_include_path(implode(PATH_SEPARATOR, array(realpath(APPLICATION_PATH . '/../library'))));
	require_once 'Zend/Application.php';
	$application = new Zend_Application(APPLICATION_ENV, APPLICATION_PATH . '/configs/application.ini');
	$application->bootstrap()->run();
} catch (Exception $e) {
    if(function_exists('http_response_code')){
        http_response_code(500);    
    }
    echo "<pre>ERRO: ".$e->getMessage();
    die;
}