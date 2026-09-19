<?php
declare(strict_types=1);

namespace App;

final class Auth
{
    private static bool $loaded = false;
    private static ?array $cached = null;

    public static function boot(): void
    {
    }

    public static function user(): ?array
    {
        if (self::$loaded) {
            return self::$cached;
        }
        self::$loaded = true;
        $id = $_SESSION['user_id'] ?? null;
        if (!$id) {
            return self::$cached = null;
        }
        return self::$cached = Database::one(
            'SELECT u.*, r.slug AS role_slug, r.name AS role_name
             FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ?',
            [$id]
        );
    }

    private static function forget(): void
    {
        self::$loaded = false;
        self::$cached = null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function role(): ?string
    {
        return self::user()['role_slug'] ?? null;
    }

    public static function requireRole(string ...$roles): array
    {
        $user = self::user();
        if (!$user || $user['status'] !== 'active') {
            flash('error', 'Please sign in to continue.');
            redirect('login');
        }
        if (!in_array($user['role_slug'], $roles, true)) {
            http_response_code(403);
            echo 'Access denied.';
            exit;
        }
        return $user;
    }

    public static function login(int $userId): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        $_SESSION['_created'] = time();
        self::forget();
        Database::update('users', [
            'last_login_at' => now(),
            'last_login_ip' => client_ip(),
        ], 'id = ?', [$userId]);
        Audit::log('login', 'users', (string)$userId);
    }

    public static function logout(): void
    {
        $uid = $_SESSION['user_id'] ?? null;
        if ($uid) {
            Audit::log('logout', 'users', (string)$uid);
        }
        self::forget();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', (bool)$p['secure'], (bool)$p['httponly']);
        }
        session_destroy();
    }

    public static function student(): ?array
    {
        $u = self::user();
        if (!$u || $u['role_slug'] !== 'student') {
            return null;
        }
        return Database::one(
            'SELECT s.*, f.name AS faculty_name, d.name AS department_name, f.code AS faculty_code, d.code AS department_code
             FROM students s
             JOIN faculties f ON f.id = s.faculty_id
             JOIN departments d ON d.id = s.department_id
             WHERE s.user_id = ?',
            [$u['id']]
        );
    }

    public static function lecturer(): ?array
    {
        $u = self::user();
        if (!$u || $u['role_slug'] !== 'lecturer') {
            return null;
        }
        return Database::one(
            'SELECT l.*, f.name AS faculty_name, d.name AS department_name, u.full_name, u.email, u.phone
             FROM lecturers l
             JOIN users u ON u.id = l.user_id
             JOIN faculties f ON f.id = l.faculty_id
             JOIN departments d ON d.id = l.department_id
             WHERE l.user_id = ?',
            [$u['id']]
        );
    }
}
