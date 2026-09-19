<?php $passport = student_passport_url($student); ?>
<div class="card">
  <div class="d-flex justify-content-between gap-3 align-items-center">
    <div>
      <div class="small text-success text-uppercase fw-semibold">Semester ledger • <?= e(setting('academic_session','2024/2025')) ?></div>
      <h1 class="h4 mb-1">Hello, <?= e($student['first_name'] . ' ' . $student['last_name']) ?></h1>
      <p class="font-tabular muted mb-1"><?= e($student['matric_no']) ?></p>
      <p class="small muted mb-0"><i class="bi bi-mortarboard"></i> <?= e($student['department_name']) ?> (<?= (int)$student['level'] ?>L)</p>
    </div>
    <?php if ($passport): ?>
      <img src="<?= e($passport) ?>" alt="Profile picture of <?= e(student_full_name($student)) ?>" class="avatar avatar-lg" loading="lazy">
    <?php else: ?>
      <div class="avatar avatar-lg avatar-initials" role="img" aria-label="Profile picture placeholder for <?= e(student_full_name($student)) ?>"><?= e(student_initials($student)) ?></div>
    <?php endif; ?>
  </div>
</div>

<?php if ($active): $a = $active[0]; ?>
<div class="hero">
  <div class="d-flex justify-content-between mb-2">
    <span class="badge badge-success pulse">Live session detected</span>
    <span class="small">Ends <?= e(format_time($a['end_time'])) ?></span>
  </div>
  <h2 class="h5"><?= e($a['code']) ?>: <?= e($a['title']) ?></h2>
  <p class="mb-3"><i class="bi bi-geo-alt"></i> <?= e($a['venue_name']) ?>, <?= e($a['building_name']) ?></p>
  <a class="btn btn-success" href="<?= e(base_url('student/attendance/' . $a['id'])) ?>"><i class="bi bi-person-bounding-box"></i> Check in now</a>
</div>
<?php else: ?>
<div class="card"><p class="mb-0 muted">No live attendance session for your registered courses right now.</p></div>
<?php endif; ?>

<div class="card">
  <div class="d-flex justify-content-between align-items-center">
    <div>
      <div class="small muted">Overall attendance rate</div>
      <div class="h3 font-tabular mb-1" style="color:#0f2942"><?= e((string)$stats['percent']) ?>%</div>
      <span class="badge <?= $stats['percent'] >= 75 ? 'badge-success' : 'badge-danger' ?>">
        <?= $stats['percent'] >= 75 ? 'Exceeds 75% exam threshold' : 'Below exam threshold' ?>
      </span>
    </div>
    <div class="text-end">
      <div class="small muted">Present</div>
      <strong class="font-tabular"><?= (int)$stats['present'] ?>/<?= (int)$stats['total'] ?></strong>
    </div>
  </div>
  <div class="progress mt-3"><span style="width: <?= min(100, (float)$stats['percent']) ?>%"></span></div>
</div>

<div class="grid-2">
  <a class="card mb-0 text-decoration-none" href="<?= e(base_url('student/attendance')) ?>">
    <i class="bi bi-broadcast text-success"></i>
    <strong class="d-block mt-1">Take attendance</strong>
    <span class="small muted">Active lectures</span>
  </a>
  <a class="card mb-0 text-decoration-none" href="<?= e(base_url('student/history')) ?>">
    <i class="bi bi-clock-history"></i>
    <strong class="d-block mt-1">History</strong>
    <span class="small muted">Past check-ins</span>
  </a>
  <a class="card mb-0 text-decoration-none" href="<?= e(base_url('student/courses')) ?>">
    <i class="bi bi-journal-plus text-success"></i>
    <strong class="d-block mt-1">Register courses</strong>
    <span class="small muted">Add courses to your registration</span>
  </a>
  <a class="card mb-0 text-decoration-none" href="<?= e(base_url('student/face')) ?>">
    <i class="bi bi-person-bounding-box text-success"></i>
    <strong class="d-block mt-1">Update face</strong>
    <span class="small muted">Recapture biometric template</span>
  </a>
</div>

<div class="card mt-3">
  <h2 class="h6">Recent check-ins</h2>
  <?php if (!$recent): ?>
    <p class="muted mb-0">No attendance recorded yet.</p>
  <?php endif; ?>
  <?php foreach ($recent as $r): ?>
    <div class="list-item">
      <div>
        <strong><?= e($r['code']) ?></strong>
        <div class="small muted"><?= e(format_date($r['session_date'])) ?> • <?= e($r['recorded_at']) ?></div>
      </div>
      <div class="ms-auto"><?= status_badge($r['status']) ?></div>
    </div>
  <?php endforeach; ?>
</div>
