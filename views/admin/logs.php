<h1 class="h4">Audit logs</h1>
<div class="card">
  <div class="table-wrap">
    <table class="data">
      <thead><tr><th>Time</th><th>User</th><th>Action</th><th>Entity</th><th>IP</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td class="font-tabular"><?= e($r['created_at']) ?></td>
          <td><?= e($r['full_name'] ?? 'system') ?></td>
          <td><?= e($r['action']) ?></td>
          <td><?= e(($r['entity'] ?? '') . ($r['entity_id'] ? '#' . $r['entity_id'] : '')) ?></td>
          <td class="font-tabular"><?= e($r['ip_address'] ?? '') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
