<h1 class="h4">Attendance history</h1>
<div class="card">
  <?php if (!$rows): ?>
    <p class="muted mb-0">No records yet.</p>
  <?php endif; ?>
  <?php foreach ($rows as $r): ?>
    <div class="list-item">
      <div>
        <strong><?= e($r['code']) ?> • <?= e($r['title']) ?></strong>
        <div class="small muted"><?= e(format_date($r['session_date'])) ?> • <?= e($r['venue_name']) ?></div>
        <div class="small muted font-tabular"><?= e($r['recorded_at']) ?> • <?= e((string)$r['distance_meters']) ?>m</div>
      </div>
      <div class="ms-auto"><?= status_badge($r['status']) ?></div>
    </div>
  <?php endforeach; ?>
</div>
