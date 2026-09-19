<h1 class="h4">Facial records</h1>
<p class="muted">Compare each student's passport photograph with the enrolled face sample.</p>
<?php if (!$rows): ?>
  <div class="card"><p class="muted mb-0">No facial templates stored.</p></div>
<?php endif; ?>
<?php foreach ($rows as $r):
    $passport = student_passport_url($r);
    $sample = public_file_url($r['sample_path'] ?? null);
?>
  <div class="card">
    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
      <div>
        <strong><?= e($r['first_name'] . ' ' . $r['last_name']) ?></strong>
        <div class="small font-tabular muted"><?= e($r['matric_no']) ?></div>
        <div class="small muted">Enrolled <?= e($r['enrolled_at']) ?></div>
      </div>
      <div class="d-flex gap-2">
        <a class="btn btn-outline btn-sm" href="<?= e(base_url('admin/students/' . (int)$r['student_id'])) ?>">View student</a>
        <form method="post" action="<?= e(base_url('admin/faces')) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
          <button class="btn btn-danger btn-sm" data-confirm="Remove this facial template?" type="submit">Revoke</button>
        </form>
      </div>
    </div>
    <div class="photo-compare photo-compare-sm mt-3">
      <figure>
        <?php if ($passport): ?>
          <img src="<?= e($passport) ?>" alt="Passport of <?= e($r['first_name'] . ' ' . $r['last_name']) ?>">
        <?php else: ?>
          <div class="photo-missing">No passport on file</div>
        <?php endif; ?>
        <figcaption>Passport</figcaption>
      </figure>
      <figure>
        <?php if ($sample): ?>
          <img src="<?= e($sample) ?>" alt="Face sample of <?= e($r['first_name'] . ' ' . $r['last_name']) ?>">
        <?php else: ?>
          <div class="photo-missing">No face sample stored</div>
        <?php endif; ?>
        <figcaption>Face sample</figcaption>
      </figure>
    </div>
  </div>
<?php endforeach; ?>
