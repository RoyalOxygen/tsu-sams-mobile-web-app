<h1 class="h4">Import students</h1>
<div class="card">
  <p>CSV header row must include: <code>matric_no,first_name,last_name,gender,faculty_code,department_code,level,phone,email</code></p>
  <form method="post" action="<?= e(base_url('admin/students/import')) ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <input class="form-control mb-3" type="file" name="csv" accept=".csv,text/csv" required>
    <button class="btn btn-primary" type="submit">Import</button>
  </form>
</div>
