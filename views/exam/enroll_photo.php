<div class="welcome-wrap">
  <p class="small text-muted mb-1">Step 3 of 5</p>
  <h1 class="h4 fw-bold">Passport</h1>
  <p class="muted">Upload a recent, clear, front-facing passport photograph. JPEG, PNG or WEBP, max 500KB.</p>
  <?php include config('paths.views') . '/partials/flash.php'; ?>
  <div class="card">
    <form method="post" action="<?= e(base_url(exam_path('photo'))) ?>" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <?php $passport = student_passport_url($student); ?>
      <div class="mb-3">
        <label class="form-label">Recent passport photograph</label>
        <?php if ($passport): ?>
          <div class="d-flex align-items-center gap-3 mb-2">
            <img src="<?= e($passport) ?>" alt="Current passport" class="avatar avatar-lg">
            <div class="small muted">A passport is already on file. Upload a new one to replace it.</div>
          </div>
        <?php endif; ?>
        <input class="form-control" type="file" name="passport" accept="image/jpeg,image/png,image/webp" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Gender</label>
        <select class="form-select" name="gender" required>
          <option value="Male" <?= (($student['gender'] ?? '') === 'Male') ? 'selected' : '' ?>>Male</option>
          <option value="Female" <?= (($student['gender'] ?? '') === 'Female') ? 'selected' : '' ?>>Female</option>
        </select>
      </div>
      <div class="mb-3">
        <label class="form-label">Email</label>
        <input class="form-control" type="email" name="email" required placeholder="name@student.tsuniversity.edu.ng" value="<?= e((string)($student['email'] ?? '')) ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Phone number</label>
        <input class="form-control" type="tel" name="phone" required placeholder="08XX XXX XXXX" value="<?= e((string)($student['phone'] ?? '')) ?>">
      </div>
      <button class="btn btn-primary" type="submit">Save and continue</button>
    </form>
  </div>
</div>
