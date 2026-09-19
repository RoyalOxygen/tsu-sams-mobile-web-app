<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Audit;
use App\Database;
use App\Security;
use App\View;
use App\Services\FaceService;

final class AdminController
{
    private function requireAdmin(): array
    {
        return Auth::requireRole('admin');
    }

    public function dashboard(): void
    {
        $this->requireAdmin();
        $counts = [
            'students' => (int)(Database::one('SELECT COUNT(*) AS c FROM students')['c'] ?? 0),
            'lecturers' => (int)(Database::one('SELECT COUNT(*) AS c FROM lecturers')['c'] ?? 0),
            'courses' => (int)(Database::one('SELECT COUNT(*) AS c FROM courses')['c'] ?? 0),
            'venues' => (int)(Database::one('SELECT COUNT(*) AS c FROM venues')['c'] ?? 0),
            'enrolled_faces' => (int)(Database::one('SELECT COUNT(*) AS c FROM face_profiles')['c'] ?? 0),
            'pending_lecturers' => (int)(Database::one("SELECT COUNT(*) AS c FROM users WHERE role_id = 2 AND status = 'pending'")['c'] ?? 0),
        ];
        $today = date('Y-m-d');
        $daily = (int)(Database::one('SELECT COUNT(*) AS c FROM attendance_records WHERE date(recorded_at) = ?', [$today])['c'] ?? 0);
        $month = date('Y-m');
        $monthly = (int)(Database::one("SELECT COUNT(*) AS c FROM attendance_records WHERE strftime('%Y-%m', recorded_at) = ?", [$month])['c'] ?? 0);
        if (Database::driver() === 'mysql') {
            $monthly = (int)(Database::one("SELECT COUNT(*) AS c FROM attendance_records WHERE DATE_FORMAT(recorded_at,'%Y-%m') = ?", [$month])['c'] ?? 0);
        }
        $revenue = Database::one("SELECT COALESCE(SUM(amount),0) AS t FROM payments WHERE status = 'paid'");
        $recentPay = Database::all(
            "SELECT p.*, s.matric_no, s.first_name, s.last_name FROM payments p
             JOIN students s ON s.id = p.student_id ORDER BY p.created_at DESC LIMIT 6"
        );
        $dailyTrend = $this->lastDays(7);
        View::render('admin/dashboard', [
            'title' => 'Administrator Dashboard',
            'counts' => $counts,
            'daily' => $daily,
            'monthly' => $monthly,
            'revenue' => (float)($revenue['t'] ?? 0),
            'recentPay' => $recentPay,
            'dailyTrend' => $dailyTrend,
            'user' => Auth::user(),
            'nav' => 'dashboard',
        ], 'layouts/admin');
    }

    public function students(): void
    {
        $this->requireAdmin();
        $q = trim((string)($_GET['q'] ?? ''));
        $sql = 'SELECT s.*, f.name AS faculty_name, d.name AS department_name,
                       (SELECT COUNT(*) FROM face_profiles fp WHERE fp.student_id = s.id) AS has_face
                FROM students s
                JOIN faculties f ON f.id = s.faculty_id
                JOIN departments d ON d.id = s.department_id';
        $params = [];
        if ($q !== '') {
            $sql .= ' WHERE s.matric_no LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ?';
            $like = '%' . $q . '%';
            $params = [$like, $like, $like];
        }
        $sql .= ' ORDER BY s.matric_no LIMIT 200';
        View::render('admin/students', [
            'title' => 'Student Management',
            'rows' => Database::all($sql, $params),
            'q' => $q,
            'nav' => 'students',
        ], 'layouts/admin');
    }

    public function studentView(string $id): void
    {
        $this->requireAdmin();
        $student = Database::one(
            'SELECT s.*, f.name AS faculty_name, d.name AS department_name, u.username, u.status AS user_status
             FROM students s
             JOIN faculties f ON f.id = s.faculty_id
             JOIN departments d ON d.id = s.department_id
             LEFT JOIN users u ON u.id = s.user_id
             WHERE s.id = ?',
            [(int)$id]
        );
        if (!$student) {
            flash('error', 'Student not found.');
            redirect('admin/students');
        }
        $face = Database::one('SELECT * FROM face_profiles WHERE student_id = ?', [(int)$id]);
        View::render('admin/student_view', [
            'title' => 'Student Record',
            'student' => $student,
            'face' => $face,
            'nav' => 'students',
        ], 'layouts/admin');
    }

