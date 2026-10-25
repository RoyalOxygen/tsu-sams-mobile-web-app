<div class="welcome-wrap">
  <div class="text-center mb-3">
    <div class="d-flex justify-content-center mb-2"><?= brand_logo(48, 'brand-logo') ?></div>
    <h1 class="h4 fw-bold"><?= e(ucfirst($role)) ?> sign in</h1>
    <p class="muted small">Taraba State University Smart Attendance</p>
  </div>
  <?php include config('paths.views') . '/partials/flash.php'; ?>
  <div class="card">
    <form method="post" action="<?= e(base_url('login')) ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="role" value="<?= e($role) ?>">
      <div class="mb-3">
        <label class="form-label"><?= $role === 'student' ? 'Matric number' : ($role === 'lecturer' ? 'Staff number' : 'Username') ?></label>
        <input class="form-control font-tabular" name="username" required autocomplete="username"
               placeholder="<?= $role === 'student' ? 'TSU/SCI/21/04882' : ($role === 'lecturer' ? 'TSU/STF/18/0429' : 'admin') ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Password</label>
        <input class="form-control" type="password" name="password" required autocomplete="current-password">
      </div>
      <button class="btn btn-primary" type="submit">Sign in</button>
    </form>
    <?php if ($role === 'student'): ?>
      <a class="btn btn-outline mt-2" href="<?= e(base_url('student/face-login')) ?>">Use face verification instead</a>
      <p class="small text-center mt-3 mb-0">New student? <a href="<?= e(base_url('register')) ?>">Complete enrollment</a></p>
    <?php elseif ($role === 'lecturer'): ?>
      <p class="small text-center mt-3 mb-0">No account? <a href="<?= e(base_url('lecturer/register')) ?>">Register as lecturer</a></p>
    <?php endif; ?>
  </div>
  <p class="footer-note"><a href="<?= e(base_url('')) ?>">Back to home</a></p>
</div>
