<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Audit;
use App\Database;
use App\Security;
use App\View;
use App\Services\FaceService;
use App\Services\GeoService;
use App\Services\PaymentService;

final class StudentController
{
    private function requireStudent(): array
    {
        Auth::requireRole('student');
        $s = Auth::student();
        if (!$s) {
            flash('error', 'Student profile not found.');
            redirect('logout');
        }
        return $s;
    }

    public function dashboard(): void
    {
        $s = $this->requireStudent();
        $session = setting('academic_session', '2024/2025');
        $semester = setting('semester', 'Harmattan');
        $active = Database::all(
            "SELECT sess.*, c.code, c.title, v.name AS venue_name, v.building_name, v.radius,
                    l.title AS lec_title, u.full_name AS lecturer_name
             FROM attendance_sessions sess
             JOIN courses c ON c.id = sess.course_id
             JOIN venues v ON v.id = sess.venue_id
             JOIN lecturers l ON l.id = sess.lecturer_id
             JOIN users u ON u.id = l.user_id
             JOIN course_registrations r ON r.course_id = c.id AND r.student_id = ?
             WHERE sess.status = 'open' AND r.academic_session = ? AND r.semester = ?
             ORDER BY sess.start_time ASC",
            [$s['id'], $session, $semester]
        );
        $stats = $this->statsFor($s);
        $recent = Database::all(
            "SELECT ar.*, c.code, c.title, sess.session_date, sess.start_time
             FROM attendance_records ar
             JOIN attendance_sessions sess ON sess.id = ar.session_id
             JOIN courses c ON c.id = sess.course_id
             WHERE ar.student_id = ?
             ORDER BY ar.recorded_at DESC LIMIT 5",
            [$s['id']]
        );
        View::render('student/dashboard', [
            'title' => 'Student Dashboard',
            'student' => $s,
            'active' => $active,
            'stats' => $stats,
            'recent' => $recent,
            'nav' => 'dashboard',
        ], 'layouts/student');
    }

    /* ---------------- Course registration (post-enrollment) ---------------- */

    public function courses(): void
    {
        $s = $this->requireStudent();
        $session = setting('academic_session', '2024/2025');
        $semester = setting('semester', 'Harmattan');
        $available = $this->coursesFor($s, $session, $semester);
        $registered = Database::all(
            "SELECT c.id, c.code, c.title, c.unit, r.registered_at
             FROM course_registrations r
             JOIN courses c ON c.id = r.course_id
             WHERE r.student_id = ? AND r.academic_session = ? AND r.semester = ?
             ORDER BY c.code",
            [$s['id'], $session, $semester]
        );
        $registeredIds = array_map(static fn(array $r): int => (int)$r['id'], $registered);
        View::render('student/courses', [
            'title' => 'Course Registration',
            'student' => $s,
            'available' => $available,
            'registered' => $registered,
            'registeredIds' => $registeredIds,
            'session' => $session,
            'semester' => $semester,
            'nav' => 'courses',
        ], 'layouts/student');
    }

    public function coursesSave(): void
    {
        $s = $this->requireStudent();
        $session = setting('academic_session', '2024/2025');
        $semester = setting('semester', 'Harmattan');
        $valid = $this->eligibleCourseIds($s, $this->courseSelection(), $session, $semester);

        $added = 0;
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            foreach ($valid as $cid) {
                $exists = Database::one(
                    'SELECT id FROM course_registrations WHERE student_id = ? AND course_id = ? AND academic_session = ? AND semester = ?',
                    [$s['id'], $cid, $session, $semester]
                );
                if (!$exists) {
                    Database::insert('course_registrations', [
                        'student_id' => $s['id'],
                        'course_id' => $cid,
                        'academic_session' => $session,
                        'semester' => $semester,
                        'registered_at' => now(),
                    ]);
                    $added++;
                }
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            flash('error', 'Courses could not be registered. Please try again.');
            redirect('student/courses');
        }

        if ($added > 0) {
            Audit::log('student.course_register', 'students', (string)$s['id'], ['added' => $added]);
            flash('success', $added === 1
                ? '1 course added to your registration.'
                : $added . ' courses added to your registration.');
        } else {
            flash('info', 'No new courses were selected.');
        }
        redirect('student/courses');
    }

