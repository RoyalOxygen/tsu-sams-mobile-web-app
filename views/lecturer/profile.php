<h1 class="h4">Profile settings</h1>
<div class="card">
  <p><strong><?= e(($lecturer['title'] ? $lecturer['title'] . ' ' : '') . $lecturer['full_name']) ?></strong></p>
  <p class="font-tabular"><?= e($lecturer['staff_no']) ?></p>
  <p class="muted"><?= e($lecturer['department_name']) ?> • <?= e($lecturer['faculty_name']) ?></p>
  <form method="post" action="<?= e(base_url('lecturer/profile')) ?>">
    <?= csrf_field() ?>
    <div class="mb-3">
      <label class="form-label">Email</label>
      <input class="form-control" type="email" name="email" value="<?= e($lecturer['email'] ?? '') ?>">
    </div>
    <div class="mb-3">
      <label class="form-label">Phone</label>
      <input class="form-control" name="phone" value="<?= e($lecturer['phone'] ?? '') ?>">
    </div>
    <div class="mb-3">
      <label class="form-label">New password (optional)</label>
      <input class="form-control" type="password" name="password" minlength="8">
    </div>
    <button class="btn btn-primary" type="submit">Save</button>
  </form>
</div>
