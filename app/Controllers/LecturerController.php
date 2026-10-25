<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Audit;
use App\Database;
use App\Security;
use App\View;

final class LecturerController
{
    private function requireLecturer(): array
    {
        Auth::requireRole('lecturer');
        $l = Auth::lecturer();
        if (!$l) {
            flash('error', 'Lecturer profile not found.');
            redirect('logout');
        }
        return $l;
    }

    public function registerForm(): void
    {
        $faculties = Database::all('SELECT * FROM faculties ORDER BY name');
        $departments = Database::all('SELECT * FROM departments ORDER BY name');
        View::render('lecturer/register', [
            'title' => 'Lecturer Registration',
            'faculties' => $faculties,
            'departments' => $departments,
        ], 'layouts/guest');
    }

    public function register(): void
    {
        $staff = strtoupper(trim((string)($_POST['staff_no'] ?? '')));
        $name = trim((string)($_POST['full_name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $title = trim((string)($_POST['title'] ?? 'Dr.'));
        $rank = trim((string)($_POST['rank'] ?? 'Lecturer'));
        $facultyId = (int)($_POST['faculty_id'] ?? 0);
        $deptId = (int)($_POST['department_id'] ?? 0);
        $phone = trim((string)($_POST['phone'] ?? ''));

        if ($staff === '' || $name === '' || $email === '' || strlen($password) < 8 || !$facultyId || !$deptId) {
            flash('error', 'Complete all required fields. Password must be at least 8 characters.');
            redirect('lecturer/register');
        }
        $dup = Database::one('SELECT id FROM users WHERE username = ? OR email = ?', [$staff, $email]);
        if ($dup) {
            flash('error', 'Staff number or email already exists.');
            redirect('lecturer/register');
        }
        $uid = Database::insert('users', [
            'role_id' => 2,
            'username' => $staff,
            'email' => $email,
            'password_hash' => Security::hashPassword($password),
            'full_name' => $name,
            'phone' => $phone,
            'status' => 'pending',
            'created_at' => now(),
        ]);
        Database::insert('lecturers', [
            'user_id' => $uid,
            'staff_no' => $staff,
            'title' => $title,
            'faculty_id' => $facultyId,
            'department_id' => $deptId,
            'rank' => $rank,
            'created_at' => now(),
        ]);
        Audit::log('lecturer.register', 'users', (string)$uid);
        flash('success', 'Registration submitted. An administrator will approve your account.');
        redirect('login?role=lecturer');
    }

    public function dashboard(): void
    {
        $l = $this->requireLecturer();
        $courses = Database::all('SELECT * FROM courses WHERE lecturer_id = ? ORDER BY code', [$l['id']]);
        $open = Database::all(
            "SELECT sess.*, c.code, c.title, v.name AS venue_name,
                    (SELECT COUNT(*) FROM attendance_records ar WHERE ar.session_id = sess.id) AS present_count,
                    (SELECT COUNT(*) FROM course_registrations r WHERE r.course_id = sess.course_id) AS enrolled
             FROM attendance_sessions sess
             JOIN courses c ON c.id = sess.course_id
             JOIN venues v ON v.id = sess.venue_id
             WHERE sess.lecturer_id = ? AND sess.status = 'open'
             ORDER BY sess.start_time",
            [$l['id']]
        );
        $upcoming = Database::all(
            "SELECT sess.*, c.code, c.title, v.name AS venue_name
             FROM attendance_sessions sess
             JOIN courses c ON c.id = sess.course_id
             JOIN venues v ON v.id = sess.venue_id
             WHERE sess.lecturer_id = ? AND sess.status = 'scheduled'
             ORDER BY sess.session_date, sess.start_time LIMIT 8",
            [$l['id']]
        );
        View::render('lecturer/dashboard', [
            'title' => 'Lecturer Dashboard',
            'lecturer' => $l,
            'courses' => $courses,
            'open' => $open,
            'upcoming' => $upcoming,
            'nav' => 'dashboard',
        ], 'layouts/lecturer');
    }

    public function courses(): void
    {
        $l = $this->requireLecturer();
        $rows = Database::all(
            'SELECT c.*, f.name AS faculty_name, d.name AS department_name,
                    (SELECT COUNT(*) FROM course_registrations r WHERE r.course_id = c.id) AS enrolled
             FROM courses c
             JOIN faculties f ON f.id = c.faculty_id
             JOIN departments d ON d.id = c.department_id
             WHERE c.lecturer_id = ? ORDER BY c.code',
            [$l['id']]
        );
        View::render('lecturer/courses', [
            'title' => 'My Courses',
            'lecturer' => $l,
            'rows' => $rows,
            'nav' => 'courses',
        ], 'layouts/lecturer');
    }

    public function courseForm(?string $id = null): void
    {
        $l = $this->requireLecturer();
        $course = null;
        if ($id) {
            $course = Database::one('SELECT * FROM courses WHERE id = ? AND lecturer_id = ?', [(int)$id, $l['id']]);
            if (!$course) {
                flash('error', 'Course not found.');
                redirect('lecturer/courses');
            }
        }
        View::render('lecturer/course_form', [
            'title' => $course ? 'Edit Course' : 'Create Course',
            'lecturer' => $l,
            'course' => $course,
            'faculties' => Database::all('SELECT * FROM faculties ORDER BY name'),
            'departments' => Database::all('SELECT * FROM departments ORDER BY name'),
            'nav' => 'courses',
        ], 'layouts/lecturer');
    }

    public function courseSave(): void
    {
        $l = $this->requireLecturer();
        $id = (int)($_POST['id'] ?? 0);
        $data = [
            'code' => strtoupper(trim((string)($_POST['code'] ?? ''))),
            'title' => trim((string)($_POST['title'] ?? '')),
            'faculty_id' => (int)($_POST['faculty_id'] ?? $l['faculty_id']),
            'department_id' => (int)($_POST['department_id'] ?? $l['department_id']),
            'level' => (int)($_POST['level'] ?? 100),
            'semester' => (($_POST['semester'] ?? '') === 'Rain') ? 'Rain' : 'Harmattan',
            'academic_session' => trim((string)($_POST['academic_session'] ?? setting('academic_session', '2024/2025'))),
            'unit' => (int)($_POST['unit'] ?? 3),
            'updated_at' => now(),
        ];
        if ($data['code'] === '' || $data['title'] === '') {
            flash('error', 'Course code and title are required.');
            redirect('lecturer/courses/create');
        }
        $dupe = Database::one(
            'SELECT id FROM courses WHERE code = ? AND academic_session = ? AND semester = ?',
            [$data['code'], $data['academic_session'], $data['semester']]
        );
        if ($dupe && (int)$dupe['id'] !== $id) {
            flash('error', 'A course with that code already exists for this session and semester.');
            redirect($id ? 'lecturer/courses/' . $id . '/edit' : 'lecturer/courses/create');
        }
        if ($id) {
            $own = Database::one('SELECT id FROM courses WHERE id = ? AND lecturer_id = ?', [$id, $l['id']]);
            if (!$own) {
                flash('error', 'Course not found.');
                redirect('lecturer/courses');
            }
            Database::update('courses', $data, 'id = ?', [$id]);
            Audit::log('course.update', 'courses', (string)$id);
            flash('success', 'Course updated.');
        } else {
            $data['lecturer_id'] = $l['id'];
            $data['status'] = 'active';
            $data['created_at'] = now();
            unset($data['updated_at']);
            $nid = Database::insert('courses', $data);
            Audit::log('course.create', 'courses', (string)$nid);
            flash('success', 'Course created.');
        }
        redirect('lecturer/courses');
    }

    public function sessions(): void
    {
        $l = $this->requireLecturer();
        $rows = Database::all(
            "SELECT sess.*, c.code, c.title, v.name AS venue_name,
                    (SELECT COUNT(*) FROM attendance_records ar WHERE ar.session_id = sess.id) AS present_count
             FROM attendance_sessions sess
             JOIN courses c ON c.id = sess.course_id
             JOIN venues v ON v.id = sess.venue_id
             WHERE sess.lecturer_id = ?
             ORDER BY sess.session_date DESC, sess.start_time DESC",
            [$l['id']]
        );
        View::render('lecturer/sessions', [
            'title' => 'Attendance Sessions',
            'lecturer' => $l,
            'rows' => $rows,
            'nav' => 'sessions',
        ], 'layouts/lecturer');
    }

    public function sessionForm(): void
    {
        $l = $this->requireLecturer();
        View::render('lecturer/session_form', [
            'title' => 'Create Attendance Session',
            'lecturer' => $l,
            'courses' => Database::all('SELECT * FROM courses WHERE lecturer_id = ? AND status = ?', [$l['id'], 'active']),
            'venues' => Database::all('SELECT * FROM venues WHERE is_active = 1 ORDER BY name'),
            'nav' => 'sessions',
        ], 'layouts/lecturer');
    }

    public function sessionSave(): void
    {
        $l = $this->requireLecturer();
        $courseId = (int)($_POST['course_id'] ?? 0);
        $venueId = (int)($_POST['venue_id'] ?? 0);
        $date = (string)($_POST['session_date'] ?? '');
        $start = (string)($_POST['start_time'] ?? '');
        $end = (string)($_POST['end_time'] ?? '');
        $own = Database::one('SELECT id FROM courses WHERE id = ? AND lecturer_id = ?', [$courseId, $l['id']]);
        if (!$own || !$venueId || $date === '' || $start === '' || $end === '') {
            flash('error', 'All session fields are required.');
            redirect('lecturer/sessions/create');
        }
        $id = Database::insert('attendance_sessions', [
            'course_id' => $courseId,
            'venue_id' => $venueId,
            'lecturer_id' => $l['id'],
            'session_date' => $date,
            'start_time' => $start,
            'end_time' => $end,
            'status' => 'scheduled',
            'created_at' => now(),
        ]);
        Audit::log('session.create', 'attendance_sessions', (string)$id);
        flash('success', 'Attendance session created.');
        redirect('lecturer/sessions');
    }

    public function sessionAction(string $id): void
    {
        $l = $this->requireLecturer();
        $sess = Database::one('SELECT * FROM attendance_sessions WHERE id = ? AND lecturer_id = ?', [(int)$id, $l['id']]);
        if (!$sess) {
            flash('error', 'Session not found.');
            redirect('lecturer/sessions');
        }
        $action = $_POST['action'] ?? '';
        if ($action === 'open') {
            Database::update('attendance_sessions', ['status' => 'open', 'opened_at' => now()], 'id = ?', [$sess['id']]);
            Audit::log('session.open', 'attendance_sessions', (string)$sess['id']);
            flash('success', 'Attendance opened. Students can now check in.');
        } elseif ($action === 'close') {
            Database::update('attendance_sessions', ['status' => 'closed', 'closed_at' => now()], 'id = ?', [$sess['id']]);
            Audit::log('session.close', 'attendance_sessions', (string)$sess['id']);
            flash('success', 'Attendance closed.');
        } elseif ($action === 'reopen') {
            Database::update('attendance_sessions', ['status' => 'open', 'opened_at' => now(), 'closed_at' => null], 'id = ?', [$sess['id']]);
            Audit::log('session.reopen', 'attendance_sessions', (string)$sess['id']);
            flash('success', 'Attendance reopened.');
        }
        redirect('lecturer/sessions');
    }

    public function records(): void
    {
        $l = $this->requireLecturer();
        $sessionId = (int)($_GET['session_id'] ?? 0);
        $sessions = Database::all(
            'SELECT sess.*, c.code, c.title FROM attendance_sessions sess
             JOIN courses c ON c.id = sess.course_id
             WHERE sess.lecturer_id = ? ORDER BY sess.session_date DESC',
            [$l['id']]
        );
        $rows = [];
        $current = null;
        if ($sessionId) {
            $current = Database::one(
                'SELECT sess.*, c.code, c.title, v.name AS venue_name
                 FROM attendance_sessions sess
                 JOIN courses c ON c.id = sess.course_id
                 JOIN venues v ON v.id = sess.venue_id
                 WHERE sess.id = ? AND sess.lecturer_id = ?',
                [$sessionId, $l['id']]
            );
            if ($current) {
                $rows = Database::all(
                    'SELECT ar.*, s.matric_no, s.first_name, s.last_name
                     FROM attendance_records ar
                     JOIN students s ON s.id = ar.student_id
                     WHERE ar.session_id = ?
                     ORDER BY ar.recorded_at',
                    [$sessionId]
                );
            }
        }
        View::render('lecturer/records', [
            'title' => 'Attendance Records',
            'lecturer' => $l,
            'sessions' => $sessions,
            'current' => $current,
            'rows' => $rows,
            'nav' => 'records',
        ], 'layouts/lecturer');
    }

    public function export(): void
    {
        $l = $this->requireLecturer();
        $format = $_GET['format'] ?? 'csv';
        $sessionId = (int)($_GET['session_id'] ?? 0);
        $sess = Database::one(
            'SELECT sess.*, c.code, c.title FROM attendance_sessions sess
             JOIN courses c ON c.id = sess.course_id
             WHERE sess.id = ? AND sess.lecturer_id = ?',
            [$sessionId, $l['id']]
        );
        if (!$sess) {
            flash('error', 'Select a valid session to export.');
            redirect('lecturer/records');
        }
        $rows = Database::all(
            'SELECT s.matric_no, s.first_name, s.last_name, ar.status, ar.recorded_at, ar.distance_meters, ar.face_score
             FROM attendance_records ar JOIN students s ON s.id = ar.student_id
             WHERE ar.session_id = ? ORDER BY s.matric_no',
            [$sessionId]
        );
        $this->sendExport($format, $sess['code'] . ' ' . $sess['session_date'], $rows);
    }

    public function profile(): void
    {
        $l = $this->requireLecturer();
        View::render('lecturer/profile', [
            'title' => 'Profile',
            'lecturer' => $l,
            'nav' => 'profile',
        ], 'layouts/lecturer');
    }

    public function profileSave(): void
    {
        $l = $this->requireLecturer();
        $phone = trim((string)($_POST['phone'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        if ($email !== '') {
            $dup = Database::one('SELECT id FROM users WHERE email = ? AND id <> ?', [$email, $l['user_id']]);
            if ($dup) {
                flash('error', 'That email address is already in use.');
                redirect('lecturer/profile');
            }
        }
        Database::update('users', [
            'phone' => $phone,
            'email' => $email !== '' ? $email : null,
            'updated_at' => now(),
        ], 'id = ?', [$l['user_id']]);
        $newPass = (string)($_POST['password'] ?? '');
        if ($newPass !== '' && strlen($newPass) < 8) {
            flash('error', 'Password must be at least 8 characters.');
            redirect('lecturer/profile');
        }
        if (strlen($newPass) >= 8) {
            Database::update('users', ['password_hash' => Security::hashPassword($newPass)], 'id = ?', [$l['user_id']]);
        }
        flash('success', 'Profile updated.');
        redirect('lecturer/profile');
    }

    private function sendExport(string $format, string $title, array $rows): never
    {
        $safe = preg_replace('/[^A-Za-z0-9_-]/', '_', $title);
        if ($format === 'csv' || $format === 'excel') {
            $ext = $format === 'excel' ? 'xls' : 'csv';
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $safe . '.' . $ext . '"');
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Matric No', 'First Name', 'Last Name', 'Status', 'Recorded At', 'Distance (m)', 'Face Score']);
            foreach ($rows as $r) {
                fputcsv($out, [$r['matric_no'], $r['first_name'], $r['last_name'], $r['status'], $r['recorded_at'], $r['distance_meters'], $r['face_score']]);
            }
            fclose($out);
            exit;
        }
        header('Content-Type: text/html; charset=utf-8');
        echo '<html><head><title>' . e($title) . '</title></head><body>';
        echo '<h1>TSU-SAMS Attendance Report</h1><h2>' . e($title) . '</h2>';
        echo '<table border="1" cellpadding="6" cellspacing="0"><tr><th>Matric</th><th>Name</th><th>Status</th><th>Time</th><th>Distance</th><th>Face</th></tr>';
        foreach ($rows as $r) {
            echo '<tr><td>' . e($r['matric_no']) . '</td><td>' . e($r['first_name'] . ' ' . $r['last_name']) . '</td><td>' . e($r['status']) . '</td><td>' . e($r['recorded_at']) . '</td><td>' . e((string)$r['distance_meters']) . '</td><td>' . e((string)$r['face_score']) . '</td></tr>';
        }
        echo '</table><script>window.print()</script></body></html>';
        exit;
    }
}
