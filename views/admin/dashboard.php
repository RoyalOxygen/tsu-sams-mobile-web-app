<div class="hero">
  <div class="d-flex justify-content-between">
    <div>
      <h1 class="h4 mb-1"><?= e($user['full_name'] ?? 'Administrator') ?></h1>
      <p class="mb-0">Registrar &amp; Chief Systems Administrator</p>
    </div>
    <span class="badge badge-success"><?= e(setting('semester','Harmattan')) ?> • <?= e(setting('academic_session','2024/2025')) ?></span>
  </div>
</div>
<div class="grid-4 mb-3">
  <div class="metric"><div class="n"><?= (int)$counts['students'] ?></div><div class="l">Total students</div></div>
  <div class="metric"><div class="n"><?= (int)$counts['lecturers'] ?></div><div class="l">Total lecturers</div></div>
  <div class="metric"><div class="n"><?= (int)$counts['courses'] ?></div><div class="l">Total courses</div></div>
  <div class="metric"><div class="n"><?= (int)$counts['venues'] ?></div><div class="l">Total venues</div></div>
</div>
<div class="grid-3 mb-3">
  <div class="metric"><div class="n"><?= (int)$daily ?></div><div class="l">Daily attendance</div></div>
  <div class="metric"><div class="n"><?= (int)$monthly ?></div><div class="l">Monthly attendance</div></div>
  <div class="metric"><div class="n font-tabular" style="font-size:18px"><?= e(money_ngn($revenue)) ?></div><div class="l">Revenue (paid)</div></div>
</div>
<div class="card">
  <h2 class="h6">7-day attendance</h2>
  <div class="d-flex align-items-end gap-2" style="height:120px">
    <?php $max = max(1, ...array_column($dailyTrend, 'count')); foreach ($dailyTrend as $d): $h = (int)(90 * $d['count'] / $max); ?>
      <div class="text-center flex-fill">
        <div class="mx-auto rounded-1" style="height:<?= $h ?>px;background:#0d7a53;width:70%"></div>
        <div class="small muted mt-1"><?= e($d['date']) ?></div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<div class="grid-2">
  <div class="card mb-0">
    <h2 class="h6">Identity health</h2>
    <p class="mb-1">Face enrolled: <strong><?= (int)$counts['enrolled_faces'] ?></strong></p>
    <p class="mb-0">Pending lecturers: <strong><?= (int)$counts['pending_lecturers'] ?></strong>
      <?php if ($counts['pending_lecturers']): ?>
        <a href="<?= e(base_url('admin/lecturers')) ?>">Review</a>
      <?php endif; ?>
    </p>
  </div>
  <div class="card mb-0">
    <h2 class="h6">Recent payments</h2>
    <?php foreach ($recentPay as $p): ?>
      <div class="list-item">
        <div>
          <strong class="font-tabular"><?= e($p['reference']) ?></strong>
          <div class="small muted"><?= e($p['matric_no']) ?> • <?= e($p['type']) ?></div>
        </div>
        <?= status_badge($p['status']) ?>
      </div>
    <?php endforeach; ?>
    <?php if (!$recentPay): ?><p class="muted mb-0">No transactions.</p><?php endif; ?>
  </div>
</div>
