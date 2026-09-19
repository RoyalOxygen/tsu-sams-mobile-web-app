<?php
$v = $values;
$known = ['institution','academic_session','semester','exam_threshold','default_radius','payments_enabled','enrollment_fee_enabled','enrollment_fee','face_update_fee_enabled','face_update_fee'];
$extras = array_diff_key($v, array_flip($known));
?>
<h1 class="h4">System settings</h1>
<p class="muted">These values are stored in the settings table and used across student, lecturer and admin portals.</p>
<form method="post" action="<?= e(base_url('admin/settings')) ?>">
  <?= csrf_field() ?>
  <div class="card">
    <h2 class="h6">Institution</h2>
    <div class="mb-3">
      <label class="form-label">Institution name</label>
      <input class="form-control" name="institution" required value="<?= e($v['institution'] ?? '') ?>">
    </div>
    <div class="grid-2 mb-3">
      <div>
        <label class="form-label">Academic session</label>
        <input class="form-control" name="academic_session" required value="<?= e($v['academic_session'] ?? '') ?>" placeholder="2024/2025">
      </div>
      <div>
        <label class="form-label">Semester</label>
        <select class="form-select" name="semester">
          <option value="Harmattan" <?= (($v['semester'] ?? '') === 'Harmattan') ? 'selected' : '' ?>>Harmattan</option>
          <option value="Rain" <?= (($v['semester'] ?? '') === 'Rain') ? 'selected' : '' ?>>Rain</option>
        </select>
      </div>
    </div>
    <div class="grid-2">
      <div>
        <label class="form-label">Exam attendance threshold (%)</label>
        <input class="form-control" type="number" min="0" max="100" name="exam_threshold" value="<?= e($v['exam_threshold'] ?? '75') ?>">
      </div>
      <div>
        <label class="form-label">Default venue radius (metres)</label>
        <input class="form-control" type="number" min="10" name="default_radius" value="<?= e($v['default_radius'] ?? '100') ?>">
      </div>
    </div>
  </div>

  <div class="card">
    <h2 class="h6">Payments</h2>
    <div class="grid-2 mb-3">
      <div>
        <div class="form-check form-switch mb-2">
          <input class="form-check-input" type="checkbox" name="enrollment_fee_enabled" id="efe" <?= (($v['enrollment_fee_enabled'] ?? $v['payments_enabled'] ?? '0') === '1') ? 'checked' : '' ?>>
          <label class="form-check-label" for="efe">Enable enrollment fee</label>
        </div>
        <label class="form-label">Enrollment fee (NGN)</label>
        <input class="form-control" type="number" step="0.01" min="0" name="enrollment_fee" value="<?= e($v['enrollment_fee'] ?? '0') ?>">
      </div>
      <div>
        <div class="form-check form-switch mb-2">
          <input class="form-check-input" type="checkbox" name="face_update_fee_enabled" id="fufe" <?= (($v['face_update_fee_enabled'] ?? $v['payments_enabled'] ?? '0') === '1') ? 'checked' : '' ?>>
          <label class="form-check-label" for="fufe">Enable face update fee</label>
        </div>
        <label class="form-label">Face update fee (NGN)</label>
        <input class="form-control" type="number" step="0.01" min="0" name="face_update_fee" value="<?= e($v['face_update_fee'] ?? '0') ?>">
      </div>
    </div>
  </div>

  <div class="card">
    <h2 class="h6">Other settings</h2>
    <?php if (!$extras): ?>
      <p class="muted small">No additional keys in the database.</p>
    <?php endif; ?>
    <?php foreach ($extras as $key => $val): ?>
      <div class="grid-2 mb-3">
        <div>
          <label class="form-label">Key</label>
          <input class="form-control font-tabular" name="extra_key[]" value="<?= e($key) ?>" readonly>
        </div>
        <div>
          <label class="form-label">Value</label>
          <input class="form-control" name="extra_value[]" value="<?= e($val) ?>">
        </div>
      </div>
    <?php endforeach; ?>
    <div class="grid-2">
      <div>
        <label class="form-label">Add new key</label>
        <input class="form-control font-tabular" name="new_key" placeholder="e.g. support_email">
      </div>
      <div>
        <label class="form-label">Value</label>
        <input class="form-control" name="new_value" placeholder="Optional">
      </div>
    </div>
  </div>

  <button class="btn btn-primary" type="submit">Save all settings</button>
</form>
