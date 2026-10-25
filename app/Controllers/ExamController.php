<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Audit;
use App\Database;
use App\Security;
use App\View;
use App\Services\CmsService;
use App\Services\FaceService;
use App\Services\PaymentService;

final class ExamController
{
    public function home(): void
    {
        View::render('exam/welcome', [
            'title' => 'Exam Face Capture',
        ], 'layouts/guest');
    }

    public function start(): void
    {
        $_SESSION['exam'] = ['step' => 1];
        View::render('exam/enroll_matric', [
            'title' => 'Exam Enrollment',
        ], 'layouts/guest');
    }

    public function lookup(): void
    {
        $query = trim((string)($_POST['matric_no'] ?? ''));
        $found = CmsService::lookup($query);
        if (!$found['ok'] || empty($found['student'])) {
            flash('error', $found['message'] ?? 'No student record found for that Matric or JAMB number.');
            redirect(exam_path('enroll'));
        }

        try {
            $student = CmsService::upsertStudent($found['student']);
        } catch (\Throwable $e) {
            flash('error', 'Student record could not be stored. Please try again.');
            redirect(exam_path('enroll'));
        }

        if (in_array($student['enrollment_status'] ?? '', ['face', 'active'], true)) {
            flash('info', 'You have already been enrolled.');
            redirect(exam_path('enroll'));
        }

        $face = Database::one('SELECT id FROM face_profiles WHERE student_id = ?', [$student['id']]);
        $_SESSION['exam'] = [
            'step' => 2,
            'student_id' => (int)$student['id'],
            'query' => $query,
            'source' => $found['source'] ?? 'cms',
            'payments' => $found['payments'] ?? [],
        ];
        View::render('exam/enroll_verify', [
            'title' => 'Confirm Identity',
            'student' => $student,
            'payments' => $found['payments'] ?? [],
            'source' => $found['source'] ?? 'cms',
            'already_enrolled' => $face !== null,
        ], 'layouts/guest');
    }

    public function confirm(): void
    {
        $sid = $this->sessionStudentId();
        $decision = (string)($_POST['decision'] ?? 'yes');
        if ($decision !== 'yes') {
            unset($_SESSION['exam']);
            flash('info', 'Identity not confirmed. Enter the correct Matric or JAMB number.');
            redirect(exam_path('enroll'));
        }
        $_SESSION['exam']['step'] = 3;
        $student = CmsService::studentWithScope($sid);
        View::render('exam/enroll_photo', [
            'title' => 'Passport and Contact',
            'student' => $student,
        ], 'layouts/guest');
    }

    public function photoForm(): void
    {
        $sid = $this->sessionStudentId();
        $student = CmsService::studentWithScope($sid);
        View::render('exam/enroll_photo', [
            'title' => 'Passport and Contact',
            'student' => $student,
        ], 'layouts/guest');
    }

