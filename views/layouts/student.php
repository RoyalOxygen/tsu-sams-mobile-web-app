<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="csrf-token" content="<?= e(App\Security::csrfToken()) ?>">
  <title><?= e($title ?? 'Student') ?> | TSU-SAMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?= e(base_url('assets/css/app.css')) ?>" rel="stylesheet">
</head>
<body>
<?php $navStudent = App\Auth::student(); ?>
<header class="topbar">
  <div class="brand">
    <?= brand_logo(40) ?>
    <div>
      <small>Student Portal</small>
      <strong>TSU-SAMS</strong>
    </div>
  </div>
  <div class="d-flex align-items-center gap-2">
    <?php if ($navStudent): ?>
      <a href="<?= e(base_url('student/profile')) ?>" class="text-decoration-none" title="<?= e(student_full_name($navStudent)) ?>">
        <?php $navPassport = student_passport_url($navStudent); ?>
        <?php if ($navPassport): ?>
          <img src="<?= e($navPassport) ?>" alt="Profile picture" class="avatar avatar-sm">
        <?php else: ?>
          <span class="avatar avatar-sm avatar-initials"><?= e(student_initials($navStudent)) ?></span>
        <?php endif; ?>
      </a>
    <?php endif; ?>
    <a class="btn btn-outline btn-sm" href="<?= e(base_url('logout')) ?>">Sign out</a>
  </div>
</header>
<div class="page">
  <div class="desktop-split">
    <nav class="side-nav">
      <div class="sec">Student</div>
      <a class="<?= ($nav ?? '') === 'dashboard' ? 'active' : '' ?>" href="<?= e(base_url('student')) ?>"><i class="bi bi-grid"></i> Dashboard</a>
      <a class="<?= ($nav ?? '') === 'courses' ? 'active' : '' ?>" href="<?= e(base_url('student/courses')) ?>"><i class="bi bi-journal-plus"></i> Register Courses</a>
      <a class="<?= ($nav ?? '') === 'attend' ? 'active' : '' ?>" href="<?= e(base_url('student/attendance')) ?>"><i class="bi bi-person-bounding-box"></i> Take Attendance</a>
      <a class="<?= ($nav ?? '') === 'history' ? 'active' : '' ?>" href="<?= e(base_url('student/history')) ?>"><i class="bi bi-clock-history"></i> History</a>
      <a class="<?= ($nav ?? '') === 'stats' ? 'active' : '' ?>" href="<?= e(base_url('student/statistics')) ?>"><i class="bi bi-bar-chart"></i> Statistics</a>
      <a class="<?= ($nav ?? '') === 'profile' ? 'active' : '' ?>" href="<?= e(base_url('student/profile')) ?>"><i class="bi bi-person"></i> Profile</a>
    </nav>
    <div>
      <?php include config('paths.views') . '/partials/flash.php'; ?>
      <?= $content ?>
    </div>
  </div>
</div>
<nav class="bottom-nav">
  <a class="<?= ($nav ?? '') === 'dashboard' ? 'active' : '' ?>" href="<?= e(base_url('student')) ?>"><i class="bi bi-grid"></i>Home</a>
  <a class="<?= ($nav ?? '') === 'courses' ? 'active' : '' ?>" href="<?= e(base_url('student/courses')) ?>"><i class="bi bi-journal-plus"></i>Courses</a>
  <a class="<?= ($nav ?? '') === 'attend' ? 'active' : '' ?>" href="<?= e(base_url('student/attendance')) ?>"><i class="bi bi-face-id"></i>Attend</a>
  <a class="<?= ($nav ?? '') === 'history' ? 'active' : '' ?>" href="<?= e(base_url('student/history')) ?>"><i class="bi bi-clock-history"></i>History</a>
  <a class="<?= ($nav ?? '') === 'stats' ? 'active' : '' ?>" href="<?= e(base_url('student/statistics')) ?>"><i class="bi bi-bar-chart"></i>Stats</a>
  <a class="<?= ($nav ?? '') === 'profile' ? 'active' : '' ?>" href="<?= e(base_url('student/profile')) ?>"><i class="bi bi-person"></i>Profile</a>
</nav>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(base_url('assets/js/app.js')) ?>"></script>
<script src="<?= e(base_url('assets/js/geo.js')) ?>"></script>
</body>
</html>
