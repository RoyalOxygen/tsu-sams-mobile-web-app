<div class="hero">
  <div class="d-flex justify-content-between">
    <div>
      <h1 class="h4 mb-1"><?= e(($lecturer['title'] ? $lecturer['title'] . ' ' : '') . $lecturer['full_name']) ?></h1>
      <p class="font-tabular mb-0"><?= e($lecturer['staff_no']) ?></p>
      <p class="small mb-0"><?= e($lecturer['rank']) ?> • <?= e($lecturer['department_name']) ?></p>
    </div>
    <span class="badge badge-success">Online</span>
  </div>
</div>

<?php if ($open): foreach ($open as $o): ?>
<div class="card" style="border-left:4px solid #0d7a53">
  <div class="d-flex justify-content-between">
    <span class="badge badge-success pulse">Live session</span>
    <span class="small muted"><?= (int)$o['present_count'] ?>/<?= (int)$o['enrolled'] ?> present</span>
  </div>
  <h2 class="h5 mt-2"><?= e($o['code']) ?>: <?= e($o['title']) ?></h2>
  <p class="muted mb-2"><i class="bi bi-geo-alt"></i> <?= e($o['venue_name']) ?></p>
  <form method="post" action="<?= e(base_url('lecturer/sessions/' . $o['id'])) ?>" class="d-inline">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="close">
    <button class="btn btn-danger btn-sm" type="submit">Close attendance</button>
  </form>
  <a class="btn btn-outline btn-sm" href="<?= e(base_url('lecturer/records?session_id=' . $o['id'])) ?>">View records</a>
</div>
<?php endforeach; else: ?>
<div class="card">No live session. Create or open one from Sessions.</div>
<?php endif; ?>

<div class="grid-2">
  <div class="metric">
    <div class="n"><?= count($courses) ?></div>
    <div class="l">My courses</div>
  </div>
  <div class="metric">
    <div class="n"><?= count($upcoming) ?></div>
    <div class="l">Scheduled sessions</div>
  </div>
</div>

<div class="card mt-3">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <h2 class="h6 mb-0">Upcoming</h2>
    <a href="<?= e(base_url('lecturer/sessions/create')) ?>">New session</a>
  </div>
  <?php foreach ($upcoming as $u): ?>
    <div class="list-item">
      <div>
        <strong><?= e($u['code']) ?></strong>
        <div class="small muted"><?= e(format_date($u['session_date'])) ?> • <?= e(format_time($u['start_time'])) ?> • <?= e($u['venue_name']) ?></div>
      </div>
      <?= status_badge($u['status']) ?>
    </div>
  <?php endforeach; ?>
</div>