    public function photo(): void
    {
        $sid = $this->sessionStudentId();
        $student = Database::one('SELECT * FROM students WHERE id = ?', [$sid]);
        if (!$student) {
            redirect(exam_path('enroll'));
        }

$email = trim((string)($_POST['email'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $gender = (($_POST['gender'] ?? '') === 'Female') ? 'Female' : 'Male';
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'A valid email address is required.');
            redirect(exam_path('photo'));
        }

        $data = [
            'gender' => $gender,
            'email' => $email,
            'updated_at' => now(),
        ];
        if ($phone !== '') {
            $data['phone'] = $phone;
        }

        $file = $_FILES['passport'] ?? [];
        $hasFile = ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
        if ($hasFile) {
            $err = Security::validateImageUpload($file);
            if ($err) {
                flash('error', $err);
                redirect(exam_path('photo'));
            }
            $data['passport_path'] = Security::storeUpload($file, 'passports', 'stu' . $sid);
        } elseif (empty($student['passport_path'])) {
            flash('error', 'Upload a recent passport photograph.');
            redirect(exam_path('photo'));
        }

        if (empty($student['enrollment_status']) || $student['enrollment_status'] === 'preloaded') {
            $data['enrollment_status'] = 'photo';
        }
        Database::update('students', $data, 'id = ?', [$sid]);
        $_SESSION['exam']['step'] = 4;
        redirect(exam_path('face'));
    }

    public function faceForm(): void
    {
        $sid = $this->sessionStudentId();
        $student = CmsService::studentWithScope($sid);
        View::render('exam/enroll_face', [
            'title' => 'Face Enrollment',
            'student' => $student,
        ], 'layouts/guest');
    }

    public function face(): void
    {
        $sid = $this->sessionStudentId(true);
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
        Database::update('students', [
            'enrollment_status' => 'face',
            'updated_at' => now(),
        ], 'id = ?', [$sid]);
        $row = Database::one('SELECT * FROM students WHERE id = ?', [$sid]);
        if ($row) {
            $this->ensureStudentUser($row);
        }
        Audit::log('exam.face_enroll', 'students', (string)$sid);
        $_SESSION['exam']['step'] = 5;
        $next = PaymentService::examRequired()
            ? base_url(exam_path('payment'))
            : base_url(exam_path('done'));
        json_response(['ok' => true, 'redirect' => $next]);
    }

    public function paymentForm(): void
    {
        $sid = $this->sessionStudentId();
        if (!PaymentService::examRequired()) {
            $this->markExamComplete($sid);
            redirect(exam_path('done'));
        }
        $student = CmsService::studentWithScope($sid);
        View::render('exam/enroll_payment', [
            'title' => 'Exam Capture Fee',
            'student' => $student,
            'fee' => PaymentService::examFee(),
        ], 'layouts/guest');
    }

    public function pay(): void
    {
        $sid = $this->sessionStudentId();
        if (!PaymentService::examRequired()) {
            $this->markExamComplete($sid);
            redirect(exam_path('done'));
        }
        $pay = PaymentService::create($sid, 'exam', PaymentService::examFee());
        PaymentService::markPaid((int)$pay['id']);
        $this->markExamComplete($sid);
        flash('success', 'Payment recorded. Reference ' . $pay['reference']);
        redirect(exam_path('done'));
    }

    public function done(): void
    {
        $sid = (int)($_SESSION['exam']['student_id'] ?? 0);
        if (!$sid) {
            redirect(exam_path(''));
        }
        $student = CmsService::studentWithScope($sid);
        $face = Database::one('SELECT * FROM face_profiles WHERE student_id = ?', [$sid]);
        if (!$face) {
            flash('error', 'Complete face enrollment first.');
            redirect(exam_path('face'));
        }
        if (PaymentService::examRequired() && !PaymentService::hasPaidExam($sid)) {
            redirect(exam_path('payment'));
        }
        $this->markExamComplete($sid);
        $info = [
            'matric' => $student['matric_no'],
            'name' => student_full_name($student),
            'faculty' => $student['faculty_name'] ?? '',
            'department' => $student['department_name'] ?? '',
            'level' => (string)$student['level'],
        ];
        unset($_SESSION['exam']);
        View::render('exam/enroll_done', [
            'title' => 'Exam Enrollment Complete',
            'info' => $info,
            'student' => $student,
        ], 'layouts/guest');
    }

    private function sessionStudentId(bool $json = false): int
    {
        $sid = (int)($_SESSION['exam']['student_id'] ?? 0);
        if ($sid) {
            return $sid;
        }
        if ($json) {
            json_response(['ok' => false, 'message' => 'Session expired. Start again.'], 400);
        }
        flash('error', 'Session expired. Start exam enrollment again.');
        redirect(exam_path('enroll'));
    }

    private function markExamComplete(int $studentId): void
    {
        $row = Database::one('SELECT * FROM students WHERE id = ?', [$studentId]);
        if (!$row) {
            return;
        }
        $this->ensureStudentUser($row);
        $status = (string)($row['enrollment_status'] ?? '');
        if ($status !== 'active') {
            Database::update('students', [
                'enrollment_status' => 'face',
                'updated_at' => now(),
            ], 'id = ?', [$studentId]);
        }
        $_SESSION['exam']['step'] = 6;
    }

    private function ensureStudentUser(array $student): void
    {
        if (!empty($student['user_id'])) {
            $user = Database::one('SELECT id, status FROM users WHERE id = ?', [(int)$student['user_id']]);
            if ($user && ($user['status'] ?? '') !== 'active') {
                Database::update('users', ['status' => 'active', 'updated_at' => now()], 'id = ?', [$user['id']]);
            }
            return;
        }
        $role = Database::one('SELECT id FROM roles WHERE slug = ?', ['student']);
        if (!$role) {
            return;
        }
        $existing = Database::one('SELECT id FROM users WHERE username = ?', [$student['matric_no']]);
        if ($existing) {
            Database::update('students', ['user_id' => $existing['id'], 'updated_at' => now()], 'id = ?', [$student['id']]);
            return;
        }
        $email = trim((string)($student['email'] ?? ''));
        if ($email !== '' && Database::one('SELECT id FROM users WHERE email = ?', [$email])) {
            $email = '';
        }
        $password = bin2hex(random_bytes(8));
        $userId = Database::insert('users', [
            'role_id' => (int)$role['id'],
            'username' => $student['matric_no'],
            'email' => $email !== '' ? $email : null,
            'password_hash' => Security::hashPassword($password),
            'full_name' => student_full_name($student),
            'phone' => $student['phone'] ?? null,
            'status' => 'active',
            'created_at' => now(),
        ]);
        Database::update('students', [
            'user_id' => $userId,
            'updated_at' => now(),
        ], 'id = ?', [$student['id']]);
        Audit::log('exam.account', 'students', (string)$student['id']);
    }
}
