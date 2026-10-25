<div class="welcome-wrap">
  <p class="small text-muted mb-1">Step 1 of 5</p>
  <h1 class="h4 fw-bold">Enter Matric / JAMB number</h1>
  <p class="muted">Your record is fetched from preloaded data.</p>
  <?php include config('paths.views') . '/partials/flash.php'; ?>
  <div class="card">
    <form method="post" action="<?= e(base_url(exam_path('enroll'))) ?>">
      <?= csrf_field() ?>
      <label class="form-label">Matric number or JAMB number</label>
      <input class="form-control font-tabular mb-3" name="matric_no" required placeholder="TSU/FSC/BIO/21/1234" style="text-transform:uppercase" value="<?= e(old('matric_no')) ?>">
      <button class="btn btn-primary" type="submit">VERIFY</button>
    </form>
  </div>
  <p class="footer-note"><a href="<?= e(base_url(exam_path(''))) ?>">Cancel</a></p>
</div>
