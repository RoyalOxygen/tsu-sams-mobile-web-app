<div class="d-flex justify-content-between align-items-center mb-1">
  <h1 class="h4 mb-0">Course registration</h1>
  <span class="badge badge-success"><?= e($semester) ?> <?= e($session) ?></span>
</div>
<p class="muted"><?= e($student['department_name']) ?> • <?= (int)$student['level'] ?>L. Add courses to your registration for this semester.</p>

<div class="card">
  <h2 class="h6">Registered courses</h2>
  <?php if (!$registered): ?>
    <p class="muted mb-0">You have not registered any course for this semester yet.</p>
  <?php endif; ?>
  <?php foreach ($registered as $r): ?>
    <div class="list-item">
      <div>
        <strong><?= e($r['code']) ?></strong>
        <div><?= e($r['title']) ?></div>
        <div class="small muted"><?= (int)$r['unit'] ?> units • registered <?= e(format_date($r['registered_at'])) ?></div>
      </div>
      <div class="ms-auto"><span class="badge badge-success">Registered</span></div>
    </div>
  <?php endforeach; ?>
</div>

<?php
$addable = array_values(array_filter($available, static fn(array $c): bool => !in_array((int)$c['id'], $registeredIds, true)));
?>
<div class="card">
  <h2 class="h6">Add courses</h2>
  <?php if (!$addable): ?>
    <p class="muted mb-0">
      <?php if ($available): ?>
        You are registered for every course published for your department and level this semester.
      <?php else: ?>
        No courses are published for your faculty, department, level and semester yet. Contact your course adviser.
      <?php endif; ?>
    </p>
  <?php else: ?>
    <form method="post" action="<?= e(base_url('student/courses')) ?>">
      <?= csrf_field() ?>
      <?php foreach ($addable as $c): ?>
        <label class="list-item">
          <input class="form-check-input mt-1" type="checkbox" name="courses[]" value="<?= (int)$c['id'] ?>">
          <div>
            <strong><?= e($c['code']) ?></strong>
            <div><?= e($c['title']) ?></div>
            <div class="small muted"><?= (int)$c['unit'] ?> units • <?= e($c['semester']) ?></div>
          </div>
        </label>
      <?php endforeach; ?>
      <button class="btn btn-primary mt-2" type="submit"><i class="bi bi-plus-lg"></i> Add selected courses</button>
    </form>
  <?php endif; ?>
</div>
