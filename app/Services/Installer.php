<?php
declare(strict_types=1);

namespace App\Services;

use App\Database;

final class Installer
{
    private static bool $checked = false;

    /** @var list<array{0:string,1:string,2:string}> name, table, columns */
    private const INDEXES = [
        ['idx_users_email', 'users', 'email'],
        ['idx_students_scope', 'students', 'faculty_id, department_id, level'],
        ['idx_lecturers_scope', 'lecturers', 'faculty_id, department_id'],
        ['idx_courses_lecturer', 'courses', 'lecturer_id'],
        ['idx_courses_scope', 'courses', 'faculty_id, department_id, level, semester, academic_session'],
        ['idx_reg_student', 'course_registrations', 'student_id, academic_session, semester'],
        ['idx_reg_course', 'course_registrations', 'course_id'],
        ['idx_sessions_course', 'attendance_sessions', 'course_id'],
        ['idx_sessions_lecturer', 'attendance_sessions', 'lecturer_id, status'],
        ['idx_sessions_status_date', 'attendance_sessions', 'status, session_date'],
        ['idx_records_student', 'attendance_records', 'student_id, recorded_at'],
        ['idx_records_recorded', 'attendance_records', 'recorded_at'],
        ['idx_face_student', 'face_profiles', 'student_id'],
        ['idx_payments_student', 'payments', 'student_id, status'],
        ['idx_transactions_payment', 'transactions', 'payment_id'],
        ['idx_audit_user', 'audit_logs', 'user_id, created_at'],
        ['idx_audit_action', 'audit_logs', 'action, created_at'],
    ];

    public static function ensure(): void
    {
        if (self::$checked) {
            return;
        }
        self::$checked = true;

        if (!Database::tableExists('roles')) {
            self::runSchema();
        } else {
            self::migrate();
        }
        self::ensurePaymentFlags();
        $count = Database::one('SELECT COUNT(*) AS c FROM roles');
        if ((int)($count['c'] ?? 0) === 0) {
            Seeder::run();
        }
    }

    private static function runSchema(): void
    {
        $file = BASE_PATH . '/database/schema.sqlite.sql';
        if (Database::driver() === 'mysql') {
            $file = BASE_PATH . '/database/schema.mysql.sql';
        }
        $sql = file_get_contents($file);
        if ($sql === false) {
            throw new \RuntimeException('Unable to read schema file: ' . $file);
        }
        Database::runSql($sql);
    }

    /**
     * Add indexes that are missing from an already-installed database.
     */
    public static function migrate(): void
    {
        foreach (self::INDEXES as [$name, $table, $columns]) {
            if (!Database::tableExists($table) || self::indexExists($table, $name)) {
                continue;
            }
            Database::pdo()->exec('CREATE INDEX ' . $name . ' ON ' . $table . '(' . $columns . ')');
        }
    }

    public static function indexExists(string $table, string $index): bool
    {
        try {
            if (Database::driver() === 'mysql') {
                $row = Database::one(
                    'SELECT COUNT(*) AS c FROM information_schema.statistics
                     WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?',
                    [$table, $index]
                );
                return (int)($row['c'] ?? 0) > 0;
            }
            foreach (Database::all('PRAGMA index_list(' . $table . ')') as $row) {
                if (($row['name'] ?? '') === $index) {
                    return true;
                }
            }
        } catch (\Throwable $e) {
            return false;
        }
        return false;
    }

    public static function seed(): void
    {
        Seeder::run();
    }

    private static function ensurePaymentFlags(): void
    {
        if (!Database::tableExists('settings')) {
            return;
        }
        $legacy = Database::one('SELECT setting_value FROM settings WHERE setting_key = ?', ['payments_enabled']);
        $fallback = (is_array($legacy) && ($legacy['setting_value'] ?? '0') === '1') ? '1' : '0';
        foreach (['enrollment_fee_enabled' => $fallback, 'face_update_fee_enabled' => $fallback] as $key => $value) {
            $row = Database::one('SELECT id FROM settings WHERE setting_key = ?', [$key]);
            if (!$row) {
                Database::insert('settings', ['setting_key' => $key, 'setting_value' => $value, 'updated_at' => now()]);
            }
        }
    }
}
