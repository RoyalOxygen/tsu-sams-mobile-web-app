<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <h1 class="h4 mb-0">Students directory</h1>
  <div class="d-flex gap-2">
    <a class="btn btn-outline btn-sm" href="<?= e(base_url('admin/students/import')) ?>">Import CSV</a>
    <a class="btn btn-primary btn-sm" href="<?= e(base_url('admin/students/create')) ?>">Add student</a>
  </div>
</div>
<form class="card" method="get">
  <input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Search matric or name">
</form>
<div class="card">
  <div class="table-wrap">
    <table class="data">
      <thead><tr><th></th><th>Matric</th><th>Name</th><th>Dept</th><th>Level</th><th>Status</th><th>Face</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): $photo = student_passport_url($r); ?>
        <tr>
          <td>
            <?php if ($photo): ?>
              <img src="<?= e($photo) ?>" alt="" class="avatar avatar-sm">
            <?php else: ?>
              <span class="avatar avatar-sm avatar-initials"><?= e(student_initials($r)) ?></span>
            <?php endif; ?>
          </td>
          <td class="font-tabular"><a href="<?= e(base_url('admin/students/' . $r['id'])) ?>"><?= e($r['matric_no']) ?></a></td>
          <td><?= e($r['first_name'] . ' ' . $r['last_name']) ?></td>
          <td><?= e($r['department_name']) ?></td>
          <td><?= (int)$r['level'] ?>L</td>
          <td><?= e(enrollment_label($r['enrollment_status'])) ?></td>
          <td><?= (int)$r['has_face'] ? 'Yes' : 'No' ?></td>
          <td>
            <a href="<?= e(base_url('admin/students/' . $r['id'])) ?>">View</a>
            ·
            <a href="<?= e(base_url('admin/students/' . $r['id'] . '/edit')) ?>">Edit</a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
