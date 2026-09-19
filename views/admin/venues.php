<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Venues</h1>
  <a class="btn btn-primary btn-sm" href="<?= e(base_url('admin/venues/create')) ?>">Create venue</a>
</div>
<div class="card">
  <?php foreach ($rows as $r): ?>
    <div class="list-item">
      <div>
        <strong><?= e($r['name']) ?></strong>
        <div class="small muted"><?= e($r['building_name']) ?></div>
        <div class="small font-tabular muted"><?= e((string)$r['latitude']) ?>, <?= e((string)$r['longitude']) ?> • <?= (int)$r['radius'] ?>m</div>
      </div>
      <a class="btn btn-outline btn-sm" href="<?= e(base_url('admin/venues/' . $r['id'] . '/edit')) ?>">Edit</a>
    </div>
  <?php endforeach; ?>
</div>
