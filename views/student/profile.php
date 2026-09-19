<h1 class="h4">Profile</h1>
<?php $passport = student_passport_url($student); ?>
<div class="card">
  <div class="d-flex align-items-center gap-3 mb-2">
    <?php if ($passport): ?>
      <img src="<?= e($passport) ?>" alt="Profile picture of <?= e(student_full_name($student)) ?>" class="avatar avatar-lg">
    <?php else: ?>
      <div class="avatar avatar-lg avatar-initials" role="img" aria-label="Profile picture placeholder for <?= e(student_full_name($student)) ?>"><?= e(student_initials($student)) ?></div>
    <?php endif; ?>
    <div>
      <p class="mb-1"><strong><?= e(student_full_name($student)) ?></strong></p>
      <p class="font-tabular mb-0"><?= e($student['matric_no']) ?></p>
    </div>
  </div>
  <p class="muted"><?= e($student['faculty_name']) ?><br><?= e($student['department_name']) ?> • <?= (int)$student['level'] ?>L</p>
  <p>Phone: <?= e($student['phone'] ?: '—') ?></p>
  <p>Enrollment: <?= e(enrollment_label($student['enrollment_status'])) ?></p>
  <p>Face template: <?= $face ? 'Enrolled ' . e($face['enrolled_at']) : 'Not enrolled' ?></p>
  <a class="btn btn-outline" href="<?= e(base_url('student/face')) ?>"><i class="bi bi-camera"></i> Update face biometric</a>
</div>

<div class="card">
  <h2 class="h6">Change password</h2>
  <form method="post" action="<?= e(base_url('student/profile')) ?>" autocomplete="off">
    <?= csrf_field() ?>
    <div class="mb-3">
      <label class="form-label">Current password</label>
      <input class="form-control" type="password" name="current_password" required>
    </div>
    <div class="mb-3">
      <label class="form-label">New password</label>
      <input class="form-control" type="password" name="password" minlength="8" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Confirm new password</label>
      <input class="form-control" type="password" name="password_confirm" minlength="8" required>
    </div>
    <button class="btn btn-primary" type="submit">Update password</button>
  </form>
</div>
