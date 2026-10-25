<h1 class="h4">Attendance records</h1>
<form class="card" method="get" action="<?= e(base_url('lecturer/records')) ?>">
  <label class="form-label">Session</label>
  <select class="form-select mb-2" name="session_id" onchange="this.form.submit()">
    <option value="">Select session</option>
    <?php foreach ($sessions as $s): ?>
      <option value="<?= (int)$s['id'] ?>" <?= ($current && (int)$current['id'] === (int)$s['id']) ? 'selected' : '' ?>>
        <?= e($s['code']) ?> • <?= e($s['session_date']) ?> • <?= e($s['status']) ?>
      </option>
    <?php endforeach; ?>
  </select>
</form>
<?php if ($current): ?>
  <div class="d-flex gap-2 mb-3 flex-wrap">
    <a class="btn btn-outline btn-sm" href="<?= e(base_url('lecturer/export?session_id=' . $current['id'] . '&format=csv')) ?>">CSV</a>
    <a class="btn btn-outline btn-sm" href="<?= e(base_url('lecturer/export?session_id=' . $current['id'] . '&format=excel')) ?>">Excel</a>
    <a class="btn btn-outline btn-sm" href="<?= e(base_url('lecturer/export?session_id=' . $current['id'] . '&format=pdf')) ?>">PDF</a>
  </div>
  <div class="card">
    <p class="mb-2"><strong><?= e($current['code']) ?></strong> • <?= e($current['venue_name']) ?></p>
    <div class="table-wrap">
      <table class="data">
        <thead><tr><th>Matric</th><th>Name</th><th>Status</th><th>Time</th><th>Distance</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td class="font-tabular"><?= e($r['matric_no']) ?></td>
            <td><?= e($r['first_name'] . ' ' . $r['last_name']) ?></td>
            <td><?= status_badge($r['status']) ?></td>
            <td class="font-tabular"><?= e($r['recorded_at']) ?></td>
            <td><?= e((string)$r['distance_meters']) ?>m</td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="5">No check-ins yet.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>
