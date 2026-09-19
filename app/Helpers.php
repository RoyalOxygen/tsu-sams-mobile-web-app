<?php
declare(strict_types=1);

function config(?string $key = null, mixed $default = null): mixed
{
    static $cfg;
    if ($cfg === null) {
        $cfg = require BASE_PATH . '/config.php';
    }
    if ($key === null) {
        return $cfg;
    }
    $parts = explode('.', $key);
    $val = $cfg;
    foreach ($parts as $p) {
        if (!is_array($val) || !array_key_exists($p, $val)) {
            return $default;
        }
        $val = $val[$p];
    }
    return $val;
}

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function base_url(string $path = ''): string
{
    $script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    $dir = rtrim(str_replace('\\', '/', dirname($script)), '/.');
    if ($dir === '/' || $dir === '\\') {
        $dir = '';
    }
    $path = ltrim($path, '/');
    return ($dir === '' ? '' : $dir) . '/' . $path;
}

function redirect(string $path): never
{
    header('Location: ' . base_url(ltrim($path, '/')));
    exit;
}

function json_response(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

function setting(string $key, ?string $default = null): string
{
    $cache = setting_all();
    return $cache[$key] ?? (string)$default;
}

/**
 * @return array<string,string>
 */
function setting_all(bool $reload = false): array
{
    static $cache = null;
    if ($reload) {
        $cache = null;
    }
    if ($cache === null) {
        $cache = [];
        foreach (App\Database::all('SELECT setting_key, setting_value FROM settings ORDER BY setting_key') as $row) {
            $cache[(string)$row['setting_key']] = (string)$row['setting_value'];
        }
    }
    return $cache;
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['_flash'][$key] = $message;
        return null;
    }
    $val = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return $val;
}

function old(string $key, string $default = ''): string
{
    $bag = $_SESSION['_old'] ?? [];
    return (string)($bag[$key] ?? $default);
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(App\Security::csrfToken()) . '">';
}

function money_ngn(float|int|string $amount): string
{
    return 'NGN ' . number_format((float)$amount, 2);
}

function format_time(string $t): string
{
    $ts = strtotime($t);
    return $ts ? date('g:i A', $ts) : $t;
}

function format_date(string $d): string
{
    $ts = strtotime($d);
    return $ts ? date('D, j M Y', $ts) : $d;
}

function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function user_agent(): string
{
    return substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 250);
}

function brand_logo(int $size = 40, string $class = 'brand-logo'): string
{
    return '<img src="' . e(base_url('assets/img/logo.png')) . '" alt="TSU-SAMS" width="' . $size . '" height="' . $size . '" class="' . e($class) . '">';
}

function student_full_name(array $s): string
{
    $parts = array_filter([$s['first_name'] ?? '', $s['other_name'] ?? '', $s['last_name'] ?? '']);
    return trim(implode(' ', $parts));
}

function student_initials(array $s): string
{
    $first = trim((string)($s['first_name'] ?? ''));
    $last = trim((string)($s['last_name'] ?? ''));
    $a = $first !== '' ? mb_substr($first, 0, 1) : '';
    $b = $last !== '' ? mb_substr($last, 0, 1) : '';
    $initials = strtoupper($a . $b);
    return $initials !== '' ? $initials : 'ST';
}

/**
 * Resolve a stored public upload path to a URL, or null when the file is missing.
 */
function public_file_url(?string $path): ?string
{
    $path = trim((string)$path);
    if ($path === '') {
        return null;
    }
    $file = rtrim((string)config('paths.public', ''), '/') . '/' . ltrim($path, '/');
    if (config('paths.public') && !is_file($file)) {
        return null;
    }
    return base_url($path);
}

/**
 * Resolve a stored passport path to a public URL, or null when unavailable.
 */
function student_passport_url(array $s): ?string
{
    return public_file_url($s['passport_path'] ?? null);
}

function enrollment_label(string $status): string
{
    return match ($status) {
        'preloaded' => 'Preloaded',
        'photo' => 'Photo uploaded',
        'face' => 'Face enrolled',
        'paid' => 'Fee paid',
        'registered' => 'Courses registered',
        'active' => 'Active',
        default => ucfirst($status),
    };
}

function status_badge(string $status): string
{
    $map = [
        'open' => 'badge-success',
        'active' => 'badge-success',
        'present' => 'badge-success',
        'paid' => 'badge-success',
        'success' => 'badge-success',
        'closed' => 'badge-muted',
        'scheduled' => 'badge-warn',
        'pending' => 'badge-warn',
        'late' => 'badge-warn',
        'failed' => 'badge-danger',
        'rejected' => 'badge-danger',
        'suspended' => 'badge-danger',
    ];
    $cls = $map[$status] ?? 'badge-muted';
    return '<span class="badge ' . $cls . '">' . e(strtoupper($status)) . '</span>';
}
