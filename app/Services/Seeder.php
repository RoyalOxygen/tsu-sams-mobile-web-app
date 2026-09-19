<?php
declare(strict_types=1);

namespace App\Services;

use App\Database;
use App\Security;

/**
 * Idempotent sample-data seeder for TSU-SAMS.
 *
 * Safe to run multiple times: reference rows are matched by their natural
 * keys (slug, code, username, matric number) before insertion.
 */
final class Seeder
{
    private const DEMO_STUDENT_MATRIC = 'TSU/SCI/21/04882';
    private const DEMO_STUDENT_PASSWORD = 'Student@123';

    public static function run(bool $withDemoFace = true): array
    {
        $stats = [
            'roles' => 0,
            'faculties' => 0,
            'departments' => 0,
            'users' => 0,
            'lecturers' => 0,
            'students' => 0,
            'venues' => 0,
            'courses' => 0,
            'sessions' => 0,
            'registrations' => 0,
            'faces' => 0,
        ];

        foreach ([['admin', 'Administrator'], ['lecturer', 'Lecturer'], ['student', 'Student']] as [$slug, $name]) {
            $stats['roles'] += self::firstOrCreate('roles', ['slug' => $slug], [
                'slug' => $slug,
                'name' => $name,
                'created_at' => now(),
            ]);
        }
        $roles = self::roleMap();

        $faculties = [
            'SCI' => 'Faculty of Science',
            'EDU' => 'Faculty of Education',
            'ART' => 'Faculty of Arts',
            'AGR' => 'Faculty of Agriculture',
            'SOC' => 'Faculty of Social & Management Sciences',
        ];
        $facultyIds = [];
        foreach ($faculties as $code => $name) {
            $stats['faculties'] += self::firstOrCreate('faculties', ['code' => $code], [
                'code' => $code,
                'name' => $name,
                'created_at' => now(),
            ]);
            $facultyIds[$code] = self::idOf('faculties', 'code', $code);
        }

        $departments = [
            ['CSC', 'SCI', 'Computer Science'],
            ['MTH', 'SCI', 'Mathematics'],
            ['PHY', 'SCI', 'Physics'],
            ['EDU', 'EDU', 'Educational Foundations'],
            ['ENG', 'ART', 'English Language'],
            ['AGR', 'AGR', 'Crop Science'],
            ['ACC', 'SOC', 'Accounting'],
        ];
        $departmentIds = [];
        foreach ($departments as [$code, $fac, $name]) {
            $stats['departments'] += self::firstOrCreate('departments', ['code' => $code], [
                'faculty_id' => $facultyIds[$fac],
                'code' => $code,
                'name' => $name,
                'created_at' => now(),
            ]);
            $departmentIds[$code] = self::idOf('departments', 'code', $code);
        }

        $stats['users'] += self::firstOrCreate('users', ['username' => 'admin'], [
            'role_id' => $roles['admin'],
            'username' => 'admin',
            'email' => 'ict@tsuniversity.edu.ng',
            'password_hash' => Security::hashPassword('Admin@TSU2025'),
            'full_name' => 'Prof. Tunde Adewale',
            'phone' => '08030000001',
            'status' => 'active',
            'created_at' => now(),
        ]);
        $adminId = self::idOf('users', 'username', 'admin');

        $lecturers = [
            [
                'username' => 'TSU/STF/18/0429',
                'email' => 'aliyu.bello@tsuniversity.edu.ng',
                'full_name' => 'Dr. Aliyu Bello',
                'phone' => '08031234567',
                'title' => 'Dr.',
                'rank' => 'Senior Lecturer',
                'faculty' => 'SCI',
                'department' => 'CSC',
                'status' => 'active',
                'approved' => true,
            ],
            [
                'username' => 'TSU/STF/19/1102',
                'email' => 'aisha.musa@tsuniversity.edu.ng',
                'full_name' => 'Dr. Aisha Musa',
                'phone' => '08039876543',
                'title' => 'Dr.',
                'rank' => 'Lecturer I',
                'faculty' => 'SCI',
                'department' => 'MTH',
                'status' => 'pending',
                'approved' => false,
            ],
        ];
        $lecturerIds = [];
        foreach ($lecturers as $lec) {
            $stats['users'] += self::firstOrCreate('users', ['username' => $lec['username']], [
                'role_id' => $roles['lecturer'],
                'username' => $lec['username'],
                'email' => $lec['email'],
                'password_hash' => Security::hashPassword('Lecturer@123'),
                'full_name' => $lec['full_name'],
                'phone' => $lec['phone'],
                'status' => $lec['status'],
                'created_at' => now(),
            ]);
            $userId = self::idOf('users', 'username', $lec['username']);
            $stats['lecturers'] += self::firstOrCreate('lecturers', ['staff_no' => $lec['username']], [
                'user_id' => $userId,
                'staff_no' => $lec['username'],
                'title' => $lec['title'],
                'faculty_id' => $facultyIds[$lec['faculty']],
                'department_id' => $departmentIds[$lec['department']],
                'rank' => $lec['rank'],
                'approved_at' => $lec['approved'] ? now() : null,
                'created_at' => now(),
            ]);
            $lecturerIds[$lec['username']] = self::idOf('lecturers', 'staff_no', $lec['username']);
        }
        $leadLecturer = $lecturerIds['TSU/STF/18/0429'];

        $students = [
            ['TSU/SCI/21/04882', 'Ibrahim', 'Danladi', 'Male', 'SCI', 'CSC', 300, '08041112233'],
            ['TSU/SCI/22/03110', 'Fatima', 'Abdullahi', 'Female', 'SCI', 'CSC', 200, '08042223344'],
            ['TSU/SCI/20/01776', 'Chinedu', 'Okafor', 'Male', 'SCI', 'MTH', 400, '08043334455'],
            ['TSU/SCI/23/05501', 'Amina', 'Yusuf', 'Female', 'SCI', 'PHY', 100, '08044445566'],
            ['TSU/EDU/21/01220', 'Blessing', 'Tanimu', 'Female', 'EDU', 'EDU', 300, '08045556677'],
            ['TSU/ART/22/00881', 'Samuel', 'Nyame', 'Male', 'ART', 'ENG', 200, '08046667788'],
            ['TSU/AGR/21/00994', 'Hauwa', 'Suleiman', 'Female', 'AGR', 'AGR', 300, '08047778899'],
            ['TSU/SOC/20/02145', 'Peter', 'Agbu', 'Male', 'SOC', 'ACC', 400, '08048889900'],
            ['TSU/SCI/21/04910', 'Zainab', 'Mohammed', 'Female', 'SCI', 'CSC', 300, '08049990011'],
            ['TSU/SCI/22/03333', 'Emmanuel', 'Kefas', 'Male', 'SCI', 'CSC', 200, '08040001122'],
            ['TSU/SCI/23/06120', 'Grace', 'Adi', 'Female', 'SCI', 'MTH', 100, '08041112200'],
            ['TSU/EDU/22/01440', 'John', 'Tari', 'Male', 'EDU', 'EDU', 200, '08042220011'],
        ];
        $studentIds = [];
        foreach ($students as [$matric, $first, $last, $gender, $fac, $dept, $level, $phone]) {
            $stats['students'] += self::firstOrCreate('students', ['matric_no' => $matric], [
                'matric_no' => $matric,
                'first_name' => $first,
                'last_name' => $last,
                'gender' => $gender,
                'faculty_id' => $facultyIds[$fac],
                'department_id' => $departmentIds[$dept],
                'level' => $level,
                'phone' => $phone,
                'email' => strtolower($first . '.' . $last) . '@student.tsuniversity.edu.ng',
                'enrollment_status' => 'preloaded',
                'is_imported' => 1,
                'created_at' => now(),
            ]);
            $studentIds[$matric] = self::idOf('students', 'matric_no', $matric);
        }

        $venues = [
            ['Lecture Theatre A', 'Faculty of Science Complex', 8.8932000, 11.3596000, 100, 250],
            ['ICT Lab 2', 'ICT Directorate Block', 8.8941000, 11.3602000, 80, 80],
            ['Lecture Hall B', 'Faculty of Education', 8.8924000, 11.3588000, 120, 180],
        ];
        $venueIds = [];
        foreach ($venues as [$name, $building, $lat, $lng, $radius, $capacity]) {
            $stats['venues'] += self::firstOrCreate('venues', ['name' => $name], [
                'name' => $name,
                'building_name' => $building,
                'latitude' => $lat,
                'longitude' => $lng,
                'radius' => $radius,
                'capacity' => $capacity,
                'is_active' => 1,
                'captured_by' => $adminId,
                'created_at' => now(),
            ]);
            $venueIds[$name] = self::idOf('venues', 'name', $name);
        }

        $session = '2024/2025';
        $semester = 'Harmattan';
        $courses = [
            ['CSC 101', 'Introduction to Computing', 'SCI', 'CSC', 100, 2],
            ['CSC 205', 'Object Oriented Programming', 'SCI', 'CSC', 200, 3],
            ['CSC 301', 'Data Communications & Networks', 'SCI', 'CSC', 300, 3],
            ['CSC 311', 'Operating Systems', 'SCI', 'CSC', 300, 3],
            ['MTH 201', 'Mathematical Methods I', 'SCI', 'MTH', 200, 3],
        ];
        $courseIds = [];
        foreach ($courses as [$code, $title, $fac, $dept, $level, $unit]) {
            $stats['courses'] += self::firstOrCreate(
                'courses',
                ['code' => $code, 'academic_session' => $session, 'semester' => $semester],
                [
                    'lecturer_id' => $leadLecturer,
                    'code' => $code,
                    'title' => $title,
                    'faculty_id' => $facultyIds[$fac],
                    'department_id' => $departmentIds[$dept],
                    'level' => $level,
                    'semester' => $semester,
                    'academic_session' => $session,
                    'unit' => $unit,
                    'status' => 'active',
                    'created_at' => now(),
                ]
            );
            $courseIds[$code] = self::idOf('courses', 'code', $code);
        }

        $openSession = Database::one(
            "SELECT id FROM attendance_sessions WHERE course_id = ? AND session_date = ?",
            [$courseIds['CSC 301'], date('Y-m-d')]
        );
        if (!$openSession) {
            Database::insert('attendance_sessions', [
                'course_id' => $courseIds['CSC 301'],
                'venue_id' => $venueIds['Lecture Theatre A'],
                'lecturer_id' => $leadLecturer,
                'session_date' => date('Y-m-d'),
                'start_time' => date('H:i:s', time() - 900),
                'end_time' => date('H:i:s', time() + 2700),
                'status' => 'open',
                'opened_at' => now(),
                'created_at' => now(),
            ]);
            $stats['sessions']++;
        }
        $scheduled = Database::one(
            "SELECT id FROM attendance_sessions WHERE course_id = ? AND status = 'scheduled'",
            [$courseIds['CSC 205']]
        );
        if (!$scheduled) {
            Database::insert('attendance_sessions', [
                'course_id' => $courseIds['CSC 205'],
                'venue_id' => $venueIds['ICT Lab 2'],
                'lecturer_id' => $leadLecturer,
                'session_date' => date('Y-m-d', strtotime('+1 day')),
                'start_time' => '14:00:00',
                'end_time' => '16:00:00',
                'status' => 'scheduled',
                'created_at' => now(),
            ]);
            $stats['sessions']++;
        }

        $settings = [
            'payments_enabled' => '0',
            'enrollment_fee_enabled' => '0',
            'face_update_fee_enabled' => '0',
            'enrollment_fee' => '2500',
            'face_update_fee' => '1500',
            'default_radius' => '100',
            'academic_session' => $session,
            'semester' => $semester,
            'exam_threshold' => '75',
            'institution' => 'Taraba State University, Jalingo',
        ];
        foreach ($settings as $k => $v) {
            $row = Database::one('SELECT id FROM settings WHERE setting_key = ?', [$k]);
            if (!$row) {
                Database::insert('settings', ['setting_key' => $k, 'setting_value' => $v]);
            }
        }

        $stats = array_merge($stats, self::seedDemoStudent($studentIds, $courseIds, $settings, $withDemoFace));

        return $stats;
    }

