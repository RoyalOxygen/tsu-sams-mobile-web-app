<h1 class="h4">Attendance statistics</h1>
<div class="card">
  <div class="h3 font-tabular mb-0"><?= e((string)$stats['percent']) ?>%</div>
  <p class="muted">Overall • threshold <?= (int)$threshold ?>%</p>
  <div class="progress"><span style="width:<?= min(100,(float)$stats['percent']) ?>%"></span></div>
</div>
<div class="card">
  <h2 class="h6">By course</h2>
  <?php foreach ($courses as $c):
    $tot = max(1, (int)$c['total_sessions']);
    $pct = round(((int)$c['present_count'] / $tot) * 100, 1);
  ?>
    <div class="mb-3">
      <div class="d-flex justify-content-between">
        <strong><?= e($c['code']) ?></strong>
        <span class="font-tabular"><?= $pct ?>%</span>
      </div>
      <div class="small muted"><?= e($c['title']) ?> • <?= (int)$c['present_count'] ?>/<?= (int)$c['total_sessions'] ?></div>
      <div class="progress mt-1"><span style="width:<?= min(100,$pct) ?>%"></span></div>
    </div>
  <?php endforeach; ?>
  <?php if (!$courses): ?><p class="muted mb-0">No registered courses.</p><?php endif; ?>
</div>
