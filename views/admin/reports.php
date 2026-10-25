<h1 class="h4">Reports hub</h1>
<p class="muted">Export institutional reports as CSV, Excel (.xls) or printable PDF.</p>
<div class="card">
  <?php
  $types = [
    'students' => 'Student reports',
    'lecturers' => 'Lecturer reports',
    'courses' => 'Course reports',
    'department' => 'Department reports',
    'faculty' => 'Faculty reports',
    'venues' => 'Venue reports',
    'payments' => 'Payment reports',
    'attendance' => 'Attendance reports',
  ];
  foreach ($types as $k => $label): ?>
    <div class="list-item">
      <strong><?= e($label) ?></strong>
      <div class="ms-auto d-flex gap-2">
        <a class="btn btn-outline btn-sm" href="<?= e(base_url('admin/reports/export?type=' . $k . '&format=csv')) ?>">CSV</a>
        <a class="btn btn-outline btn-sm" href="<?= e(base_url('admin/reports/export?type=' . $k . '&format=excel')) ?>">Excel</a>
        <a class="btn btn-outline btn-sm" href="<?= e(base_url('admin/reports/export?type=' . $k . '&format=pdf')) ?>">PDF</a>
      </div>
    </div>
  <?php endforeach; ?>
</div>
