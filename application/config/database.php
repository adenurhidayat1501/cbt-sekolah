<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Auto-load .env environment file if present for easy database configuration
$env_file = dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . '.env';
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv("{$name}={$value}");
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

$active_group = 'default';
$query_builder = TRUE;

$db['default'] = array(
    'dsn'	=> '',
    'hostname' => getenv('DB_HOST') ? getenv('DB_HOST') : 'localhost',
    'username' => getenv('DB_USER') ? getenv('DB_USER') : '',
    'password' => getenv('DB_PASS') ? getenv('DB_PASS') : '',
    'database' => getenv('DB_NAME') ? getenv('DB_NAME') : '',
    'port'     => getenv('DB_PORT') ? (int)getenv('DB_PORT') : 3306,
    'dbdriver' => 'mysqli',
    'dbprefix' => '',
    'pconnect' => FALSE,
    'db_debug' => (ENVIRONMENT !== 'production'),
    'cache_on' => FALSE,
    'cachedir' => '',
    'char_set' => 'utf8mb4',
    'dbcollat' => 'utf8mb4_unicode_ci',
    'swap_pre' => '',
    'encrypt' => FALSE,
    'compress' => FALSE,
    'stricton' => FALSE,
    'failover' => array(),
    'save_queries' => TRUE
);
