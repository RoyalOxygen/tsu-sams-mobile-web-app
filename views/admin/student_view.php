<?php
$passport = student_passport_url($student);
$faceSample = public_file_url($face['sample_path'] ?? null);
?>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <h1 class="h4 mb-0">Student record</h1>
  <div class="d-flex gap-2">
    <a class="btn btn-outline btn-sm" href="<?= e(base_url('admin/students')) ?>">Back</a>
    <a class="btn btn-primary btn-sm" href="<?= e(base_url('admin/students/' . (int)$student['id'] . '/edit')) ?>">Edit student</a>
  </div>
</div>

<div class="card">
  <div class="d-flex align-items-center gap-3 mb-3">
    <?php if ($passport): ?>
      <img src="<?= e($passport) ?>" alt="Profile picture of <?= e(student_full_name($student)) ?>" class="avatar avatar-lg">
    <?php else: ?>
      <div class="avatar avatar-lg avatar-initials" role="img" aria-label="Profile picture placeholder for <?= e(student_full_name($student)) ?>"><?= e(student_initials($student)) ?></div>
    <?php endif; ?>
    <div>
      <p class="mb-1"><strong><?= e(student_full_name($student)) ?></strong></p>
      <p class="font-tabular mb-1"><?= e($student['matric_no']) ?></p>
      <span class="badge <?= $student['enrollment_status'] === 'active' ? 'badge-success' : 'badge-muted' ?>"><?= e(enrollment_label($student['enrollment_status'])) ?></span>
    </div>
  </div>
  <div class="grid-2">
    <div>
      <div class="small muted">Faculty</div>
      <p><?= e($student['faculty_name']) ?></p>
    </div>
    <div>
      <div class="small muted">Department</div>
      <p><?= e($student['department_name']) ?></p>
    </div>
    <div>
      <div class="small muted">Level</div>
      <p><?= (int)$student['level'] ?>L</p>
    </div>
    <div>
      <div class="small muted">Gender</div>
      <p><?= e($student['gender']) ?></p>
    </div>
    <div>
      <div class="small muted">Phone</div>
      <p><?= e($student['phone'] ?: '—') ?></p>
    </div>
    <div>
      <div class="small muted">Email</div>
      <p><?= e($student['email'] ?: '—') ?></p>
    </div>
    <div>
      <div class="small muted">Portal account</div>
      <p><?= e($student['username'] ?: 'Not enrolled') ?><?php if (!empty($student['user_status'])): ?> (<?= e($student['user_status']) ?>)<?php endif; ?></p>
    </div>
    <div>
      <div class="small muted">Face template</div>
      <p><?= $face ? 'Enrolled ' . e($face['enrolled_at']) : 'Not enrolled' ?></p>
    </div>
  </div>
</div>

<div class="card">
  <h2 class="h6">Student password</h2>
  <p class="muted small"><?= !empty($student['user_id']) ? 'Reset the portal password for this student.' : 'This student has no login yet. Setting a password will create an active portal account.' ?></p>
  <form method="post" action="<?= e(base_url('admin/students/' . (int)$student['id'] . '/password')) ?>" autocomplete="off">
    <?= csrf_field() ?>
    <div class="mb-3">
      <label class="form-label">New password</label>
      <input class="form-control" type="password" name="password" minlength="8" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Confirm new password</label>
      <input class="form-control" type="password" name="password_confirm" minlength="8" required>
    </div>
    <button class="btn btn-primary" type="submit"><?= !empty($student['user_id']) ? 'Update password' : 'Create login and set password' ?></button>
  </form>
</div>

<div class="card">
  <h2 class="h6">Identity photos</h2>
  <div class="photo-compare">
    <figure>
      <?php if ($passport): ?>
        <img src="<?= e($passport) ?>" alt="Passport photograph">
      <?php else: ?>
        <div class="photo-missing">No passport on file</div>
      <?php endif; ?>
      <figcaption>Passport</figcaption>
    </figure>
    <figure>
      <?php if ($faceSample): ?>
        <img src="<?= e($faceSample) ?>" alt="Enrolled face sample">
      <?php else: ?>
        <div class="photo-missing"><?= $face ? 'Template stored, no sample photo' : 'No face enrolled' ?></div>
      <?php endif; ?>
      <figcaption>Face sample</figcaption>
    </figure>
  </div>
</div>
