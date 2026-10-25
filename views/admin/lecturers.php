<h1 class="h4">Lecturer management</h1>
<div class="card">
  <?php foreach ($rows as $r): ?>
    <div class="list-item">
      <div>
        <strong><?= e(($r['title'] ? $r['title'] . ' ' : '') . $r['full_name']) ?></strong>
        <div class="small font-tabular muted"><?= e($r['staff_no']) ?></div>
        <div class="small muted"><?= e($r['department_name']) ?> • <?= e($r['rank'] ?? '') ?></div>
        <?= status_badge($r['status']) ?>
      </div>
      <div class="d-flex flex-column gap-1">
      <a class="btn btn-outline btn-sm" href="<?= e(base_url('admin/lecturers/' . (int)$r['id'] . '/edit')) ?>">Edit</a>
      <form method="post" action="<?= e(base_url('admin/lecturers')) ?>" class="d-flex flex-column gap-1">
        <?= csrf_field() ?>
        <input type="hidden" name="user_id" value="<?= (int)$r['user_id'] ?>">
        <?php if ($r['status'] === 'pending'): ?>
          <button class="btn btn-success btn-sm" name="action" value="approve" type="submit">Approve</button>
          <button class="btn btn-danger btn-sm" name="action" value="reject" type="submit">Reject</button>
        <?php elseif ($r['status'] === 'active'): ?>
          <button class="btn btn-danger btn-sm" name="action" value="suspend" type="submit">Suspend</button>
        <?php else: ?>
          <button class="btn btn-success btn-sm" name="action" value="activate" type="submit">Activate</button>
        <?php endif; ?>
      </form>
      </div>
    </div>
  <?php endforeach; ?>
</div>