    public function studentForm(?string $id = null): void
    {
        $this->requireAdmin();
        $student = $id ? Database::one('SELECT * FROM students WHERE id = ?', [(int)$id]) : null;
        if ($id && !$student) {
            flash('error', 'Student not found.');
            redirect('admin/students');
        }
        View::render('admin/student_form', [
            'title' => $student ? 'Edit Student' : 'Add Student',
            'student' => $student,
            'faculties' => Database::all('SELECT * FROM faculties ORDER BY name'),
            'departments' => Database::all('SELECT * FROM departments ORDER BY name'),
            'nav' => 'students',
        ], 'layouts/admin');
    }

    public function studentSave(): void
    {
        $this->requireAdmin();
        $id = (int)($_POST['id'] ?? 0);
        $data = [
            'matric_no' => strtoupper(trim((string)($_POST['matric_no'] ?? ''))),
            'first_name' => trim((string)($_POST['first_name'] ?? '')),
            'last_name' => trim((string)($_POST['last_name'] ?? '')),
            'other_name' => trim((string)($_POST['other_name'] ?? '')) ?: null,
            'gender' => ($_POST['gender'] ?? 'Male') === 'Female' ? 'Female' : 'Male',
            'faculty_id' => (int)($_POST['faculty_id'] ?? 0),
            'department_id' => (int)($_POST['department_id'] ?? 0),
            'level' => (int)($_POST['level'] ?? 100),
            'phone' => trim((string)($_POST['phone'] ?? '')) ?: null,
            'email' => trim((string)($_POST['email'] ?? '')) ?: null,
            'updated_at' => now(),
        ];
        if ($data['matric_no'] === '' || $data['first_name'] === '' || $data['last_name'] === '') {
            flash('error', 'Matric number and names are required.');
            redirect('admin/students');
        }
        if (!$data['faculty_id'] || !$data['department_id']) {
            flash('error', 'Select a valid faculty and department.');
            redirect('admin/students');
        }
        $dept = Database::one('SELECT id FROM departments WHERE id = ? AND faculty_id = ?', [$data['department_id'], $data['faculty_id']]);
        if (!$dept) {
            flash('error', 'The selected department does not belong to that faculty.');
            redirect('admin/students');
        }
        $dupSql = 'SELECT id FROM students WHERE matric_no = ?';
        $dupParams = [$data['matric_no']];
        if ($id) {
            $dupSql .= ' AND id <> ?';
            $dupParams[] = $id;
        }
        if (Database::one($dupSql, $dupParams)) {
            flash('error', 'A student with that matric number already exists.');
            redirect('admin/students');
        }

        $file = $_FILES['passport'] ?? [];
        $uploaded = ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
        if ($uploaded) {
            $err = Security::validateImageUpload($file);
            if ($err) {
                flash('error', $err);
                redirect($id ? 'admin/students/' . $id . '/edit' : 'admin/students/create');
            }
        }

        if ($id) {
            if ($uploaded) {
                $data['passport_path'] = Security::storeUpload($file, 'passports', 'stu' . $id);
            }
            $data['updated_at'] = now();
            Database::update('students', $data, 'id = ?', [$id]);
            $newPass = (string)($_POST['password'] ?? '');
            if ($newPass !== '') {
                $this->setStudentPassword($id, $newPass);
            }
            Audit::log('student.update', 'students', (string)$id);
            flash('success', $newPass !== '' ? 'Student updated and password reset.' : 'Student updated.');
            redirect('admin/students/' . $id);
        }

        unset($data['updated_at']);
        $data['enrollment_status'] = 'preloaded';
        $data['is_imported'] = 0;
        $data['created_at'] = now();
        $nid = Database::insert('students', $data);
        if ($uploaded) {
            Database::update('students', [
                'passport_path' => Security::storeUpload($file, 'passports', 'stu' . $nid),
            ], 'id = ?', [$nid]);
        }
        Audit::log('student.create', 'students', (string)$nid);
        flash('success', 'Student added to registry.');
        redirect('admin/students/' . $nid);
    }

    public function importForm(): void
    {
        $this->requireAdmin();
        View::render('admin/import', ['title' => 'Import Students', 'nav' => 'students'], 'layouts/admin');
    }

