<h1 class="h4">Administrator profile</h1>
<div class="card">
  <p class="mb-1"><strong><?= e($admin['full_name']) ?></strong></p>
  <p class="font-tabular muted mb-0"><?= e($admin['username']) ?></p>
</div>
<div class="card">
  <h2 class="h6">Change password</h2>
  <form method="post" action="<?= e(base_url('admin/profile')) ?>" autocomplete="off">
    <?= csrf_field() ?>
    <div class="mb-3">
      <label class="form-label">Current password</label>
      <input class="form-control" type="password" name="current_password" required>
    </div>
    <div class="mb-3">
      <label class="form-label">New password</label>
      <input class="form-control" type="password" name="password" minlength="8" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Confirm new password</label>
      <input class="form-control" type="password" name="password_confirm" minlength="8" required>
    </div>
    <button class="btn btn-primary" type="submit">Update password</button>
  </form>
</div>
