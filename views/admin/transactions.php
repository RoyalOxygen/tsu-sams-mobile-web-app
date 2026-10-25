<h1 class="h4">Transactions</h1>
<div class="card">
  <div class="table-wrap">
    <table class="data">
      <thead><tr><th>Reference</th><th>Student</th><th>Type</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td class="font-tabular"><?= e($r['reference']) ?></td>
          <td><?= e($r['matric_no']) ?></td>
          <td><?= e($r['type']) ?></td>
          <td><?= e(money_ngn((float)$r['amount'])) ?></td>
          <td><?= status_badge($r['status']) ?></td>
          <td><?= e($r['created_at']) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="6">No transactions.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
