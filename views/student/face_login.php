<div class="welcome-wrap">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <div>
      <h1 class="h4 mb-0">Face verification</h1>
      <p class="muted small mb-0">Look at the camera. Matric number is optional when ArcFace 1:N match succeeds.</p>
    </div>
    <span class="badge badge-success pulse">InsightFace</span>
  </div>
  <?php include config('paths.views') . '/partials/flash.php'; ?>
  <div class="mb-3">
    <label class="form-label">Matric number (optional)</label>
    <input id="matric" class="form-control font-tabular" placeholder="TSU/SCI/21/04882" style="text-transform:uppercase">
  </div>
  <div class="camera-frame mb-3">
    <video id="cam" autoplay playsinline muted></video>
    <div class="scan-overlay"></div>
  </div>
  <p id="status" class="small text-center muted">Preparing camera…</p>
  <button id="verifyBtn" class="btn btn-primary" disabled>Verify face and continue</button>
  <p class="footer-note"><a href="<?= e(base_url('login?role=student')) ?>">Use password instead</a></p>
</div>
<script src="<?= e(base_url('assets/js/app.js')) ?>"></script>
<script src="<?= e(base_url('assets/js/face.js')) ?>"></script>
<script>
(async function () {
  const video = document.getElementById('cam');
  const status = document.getElementById('status');
  const btn = document.getElementById('verifyBtn');
  try {
    await TSUFace.startCamera(video);
    status.textContent = 'Camera open. Align your face, then verify.';
    btn.disabled = false;
  } catch (err) {
    status.textContent = err.message;
  }
  btn.addEventListener('click', async () => {
    btn.disabled = true;
    status.textContent = 'Sending live frame to ArcFace…';
    const snap = TSUFace.snapshot(video);
    const body = new FormData();
    body.append('_csrf', TSU.csrf);
    body.append('matric_no', document.getElementById('matric').value.trim().toUpperCase());
    body.append('snapshot', snap);
    const res = await fetch(<?= json_encode(base_url('student/face-login')) ?>, { method: 'POST', body, credentials: 'same-origin' });
    const json = await res.json().catch(() => ({ ok: false, message: 'Unexpected response' }));
    if (json.ok) {
      TSUFace.stopCamera();
      window.location = json.redirect;
    } else {
      status.textContent = json.message || 'Verification failed.';
      btn.disabled = false;
    }
  });
})();
</script>
