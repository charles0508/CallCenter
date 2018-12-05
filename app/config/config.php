<?php

use Phalcon\Config;
use Phalcon\Logger;

return new Config([
    'database' => [
        'adapter' => 'Mysql',
        'host' => '127.0.0.1',
        'username' => 'root',
        'password' => 'root',
        'dbname' => 'callapi'
    ],
    'jxunsys' => [
        'hostname' => 'localhost',
        'database' => 'jxuntelsys',
        'username' => 'root',
        'password' => 'PassWord',
        'tablepre' => 'mix_',
        'charset' => 'utf8',
        'type' => 'mysql',
        'debug' => true,
        'pconnect' => 0,
        'autoconnect' => 0
    ],
    'cdrdb' => [
        'hostname' => 'localhost',
        'database' => 'jxuntelcalldata',
        'username' => 'root',
        'password' => 'PassWord',
        'tablepre' => '',
        'charset' => 'utf8',
        'type' => 'mysql',
        'debug' => true,
        'pconnect' => 0,
        'autoconnect' => 0
    ],
    'asterisk' =>[
        'hostname' => 'localhost',
        'database' => 'asterisk',
        'username' => 'root',
        'password' => 'PassWord',
        'tablepre' => 'mix_',
        'charset' => 'utf8',
        'type' => 'mysql',
        'debug' => true,
        'pconnect' => 0,
        'autoconnect' => 0
    ],
    'redis' => [
        'redishost'=>'127.0.0.1',
        'redisport'=>'6379',
        'redispass'=>'singheadredis',
        'outerlist'=>'singheadevent',
    ],
    'application' => [
        'controllersDir' => APP_PATH . '/controllers/',
        'modelsDir'      => APP_PATH . '/models/',
        'formsDir'       => APP_PATH . '/forms/',
        'viewsDir'       => APP_PATH . '/views/',
        'libraryDir'     => APP_PATH . '/library/',
        'pluginsDir'     => APP_PATH . '/plugins/',
        'cacheDir'       => BASE_PATH . '/cache/',
        'baseUri'        => '/callApi/',
        'publicUrl'      => 'www.jxuncall.com',
        'cryptSalt'      => 'eEAfR|_&G&f,+vU]:jFr!!A&+71w1Ms9~8_4L!<@[N@DyaIP_2My|:+.u>/6m,$D'
    ],
    'mail' => [
        'fromName' => 'callapi',
        'fromEmail' => 'phosphorum@phalconphp.com',
        'smtp' => [
            'server' => 'smtp.gmail.com',
            'port' => 587,
            'security' => 'tls',
            'username' => '',
            'password' => ''
        ]
    ],
    'amazon' => [
        'AWSAccessKeyId' => '',
        'AWSSecretKey' => ''
    ],
    'logger' => [
        'path'     => BASE_PATH . '/logs/',
        'format'   => '%date% [%type%] %message%',
        'date'     => 'D j H:i:s',
        'logLevel' => Logger::DEBUG,
        'filename' => 'application.log',
    ],
    // Set to false to disable sending emails (for use in test environment)
    'useMail' => true
]);