    public function import(): void
    {
        $this->requireAdmin();
        $file = $_FILES['csv'] ?? null;
        if (!$file || ($file['error'] ?? 1) !== UPLOAD_ERR_OK) {
            flash('error', 'Upload a CSV file.');
            redirect('admin/students/import');
        }
        $fh = fopen($file['tmp_name'], 'r');
        if (!$fh) {
            flash('error', 'Unable to read file.');
            redirect('admin/students/import');
        }
        $header = fgetcsv($fh);
        $imported = 0;
        $skipped = 0;
        while (($row = fgetcsv($fh)) !== false) {
            $map = [];
            foreach ($header as $i => $col) {
                $map[strtolower(trim((string)$col))] = trim((string)($row[$i] ?? ''));
            }
            $matric = strtoupper($map['matric_no'] ?? $map['matric'] ?? '');
            if ($matric === '') {
                $skipped++;
                continue;
            }
            if (Database::one('SELECT id FROM students WHERE matric_no = ?', [$matric])) {
                $skipped++;
                continue;
            }
            $facCode = strtoupper($map['faculty_code'] ?? $map['faculty'] ?? 'SCI');
            $deptCode = strtoupper($map['department_code'] ?? $map['department'] ?? 'CSC');
            $fac = Database::one('SELECT id FROM faculties WHERE code = ?', [$facCode]);
            $dept = Database::one('SELECT id FROM departments WHERE code = ?', [$deptCode]);
            if (!$fac || !$dept) {
                $skipped++;
                continue;
            }
            Database::insert('students', [
                'matric_no' => $matric,
                'first_name' => $map['first_name'] ?? $map['firstname'] ?? 'Student',
                'last_name' => $map['last_name'] ?? $map['lastname'] ?? 'Unknown',
                'gender' => ($map['gender'] ?? 'Male') === 'Female' ? 'Female' : 'Male',
                'faculty_id' => $fac['id'],
                'department_id' => $dept['id'],
                'level' => (int)($map['level'] ?? 100),
                'phone' => $map['phone'] ?? null,
                'email' => $map['email'] ?? null,
                'enrollment_status' => 'preloaded',
                'is_imported' => 1,
                'created_at' => now(),
            ]);
            $imported++;
        }
        fclose($fh);
        Audit::log('student.import', 'students', null, ['imported' => $imported, 'skipped' => $skipped]);
        flash('success', "Imported {$imported} students. Skipped {$skipped}.");
        redirect('admin/students');
    }

    public function faces(): void
    {
        $this->requireAdmin();
        $rows = Database::all(
            'SELECT fp.*, s.matric_no, s.first_name, s.last_name, s.passport_path
             FROM face_profiles fp JOIN students s ON s.id = fp.student_id
             ORDER BY fp.enrolled_at DESC'
        );
        View::render('admin/faces', ['title' => 'Facial Records', 'rows' => $rows, 'nav' => 'faces'], 'layouts/admin');
    }

    public function faceDelete(): void
    {
        $this->requireAdmin();
        $id = (int)($_POST['id'] ?? 0);
        $fp = Database::one('SELECT * FROM face_profiles WHERE id = ?', [$id]);
        if ($fp) {
            FaceService::deleteEnrollment((int)$fp['student_id']);
            Database::query('DELETE FROM face_profiles WHERE id = ?', [$id]);
            Database::update('students', ['enrollment_status' => 'photo', 'updated_at' => now()], 'id = ?', [$fp['student_id']]);
            Audit::log('face.delete', 'face_profiles', (string)$id);
            flash('success', 'Facial record removed. Student must re-enrol face.');
        }
        redirect('admin/faces');
    }

    public function lecturers(): void
    {
        $this->requireAdmin();
        $rows = Database::all(
            'SELECT l.*, u.full_name, u.email, u.status, u.phone, f.name AS faculty_name, d.name AS department_name
             FROM lecturers l
             JOIN users u ON u.id = l.user_id
             JOIN faculties f ON f.id = l.faculty_id
             JOIN departments d ON d.id = l.department_id
             ORDER BY u.created_at DESC'
        );
        View::render('admin/lecturers', ['title' => 'Lecturer Management', 'rows' => $rows, 'nav' => 'lecturers'], 'layouts/admin');
    }

    public function lecturerAction(): void
    {
        $this->requireAdmin();
        $id = (int)($_POST['user_id'] ?? 0);
        $action = $_POST['action'] ?? '';
        $user = Database::one('SELECT * FROM users WHERE id = ? AND role_id = 2', [$id]);
        if (!$user) {
            flash('error', 'Lecturer not found.');
            redirect('admin/lecturers');
        }
        if ($action === 'approve') {
            Database::update('users', ['status' => 'active', 'updated_at' => now()], 'id = ?', [$id]);
            Database::update('lecturers', ['approved_at' => now()], 'user_id = ?', [$id]);
            Audit::log('lecturer.approve', 'users', (string)$id);
            flash('success', 'Lecturer approved.');
        } elseif ($action === 'reject') {
            Database::update('users', ['status' => 'rejected', 'updated_at' => now()], 'id = ?', [$id]);
            Audit::log('lecturer.reject', 'users', (string)$id);
            flash('success', 'Lecturer rejected.');
        } elseif ($action === 'suspend') {
            Database::update('users', ['status' => 'suspended', 'updated_at' => now()], 'id = ?', [$id]);
            Audit::log('lecturer.suspend', 'users', (string)$id);
            flash('success', 'Lecturer suspended.');
        } elseif ($action === 'activate') {
            Database::update('users', ['status' => 'active', 'updated_at' => now()], 'id = ?', [$id]);
            flash('success', 'Lecturer reactivated.');
        }
        redirect('admin/lecturers');
    }