    /**
     * Active courses published for a student's faculty, department, level and term.
     */
    private function coursesFor(array $student, string $session, string $semester): array
    {
        return Database::all(
            'SELECT c.*, f.name AS faculty_name, d.name AS department_name
             FROM courses c
             JOIN faculties f ON f.id = c.faculty_id
             JOIN departments d ON d.id = c.department_id
             WHERE c.faculty_id = ? AND c.department_id = ? AND c.level = ?
               AND c.semester = ? AND c.academic_session = ? AND c.status = ?
             ORDER BY c.code',
            [$student['faculty_id'], $student['department_id'], $student['level'], $semester, $session, 'active']
        );
    }

    /**
     * Filter a submitted list of course IDs down to those the student is eligible for.
     *
     * @param array<int,mixed> $ids
     * @return array<int,int>
     */
    private function eligibleCourseIds(array $student, array $ids, string $session, string $semester): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (!$ids) {
            return [];
        }
        $place = implode(',', array_fill(0, count($ids), '?'));
        $rows = Database::all(
            "SELECT id FROM courses
             WHERE id IN ($place) AND faculty_id = ? AND department_id = ? AND level = ?
               AND semester = ? AND academic_session = ? AND status = 'active'",
            array_merge($ids, [
                $student['faculty_id'],
                $student['department_id'],
                $student['level'],
                $semester,
                $session,
            ])
        );
        return array_map(static fn(array $r): int => (int)$r['id'], $rows);
    }

    /**
     * Normalise a submitted courses[] selection into a list of integers.
     *
     * @return array<int,int>
     */
    private function courseSelection(): array
    {
        $ids = $_POST['courses'] ?? [];
        if (is_string($ids)) {
            $ids = [$ids];
        }
        if (!is_array($ids)) {
            return [];
        }
        return array_values(array_unique(array_filter(array_map('intval', $ids))));
    }

    public function history(): void
    {
        $s = $this->requireStudent();
        $rows = Database::all(
            "SELECT ar.*, c.code, c.title, sess.session_date, sess.start_time, sess.end_time, v.name AS venue_name
             FROM attendance_records ar
             JOIN attendance_sessions sess ON sess.id = ar.session_id
             JOIN courses c ON c.id = sess.course_id
             JOIN venues v ON v.id = sess.venue_id
             WHERE ar.student_id = ?
             ORDER BY ar.recorded_at DESC",
            [$s['id']]
        );
        View::render('student/history', [
            'title' => 'Attendance History',
            'student' => $s,
            'rows' => $rows,
            'nav' => 'history',
        ], 'layouts/student');
    }

    public function statistics(): void
    {
        $s = $this->requireStudent();
        $session = setting('academic_session', '2024/2025');
        $semester = setting('semester', 'Harmattan');
        $courses = Database::all(
            "SELECT c.id, c.code, c.title,
                (SELECT COUNT(*) FROM attendance_sessions sess WHERE sess.course_id = c.id AND sess.status IN ('open','closed')) AS total_sessions,
                (SELECT COUNT(*) FROM attendance_records ar
                    JOIN attendance_sessions sess ON sess.id = ar.session_id
                    WHERE ar.student_id = ? AND sess.course_id = c.id) AS present_count
             FROM courses c
             JOIN course_registrations r ON r.course_id = c.id
             WHERE r.student_id = ? AND r.academic_session = ? AND r.semester = ?
             ORDER BY c.code",
            [$s['id'], $s['id'], $session, $semester]
        );
        View::render('student/statistics', [
            'title' => 'Attendance Statistics',
            'student' => $s,
            'courses' => $courses,
            'stats' => $this->statsFor($s),
            'threshold' => (int)setting('exam_threshold', '75'),
            'nav' => 'stats',
        ], 'layouts/student');
    }

    public function profile(): void
    {
        $s = $this->requireStudent();
        $face = Database::one('SELECT * FROM face_profiles WHERE student_id = ?', [$s['id']]);
        View::render('student/profile', [
            'title' => 'Profile',
            'student' => $s,
            'face' => $face,
            'nav' => 'profile',
        ], 'layouts/student');
    }

    public function profileSave(): void
    {
        $s = $this->requireStudent();
        $user = Auth::user();
        $current = (string)($_POST['current_password'] ?? '');
        $newPass = (string)($_POST['password'] ?? '');
        $confirm = (string)($_POST['password_confirm'] ?? '');
        if ($current === '' || $newPass === '' || $confirm === '') {
            flash('error', 'Enter your current password and a new password.');
            redirect('student/profile');
        }
        if (!Security::verifyPassword($current, (string)($user['password_hash'] ?? ''))) {
            flash('error', 'Current password is incorrect.');
            redirect('student/profile');
        }
        if (strlen($newPass) < 8) {
            flash('error', 'New password must be at least 8 characters.');
            redirect('student/profile');
        }
        if ($newPass !== $confirm) {
            flash('error', 'New password and confirmation do not match.');
            redirect('student/profile');
        }
        if (Security::verifyPassword($newPass, (string)($user['password_hash'] ?? ''))) {
            flash('error', 'Choose a password that is different from your current one.');
            redirect('student/profile');
        }
        Database::update('users', [
            'password_hash' => Security::hashPassword($newPass),
            'updated_at' => now(),
        ], 'id = ?', [$user['id']]);
        Audit::log('student.password_change', 'users', (string)$user['id']);
        flash('success', 'Password updated.');
        redirect('student/profile');
    }

    public function sessions(): void
    {
        $s = $this->requireStudent();
        $session = setting('academic_session', '2024/2025');
        $semester = setting('semester', 'Harmattan');
        $active = Database::all(
            "SELECT sess.*, c.code, c.title, v.name AS venue_name, v.building_name, v.radius, v.latitude, v.longitude
             FROM attendance_sessions sess
             JOIN courses c ON c.id = sess.course_id
             JOIN venues v ON v.id = sess.venue_id
             JOIN course_registrations r ON r.course_id = c.id AND r.student_id = ?
             WHERE sess.status = 'open' AND r.academic_session = ? AND r.semester = ?
             ORDER BY sess.start_time ASC",
            [$s['id'], $session, $semester]
        );
        View::render('student/sessions', [
            'title' => 'Take Attendance',
            'student' => $s,
            'active' => $active,
            'nav' => 'attend',
        ], 'layouts/student');
    }

    public function attendForm(string $id): void
    {
        $s = $this->requireStudent();
        $sess = $this->loadOpenSession((int)$id, (int)$s['id']);
        if (!$sess) {
            flash('error', 'Session is not available for attendance.');
            redirect('student/attendance');
        }
        $already = Database::one(
            'SELECT id FROM attendance_records WHERE session_id = ? AND student_id = ?',
            [$sess['id'], $s['id']]
        );
        View::render('student/attend', [
            'title' => 'Verify Attendance',
            'student' => $s,
            'sess' => $sess,
            'already' => $already,
            'nav' => 'attend',
        ], 'layouts/student');
    }

    public function attendSubmit(string $id): void
    {
        $s = $this->requireStudent();
        $sess = $this->loadOpenSession((int)$id, (int)$s['id']);
        if (!$sess) {
            json_response(['ok' => false, 'message' => 'Session is not active.'], 400);
        }
        $existing = Database::one(
            'SELECT id FROM attendance_records WHERE session_id = ? AND student_id = ?',
            [$sess['id'], $s['id']]
        );
        if ($existing) {
            json_response(['ok' => false, 'message' => 'Attendance already recorded for this lecture.'], 409);
        }

        $lat = (float)($_POST['latitude'] ?? 0);
        $lng = (float)($_POST['longitude'] ?? 0);
        $images = FaceService::imagesFromRequest();
        if (!$images) {
            json_response(['ok' => false, 'code' => 'face', 'message' => 'Face capture required. Recapture your face.'], 400);
        }

        $face = FaceService::matchStudent((int)$s['id'], $images[0]);
        if (!$face['ok']) {
            Audit::log('attendance.face_fail', 'attendance_sessions', (string)$sess['id'], $face);
            json_response(['ok' => false, 'code' => 'face', 'message' => $face['reason'], 'score' => $face['score']], 403);
        }

        $geo = GeoService::withinRadius($lat, $lng, $sess);
        if (!$geo['ok']) {
            Audit::log('attendance.geo_fail', 'attendance_sessions', (string)$sess['id'], $geo);
            json_response([
                'ok' => false,
                'code' => 'geo',
                'message' => 'You are outside the approved venue radius (' . $geo['radius'] . 'm). Distance: ' . $geo['distance'] . 'm.',
                'distance' => $geo['distance'],
                'radius' => $geo['radius'],
            ], 403);
        }

        $start = strtotime($sess['session_date'] . ' ' . $sess['start_time']);
        $status = (time() - $start) > 900 ? 'late' : 'present';

        Database::insert('attendance_records', [
            'session_id' => $sess['id'],
            'student_id' => $s['id'],
            'latitude' => $lat,
            'longitude' => $lng,
            'distance_meters' => $geo['distance'],
            'face_score' => $face['score'],
            'status' => $status,
            'ip_address' => client_ip(),
            'user_agent' => user_agent(),
            'recorded_at' => now(),
        ]);
        Audit::log('attendance.record', 'attendance_sessions', (string)$sess['id'], [
            'student' => $s['matric_no'],
            'status' => $status,
        ]);
        $_SESSION['last_attendance'] = [
            'course' => $sess['code'] . ': ' . $sess['title'],
            'venue' => $sess['venue_name'],
            'status' => $status,
            'distance' => $geo['distance'],
            'time' => now(),
        ];
        json_response(['ok' => true, 'redirect' => base_url('student/attendance/success')]);
    }

    public function attendSuccess(): void
    {
        $s = $this->requireStudent();
        $info = $_SESSION['last_attendance'] ?? null;
        unset($_SESSION['last_attendance']);
        if (!$info) {
            redirect('student');
        }
        Auth::logout();
        session_start();
        View::render('student/success', [
            'title' => 'Attendance Recorded',
            'info' => $info,
            'student' => $s,
        ], 'layouts/guest');
    }

    public function fail(): void
    {
        View::render('student/fail', [
            'title' => 'Verification Failed',
            'message' => $_GET['m'] ?? 'Attendance could not be verified.',
        ], 'layouts/guest');
    }

    private function loadOpenSession(int $id, int $studentId): ?array
    {
        return Database::one(
            "SELECT sess.*, c.code, c.title, v.name AS venue_name, v.building_name,
                    v.latitude, v.longitude, v.radius
             FROM attendance_sessions sess
             JOIN courses c ON c.id = sess.course_id
             JOIN venues v ON v.id = sess.venue_id
             JOIN course_registrations r ON r.course_id = c.id AND r.student_id = ?
             WHERE sess.id = ? AND sess.status = 'open'",
            [$studentId, $id]
        );
    }

    private function statsFor(array $s): array
    {
        $present = Database::one(
            'SELECT COUNT(*) AS c FROM attendance_records WHERE student_id = ?',
            [$s['id']]
        );
        $total = Database::one(
            "SELECT COUNT(*) AS c FROM attendance_sessions sess
             JOIN course_registrations r ON r.course_id = sess.course_id
             WHERE r.student_id = ? AND sess.status IN ('open','closed')",
            [$s['id']]
        );
        $p = (int)($present['c'] ?? 0);
        $t = max(1, (int)($total['c'] ?? 0));
        $pct = round(($p / $t) * 100, 1);
        return ['present' => $p, 'total' => (int)($total['c'] ?? 0), 'percent' => $pct];
    }

    /* ---------------- Enrollment ---------------- */

    public function enrollStart(): void
    {
        $_SESSION['enroll'] = ['step' => 1];
        View::render('student/enroll_matric', ['title' => 'Student Registration'], 'layouts/guest');
    }

    public function enrollMatric(): void
    {
        $matric = strtoupper(trim((string)($_POST['matric_no'] ?? '')));
        $student = Database::one(
            'SELECT s.*, f.name AS faculty_name, d.name AS department_name
             FROM students s
             JOIN faculties f ON f.id = s.faculty_id
             JOIN departments d ON d.id = s.department_id
             WHERE s.matric_no = ?',
            [$matric]
        );
        if (!$student) {
            flash('error', 'No preloaded record found for that matric number. Contact the Registry.');
            redirect('register');
        }
        if ($student['user_id']) {
            flash('error', 'This student is already enrolled. Please sign in.');
            redirect('login?role=student');
        }
        $_SESSION['enroll'] = ['step' => 2, 'student_id' => (int)$student['id']];
        View::render('student/enroll_verify', [
            'title' => 'Verify Record',
            'student' => $student,
        ], 'layouts/guest');
    }

    public function enrollConfirm(): void
    {
        $sid = (int)($_SESSION['enroll']['student_id'] ?? 0);
        if (!$sid) {
            redirect('register');
        }
        $_SESSION['enroll']['step'] = 3;
        $student = Database::one('SELECT * FROM students WHERE id = ?', [$sid]);
        View::render('student/enroll_photo', [
            'title' => 'Upload Passport',
            'student' => $student,
        ], 'layouts/guest');
    }

    public function enrollPhoto(): void
    {
        $sid = (int)($_SESSION['enroll']['student_id'] ?? 0);
        if (!$sid) {
            redirect('register');
        }
        $err = Security::validateImageUpload($_FILES['passport'] ?? []);
        if ($err) {
            flash('error', $err);
            redirect('register/photo');
        }
        $path = Security::storeUpload($_FILES['passport'], 'passports', 'stu' . $sid);
        Database::update('students', [
            'passport_path' => $path,
            'enrollment_status' => 'photo',
            'updated_at' => now(),
        ], 'id = ?', [$sid]);
        $_SESSION['enroll']['step'] = 4;
        redirect('register/face');
    }

    public function enrollPhotoForm(): void
    {
        $sid = (int)($_SESSION['enroll']['student_id'] ?? 0);
        if (!$sid) {
            redirect('register');
        }
        $student = Database::one('SELECT * FROM students WHERE id = ?', [$sid]);
        View::render('student/enroll_photo', ['title' => 'Upload Passport', 'student' => $student], 'layouts/guest');
    }

    public function enrollFaceForm(): void
    {
        $sid = (int)($_SESSION['enroll']['student_id'] ?? 0);
        if (!$sid) {
            redirect('register');
        }
        $student = Database::one('SELECT * FROM students WHERE id = ?', [$sid]);
        View::render('student/enroll_face', ['title' => 'Face Enrollment', 'student' => $student], 'layouts/guest');
    }

    public function enrollFace(): void
    {
        $sid = (int)($_SESSION['enroll']['student_id'] ?? 0);
        if (!$sid) {
            json_response(['ok' => false, 'message' => 'Session expired.'], 400);
        }
        $images = FaceService::imagesFromRequest();
        if (count($images) < 1) {
            json_response(['ok' => false, 'message' => 'Could not capture a face image. Try better lighting.'], 400);
        }
        $sample = FaceService::saveSampleFromDataUrl((string)($_POST['snapshot'] ?? ''), $sid);
        if ($sample === null) {
            $dir = rtrim((string)config('paths.uploads'), '/') . '/faces';
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $name = 'face_' . $sid . '_' . bin2hex(random_bytes(4)) . '.jpg';
            file_put_contents($dir . '/' . $name, $images[0]);
            $sample = 'uploads/faces/' . $name;
        }
        $enrolled = FaceService::enroll($sid, $images, $sample);
        if (!$enrolled['ok']) {
            json_response(['ok' => false, 'message' => $enrolled['message']], 400);
        }
        Database::update('students', ['enrollment_status' => 'face', 'updated_at' => now()], 'id = ?', [$sid]);
        $_SESSION['enroll']['step'] = 5;
        json_response(['ok' => true, 'redirect' => base_url('register/payment')]);
    }

    public function enrollPaymentForm(): void
    {
        $sid = (int)($_SESSION['enroll']['student_id'] ?? 0);
        if (!$sid) {
            redirect('register');
        }
        $fee = PaymentService::enrollmentFee();
        if (!PaymentService::enrollmentRequired()) {
            Database::update('students', ['enrollment_status' => 'paid', 'updated_at' => now()], 'id = ?', [$sid]);
            $_SESSION['enroll']['step'] = 6;
            redirect('register/courses');
        }
        $student = Database::one('SELECT * FROM students WHERE id = ?', [$sid]);
        View::render('student/enroll_payment', [
            'title' => 'Enrollment Fee',
            'student' => $student,
            'fee' => $fee,
        ], 'layouts/guest');
    }

    public function enrollPay(): void
    {
        $sid = (int)($_SESSION['enroll']['student_id'] ?? 0);
        if (!$sid) {
            redirect('register');
        }
        $pay = PaymentService::create($sid, 'enrollment', PaymentService::enrollmentFee());
        PaymentService::markPaid((int)$pay['id']);
        Database::update('students', ['enrollment_status' => 'paid', 'updated_at' => now()], 'id = ?', [$sid]);
        $_SESSION['enroll']['step'] = 6;
        flash('success', 'Payment recorded. Reference ' . $pay['reference']);
        redirect('register/courses');
    }

    public function enrollCoursesForm(): void
    {
        $sid = (int)($_SESSION['enroll']['student_id'] ?? 0);
        if (!$sid) {
            redirect('register');
        }
        $student = Database::one('SELECT * FROM students WHERE id = ?', [$sid]);
        $session = setting('academic_session', '2024/2025');
        $semester = setting('semester', 'Harmattan');
        $courses = $this->coursesFor($student, $session, $semester);
        View::render('student/enroll_courses', [
            'title' => 'Course Registration',
            'student' => $student,
            'courses' => $courses,
            'session' => $session,
            'semester' => $semester,
        ], 'layouts/guest');
    }

    public function enrollCourses(): void
    {
        $sid = (int)($_SESSION['enroll']['student_id'] ?? 0);
        if (!$sid) {
            redirect('register');
        }
        $student = Database::one('SELECT * FROM students WHERE id = ?', [$sid]);
        if (!$student) {
            flash('error', 'Student record not found.');
            redirect('register');
        }
        $session = setting('academic_session', '2024/2025');
        $semester = setting('semester', 'Harmattan');
        $validIds = $this->eligibleCourseIds($student, $this->courseSelection(), $session, $semester);

        $role = Database::one('SELECT id FROM roles WHERE slug = ?', ['student']);
        if (!$role) {
            flash('error', 'Student role is not configured. Contact the administrator.');
            redirect('register/courses');
        }
        if (Database::one('SELECT id FROM users WHERE username = ?', [$student['matric_no']])) {
            flash('error', 'An account already exists for this matric number. Please sign in.');
            redirect('login?role=student');
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            foreach ($validIds as $cid) {
                $exists = Database::one(
                    'SELECT id FROM course_registrations WHERE student_id = ? AND course_id = ? AND academic_session = ? AND semester = ?',
                    [$sid, $cid, $session, $semester]
                );
                if (!$exists) {
                    Database::insert('course_registrations', [
                        'student_id' => $sid,
                        'course_id' => $cid,
                        'academic_session' => $session,
                        'semester' => $semester,
                        'registered_at' => now(),
                    ]);
                }
            }
            $password = bin2hex(random_bytes(4));
            $userId = Database::insert('users', [
                'role_id' => (int)$role['id'],
                'username' => $student['matric_no'],
                'email' => $student['email'] ?: null,
                'password_hash' => Security::hashPassword($password),
                'full_name' => student_full_name($student),
                'phone' => $student['phone'],
                'status' => 'active',
                'created_at' => now(),
            ]);
            Database::update('students', [
                'user_id' => $userId,
                'enrollment_status' => 'active',
                'updated_at' => now(),
            ], 'id = ?', [$sid]);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            flash('error', 'Enrollment could not be completed. Please try again.');
            redirect('register/courses');
        }

        Audit::log('student.enroll', 'students', (string)$sid);
        $_SESSION['enroll_done'] = [
            'matric' => $student['matric_no'],
            'name' => student_full_name($student),
            'password' => $password,
        ];
        unset($_SESSION['enroll']);
        redirect('register/done');
    }

    public function enrollDone(): void
    {
        $info = $_SESSION['enroll_done'] ?? null;
        if (!$info) {
            redirect('login?role=student');
        }
        View::render('student/enroll_done', [
            'title' => 'Enrollment Complete',
            'info' => $info,
        ], 'layouts/guest');
    }

    public function faceLoginForm(): void
    {
        View::render('student/face_login', ['title' => 'Face Login'], 'layouts/guest');
    }

    public function faceLogin(): void
    {
        $matric = strtoupper(trim((string)($_POST['matric_no'] ?? '')));
        $images = FaceService::imagesFromRequest();
        if (!$images) {
            json_response(['ok' => false, 'message' => 'Live face capture is required.'], 400);
        }
        $ident = $matric !== '' ? $matric : 'face:' . client_ip();
        if (Security::tooManyAttempts($ident)) {
            json_response(['ok' => false, 'message' => 'Too many attempts. Try later.'], 429);
        }
        $student = null;
        if ($matric !== '') {
            $student = Database::one(
                'SELECT s.*, u.id AS uid, u.status AS ustatus FROM students s LEFT JOIN users u ON u.id = s.user_id WHERE s.matric_no = ?',
                [$matric]
            );
            if (!$student || !$student['uid'] || $student['ustatus'] !== 'active') {
                Security::recordAttempt($ident, false);
                json_response(['ok' => false, 'message' => 'Student account not found or not active. Complete enrollment first.'], 404);
            }
            $match = FaceService::matchStudent((int)$student['id'], $images[0]);
            if (!$match['ok']) {
                Security::recordAttempt($ident, false);
                json_response(['ok' => false, 'message' => $match['reason'], 'score' => $match['score'] ?? 0], 403);
            }
        } else {
            $match = FaceService::verifyImage($images[0]);
            if (!$match['ok'] || empty($match['student_id'])) {
                Security::recordAttempt($ident, false);
                json_response(['ok' => false, 'message' => $match['reason'] ?? 'Face not recognized.'], 403);
            }
            $student = Database::one(
                'SELECT s.*, u.id AS uid, u.status AS ustatus FROM students s LEFT JOIN users u ON u.id = s.user_id WHERE s.id = ?',
                [(int)$match['student_id']]
            );
            if (!$student || !$student['uid'] || $student['ustatus'] !== 'active') {
                Security::recordAttempt($ident, false);
                json_response(['ok' => false, 'message' => 'Matched face has no active student account.'], 403);
            }
        }
        Security::recordAttempt($ident, true);
        Auth::login((int)$student['uid']);
        json_response(['ok' => true, 'redirect' => base_url('student/attendance')]);
    }

    public function faceUpdateForm(): void
    {
        $s = $this->requireStudent();
        $fee = PaymentService::faceUpdateFee();
        $required = PaymentService::faceUpdateRequired();
        $paid = PaymentService::hasPaidFaceUpdate((int)$s['id']);
        if ($required && !$paid) {
            View::render('student/face_update_payment', [
                'title' => 'Update Face Biometric',
                'student' => $s,
                'fee' => $fee,
                'nav' => 'profile',
            ], 'layouts/student');
            return;
        }
        $face = Database::one('SELECT * FROM face_profiles WHERE student_id = ?', [$s['id']]);
        View::render('student/face_update', [
            'title' => 'Update Face Biometric',
            'student' => $s,
            'face' => $face,
            'nav' => 'profile',
        ], 'layouts/student');
    }

    public function faceUpdatePay(): void
    {
        $s = $this->requireStudent();
        if (!PaymentService::faceUpdateRequired()) {
            redirect('student/face');
        }
        if (PaymentService::hasPaidFaceUpdate((int)$s['id'])) {
            redirect('student/face');
        }
        $pay = PaymentService::create((int)$s['id'], 'face_update', PaymentService::faceUpdateFee());
        PaymentService::markPaid((int)$pay['id']);
        Audit::log('student.face_update_pay', 'payments', (string)$pay['id']);
        flash('success', 'Face update fee recorded. Reference ' . $pay['reference']);
        redirect('student/face');
    }

    public function faceUpdate(): void
    {
        $s = $this->requireStudent();
        if (PaymentService::faceUpdateRequired() && !PaymentService::hasPaidFaceUpdate((int)$s['id'])) {
            json_response(['ok' => false, 'message' => 'Pay the face update fee before recapturing.'], 402);
        }
        $images = FaceService::imagesFromRequest();
        if (count($images) < 1) {
            json_response(['ok' => false, 'message' => 'Could not capture a face image. Try better lighting.'], 400);
        }
        $sample = FaceService::saveSampleFromDataUrl((string)($_POST['snapshot'] ?? ''), (int)$s['id']);
        if ($sample === null) {
            $dir = rtrim((string)config('paths.uploads'), '/') . '/faces';
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $name = 'face_' . $s['id'] . '_' . bin2hex(random_bytes(4)) . '.jpg';
            file_put_contents($dir . '/' . $name, $images[0]);
            $sample = 'uploads/faces/' . $name;
        }
        $enrolled = FaceService::enroll((int)$s['id'], $images, $sample);
        if (!$enrolled['ok']) {
            json_response(['ok' => false, 'message' => $enrolled['message']], 400);
        }
        Audit::log('student.face_update', 'students', (string)$s['id']);
        flash('success', 'Face biometric updated.');
        json_response(['ok' => true, 'redirect' => base_url('student/profile')]);
    }
}
