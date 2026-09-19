<div class="welcome-wrap">
  <div class="card text-center">
    <div class="text-success mb-2"><i class="bi bi-check-circle-fill fs-1"></i></div>
    <h1 class="h4">Attendance recorded</h1>
    <p class="mb-1"><strong><?= e($info['course']) ?></strong></p>
    <p class="muted"><?= e($info['venue']) ?></p>
    <?= status_badge($info['status']) ?>
    <p class="small muted mt-3 font-tabular"><?= e($info['time']) ?> • <?= e((string)$info['distance']) ?>m from venue</p>
    <div class="alert alert-info mt-3">You have been signed out automatically for this device session.</div>
    <a class="btn btn-primary" href="<?= e(base_url('student/face-login')) ?>">Done</a>
  </div>
</div>
