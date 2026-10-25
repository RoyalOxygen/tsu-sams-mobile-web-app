<?php
declare(strict_types=1);

namespace App\Services;

use App\Database;

final class CmsService
{
    /**
     * @return array{ok:bool,message?:string,student?:array,payments?:list<array>,raw?:string,source?:string}
     */
    public static function lookup(string $query): array
    {
        $query = trim($query);
        if ($query === '') {
            return ['ok' => false, 'message' => 'Enter a matric number or JAMB number.'];
        }

        $cms = self::fetchFromCms($query);
        if ($cms['ok'] && !empty($cms['student']['matric_no'])) {
            $cms['source'] = 'cms';
            return $cms;
        }

        $local = self::lookupLocal($query);
        if ($local['ok']) {
            $local['source'] = 'local';
            $local['message'] = $cms['message'] ?? $local['message'] ?? null;
            return $local;
        }

        return [
            'ok' => false,
            'message' => $cms['message'] ?? 'Matric number or JAMB number not found.',
        ];
    }

    /**
     * Insert or refresh a local student row from CMS (or local) payload.
     * CMS-specific display fields (programme, state_of_origin, cms_photo_url)
     * are preserved on the returned array so views can render them.
     *
     * @param array<string,mixed> $payload
     */
    public static function upsertStudent(array $payload): array
    {
        $matric = strtoupper(trim((string)($payload['matric_no'] ?? '')));
        if ($matric === '') {
            throw new \InvalidArgumentException('Matric number is required.');
        }

        $facultyId = self::ensureFaculty($payload['faculty_id'] ?? null, $payload['faculty_name'] ?? $payload['faculty'] ?? null);
        $departmentId = self::ensureDepartment(
            $payload['department_id'] ?? null,
            $payload['department_name'] ?? $payload['department'] ?? null,
            $facultyId
        );

        $first = trim((string)($payload['first_name'] ?? 'Student'));
        $last = trim((string)($payload['last_name'] ?? 'Unknown'));
        $other = trim((string)($payload['other_name'] ?? ''));
        $gender = self::normalizeGender($payload['gender'] ?? null);
        $level = self::normalizeLevel($payload['level'] ?? 100);
        $email = trim((string)($payload['email'] ?? ''));
        $phone = trim((string)($payload['phone'] ?? ''));

        $existing = Database::one('SELECT * FROM students WHERE matric_no = ?', [$matric]);
        $hasFaculty = trim((string)($payload['faculty_name'] ?? $payload['faculty'] ?? '')) !== '';
        $hasDept = trim((string)($payload['department_name'] ?? $payload['department'] ?? '')) !== '';

        if ($existing) {
            $data = ['updated_at' => now()];
            if ($first !== '') {
                $data['first_name'] = $first;
            }
            if ($last !== '') {
                $data['last_name'] = $last;
            }
            if ($other !== '') {
                $data['other_name'] = $other;
            }
            if ($hasFaculty) {
                $data['faculty_id'] = $facultyId;
            }
            if ($hasDept) {
                $data['department_id'] = $departmentId;
            }
            if (!empty($payload['level'])) {
                $data['level'] = $level;
            }
            if ($gender !== null) {
                $data['gender'] = $gender;
            }
            if ($email !== '') {
                $data['email'] = $email;
            }
            if ($phone !== '') {
                $data['phone'] = $phone;
            }
            if (count($data) > 1) {
                Database::update('students', $data, 'id = ?', [$existing['id']]);
            }
        } else {
            $id = Database::insert('students', [
                'matric_no' => $matric,
                'first_name' => $first !== '' ? $first : 'Student',
                'last_name' => $last !== '' ? $last : 'Unknown',
                'other_name' => $other !== '' ? $other : null,
                'gender' => $gender ?? 'Male',
                'faculty_id' => $facultyId,
                'department_id' => $departmentId,
                'level' => $level,
                'email' => $email !== '' ? $email : null,
                'phone' => $phone !== '' ? $phone : null,
                'enrollment_status' => 'preloaded',
                'is_imported' => 1,
                'created_at' => now(),
            ]);
            $existing = Database::one('SELECT * FROM students WHERE id = ?', [$id]);
        }

        $row = self::studentWithScope((int)$existing['id']);

        foreach (['programme', 'state_of_origin', 'cms_photo_url', 'faculty_code', 'department_code'] as $field) {
            if (isset($payload[$field]) && !isset($row[$field])) {
                $row[$field] = $payload[$field];
            }
        }

        return $row;
    }

    public static function studentWithScope(int $id): array
    {
        $row = Database::one(
            'SELECT s.*, f.name AS faculty_name, d.name AS department_name
             FROM students s
             JOIN faculties f ON f.id = s.faculty_id
             JOIN departments d ON d.id = s.department_id
             WHERE s.id = ?',
            [$id]
        );
        if (!$row) {
            throw new \RuntimeException('Student record is missing.');
        }
        return $row;
    }