    public function lecturerForm(string $id): void
    {
        $this->requireAdmin();
        $lecturer = Database::one(
            'SELECT l.*, u.full_name, u.email, u.phone, u.status
             FROM lecturers l JOIN users u ON u.id = l.user_id WHERE l.id = ?',
            [(int)$id]
        );
        if (!$lecturer) {
            flash('error', 'Lecturer not found.');
            redirect('admin/lecturers');
        }
        View::render('admin/lecturer_form', [
            'title' => 'Edit Lecturer',
            'lecturer' => $lecturer,
            'faculties' => Database::all('SELECT * FROM faculties ORDER BY name'),
            'departments' => Database::all('SELECT * FROM departments ORDER BY name'),
            'nav' => 'lecturers',
        ], 'layouts/admin');
    }

    public function lecturerSave(): void
    {
        $this->requireAdmin();
        $id = (int)($_POST['id'] ?? 0);
        $lecturer = Database::one('SELECT * FROM lecturers WHERE id = ?', [$id]);
        if (!$lecturer) {
            flash('error', 'Lecturer not found.');
            redirect('admin/lecturers');
        }
        $fullName = trim((string)($_POST['full_name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $title = trim((string)($_POST['title'] ?? ''));
        $rank = trim((string)($_POST['rank'] ?? ''));
        $facultyId = (int)($_POST['faculty_id'] ?? 0);
        $departmentId = (int)($_POST['department_id'] ?? 0);
        if ($fullName === '' || !$facultyId || !$departmentId) {
            flash('error', 'Name, faculty and department are required.');
            redirect('admin/lecturers/' . $id . '/edit');
        }
        $dept = Database::one('SELECT id FROM departments WHERE id = ? AND faculty_id = ?', [$departmentId, $facultyId]);
        if (!$dept) {
            flash('error', 'The selected department does not belong to that faculty.');
            redirect('admin/lecturers/' . $id . '/edit');
        }
        if ($email !== '') {
            $dup = Database::one('SELECT id FROM users WHERE email = ? AND id <> ?', [$email, $lecturer['user_id']]);
            if ($dup) {
                flash('error', 'That email address is already in use.');
                redirect('admin/lecturers/' . $id . '/edit');
            }
        }
        Database::update('users', [
            'full_name' => $fullName,
            'email' => $email !== '' ? $email : null,
            'phone' => $phone !== '' ? $phone : null,
            'updated_at' => now(),
        ], 'id = ?', [$lecturer['user_id']]);
        Database::update('lecturers', [
            'title' => $title !== '' ? $title : null,
            'rank' => $rank !== '' ? $rank : null,
            'faculty_id' => $facultyId,
            'department_id' => $departmentId,
        ], 'id = ?', [$id]);
        $newPass = (string)($_POST['password'] ?? '');
        if ($newPass !== '') {
            if (strlen($newPass) < 8) {
                flash('error', 'Password must be at least 8 characters.');
                redirect('admin/lecturers/' . $id . '/edit');
            }
            Database::update('users', [
                'password_hash' => Security::hashPassword($newPass),
                'updated_at' => now(),
            ], 'id = ?', [$lecturer['user_id']]);
        }
        Audit::log('lecturer.update', 'lecturers', (string)$id);
        flash('success', $newPass !== '' ? 'Lecturer updated and password reset.' : 'Lecturer updated.');
        redirect('admin/lecturers');
    }

    public function profile(): void
    {
        $admin = $this->requireAdmin();
        View::render('admin/profile', [
            'title' => 'Administrator Profile',
            'admin' => $admin,
            'nav' => 'profile',
        ], 'layouts/admin');
    }

    public function profileSave(): void
    {
        $admin = $this->requireAdmin();
        $current = (string)($_POST['current_password'] ?? '');
        $newPass = (string)($_POST['password'] ?? '');
        $confirm = (string)($_POST['password_confirm'] ?? '');
        if ($current === '' || $newPass === '' || $confirm === '') {
            flash('error', 'Enter your current password and a new password.');
            redirect('admin/profile');
        }
        if (!Security::verifyPassword($current, (string)($admin['password_hash'] ?? ''))) {
            flash('error', 'Current password is incorrect.');
            redirect('admin/profile');
        }
        if (strlen($newPass) < 8) {
            flash('error', 'New password must be at least 8 characters.');
            redirect('admin/profile');
        }
        if ($newPass !== $confirm) {
            flash('error', 'New password and confirmation do not match.');
            redirect('admin/profile');
        }
        if (Security::verifyPassword($newPass, (string)($admin['password_hash'] ?? ''))) {
            flash('error', 'Choose a password that is different from your current one.');
            redirect('admin/profile');
        }
        Database::update('users', [
            'password_hash' => Security::hashPassword($newPass),
            'updated_at' => now(),
        ], 'id = ?', [$admin['id']]);
        Audit::log('admin.password_change', 'users', (string)$admin['id']);
        flash('success', 'Password updated.');
        redirect('admin/profile');
    }

    public function studentPassword(string $id): void
    {
        $this->requireAdmin();
        $sid = (int)$id;
        $newPass = (string)($_POST['password'] ?? '');
        $confirm = (string)($_POST['password_confirm'] ?? '');
        if ($newPass === '' || $confirm === '') {
            flash('error', 'Enter and confirm the new student password.');
            redirect('admin/students/' . $sid);
        }
        if ($newPass !== $confirm) {
            flash('error', 'New password and confirmation do not match.');
            redirect('admin/students/' . $sid);
        }
        $this->setStudentPassword($sid, $newPass, 'admin/students/' . $sid);
        flash('success', 'Student password updated.');
        redirect('admin/students/' . $sid);
    }

    private function setStudentPassword(int $studentId, string $newPass, string $back = ''): void
    {
        $back = $back !== '' ? $back : 'admin/students/' . $studentId . '/edit';
        if (strlen($newPass) < 8) {
            flash('error', 'Password must be at least 8 characters.');
            redirect($back);
        }
        $student = Database::one('SELECT * FROM students WHERE id = ?', [$studentId]);
        if (!$student) {
            flash('error', 'Student not found.');
            redirect('admin/students');
        }
        if (!empty($student['user_id'])) {
            Database::update('users', [
                'password_hash' => Security::hashPassword($newPass),
                'updated_at' => now(),
            ], 'id = ?', [$student['user_id']]);
        } else {
            $role = Database::one('SELECT id FROM roles WHERE slug = ?', ['student']);
            if (!$role) {
                flash('error', 'Student role is not configured.');
                redirect($back);
            }
            if (Database::one('SELECT id FROM users WHERE username = ?', [$student['matric_no']])) {
                flash('error', 'A login already exists for this matric number.');
                redirect($back);
            }
            $userId = Database::insert('users', [
                'role_id' => (int)$role['id'],
                'username' => $student['matric_no'],
                'email' => $student['email'] ?: null,
                'password_hash' => Security::hashPassword($newPass),
                'full_name' => student_full_name($student),
                'phone' => $student['phone'],
                'status' => 'active',
                'created_at' => now(),
            ]);
            Database::update('students', [
                'user_id' => $userId,
                'enrollment_status' => $student['enrollment_status'] === 'preloaded' ? 'active' : $student['enrollment_status'],
                'updated_at' => now(),
            ], 'id = ?', [$studentId]);
        }
        Audit::log('admin.student_password', 'students', (string)$studentId);
    }

    public function venues(): void
    {
        $this->requireAdmin();
        $rows = Database::all('SELECT * FROM venues ORDER BY name');
        View::render('admin/venues', ['title' => 'Venue Management', 'rows' => $rows, 'nav' => 'venues'], 'layouts/admin');
    }

    public function venueForm(?string $id = null): void
    {
        $this->requireAdmin();
        $venue = $id ? Database::one('SELECT * FROM venues WHERE id = ?', [(int)$id]) : null;
        View::render('admin/venue_form', [
            'title' => $venue ? 'Edit Venue' : 'Create Venue',
            'venue' => $venue,
            'nav' => 'venues',
        ], 'layouts/admin');
    }

    public function venueSave(): void
    {
        $user = $this->requireAdmin();
        $id = (int)($_POST['id'] ?? 0);
        $data = [
            'name' => trim((string)($_POST['name'] ?? '')),
            'building_name' => trim((string)($_POST['building_name'] ?? '')),
            'latitude' => (float)($_POST['latitude'] ?? 0),
            'longitude' => (float)($_POST['longitude'] ?? 0),
            'radius' => max(10, (int)($_POST['radius'] ?? 100)),
            'capacity' => (int)($_POST['capacity'] ?? 0) ?: null,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'updated_at' => now(),
        ];
        if ($data['name'] === '' || $data['building_name'] === '') {
            flash('error', 'Venue name and building are required.');
            redirect('admin/venues/create');
        }
        if ($id) {
            Database::update('venues', $data, 'id = ?', [$id]);
            Audit::log('venue.update', 'venues', (string)$id);
            flash('success', 'Venue updated.');
        } else {
            unset($data['updated_at']);
            $data['captured_by'] = $user['id'];
            $data['created_at'] = now();
            $nid = Database::insert('venues', $data);
            Audit::log('venue.create', 'venues', (string)$nid);
            flash('success', 'Venue created with GPS geofence.');
        }
        redirect('admin/venues');
    }

    public function payments(): void
    {
        $this->requireAdmin();
        View::render('admin/payments', [
            'title' => 'Payment Settings',
            'enrollment_enabled' => setting('enrollment_fee_enabled', setting('payments_enabled', '0')),
            'face_update_enabled' => setting('face_update_fee_enabled', setting('payments_enabled', '0')),
            'enrollment_fee' => setting('enrollment_fee', '0'),
            'face_update_fee' => setting('face_update_fee', '0'),
            'nav' => 'payments',
        ], 'layouts/admin');
    }

    public function paymentsSave(): void
    {
        $this->requireAdmin();
        $enrollOn = isset($_POST['enrollment_fee_enabled']) ? '1' : '0';
        $faceOn = isset($_POST['face_update_fee_enabled']) ? '1' : '0';
        if ($enrollOn === '0' && $faceOn === '0' && isset($_POST['payments_enabled'])) {
            $enrollOn = '1';
            $faceOn = '1';
        }
        $this->upsertSetting('enrollment_fee_enabled', $enrollOn);
        $this->upsertSetting('face_update_fee_enabled', $faceOn);
        $this->upsertSetting('payments_enabled', ($enrollOn === '1' || $faceOn === '1') ? '1' : '0');
        $this->upsertSetting('enrollment_fee', (string)max(0, (float)($_POST['enrollment_fee'] ?? 0)));
        $this->upsertSetting('face_update_fee', (string)max(0, (float)($_POST['face_update_fee'] ?? 0)));
        setting_all(true);
        Audit::log('settings.payments', 'settings', null);
        flash('success', 'Payment settings saved.');
        redirect('admin/payments');
    }

    public function settings(): void
    {
        $this->requireAdmin();
        View::render('admin/settings', [
            'title' => 'System Settings',
            'values' => setting_all(),
            'nav' => 'settings',
        ], 'layouts/admin');
    }

    public function settingsSave(): void
    {
        $this->requireAdmin();
        $known = [
            'institution' => trim((string)($_POST['institution'] ?? '')),
            'academic_session' => trim((string)($_POST['academic_session'] ?? '')),
            'semester' => (($_POST['semester'] ?? '') === 'Rain') ? 'Rain' : 'Harmattan',
            'exam_threshold' => (string)max(0, min(100, (int)($_POST['exam_threshold'] ?? 75))),
            'default_radius' => (string)max(10, (int)($_POST['default_radius'] ?? 100)),
            'enrollment_fee_enabled' => isset($_POST['enrollment_fee_enabled']) ? '1' : '0',
            'face_update_fee_enabled' => isset($_POST['face_update_fee_enabled']) ? '1' : '0',
            'payments_enabled' => (isset($_POST['enrollment_fee_enabled']) || isset($_POST['face_update_fee_enabled'])) ? '1' : '0',
            'enrollment_fee' => (string)max(0, (float)($_POST['enrollment_fee'] ?? 0)),
            'face_update_fee' => (string)max(0, (float)($_POST['face_update_fee'] ?? 0)),
        ];
        if ($known['institution'] === '' || $known['academic_session'] === '') {
            flash('error', 'Institution name and academic session are required.');
            redirect('admin/settings');
        }
        foreach ($known as $key => $value) {
            $this->upsertSetting($key, $value);
        }

        $postedKeys = $_POST['extra_key'] ?? [];
        $postedVals = $_POST['extra_value'] ?? [];
        if (!is_array($postedKeys)) {
            $postedKeys = [];
        }
        if (!is_array($postedVals)) {
            $postedVals = [];
        }
        $reserved = array_keys($known);
        foreach ($postedKeys as $i => $rawKey) {
            $key = strtolower(trim((string)$rawKey));
            if ($key === '' || in_array($key, $reserved, true) || !preg_match('/^[a-z][a-z0-9_]{1,62}$/', $key)) {
                continue;
            }
            $value = trim((string)($postedVals[$i] ?? ''));
            $this->upsertSetting($key, $value);
        }

        $newKey = strtolower(trim((string)($_POST['new_key'] ?? '')));
        $newVal = trim((string)($_POST['new_value'] ?? ''));
        if ($newKey !== '') {
            if (!preg_match('/^[a-z][a-z0-9_]{1,62}$/', $newKey) || in_array($newKey, $reserved, true)) {
                flash('error', 'New setting key must be a unique lowercase identifier (letters, numbers, underscore).');
                redirect('admin/settings');
            }
            $this->upsertSetting($newKey, $newVal);
        }

        setting_all(true);
        Audit::log('settings.update', 'settings', null, ['keys' => array_keys($known)]);
        flash('success', 'System settings saved.');
        redirect('admin/settings');
    }

    public function transactions(): void
    {
        $this->requireAdmin();
        $rows = Database::all(
            "SELECT p.*, s.matric_no, s.first_name, s.last_name
             FROM payments p JOIN students s ON s.id = p.student_id
             ORDER BY p.created_at DESC LIMIT 200"
        );
        View::render('admin/transactions', ['title' => 'Transactions', 'rows' => $rows, 'nav' => 'payments'], 'layouts/admin');
    }

    public function reports(): void
    {
        $this->requireAdmin();
        View::render('admin/reports', [
            'title' => 'Reports Hub',
            'nav' => 'reports',
            'faculties' => Database::all('SELECT * FROM faculties ORDER BY name'),
            'departments' => Database::all('SELECT * FROM departments ORDER BY name'),
        ], 'layouts/admin');
    }

    public function reportExport(): void
    {
        $this->requireAdmin();
        $type = $_GET['type'] ?? 'students';
        $format = $_GET['format'] ?? 'csv';
        $rows = [];
        $headers = [];
        if ($type === 'students') {
            $headers = ['Matric', 'Name', 'Faculty', 'Department', 'Level', 'Status'];
            $data = Database::all(
                'SELECT s.matric_no, s.first_name, s.last_name, f.name AS faculty_name, d.name AS department_name, s.level, s.enrollment_status
                 FROM students s JOIN faculties f ON f.id = s.faculty_id JOIN departments d ON d.id = s.department_id ORDER BY s.matric_no'
            );
            foreach ($data as $r) {
                $rows[] = [$r['matric_no'], $r['first_name'] . ' ' . $r['last_name'], $r['faculty_name'], $r['department_name'], $r['level'], $r['enrollment_status']];
            }
        } elseif ($type === 'lecturers') {
            $headers = ['Staff No', 'Name', 'Faculty', 'Department', 'Status'];
            $data = Database::all(
                'SELECT l.staff_no, u.full_name, f.name AS faculty_name, d.name AS department_name, u.status
                 FROM lecturers l JOIN users u ON u.id = l.user_id
                 JOIN faculties f ON f.id = l.faculty_id JOIN departments d ON d.id = l.department_id'
            );
            foreach ($data as $r) {
                $rows[] = [$r['staff_no'], $r['full_name'], $r['faculty_name'], $r['department_name'], $r['status']];
            }
        } elseif ($type === 'courses') {
            $headers = ['Code', 'Title', 'Faculty', 'Department', 'Level', 'Semester', 'Session'];
            $data = Database::all(
                'SELECT c.*, f.name AS faculty_name, d.name AS department_name FROM courses c
                 JOIN faculties f ON f.id = c.faculty_id JOIN departments d ON d.id = c.department_id'
            );
            foreach ($data as $r) {
                $rows[] = [$r['code'], $r['title'], $r['faculty_name'], $r['department_name'], $r['level'], $r['semester'], $r['academic_session']];
            }
        } elseif ($type === 'venues') {
            $headers = ['Name', 'Building', 'Latitude', 'Longitude', 'Radius'];
            $data = Database::all('SELECT * FROM venues');
            foreach ($data as $r) {
                $rows[] = [$r['name'], $r['building_name'], $r['latitude'], $r['longitude'], $r['radius']];
            }
        } elseif ($type === 'payments') {
            $headers = ['Reference', 'Matric', 'Type', 'Amount', 'Status', 'Date'];
            $data = Database::all(
                'SELECT p.*, s.matric_no FROM payments p JOIN students s ON s.id = p.student_id'
            );
            foreach ($data as $r) {
                $rows[] = [$r['reference'], $r['matric_no'], $r['type'], $r['amount'], $r['status'], $r['created_at']];
            }
        } elseif ($type === 'attendance') {
            $headers = ['Date', 'Course', 'Matric', 'Name', 'Status', 'Distance', 'Face Score'];
            $data = Database::all(
                'SELECT ar.*, s.matric_no, s.first_name, s.last_name, c.code, sess.session_date
                 FROM attendance_records ar
                 JOIN students s ON s.id = ar.student_id
                 JOIN attendance_sessions sess ON sess.id = ar.session_id
                 JOIN courses c ON c.id = sess.course_id
                 ORDER BY ar.recorded_at DESC LIMIT 2000'
            );
            foreach ($data as $r) {
                $rows[] = [$r['session_date'], $r['code'], $r['matric_no'], $r['first_name'] . ' ' . $r['last_name'], $r['status'], $r['distance_meters'], $r['face_score']];
            }
        } elseif ($type === 'faculty' || $type === 'department') {
            $headers = ['Unit', 'Students', 'Courses'];
            if ($type === 'faculty') {
                $data = Database::all(
                    'SELECT f.name,
                            (SELECT COUNT(*) FROM students s WHERE s.faculty_id = f.id) AS students,
                            (SELECT COUNT(*) FROM courses c WHERE c.faculty_id = f.id) AS courses
                     FROM faculties f'
                );
            } else {
                $data = Database::all(
                    'SELECT d.name,
                            (SELECT COUNT(*) FROM students s WHERE s.department_id = d.id) AS students,
                            (SELECT COUNT(*) FROM courses c WHERE c.department_id = d.id) AS courses
                     FROM departments d'
                );
            }
            foreach ($data as $r) {
                $rows[] = [$r['name'], $r['students'], $r['courses']];
            }
        }
        $this->download($type . '_report', $headers, $rows, $format);
    }

    public function logs(): void
    {
        $this->requireAdmin();
        $rows = Database::all(
            'SELECT a.*, u.full_name FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id
             ORDER BY a.id DESC LIMIT 200'
        );
        View::render('admin/logs', ['title' => 'Audit Logs', 'rows' => $rows, 'nav' => 'logs'], 'layouts/admin');
    }

    private function upsertSetting(string $key, string $value): void
    {
        $row = Database::one('SELECT id FROM settings WHERE setting_key = ?', [$key]);
        if ($row) {
            Database::update('settings', ['setting_value' => $value, 'updated_at' => now()], 'setting_key = ?', [$key]);
        } else {
            Database::insert('settings', ['setting_key' => $key, 'setting_value' => $value]);
        }
    }

    private function lastDays(int $n): array
    {
        $out = [];
        for ($i = $n - 1; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-{$i} days"));
            $c = Database::one('SELECT COUNT(*) AS c FROM attendance_records WHERE date(recorded_at) = ?', [$d]);
            $out[] = ['date' => date('D j', strtotime($d)), 'count' => (int)($c['c'] ?? 0)];
        }
        return $out;
    }

    private function download(string $name, array $headers, array $rows, string $format): never
    {
        $safe = preg_replace('/[^A-Za-z0-9_-]/', '_', $name);
        if ($format === 'pdf') {
            header('Content-Type: text/html; charset=utf-8');
            echo '<html><head><title>' . e($name) . '</title><style>body{font-family:Inter,Arial;padding:24px}table{border-collapse:collapse;width:100%}th,td{border:1px solid #ccc;padding:6px;font-size:12px}h1{color:#0F2942}</style></head><body>';
            echo '<h1>TSU-SAMS</h1><h2>' . e(ucfirst(str_replace('_', ' ', $name))) . '</h2>';
            echo '<table><tr>';
            foreach ($headers as $h) {
                echo '<th>' . e($h) . '</th>';
            }
            echo '</tr>';
            foreach ($rows as $r) {
                echo '<tr>';
                foreach ($r as $c) {
                    echo '<td>' . e((string)$c) . '</td>';
                }
                echo '</tr>';
            }
            echo '</table><script>window.print()</script></body></html>';
            exit;
        }
        $ext = $format === 'excel' ? 'xls' : 'csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $safe . '.' . $ext . '"');
        $out = fopen('php://output', 'w');
        fputcsv($out, $headers);
        foreach ($rows as $r) {
            fputcsv($out, $r);
        }
        fclose($out);
        exit;
    }
}
