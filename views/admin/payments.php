<h1 class="h4">Payment settings</h1>
<div class="card">
  <form method="post" action="<?= e(base_url('admin/payments')) ?>">
    <?= csrf_field() ?>
    <div class="grid-2 mb-3">
      <div>
        <div class="form-check form-switch mb-2">
          <input class="form-check-input" type="checkbox" name="enrollment_fee_enabled" id="efe" <?= $enrollment_enabled === '1' ? 'checked' : '' ?>>
          <label class="form-check-label" for="efe">Enable enrollment fee</label>
        </div>
        <label class="form-label">Enrollment fee (NGN)</label>
        <input class="form-control" type="number" step="0.01" min="0" name="enrollment_fee" value="<?= e($enrollment_fee) ?>">
      </div>
      <div>
        <div class="form-check form-switch mb-2">
          <input class="form-check-input" type="checkbox" name="face_update_fee_enabled" id="fufe" <?= $face_update_enabled === '1' ? 'checked' : '' ?>>
          <label class="form-check-label" for="fufe">Enable face update fee</label>
        </div>
        <label class="form-label">Face update fee (NGN)</label>
        <input class="form-control" type="number" step="0.01" min="0" name="face_update_fee" value="<?= e($face_update_fee) ?>">
      </div>
    </div>
    <button class="btn btn-primary" type="submit">Save settings</button>
  </form>
  <p class="mt-3 mb-0"><a href="<?= e(base_url('admin/transactions')) ?>">View transactions</a></p>
</div>
