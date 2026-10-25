<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">My courses</h1>
  <a class="btn btn-primary btn-sm" href="<?= e(base_url('lecturer/courses/create')) ?>">Create course</a>
</div>
<div class="card">
  <div class="table-wrap">
    <table class="data">
      <thead><tr><th>Code</th><th>Title</th><th>Level</th><th>Semester</th><th>Enrolled</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td class="font-tabular"><?= e($r['code']) ?></td>
          <td><?= e($r['title']) ?></td>
          <td><?= (int)$r['level'] ?>L</td>
          <td><?= e($r['semester']) ?></td>
          <td><?= (int)$r['enrolled'] ?></td>
          <td><a href="<?= e(base_url('lecturer/courses/' . $r['id'] . '/edit')) ?>">Edit</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="6">No courses yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
