<div class="welcome-wrap">
  <h1 class="h4 fw-bold">Lecturer registration</h1>
  <p class="muted">Accounts require administrator approval before sign-in.</p>
  <?php include config('paths.views') . '/partials/flash.php'; ?>
  <div class="card">
    <form method="post" action="<?= e(base_url('lecturer/register')) ?>">
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label">Staff number</label>
        <input class="form-control font-tabular" name="staff_no" required placeholder="TSU/STF/18/0429">
      </div>
      <div class="mb-3">
        <label class="form-label">Full name</label>
        <input class="form-control" name="full_name" required>
      </div>
      <div class="grid-2 mb-3">
        <div>
          <label class="form-label">Title</label>
          <input class="form-control" name="title" value="Dr.">
        </div>
        <div>
          <label class="form-label">Rank</label>
          <input class="form-control" name="rank" value="Lecturer I">
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label">Email</label>
        <input class="form-control" type="email" name="email" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Phone</label>
        <input class="form-control" name="phone">
      </div>
      <div class="mb-3">
        <label class="form-label">Faculty</label>
        <select class="form-select" name="faculty_id" required>
          <?php foreach ($faculties as $f): ?>
            <option value="<?= (int)$f['id'] ?>"><?= e($f['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="mb-3">
        <label class="form-label">Department</label>
        <select class="form-select" name="department_id" required>
          <?php foreach ($departments as $d): ?>
            <option value="<?= (int)$d['id'] ?>"><?= e($d['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="mb-3">
        <label class="form-label">Password (min 8 characters)</label>
        <input class="form-control" type="password" name="password" required minlength="8">
      </div>
      <button class="btn btn-primary" type="submit">Submit for approval</button>
    </form>
  </div>
  <p class="footer-note"><a href="<?= e(base_url('login?role=lecturer')) ?>">Already registered? Sign in</a></p>
</div>
