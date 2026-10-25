<div class="welcome-wrap">
  <p class="small text-muted mb-1">Step 2 of 5</p>
  <h1 class="h4 fw-bold">Confirm identity</h1>
  <?php include config('paths.views') . '/partials/flash.php'; ?>
  <div class="card">
    <div class="d-flex justify-content-between align-items-start gap-3">
      <div>
        <div class="text-muted small">Matric</div>
        <strong class="font-tabular"><?= e($student['matric_no']) ?></strong>
        <h2 class="h5 mt-2 mb-1"><?= e(student_full_name($student)) ?></h2>
        <p class="muted mb-0"><?= e($student['faculty_name'] ?? '') ?></p>
        <p class="muted mb-0"><?= e($student['department_name'] ?? '') ?> • <?= e((string)$student['level']) ?>L</p>
        <?php /* <p class="small muted mb-0"><?= e($student['programme']) ?></p> */ ?>
        <?php /* <p class="small muted mb-0"><?= e($student['state_of_origin']) ?></p> */ ?>
      </div>
      <?php /* $passport = student_passport_url($student) ?: ($student['cms_photo_url'] ?? null); ?>
      <?php if ($passport): ?>
        <img src="<?= e($passport) ?>" alt="Passport" class="avatar avatar-lg">
      <?php else: ?>
        <div class="avatar avatar-lg avatar-initials"><?= e(student_initials($student)) ?></div>
      <?php endif; */ ?>
    </div>
    <div class="mt-3">
      <span class="badge <?= ($source ?? '') === 'cms' ? 'badge-success' : 'badge-warn' ?>"><?= ($source ?? '') === 'cms' ? 'PRELOADED' : 'LOCAL RECORD' ?></span>
      <?php if (!empty($already_enrolled)): ?>
        <span class="badge badge-muted">FACE ON FILE</span>
      <?php endif; ?>
    </div>
  </div>

  <?php /* <div class="card">
    <h2 class="h6">Payment history</h2>
    <div class="table-wrap">
      <table class="data">
        <thead>
          <tr>
            <th>Session</th>
            <th>Level</th>
            <th>Amount</th>
            <th>Sem</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($payments as $p): ?>
            <tr>
              <td><?= e((string)($p['session'] ?? '')) ?></td>
              <td><?= e((string)($p['level'] ?? '')) ?></td>
              <td class="font-tabular"><?= e((string)($p['amount'] ?? '')) ?></td>
              <td><?= e((string)($p['semester'] ?? '')) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div> */ ?>

  <form method="post" action="<?= e(base_url(exam_path('confirm'))) ?>" class="mb-2">
    <?= csrf_field() ?>
    <input type="hidden" name="decision" value="yes">
    <button class="btn btn-success" type="submit">It's me</button>
  </form>
  <form method="post" action="<?= e(base_url(exam_path('confirm'))) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="decision" value="no">
    <button class="btn btn-outline" type="submit">Not me</button>
  </form>
</div>
