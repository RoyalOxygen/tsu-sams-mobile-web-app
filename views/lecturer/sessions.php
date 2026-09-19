<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Attendance sessions</h1>
  <a class="btn btn-primary btn-sm" href="<?= e(base_url('lecturer/sessions/create')) ?>">Create session</a>
</div>
<div class="card">
  <?php foreach ($rows as $r): ?>
    <div class="list-item">
      <div>
        <strong><?= e($r['code']) ?> • <?= e($r['title']) ?></strong>
        <div class="small muted"><?= e(format_date($r['session_date'])) ?> • <?= e(format_time($r['start_time'])) ?>–<?= e(format_time($r['end_time'])) ?> • <?= e($r['venue_name']) ?></div>
        <div class="small muted"><?= (int)$r['present_count'] ?> present</div>
        <form method="post" action="<?= e(base_url('lecturer/sessions/' . $r['id'])) ?>" class="mt-2 d-flex gap-2 flex-wrap">
          <?= csrf_field() ?>
          <?php if ($r['status'] === 'scheduled' || $r['status'] === 'closed'): ?>
            <button class="btn btn-success btn-sm" name="action" value="<?= $r['status'] === 'closed' ? 'reopen' : 'open' ?>" type="submit"><?= $r['status'] === 'closed' ? 'Reopen' : 'Open' ?></button>
          <?php endif; ?>
          <?php if ($r['status'] === 'open'): ?>
            <button class="btn btn-danger btn-sm" name="action" value="close" type="submit">Close</button>
          <?php endif; ?>
          <a class="btn btn-outline btn-sm" href="<?= e(base_url('lecturer/records?session_id=' . $r['id'])) ?>">Records</a>
        </form>
      </div>
      <div><?= status_badge($r['status']) ?></div>
    </div>
  <?php endforeach; ?>
  <?php if (!$rows): ?><p class="muted mb-0">No sessions yet.</p><?php endif; ?>
</div>
