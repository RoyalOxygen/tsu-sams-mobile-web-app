<?php
declare(strict_types=1);

namespace App;

use App\Controllers\ExamController;

final class ExamRoutes
{
    public static function register(Router $router, string $prefix = ''): void
    {
        $p = rtrim($prefix, '/');
        $router->get($p === '' ? '/' : $p, [ExamController::class, 'home']);
        $router->get($p . '/enroll', [ExamController::class, 'start']);
        $router->post($p . '/enroll', [ExamController::class, 'lookup']);
        $router->post($p . '/confirm', [ExamController::class, 'confirm']);
        $router->get($p . '/photo', [ExamController::class, 'photoForm']);
        $router->post($p . '/photo', [ExamController::class, 'photo']);
        $router->get($p . '/face', [ExamController::class, 'faceForm']);
        $router->post($p . '/face', [ExamController::class, 'face']);
        $router->get($p . '/payment', [ExamController::class, 'paymentForm']);
        $router->post($p . '/payment', [ExamController::class, 'pay']);
        $router->get($p . '/done', [ExamController::class, 'done']);
    }
}
