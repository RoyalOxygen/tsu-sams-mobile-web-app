<div class="welcome-wrap">
  <div class="card text-center">
    <div class="text-success mb-2"><i class="bi bi-check-circle fs-1"></i></div>
    <h1 class="h4">Enrollment complete</h1>
    <p>Welcome, <?= e($info['name']) ?></p>
    <p class="font-tabular mb-1"><strong><?= e($info['matric']) ?></strong></p>
    <div class="alert alert-info">Temporary password: <strong><?= e($info['password']) ?></strong><br>Change it after first login. Prefer face login on campus.</div>
    <a class="btn btn-success" href="<?= e(base_url('student/face-login')) ?>">Continue to face login</a>
    <a class="btn btn-outline mt-2" href="<?= e(base_url('login?role=student')) ?>">Password login</a>
  </div>
</div>
