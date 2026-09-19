<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="csrf-token" content="<?= e(App\Security::csrfToken()) ?>">
  <title><?= e($title ?? 'Admin') ?> | TSU-SAMS</title>
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
      <small>Administrator</small>
      <strong>TSU Registry Terminal</strong>
    </div>
  </div>
  <a class="btn btn-outline btn-sm" href="<?= e(base_url('logout')) ?>">Sign out</a>
</header>
<div class="page page-wide">
  <div class="desktop-split">
    <nav class="side-nav">
      <div class="sec">Overview</div>
      <a class="<?= ($nav ?? '') === 'dashboard' ? 'active' : '' ?>" href="<?= e(base_url('admin')) ?>"><i class="bi bi-grid-1x2"></i> Dashboard</a>
      <div class="sec">Identity</div>
      <a class="<?= ($nav ?? '') === 'students' ? 'active' : '' ?>" href="<?= e(base_url('admin/students')) ?>"><i class="bi bi-mortarboard"></i> Students</a>
      <a class="<?= ($nav ?? '') === 'faces' ? 'active' : '' ?>" href="<?= e(base_url('admin/faces')) ?>"><i class="bi bi-person-bounding-box"></i> Facial Records</a>
      <a class="<?= ($nav ?? '') === 'lecturers' ? 'active' : '' ?>" href="<?= e(base_url('admin/lecturers')) ?>"><i class="bi bi-person-workspace"></i> Lecturers</a>
      <div class="sec">Campus</div>
      <a class="<?= ($nav ?? '') === 'venues' ? 'active' : '' ?>" href="<?= e(base_url('admin/venues')) ?>"><i class="bi bi-geo-alt"></i> Venues</a>
      <a class="<?= ($nav ?? '') === 'payments' ? 'active' : '' ?>" href="<?= e(base_url('admin/payments')) ?>"><i class="bi bi-cash-stack"></i> Payments</a>
      <a class="<?= ($nav ?? '') === 'reports' ? 'active' : '' ?>" href="<?= e(base_url('admin/reports')) ?>"><i class="bi bi-graph-up"></i> Reports</a>
      <a class="<?= ($nav ?? '') === 'logs' ? 'active' : '' ?>" href="<?= e(base_url('admin/logs')) ?>"><i class="bi bi-shield-check"></i> Audit Logs</a>
      <div class="sec">Account</div>
      <a class="<?= ($nav ?? '') === 'settings' ? 'active' : '' ?>" href="<?= e(base_url('admin/settings')) ?>"><i class="bi bi-sliders"></i> Settings</a>
      <a class="<?= ($nav ?? '') === 'profile' ? 'active' : '' ?>" href="<?= e(base_url('admin/profile')) ?>"><i class="bi bi-person-gear"></i> Profile</a>
    </nav>
    <div>
      <?php include config('paths.views') . '/partials/flash.php'; ?>
      <?= $content ?>
    </div>
  </div>
</div>
<nav class="bottom-nav">
  <a class="<?= ($nav ?? '') === 'dashboard' ? 'active' : '' ?>" href="<?= e(base_url('admin')) ?>"><i class="bi bi-grid"></i>Home</a>
  <a class="<?= ($nav ?? '') === 'students' ? 'active' : '' ?>" href="<?= e(base_url('admin/students')) ?>"><i class="bi bi-mortarboard"></i>Students</a>
  <a class="<?= ($nav ?? '') === 'venues' ? 'active' : '' ?>" href="<?= e(base_url('admin/venues')) ?>"><i class="bi bi-geo-alt"></i>Venues</a>
  <a class="<?= ($nav ?? '') === 'reports' ? 'active' : '' ?>" href="<?= e(base_url('admin/reports')) ?>"><i class="bi bi-graph-up"></i>Reports</a>
</nav>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(base_url('assets/js/app.js')) ?>"></script>
<script src="<?= e(base_url('assets/js/geo.js')) ?>"></script>
</body>
</html>
