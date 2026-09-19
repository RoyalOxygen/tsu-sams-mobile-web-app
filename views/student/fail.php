<div class="welcome-wrap">
  <div class="card text-center">
    <div class="text-danger mb-2"><i class="bi bi-x-circle-fill fs-1"></i></div>
    <h1 class="h4">Verification failed</h1>
    <p><?= e($message) ?></p>
    <p class="small muted">Attendance is recorded only when face, course registration, open session, and GPS radius all pass.</p>
    <a class="btn btn-primary" href="<?= e(base_url('student/face-login')) ?>">Try again</a>
  </div>
</div>
