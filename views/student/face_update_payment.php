<h1 class="h4">Face update fee</h1>
<p class="muted">A biometric recapture levy is required for <?= e($student['matric_no']) ?> before the new template is stored.</p>
<div class="card">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <span>Amount due</span>
    <strong class="h4 mb-0 font-tabular"><?= e(money_ngn($fee)) ?></strong>
  </div>
  <form method="post" action="<?= e(base_url('student/face/pay')) ?>">
    <?= csrf_field() ?>
    <button class="btn btn-success" type="submit">Pay with campus gateway (demo)</button>
  </form>
  <p class="small muted mt-3 mb-0">Demo settlement records a successful transaction for preview. Connect Paystack/Remita in production.</p>
</div>
