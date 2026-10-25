<div class="welcome-wrap">
  <div class="text-center pt-3 pb-2">
    <div class="d-inline-flex align-items-center justify-content-center bg-white shadow-sm rounded-4 p-2 mb-2">
      <?= brand_logo(72, 'brand-logo brand-logo-lg') ?>
    </div>
    <p class="text-uppercase small text-muted mb-1" style="letter-spacing:.12em">Taraba State University, Jalingo</p>
    <span class="badge badge-muted">Exam Face Capture<br>Mobile App</span>
    <p class="small text-muted mt-2">Passport, and biometric enrollment for examinations</p>
  </div>

  <?php include config('paths.views') . '/partials/flash.php'; ?>

  <div class="card card-hero">
    <div class="hero hero-media mb-0">
      <img src="<?= e(base_url('assets/img/welcome-hero.png')) ?>" alt="TSU exam capture">
    </div>
  </div>

  <a class="btn btn-primary mb-2" href="<?= e(base_url(exam_path('enroll'))) ?>"><i class="bi bi-person-plus"></i> Enroll</a>

  <p class="footer-note">Secured by Taraba State University ICT Directorate<br><?= e(setting('academic_session', '2024/2025')) ?> Academic Session • Exam Capture</p>
</div>
