<h1 class="h4">Create attendance session</h1>
<div class="card">
  <form method="post" action="<?= e(base_url('lecturer/sessions')) ?>">
    <?= csrf_field() ?>
    <div class="mb-3">
      <label class="form-label">Course</label>
      <select class="form-select" name="course_id" required>
        <?php foreach ($courses as $c): ?>
          <option value="<?= (int)$c['id'] ?>"><?= e($c['code']) ?> — <?= e($c['title']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label">Venue</label>
      <select class="form-select" name="venue_id" required>
        <?php foreach ($venues as $v): ?>
          <option value="<?= (int)$v['id'] ?>"><?= e($v['name']) ?> (<?= (int)$v['radius'] ?>m)</option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label">Date</label>
      <input class="form-control" type="date" name="session_date" required value="<?= e(date('Y-m-d')) ?>">
    </div>
    <div class="grid-2 mb-3">
      <div>
        <label class="form-label">Start time</label>
        <input class="form-control" type="time" name="start_time" required>
      </div>
      <div>
        <label class="form-label">End time</label>
        <input class="form-control" type="time" name="end_time" required>
      </div>
    </div>
    <button class="btn btn-primary" type="submit">Create session</button>
  </form>
</div>
