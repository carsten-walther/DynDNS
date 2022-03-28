<?php

$basePath = __DIR__;

// for infinite time of execution
ini_set('max_execution_time', '0');

error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors', 'On');
ini_set('error_log', $basePath . '/php_error.log');

/**
 * run: php -S localhost:8000
 */

require $basePath . '/config.php';
require $basePath . '/vendor/autoload.php';

$dynDnsController = new \CarstenWalther\DynDNS\Controller\DynDnsController($basePath, [
    'data' => $basePath . '/data'
]);
