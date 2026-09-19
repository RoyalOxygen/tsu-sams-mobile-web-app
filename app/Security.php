<?php
declare(strict_types=1);

namespace App;

final class Security
{
    private static array $config = [];

    public static function configure(array $config): void
    {
        self::$config = $config;
    }

    public static function boot(array $config): void
    {
        self::configure($config);
        self::startSession();
        self::headers();
    }

    private static function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $name = self::$config['app']['session'] ?? 'TSUSAMSSESSID';
        $lifetime = (int)(self::$config['security']['session_lifetime'] ?? 7200);
        session_name($name);
        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
        if (!isset($_SESSION['_created'])) {
            $_SESSION['_created'] = time();
            session_regenerate_id(true);
        } elseif (time() - (int)$_SESSION['_created'] > 1800) {
            session_regenerate_id(true);
            $_SESSION['_created'] = time();
        }
    }

    private static function headers(): void
    {
        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('X-XSS-Protection: 1; mode=block');
        header("Content-Security-Policy: default-src 'self'; img-src 'self' data: blob: https:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net; font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net data:; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; connect-src 'self' https://cdn.jsdelivr.net; media-src 'self' blob:;");
    }

    public static function csrfToken(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function verifyCsrf(?string $token = null): bool
    {
        $token = $token ?? ($_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
        $sess = $_SESSION['_csrf'] ?? '';
        return is_string($token) && $sess !== '' && hash_equals($sess, $token);
    }

    public static function requireCsrf(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !self::verifyCsrf()) {
            http_response_code(419);
            echo 'Invalid security token. Please refresh and try again.';
            exit;
        }
    }

    public static function hashPassword(string $plain): string
    {
        return password_hash($plain, PASSWORD_BCRYPT, [
            'cost' => (int)(self::$config['security']['password_cost'] ?? 12),
        ]);
    }

    public static function verifyPassword(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }

    public static function encrypt(string $plain): string
    {
        $key = hash('sha256', (string)self::$config['app']['key'], true);
        $iv = random_bytes(16);
        $cipher = openssl_encrypt($plain, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $cipher);
    }

    public static function decrypt(string $payload): string
    {
        $raw = base64_decode($payload, true);
        if ($raw === false || strlen($raw) < 17) {
            return '';
        }
        $iv = substr($raw, 0, 16);
        $cipher = substr($raw, 16);
        $key = hash('sha256', (string)self::$config['app']['key'], true);
        $out = openssl_decrypt($cipher, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        return $out === false ? '' : $out;
    }

    public static function tooManyAttempts(string $identifier): bool
    {
        $window = (int)(self::$config['security']['rate_limit_window'] ?? 900);
        $max = (int)(self::$config['security']['rate_limit_attempts'] ?? 8);
        $since = date('Y-m-d H:i:s', time() - $window);
        $row = Database::one(
            'SELECT COUNT(*) AS c FROM login_attempts WHERE identifier = ? AND ip_address = ? AND success = 0 AND created_at >= ?',
            [$identifier, client_ip(), $since]
        );
        return (int)($row['c'] ?? 0) >= $max;
    }

    public static function recordAttempt(string $identifier, bool $success): void
    {
        Database::insert('login_attempts', [
            'identifier' => $identifier,
            'ip_address' => client_ip(),
            'success' => $success ? 1 : 0,
            'created_at' => now(),
        ]);
    }

    public static function validateImageUpload(array $file): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return 'No file uploaded.';
        }
        if (($file['error'] ?? 0) !== UPLOAD_ERR_OK) {
            return 'Upload failed.';
        }
        $max = (int)(self::$config['security']['max_upload_bytes'] ?? 3145728);
        if (($file['size'] ?? 0) > $max) {
            return 'File exceeds 3MB limit.';
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        $allowed = self::$config['security']['allowed_image_types'] ?? ['image/jpeg', 'image/png'];
        if (!in_array($mime, $allowed, true)) {
            return 'Only JPEG, PNG or WEBP images are allowed.';
        }
        return null;
    }

    public static function storeUpload(array $file, string $subdir, string $basename): string
    {
        $ext = strtolower(pathinfo($file['name'] ?? 'jpg', PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $ext = 'jpg';
        }
        $name = preg_replace('/[^a-zA-Z0-9_-]/', '', $basename) . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dir = rtrim((string)self::$config['paths']['uploads'], '/') . '/' . $subdir;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $dest = $dir . '/' . $name;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            throw new \RuntimeException('Unable to store uploaded file.');
        }
        return 'uploads/' . $subdir . '/' . $name;
    }
}
