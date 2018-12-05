<?php

use Phalcon\DI\FactoryDefault;
use Phalcon\Mvc\Application;

error_reporting(E_ALL);

/**
 * Define some useful constants
 */
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
//define('CONFIG_PATH', APP_PATH. '/config/');
define('CONFIG_PATH', '/tmp/configs/');
define('WEB_URL', "http://".$_SERVER['HTTP_HOST'].substr($_SERVER['PHP_SELF'], 0, strrpos($_SERVER['PHP_SELF'], '/public')+1));//定义网站根路径,包含http、server
try {
    /**
     * The FactoryDefault Dependency Injector automatically register the right services providing a full stack framework
     */
    
    $di = new FactoryDefault();

    /**
     * Read services
     */
    include APP_PATH . "/config/services.php";

    /**
     * Get config service for use in inline setup below
     */
    $config = $di->getConfig();

    /**
     * Include Autoloader
     */
    include APP_PATH . '/config/loader.php';

    /**
    * Handle the request
    */
    $application = new Application($di);

    echo $application->handle()
        ->getContent();

} catch (Exception $e) {
	echo $e->getMessage(), '<br>';
	echo nl2br(htmlentities($e->getTraceAsString()));
}