    /**
     * @return array{ok:bool,message?:string,student?:array,payments?:list<array>,raw?:string}
     */
    private static function fetchFromCms(string $query): array
    {
        if (!function_exists('curl_init')) {
            return ['ok' => false, 'message' => 'PHP cURL is required to reach the Registry CMS.'];
        }

        $searchUrl = (string)config('cms.search_url');
        $lookupUrl = (string)config('cms.lookup_url');
        $timeout = (int)config('cms.timeout', 25);

        $cookie = tempnam(sys_get_temp_dir(), 'tsu_cms_');
        if ($cookie === false) {
            return ['ok' => false, 'message' => 'Unable to create CMS session file.'];
        }

        try {
            $warm = self::curl($searchUrl, [
                CURLOPT_COOKIEJAR => $cookie,
                CURLOPT_COOKIEFILE => $cookie,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_FOLLOWLOCATION => true,
            ]);
            if (!$warm['ok']) {
                return ['ok' => false, 'message' => 'Registry CMS is unreachable.'];
            }

            $post = self::curl($lookupUrl, [
                CURLOPT_COOKIEJAR => $cookie,
                CURLOPT_COOKIEFILE => $cookie,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query(['matricno' => $query]),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/x-www-form-urlencoded',
                    'Referer: ' . $searchUrl,
                    'X-Requested-With: XMLHttpRequest',
                ],
            ]);
            if (!$post['ok']) {
                return ['ok' => false, 'message' => 'CMS lookup failed. Try again shortly.'];
            }

            $body = trim((string)($post['body'] ?? ''));
            if ($body === '' || strcasecmp($body, 'error') === 0) {
                return ['ok' => false, 'message' => 'Matric number or JAMB number not found.'];
            }
            if ($body === '0') {
                return ['ok' => false, 'message' => 'This card has not been assigned.'];
            }

            $parsed = self::parseHtml($body, $query);
            if (!$parsed['ok']) {
                return $parsed;
            }
            $parsed['raw'] = $body;
            return $parsed;
        } finally {
            if (is_file($cookie)) {
                @unlink($cookie);
            }
        }
    }

    /**
     * @return array{ok:bool,student?:array,payments?:array,message?:string}
     */
    private static function lookupLocal(string $query): array
    {
        $q = strtoupper(trim($query));
        $student = Database::one(
            'SELECT s.*, f.name AS faculty_name, d.name AS department_name
             FROM students s
             JOIN faculties f ON f.id = s.faculty_id
             JOIN departments d ON d.id = s.department_id
             WHERE UPPER(s.matric_no) = ?',
            [$q]
        );
        if (!$student) {
            return ['ok' => false];
        }
        return [
            'ok' => true,
            'student' => $student,
            'payments' => [],
        ];
    }

    /**
     * @return array{ok:bool,message?:string,student?:array,payments?:list<array>}
     */
    public static function parseHtml(string $html, string $fallbackMatric = ''): array
    {
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $photo = '';
        if (preg_match('#<img[^>]+src=["\']([^"\']+)["\']#i', $html, $m)) {
            $photo = self::absoluteUrl(trim($m[1]));
        }

        $name = '';
        if (preg_match('#<h4[^>]*>\s*([^<]+)\s*</h4>#i', $html, $m)) {
            $name = trim($m[1]);
        }

        $h5 = [];
        if (preg_match_all('#<h5[^>]*>\s*([^<]+)\s*</h5>#i', $html, $m)) {
            $h5 = array_map('trim', $m[1]);
        }

        $programme = '';
        $origin = '';
        if (preg_match('#<p class="text-secondary[^"]*"[^>]*>\s*([^<]+)\s*</p>#i', $html, $m)) {
            $programme = trim($m[1]);
        }
        if (preg_match('#<p class="text-muted[^"]*"[^>]*>\s*([^<]+)\s*</p>#i', $html, $m)) {
            $origin = trim($m[1]);
        }

        $matric = $fallbackMatric;
        $level = 0;
        foreach ($h5 as $value) {
            if (preg_match('#TSU/[A-Z0-9/]+#i', $value) || preg_match('#^\d{8,}$#', $value)) {
                $matric = strtoupper($value);
            } elseif (preg_match('#^\d{2,3}$#', $value)) {
                $level = (int)$value;
            }
        }
        if ($level === 0 && preg_match('#>(100|200|300|400|500)<#', $html, $m)) {
            $level = (int)$m[1];
        }

        $names = self::splitName($name);
        $dept = self::departmentFromProgramme($programme);
        $payments = self::parsePayments($html);

        if ($names['first_name'] === '' && $matric === '') {
            return ['ok' => false, 'message' => 'Could not read student details from CMS.'];
        }

        return [
            'ok' => true,
            'student' => [
                'matric_no' => $matric !== '' ? $matric : strtoupper($fallbackMatric),
                'first_name' => $names['first_name'],
                'last_name' => $names['last_name'],
                'other_name' => $names['other_name'],
                'level' => $level > 0 ? $level : 100,
                'programme' => $programme,
                'state_of_origin' => $origin,
                'cms_photo_url' => $photo,
                'faculty_code' => $dept['faculty_code'],
                'faculty_name' => $dept['faculty_name'],
                'department_code' => $dept['department_code'],
                'department_name' => $dept['department_name'],
            ],
            'payments' => $payments,
        ];
    }

    /**
     * @param array<int,mixed> $opts
     * @return array{ok:bool,body?:string,status?:int,error?:string}
     */
    private static function curl(string $url, array $opts): array
    {
        $ch = curl_init($url);
        $defaults = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_USERAGENT => 'TSU-Exam-Face/1.0',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ];
        curl_setopt_array($ch, $defaults + $opts);
        $raw = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($raw === false) {
            return ['ok' => false, 'error' => $err, 'status' => $status];
        }
        if ($status >= 400) {
            return ['ok' => false, 'error' => 'HTTP ' . $status, 'status' => $status, 'body' => (string)$raw];
        }
        return ['ok' => true, 'body' => (string)$raw, 'status' => $status];
    }

    /**
     * @return array{first_name:string,other_name:string,last_name:string}
     */
    public static function splitName(string $full): array
    {
        $parts = preg_split('/\s+/', trim($full)) ?: [];
        $parts = array_values(array_filter($parts, static fn($p) => $p !== ''));
        if (!$parts) {
            return ['first_name' => '', 'other_name' => '', 'last_name' => ''];
        }
        if (count($parts) === 1) {
            return ['first_name' => $parts[0], 'other_name' => '', 'last_name' => $parts[0]];
        }
        $first = array_shift($parts);
        $last = array_pop($parts);
        return [
            'first_name' => $first,
            'other_name' => implode(' ', $parts),
            'last_name' => (string)$last,
        ];
    }

    /**
     * @return array{faculty_code:string,faculty_name:string,department_code:string,department_name:string}
     */
    public static function departmentFromProgramme(string $programme): array
    {
        $p = strtoupper($programme);
        $map = [
            'COMPUTER SCIENCE EDUCATION' => ['EDU', 'Faculty of Education', 'CSE', 'Computer Science Education'],
            'COMPUTER SCIENCE' => ['SCI', 'Faculty of Science', 'CSC', 'Computer Science'],
            'MATHEMATICS' => ['SCI', 'Faculty of Science', 'MTH', 'Mathematics'],
            'PHYSICS' => ['SCI', 'Faculty of Science', 'PHY', 'Physics'],
            'CHEMISTRY' => ['SCI', 'Faculty of Science', 'CHM', 'Chemistry'],
            'BIOLOGY' => ['SCI', 'Faculty of Science', 'BIO', 'Biology'],
            'ENGLISH' => ['ART', 'Faculty of Arts', 'ENG', 'English Language'],
            'ACCOUNTING' => ['SOC', 'Faculty of Social & Management Sciences', 'ACC', 'Accounting'],
            'ECONOMICS' => ['SOC', 'Faculty of Social & Management Sciences', 'ECO', 'Economics'],
            'AGRICULTURE' => ['AGR', 'Faculty of Agriculture', 'AGR', 'Crop Science'],
            'CROP SCIENCE' => ['AGR', 'Faculty of Agriculture', 'AGR', 'Crop Science'],
            'EDUCATION' => ['EDU', 'Faculty of Education', 'EDU', 'Educational Foundations'],
            'LAW' => ['LAW', 'Faculty of Law', 'LAW', 'Law'],
            'MEDICINE' => ['MED', 'College of Health Sciences', 'MED', 'Medicine'],
            'NURSING' => ['MED', 'College of Health Sciences', 'NUR', 'Nursing'],
        ];
        foreach ($map as $needle => $row) {
            if (str_contains($p, $needle)) {
                return [
                    'faculty_code' => $row[0],
                    'faculty_name' => $row[1],
                    'department_code' => $row[2],
                    'department_name' => $row[3],
                ];
            }
        }
        return [
            'faculty_code' => 'GEN',
            'faculty_name' => 'General Studies',
            'department_code' => 'GEN',
            'department_name' => $programme !== '' ? $programme : 'Unspecified',
        ];
    }

    /**
     * @return list<array{session:string,level:string,amount:string,semester:string,transaction_id:string,paid_at:string}>
     */
    private static function parsePayments(string $html): array
    {
        $rows = [];
        if (!preg_match_all('#<tr>\s*<th scope="row">\d+</th>\s*<td>([^<]+)</td>\s*<td>([^<]+)</td>\s*<td>([^<]+)</td>\s*<td>([^<]+)</td>\s*<td>(.*?)</td>#is', $html, $m, PREG_SET_ORDER)) {
            return $rows;
        }
        foreach ($m as $row) {
            $txn = trim(strip_tags($row[5]));
            $paidAt = '';
            if (preg_match('#(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2})#', $row[5], $t)) {
                $paidAt = $t[1];
            }
            $rows[] = [
                'session' => trim($row[1]),
                'level' => trim($row[2]),
                'amount' => trim($row[3]),
                'semester' => trim($row[4]),
                'transaction_id' => preg_replace('/\s+/', ' ', $txn) ?? $txn,
                'paid_at' => $paidAt,
            ];
        }
        return $rows;
    }

    private static function absoluteUrl(string $src): string
    {
        if ($src === '') {
            return '';
        }
        if (preg_match('#^https?://#i', $src)) {
            return $src;
        }
        $base = rtrim((string)config('cms.base_url'), '/');
        if (str_starts_with($src, '../')) {
            return $base . '/' . ltrim((preg_replace('#^\.\./#', '', $src) ?? $src), '/');
        }
        if (str_starts_with($src, '/')) {
            return $base . $src;
        }
        return $base . '/verify/' . ltrim($src, '/');
    }

    private static function ensureFaculty(mixed $id, mixed $name): int
    {
        $name = trim((string)$name);
        if ($name !== '') {
            $row = Database::one(
                'SELECT id FROM faculties WHERE LOWER(name) = LOWER(?) OR LOWER(code) = LOWER(?)',
                [$name, $name]
            );
            if ($row) {
                return (int)$row['id'];
            }
            $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name) ?: 'FAC', 0, 8));
            $dup = Database::one('SELECT id FROM faculties WHERE code = ?', [$code]);
            if ($dup) {
                $code = $code . substr((string)time(), -3);
            }
            return Database::insert('faculties', [
                'code' => $code,
                'name' => $name,
                'created_at' => now(),
            ]);
        }
        $row = Database::one('SELECT id FROM faculties ORDER BY id ASC LIMIT 1');
        if ($row) {
            return (int)$row['id'];
        }
        return Database::insert('faculties', [
            'code' => 'GEN',
            'name' => 'General Studies',
            'created_at' => now(),
        ]);
    }

    private static function ensureDepartment(mixed $id, mixed $name, int $facultyId): int
    {
        $name = trim((string)$name);
        if ($name !== '') {
            $row = Database::one(
                'SELECT id FROM departments WHERE (LOWER(name) = LOWER(?) OR LOWER(code) = LOWER(?)) AND faculty_id = ?',
                [$name, $name, $facultyId]
            );
            if ($row) {
                return (int)$row['id'];
            }
            $row = Database::one(
                'SELECT id FROM departments WHERE LOWER(name) = LOWER(?) OR LOWER(code) = LOWER(?)',
                [$name, $name]
            );
            if ($row) {
                return (int)$row['id'];
            }
            $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name) ?: 'DEP', 0, 8));
            $dup = Database::one('SELECT id FROM departments WHERE code = ?', [$code]);
            if ($dup) {
                $code = $code . substr((string)time(), -3);
            }
            return Database::insert('departments', [
                'faculty_id' => $facultyId,
                'code' => $code,
                'name' => $name,
                'created_at' => now(),
            ]);
        }
        $row = Database::one('SELECT id FROM departments WHERE faculty_id = ? ORDER BY id ASC LIMIT 1', [$facultyId]);
        if ($row) {
            return (int)$row['id'];
        }
        $row = Database::one('SELECT id FROM departments ORDER BY id ASC LIMIT 1');
        if ($row) {
            return (int)$row['id'];
        }
        return Database::insert('departments', [
            'faculty_id' => $facultyId,
            'code' => 'GEN',
            'name' => 'General',
            'created_at' => now(),
        ]);
    }

    private static function normalizeGender(mixed $value): ?string
    {
        $v = strtolower(trim((string)$value));
        if ($v === '') {
            return null;
        }
        if (in_array($v, ['f', 'female', 'woman', 'girl'], true)) {
            return 'Female';
        }
        if (in_array($v, ['m', 'male', 'man', 'boy'], true)) {
            return 'Male';
        }
        return null;
    }

    private static function normalizeLevel(mixed $value): int
    {
        if (is_string($value) && preg_match('/(\d{3})/', $value, $m)) {
            $value = $m[1];
        }
        $level = (int)$value;
        if (!in_array($level, [100, 200, 300, 400, 500, 600], true)) {
            return 100;
        }
        return $level;
    }
}
