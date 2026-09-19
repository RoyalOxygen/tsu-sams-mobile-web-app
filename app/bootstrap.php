<?php
declare(strict_types=1);

$config = require dirname(__DIR__) . '/config.php';
date_default_timezone_set($config['app']['timezone']);

$debug = (bool)($config['app']['debug'] ?? false);
error_reporting($debug ? E_ALL : E_ALL & ~E_DEPRECATED);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');
if (!empty($config['paths']['logs']) && is_dir($config['paths']['logs'])) {
    ini_set('error_log', $config['paths']['logs'] . '/php-error.log');
}

if (PHP_SAPI !== 'cli') {
    set_exception_handler(static function (\Throwable $e) use ($debug): void {
        error_log('[TSU-SAMS] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
        if (!headers_sent()) {
            http_response_code(500);
        }
        if ($debug) {
            echo '<pre>' . htmlspecialchars((string)$e, ENT_QUOTES, 'UTF-8') . '</pre>';
        } else {
            echo 'Something went wrong. Please try again later.';
        }
    });
}

if (!defined('BASE_PATH')) {
    define('BASE_PATH', $config['paths']['root']);
}

spl_autoload_register(static function (string $class) use ($config): void {
    $map = [
        'App\\' => $config['paths']['root'] . '/app/',
    ];
    foreach ($map as $prefix => $dir) {
        if (str_starts_with($class, $prefix)) {
            $rel = str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
            $file = $dir . $rel;
            if (is_file($file)) {
                require $file;
            }
        }
    }
});

require_once __DIR__ . '/Helpers.php';

App\Database::init($config);
if (PHP_SAPI === 'cli') {
    // CLI scripts (seeders, migrations) need config but no session/headers.
    App\Security::configure($config);
} else {
    App\Security::boot($config);
}
App\Auth::boot();
