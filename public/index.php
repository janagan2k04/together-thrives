<?php
/**
 * TogetherThrives - Front Controller. Every request enters here.
 */
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('CONFIG_PATH', BASE_PATH . '/config');
define('STORAGE_PATH', BASE_PATH . '/storage');

require APP_PATH . '/core/Autoloader.php';
Core\Autoloader::register();
require APP_PATH . '/core/Helpers.php';

$config = require CONFIG_PATH . '/config.php';
date_default_timezone_set($config['timezone']);

if ($config['debug']) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}

(new Core\App($config))->run();
