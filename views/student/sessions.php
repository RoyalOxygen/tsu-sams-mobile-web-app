<h1 class="h4">Active attendance sessions</h1>
<p class="muted">Select a lecture. GPS and face checks run on the next screen.</p>
<?php if (!$active): ?>
  <div class="card">No open sessions for your registered courses.</div>
<?php endif; ?>
<?php foreach ($active as $a): ?>
  <a class="card d-block text-decoration-none" href="<?= e(base_url('student/attendance/' . $a['id'])) ?>">
    <div class="d-flex justify-content-between">
      <span class="badge badge-success pulse">OPEN</span>
      <span class="small muted"><?= e(format_time($a['start_time'])) ?> – <?= e(format_time($a['end_time'])) ?></span>
    </div>
    <h2 class="h5 mt-2 mb-1"><?= e($a['code']) ?>: <?= e($a['title']) ?></h2>
    <p class="muted mb-0"><i class="bi bi-geo-alt"></i> <?= e($a['venue_name']) ?> • <?= (int)$a['radius'] ?>m radius</p>
  </a>
<?php endforeach; ?>
