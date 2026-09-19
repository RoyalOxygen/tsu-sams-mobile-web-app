<div class="welcome-wrap">
  <p class="small text-muted mb-1">Step 5 of 6</p>
  <h1 class="h4 fw-bold">Enrollment fee</h1>
  <p class="muted">Face enrollment levy for <?= e($student['matric_no']) ?></p>
  <?php include config('paths.views') . '/partials/flash.php'; ?>
  <div class="card">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <span>Amount due</span>
      <strong class="h4 mb-0 font-tabular"><?= e(money_ngn($fee)) ?></strong>
    </div>
    <form method="post" action="<?= e(base_url('register/payment')) ?>">
      <?= csrf_field() ?>
      <button class="btn btn-success" type="submit">Pay with campus gateway (demo)</button>
    </form>
    <p class="small muted mt-3 mb-0">Demo settlement records a successful transaction for preview. Connect Paystack/Remita in production.</p>
  </div>
</div>
