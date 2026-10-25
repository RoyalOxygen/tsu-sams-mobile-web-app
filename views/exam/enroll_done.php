<div class="welcome-wrap">
  <div class="card text-center">
    <div class="text-success mb-2"><i class="bi bi-check-circle fs-1"></i></div>
    <h1 class="h4">Face enrollment successful</h1>
    <p>Your biometric template is now on file for examinations and lecture attendance.</p>
    <p class="font-tabular mb-1"><strong><?= e($info['matric']) ?></strong></p>
    <p class="mb-1"><?= e($info['name']) ?></p>
    <p class="muted small"><?= e($info['faculty']) ?><br><?= e($info['department']) ?> • <?= e($info['level']) ?>L</p>
    <?php include config('paths.views') . '/partials/flash.php'; ?>
    <a class="btn btn-primary" href="<?= e(base_url(exam_path(''))) ?>">Done</a>
  </div>
  <p class="footer-note"></p>
</div>
