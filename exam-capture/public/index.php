<?php
declare(strict_types=1);

define('EXAM_CAPTURE', true);

if (php_sapi_name() === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $file = __DIR__ . $path;
    if ($path !== '/' && is_file($file)) {
        return false;
    }
    $shared = dirname(__DIR__, 2) . '/public' . $path;
    if ($path !== '/' && is_file($shared)) {
        $mime = mime_content_type($shared) ?: 'application/octet-stream';
        header('Content-Type: ' . $mime);
        readfile($shared);
        exit;
    }
}

require dirname(__DIR__, 2) . '/app/bootstrap.php';

use App\Router;
use App\ExamRoutes;
use App\Services\Installer;

Installer::ensure();

$router = new Router();
ExamRoutes::register($router, '');
$router->dispatch();
