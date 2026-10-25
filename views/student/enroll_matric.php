<div class="welcome-wrap">
  <p class="small text-muted mb-1">Step 1 of 6</p>
  <h1 class="h4 fw-bold">Enter matric number</h1>
  <p class="muted">Your record must already exist in the Registry preload.</p>
  <?php include config('paths.views') . '/partials/flash.php'; ?>
  <div class="card">
    <form method="post" action="<?= e(base_url('register')) ?>">
      <?= csrf_field() ?>
      <label class="form-label">Matric number</label>
      <input class="form-control font-tabular mb-3" name="matric_no" required placeholder="TSU/SCI/21/04882" style="text-transform:uppercase">
      <button class="btn btn-primary" type="submit">Verify record</button>
    </form>
  </div>
  <p class="footer-note"><a href="<?= e(base_url('')) ?>">Cancel</a></p>
</div>
