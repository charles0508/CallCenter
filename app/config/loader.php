<?php

use Phalcon\Loader;

$loader = new Loader();

/**
 * We're a registering a set of directories taken from the configuration file
 */

$loader->registerNamespaces([
    'callApi\Models'      => $config->application->modelsDir,
    'callApi\Controllers' => $config->application->controllersDir,
    'callApi\Forms'       => $config->application->formsDir,
    'callApi'             => $config->application->libraryDir
]);
$loader->register();
// Use composer autoloader to load vendor classes
require_once APP_PATH . '/library/Functions/global.php';
