<?php
ini_set('display_errors', 'on');
ini_set('error_reporting', E_ALL);
try {
    defined('APPLICATION_PATH') || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../private/application'));
    defined('APPLICATION_ENV') || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'development'));
    set_include_path(implode(PATH_SEPARATOR, array(realpath(APPLICATION_PATH . '/../library'))));

    include_once "IMDT/Util/Hash.php";
    include_once "IMDT/Util/String.php";

    $password = $argv[1];
    echo IMDT_Util_Hash::generate($password);
    exit(0);
} catch (Exception $e) {
    echo "<pre>ERRO: " . $e->getMessage();
    die;
}