    /**
     * Create a ready-to-use demo student (active login + registrations) so the
     * student portal and attendance flow can be exercised immediately.
     */
    private static function seedDemoStudent(array $studentIds, array $courseIds, array $settings, bool $withDemoFace): array
    {
        $stats = ['users' => 0, 'registrations' => 0, 'faces' => 0, 'students' => 0];
        $matric = self::DEMO_STUDENT_MATRIC;
        if (!isset($studentIds[$matric])) {
            return $stats;
        }
        $sid = $studentIds[$matric];
        $student = Database::one('SELECT * FROM students WHERE id = ?', [$sid]);
        $role = Database::one('SELECT id FROM roles WHERE slug = ?', ['student']);
        if (!$student || !$role) {
            return $stats;
        }

        if (!$student['user_id']) {
            $stats['users'] += self::firstOrCreate('users', ['username' => $matric], [
                'role_id' => (int)$role['id'],
                'username' => $matric,
                'email' => $student['email'] ?: strtolower($matric) . '@student.tsuniversity.edu.ng',
                'password_hash' => Security::hashPassword(self::DEMO_STUDENT_PASSWORD),
                'full_name' => trim($student['first_name'] . ' ' . $student['last_name']),
                'phone' => $student['phone'],
                'status' => 'active',
                'created_at' => now(),
            ]);
            $userId = self::idOf('users', 'username', $matric);
            Database::update('students', [
                'user_id' => $userId,
                'enrollment_status' => 'active',
                'updated_at' => now(),
            ], 'id = ?', [$sid]);
            $stats['students']++;
        }

        foreach (['CSC 301', 'CSC 311'] as $code) {
            if (!isset($courseIds[$code])) {
                continue;
            }
            $exists = Database::one(
                'SELECT id FROM course_registrations WHERE student_id = ? AND course_id = ? AND academic_session = ? AND semester = ?',
                [$sid, $courseIds[$code], $settings['academic_session'], $settings['semester']]
            );
            if (!$exists) {
                Database::insert('course_registrations', [
                    'student_id' => $sid,
                    'course_id' => $courseIds[$code],
                    'academic_session' => $settings['academic_session'],
                    'semester' => $settings['semester'],
                    'registered_at' => now(),
                ]);
                $stats['registrations']++;
            }
        }

        if ($withDemoFace && !Database::one('SELECT id FROM face_profiles WHERE student_id = ?', [$sid])) {
            FaceService::storeDescriptor($sid, array_fill(0, 512, 0.1), null);
            $stats['faces']++;
        }

        return $stats;
    }

    /**
     * @return array<string,int>
     */
    private static function roleMap(): array
    {
        $map = [];
        foreach (Database::all('SELECT id, slug FROM roles') as $row) {
            $map[(string)$row['slug']] = (int)$row['id'];
        }
        return $map;
    }

    private static function idOf(string $table, string $column, string $value): int
    {
        $row = Database::one('SELECT id FROM ' . $table . ' WHERE ' . $column . ' = ?', [$value]);
        return (int)($row['id'] ?? 0);
    }

    /**
     * Insert a row unless a row matching $match already exists.
     *
     * @return int 1 when a row was inserted, 0 when it already existed
     */
    private static function firstOrCreate(string $table, array $match, array $values): int
    {
        $where = [];
        $params = [];
        foreach ($match as $col => $val) {
            $where[] = $col . ' = ?';
            $params[] = $val;
        }
        $existing = Database::one(
            'SELECT id FROM ' . $table . ' WHERE ' . implode(' AND ', $where) . ' LIMIT 1',
            $params
        );
        if ($existing) {
            return 0;
        }
        Database::insert($table, $values);
        return 1;
    }
}
