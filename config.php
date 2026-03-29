<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Bani Database Configuration
|--------------------------------------------------------------------------
| Keep this file in GitHub with safe placeholder values.
| Put your real cPanel database credentials in config.local.php on the server.
*/

if (is_file(__DIR__ . '/config.local.php')) {
    require __DIR__ . '/config.local.php';
}

defined('BANI_DB_HOST') || define('BANI_DB_HOST', 'localhost');
defined('BANI_DB_PORT') || define('BANI_DB_PORT', '3306');
defined('BANI_DB_NAME') || define('BANI_DB_NAME', '');
defined('BANI_DB_USER') || define('BANI_DB_USER', '');
defined('BANI_DB_PASS') || define('BANI_DB_PASS', '');
defined('BANI_API_BASE') || define('BANI_API_BASE', '');
defined('BANI_API_SYNC_SHIPMENTS') || define('BANI_API_SYNC_SHIPMENTS', false);
