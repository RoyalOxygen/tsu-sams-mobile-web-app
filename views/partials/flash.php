<?php if (!empty($flash_success)): ?>
  <div class="alert alert-success"><?= e($flash_success) ?></div>
<?php endif; ?>
<?php if (!empty($flash_error)): ?>
  <div class="alert alert-danger"><?= e($flash_error) ?></div>
<?php endif; ?>
<?php if (!empty($flash_info)): ?>
  <div class="alert alert-info"><?= e($flash_info) ?></div>
<?php endif; ?>
