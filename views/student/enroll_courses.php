<div class="welcome-wrap">
  <p class="small text-muted mb-1">Step 6 of 6</p>
  <h1 class="h4 fw-bold">Course registration</h1>
  <p class="muted"><?= e($semester) ?> <?= e($session) ?> • <?= e((string)$student['level']) ?>L courses assigned to your department.</p>
  <?php include config('paths.views') . '/partials/flash.php'; ?>
  <form method="post" action="<?= e(base_url('register/courses')) ?>">
    <?= csrf_field() ?>
    <div class="card">
      <?php if (!$courses): ?>
        <p class="muted mb-0">No courses published for your faculty, department, level and semester yet. Contact your course adviser. You may continue with none selected after an administrator publishes courses.</p>
      <?php endif; ?>
      <?php foreach ($courses as $c): ?>
        <label class="list-item">
          <input class="form-check-input mt-1" type="checkbox" name="courses[]" value="<?= (int)$c['id'] ?>" checked>
          <div>
            <strong><?= e($c['code']) ?></strong>
            <div><?= e($c['title']) ?></div>
            <div class="small muted"><?= (int)$c['unit'] ?> units • <?= e($c['semester']) ?></div>
          </div>
        </label>
      <?php endforeach; ?>
    </div>
    <button class="btn btn-primary" type="submit">Complete enrollment</button>
  </form>
</div>
