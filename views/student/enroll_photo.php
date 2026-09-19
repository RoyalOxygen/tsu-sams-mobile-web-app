<div class="welcome-wrap">
  <p class="small text-muted mb-1">Step 3 of 6</p>
  <h1 class="h4 fw-bold">Upload passport photograph</h1>
  <p class="muted">Use a recent, clear, front-facing passport photo. JPEG or PNG, max 3MB.</p>
  <?php include config('paths.views') . '/partials/flash.php'; ?>
  <div class="card">
    <form method="post" action="<?= e(base_url('register/photo')) ?>" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <label class="form-label">Passport photograph</label>
      <input class="form-control mb-3" type="file" name="passport" accept="image/jpeg,image/png,image/webp" required>
      <button class="btn btn-primary" type="submit">Upload and continue</button>
    </form>
  </div>
</div>
