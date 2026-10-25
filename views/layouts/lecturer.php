<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="csrf-token" content="<?= e(App\Security::csrfToken()) ?>">
  <title><?= e($title ?? 'Lecturer') ?> | TSU-SAMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?= e(base_url('assets/css/app.css')) ?>" rel="stylesheet">
</head>
<body>
<header class="topbar">
  <div class="brand">
    <?= brand_logo(40) ?>
    <div>
      <small>Faculty Console</small>
      <strong>TSU-SAMS Lecturer</strong>
    </div>
  </div>
  <a class="btn btn-outline btn-sm" href="<?= e(base_url('logout')) ?>">Sign out</a>
</header>
<div class="page page-wide">
  <div class="desktop-split">
    <nav class="side-nav">
      <div class="sec">Lecturer</div>
      <a class="<?= ($nav ?? '') === 'dashboard' ? 'active' : '' ?>" href="<?= e(base_url('lecturer')) ?>"><i class="bi bi-speedometer2"></i> Dashboard</a>
      <a class="<?= ($nav ?? '') === 'courses' ? 'active' : '' ?>" href="<?= e(base_url('lecturer/courses')) ?>"><i class="bi bi-journal-text"></i> Courses</a>
      <a class="<?= ($nav ?? '') === 'sessions' ? 'active' : '' ?>" href="<?= e(base_url('lecturer/sessions')) ?>"><i class="bi bi-broadcast"></i> Sessions</a>
      <a class="<?= ($nav ?? '') === 'records' ? 'active' : '' ?>" href="<?= e(base_url('lecturer/records')) ?>"><i class="bi bi-clipboard-data"></i> Records</a>
      <a class="<?= ($nav ?? '') === 'profile' ? 'active' : '' ?>" href="<?= e(base_url('lecturer/profile')) ?>"><i class="bi bi-person-badge"></i> Profile</a>
    </nav>
    <div>
      <?php include config('paths.views') . '/partials/flash.php'; ?>
      <?= $content ?>
    </div>
  </div>
</div>
<nav class="bottom-nav">
  <a class="<?= ($nav ?? '') === 'dashboard' ? 'active' : '' ?>" href="<?= e(base_url('lecturer')) ?>"><i class="bi bi-speedometer2"></i>Home</a>
  <a class="<?= ($nav ?? '') === 'courses' ? 'active' : '' ?>" href="<?= e(base_url('lecturer/courses')) ?>"><i class="bi bi-journal-text"></i>Courses</a>
  <a class="<?= ($nav ?? '') === 'sessions' ? 'active' : '' ?>" href="<?= e(base_url('lecturer/sessions')) ?>"><i class="bi bi-broadcast"></i>Sessions</a>
  <a class="<?= ($nav ?? '') === 'records' ? 'active' : '' ?>" href="<?= e(base_url('lecturer/records')) ?>"><i class="bi bi-clipboard-data"></i>Records</a>
</nav>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(base_url('assets/js/app.js')) ?>"></script>
</body>
</html>
