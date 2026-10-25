<h1 class="h4"><?= $course ? 'Edit course' : 'Create course' ?></h1>
<div class="card">
  <form method="post" action="<?= e(base_url('lecturer/courses')) ?>">
    <?= csrf_field() ?>
    <?php if ($course): ?><input type="hidden" name="id" value="<?= (int)$course['id'] ?>"><?php endif; ?>
    <div class="mb-3">
      <label class="form-label">Course code</label>
      <input class="form-control" name="code" required value="<?= e($course['code'] ?? '') ?>" placeholder="CSC 301">
    </div>
    <div class="mb-3">
      <label class="form-label">Course title</label>
      <input class="form-control" name="title" required value="<?= e($course['title'] ?? '') ?>">
    </div>
    <div class="mb-3">
      <label class="form-label">Faculty</label>
      <select class="form-select" name="faculty_id">
        <?php foreach ($faculties as $f): ?>
          <option value="<?= (int)$f['id'] ?>" <?= ((int)($course['faculty_id'] ?? $lecturer['faculty_id']) === (int)$f['id']) ? 'selected' : '' ?>><?= e($f['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label">Department</label>
      <select class="form-select" name="department_id">
        <?php foreach ($departments as $d): ?>
          <option value="<?= (int)$d['id'] ?>" <?= ((int)($course['department_id'] ?? $lecturer['department_id']) === (int)$d['id']) ? 'selected' : '' ?>><?= e($d['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="grid-2 mb-3">
      <div>
        <label class="form-label">Level</label>
        <select class="form-select" name="level">
          <?php foreach ([100,200,300,400,500] as $lv): ?>
            <option value="<?= $lv ?>" <?= ((int)($course['level'] ?? 100) === $lv) ? 'selected' : '' ?>><?= $lv ?>L</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="form-label">Semester</label>
        <select class="form-select" name="semester">
          <option value="Harmattan" <?= ($course['semester'] ?? 'Harmattan') === 'Harmattan' ? 'selected' : '' ?>>Harmattan</option>
          <option value="Rain" <?= ($course['semester'] ?? '') === 'Rain' ? 'selected' : '' ?>>Rain</option>
        </select>
      </div>
    </div>
    <div class="grid-2 mb-3">
      <div>
        <label class="form-label">Academic session</label>
        <input class="form-control" name="academic_session" value="<?= e($course['academic_session'] ?? setting('academic_session','2024/2025')) ?>">
      </div>
      <div>
        <label class="form-label">Units</label>
        <input class="form-control" type="number" name="unit" min="1" max="6" value="<?= (int)($course['unit'] ?? 3) ?>">
      </div>
    </div>
    <button class="btn btn-primary" type="submit">Save course</button>
  </form>
</div>
