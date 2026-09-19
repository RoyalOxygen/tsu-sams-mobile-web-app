<?php
declare(strict_types=1);

if (php_sapi_name() === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $file = __DIR__ . $path;
    if ($path !== '/' && is_file($file)) {
        return false;
    }
}

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Router;
use App\Controllers\HomeController;
use App\Controllers\AuthController;
use App\Controllers\StudentController;
use App\Controllers\LecturerController;
use App\Controllers\AdminController;
use App\Services\Installer;

Installer::ensure();

$router = new Router();

$router->get('/', [HomeController::class, 'index']);
$router->get('/help', [HomeController::class, 'help']);

$router->get('/login', [AuthController::class, 'loginForm']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/logout', [AuthController::class, 'logout']);
$router->post('/logout', [AuthController::class, 'logout']);

$router->get('/register', [StudentController::class, 'enrollStart']);
$router->post('/register', [StudentController::class, 'enrollMatric']);
$router->post('/register/confirm', [StudentController::class, 'enrollConfirm']);
$router->get('/register/photo', [StudentController::class, 'enrollPhotoForm']);
$router->post('/register/photo', [StudentController::class, 'enrollPhoto']);
$router->get('/register/face', [StudentController::class, 'enrollFaceForm']);
$router->post('/register/face', [StudentController::class, 'enrollFace']);
$router->get('/register/payment', [StudentController::class, 'enrollPaymentForm']);
$router->post('/register/payment', [StudentController::class, 'enrollPay']);
$router->get('/register/courses', [StudentController::class, 'enrollCoursesForm']);
$router->post('/register/courses', [StudentController::class, 'enrollCourses']);
$router->get('/register/done', [StudentController::class, 'enrollDone']);

$router->get('/student/face-login', [StudentController::class, 'faceLoginForm']);
$router->post('/student/face-login', [StudentController::class, 'faceLogin']);

$router->get('/student', [StudentController::class, 'dashboard']);
$router->get('/student/courses', [StudentController::class, 'courses']);
$router->post('/student/courses', [StudentController::class, 'coursesSave']);
$router->get('/student/history', [StudentController::class, 'history']);
$router->get('/student/statistics', [StudentController::class, 'statistics']);
$router->get('/student/profile', [StudentController::class, 'profile']);
$router->post('/student/profile', [StudentController::class, 'profileSave']);
$router->get('/student/face', [StudentController::class, 'faceUpdateForm']);
$router->post('/student/face', [StudentController::class, 'faceUpdate']);
$router->post('/student/face/pay', [StudentController::class, 'faceUpdatePay']);
$router->get('/student/attendance', [StudentController::class, 'sessions']);
$router->get('/student/attendance/success', [StudentController::class, 'attendSuccess']);
$router->get('/student/attendance/fail', [StudentController::class, 'fail']);
$router->get('/student/attendance/{id}', [StudentController::class, 'attendForm']);
$router->post('/student/attendance/{id}', [StudentController::class, 'attendSubmit']);

$router->get('/lecturer/register', [LecturerController::class, 'registerForm']);
$router->post('/lecturer/register', [LecturerController::class, 'register']);
$router->get('/lecturer', [LecturerController::class, 'dashboard']);
$router->get('/lecturer/courses', [LecturerController::class, 'courses']);
$router->get('/lecturer/courses/create', [LecturerController::class, 'courseForm']);
$router->get('/lecturer/courses/{id}/edit', [LecturerController::class, 'courseForm']);
$router->post('/lecturer/courses', [LecturerController::class, 'courseSave']);
$router->get('/lecturer/sessions', [LecturerController::class, 'sessions']);
$router->get('/lecturer/sessions/create', [LecturerController::class, 'sessionForm']);
$router->post('/lecturer/sessions', [LecturerController::class, 'sessionSave']);
$router->post('/lecturer/sessions/{id}', [LecturerController::class, 'sessionAction']);
$router->get('/lecturer/records', [LecturerController::class, 'records']);
$router->get('/lecturer/export', [LecturerController::class, 'export']);
$router->get('/lecturer/profile', [LecturerController::class, 'profile']);
$router->post('/lecturer/profile', [LecturerController::class, 'profileSave']);

$router->get('/admin', [AdminController::class, 'dashboard']);
$router->get('/admin/students', [AdminController::class, 'students']);
$router->get('/admin/students/create', [AdminController::class, 'studentForm']);
$router->get('/admin/students/import', [AdminController::class, 'importForm']);
$router->post('/admin/students/import', [AdminController::class, 'import']);
$router->get('/admin/students/{id}/edit', [AdminController::class, 'studentForm']);
$router->get('/admin/students/{id}', [AdminController::class, 'studentView']);
$router->post('/admin/students/{id}/password', [AdminController::class, 'studentPassword']);
$router->post('/admin/students', [AdminController::class, 'studentSave']);
$router->get('/admin/faces', [AdminController::class, 'faces']);
$router->post('/admin/faces', [AdminController::class, 'faceDelete']);
$router->get('/admin/lecturers', [AdminController::class, 'lecturers']);
$router->post('/admin/lecturers', [AdminController::class, 'lecturerAction']);
$router->get('/admin/lecturers/{id}/edit', [AdminController::class, 'lecturerForm']);
$router->post('/admin/lecturers/save', [AdminController::class, 'lecturerSave']);
$router->get('/admin/profile', [AdminController::class, 'profile']);
$router->post('/admin/profile', [AdminController::class, 'profileSave']);
$router->get('/admin/settings', [AdminController::class, 'settings']);
$router->post('/admin/settings', [AdminController::class, 'settingsSave']);
$router->get('/admin/venues', [AdminController::class, 'venues']);
$router->get('/admin/venues/create', [AdminController::class, 'venueForm']);
$router->get('/admin/venues/{id}/edit', [AdminController::class, 'venueForm']);
$router->post('/admin/venues', [AdminController::class, 'venueSave']);
$router->get('/admin/payments', [AdminController::class, 'payments']);
$router->post('/admin/payments', [AdminController::class, 'paymentsSave']);
$router->get('/admin/transactions', [AdminController::class, 'transactions']);
$router->get('/admin/reports', [AdminController::class, 'reports']);
$router->get('/admin/reports/export', [AdminController::class, 'reportExport']);
$router->get('/admin/logs', [AdminController::class, 'logs']);

$router->dispatch();
