<div class="welcome-wrap">
  <p class="small text-muted mb-1">Step 2 of 6</p>
  <h1 class="h4 fw-bold">Verify student record</h1>
  <?php include config('paths.views') . '/partials/flash.php'; ?>
  <div class="card">
    <div class="d-flex justify-content-between">
      <div>
        <div class="text-muted small">Matric</div>
        <strong class="font-tabular"><?= e($student['matric_no']) ?></strong>
        <h2 class="h5 mt-2 mb-1"><?= e(student_full_name($student)) ?></h2>
        <p class="muted mb-0"><?= e($student['faculty_name']) ?></p>
        <p class="muted"><?= e($student['department_name']) ?> • <?= e((string)$student['level']) ?>L</p>
      </div>
      <span class="badge badge-warn">PRELOADED</span>
    </div>
    <form method="post" action="<?= e(base_url('register/confirm')) ?>">
      <?= csrf_field() ?>
      <button class="btn btn-success" type="submit">This is me, continue</button>
    </form>
    <a class="btn btn-outline mt-2" href="<?= e(base_url('register')) ?>">Not my record</a>
  </div>
</div>
