<h1 class="h4">Edit lecturer</h1>
<div class="card">
  <form method="post" action="<?= e(base_url('admin/lecturers/save')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int)$lecturer['id'] ?>">
    <div class="mb-3">
      <label class="form-label">Staff number</label>
      <input class="form-control font-tabular" value="<?= e($lecturer['staff_no']) ?>" disabled>
    </div>
    <div class="grid-2 mb-3">
      <div>
        <label class="form-label">Title</label>
        <input class="form-control" name="title" value="<?= e($lecturer['title'] ?? '') ?>">
      </div>
      <div>
        <label class="form-label">Full name</label>
        <input class="form-control" name="full_name" required value="<?= e($lecturer['full_name'] ?? '') ?>">
      </div>
    </div>
    <div class="mb-3">
      <label class="form-label">Rank</label>
      <input class="form-control" name="rank" value="<?= e($lecturer['rank'] ?? '') ?>">
    </div>
    <div class="mb-3">
      <label class="form-label">Faculty</label>
      <select class="form-select" name="faculty_id">
        <?php foreach ($faculties as $f): ?>
          <option value="<?= (int)$f['id'] ?>" <?= ((int)($lecturer['faculty_id'] ?? 0) === (int)$f['id']) ? 'selected' : '' ?>><?= e($f['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label">Department</label>
      <select class="form-select" name="department_id">
        <?php foreach ($departments as $d): ?>
          <option value="<?= (int)$d['id'] ?>" <?= ((int)($lecturer['department_id'] ?? 0) === (int)$d['id']) ? 'selected' : '' ?>><?= e($d['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="grid-2 mb-3">
      <div>
        <label class="form-label">Email</label>
        <input class="form-control" type="email" name="email" value="<?= e($lecturer['email'] ?? '') ?>">
      </div>
      <div>
        <label class="form-label">Phone</label>
        <input class="form-control" name="phone" value="<?= e($lecturer['phone'] ?? '') ?>">
      </div>
    </div>
    <div class="mb-3">
      <label class="form-label">Set new password</label>
      <input class="form-control" type="password" name="password" minlength="8" autocomplete="new-password">
      <div class="small muted mt-1">Leave empty to keep the current password. Minimum 8 characters.</div>
    </div>
    <button class="btn btn-primary" type="submit">Save lecturer</button>
    <a class="btn btn-outline" href="<?= e(base_url('admin/lecturers')) ?>">Cancel</a>
  </form>
</div>
