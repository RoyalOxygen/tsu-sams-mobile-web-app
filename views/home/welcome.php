<div class="welcome-wrap">
  <div class="text-center pt-3 pb-2">
    <div class="d-inline-flex align-items-center justify-content-center bg-white shadow-sm rounded-4 p-2 mb-2">
      <?= brand_logo(72, 'brand-logo brand-logo-lg') ?>
    </div>
    <p class="text-uppercase small text-muted mb-1" style="letter-spacing:.12em">Taraba State University, Jalingo</p>
    <p class="visually-hidden">TSU-SAMS</p>
    <span class="badge badge-muted">Smart Attendance Management System</span>
    <p class="small text-muted mt-2">Facial biometrics and geofenced lecture attendance</p>
  </div>

  <?php include config('paths.views') . '/partials/flash.php'; ?>

  <div class="card card-hero">
    <div class="hero hero-media mb-0">
      <img src="<?= e(base_url('assets/img/welcome-hero.webp')) ?>" alt="TSU-SAMS cryptographic spatial lock">
    </div>
  </div>

 

  <a class="btn btn-primary mb-2" href="<?= e(base_url('register')) ?>"><i class="bi bi-person-plus"></i> Get started / Register</a>
  <a class="btn btn-outline mb-2" href="<?= e(base_url('student/face-login')) ?>"><i class="bi bi-camera"></i> Student face login</a>
  <a class="btn btn-outline mb-2" href="<?= e(base_url('login?role=student')) ?>"><i class="bi bi-box-arrow-in-right"></i> Student password login</a>

  <div class="d-flex justify-content-center gap-3 mt-3 small">
    <a href="<?= e(base_url('login?role=lecturer')) ?>"><i class="bi bi-mortarboard"></i> Lecturer</a>
    <span class="text-muted">•</span>
    <a href="<?= e(base_url('login?role=admin')) ?>"><i class="bi bi-gear"></i> Admin</a>
    <span class="text-muted">•</span>
    <a href="<?= e(base_url('help')) ?>">Helpdesk</a>
  </div>
  <p class="footer-note">Secured by Taraba State University ICT Directorate<br><?= e(setting('academic_session', '2024/2025')) ?> Academic Session • v<?= e(config('app.version')) ?></p>
</div>
