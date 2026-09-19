<div class="welcome-wrap">
  <p class="small text-muted mb-1">Step 4 of 6</p>
  <h1 class="h4 fw-bold">Live face enrollment</h1>
  <p class="muted">Allow camera access. Three ArcFace samples are captured and averaged by the TSU-SAMS Face Service (InsightFace buffalo_l).</p>
  <div class="camera-frame mb-3">
    <video id="cam" autoplay playsinline muted></video>
    <div class="scan-overlay"></div>
  </div>
  <p id="status" class="small text-center muted">Opening camera…</p>
  <button id="captureBtn" class="btn btn-success" disabled>Capture biometric template</button>
  <p class="footer-note">512-d ArcFace embeddings are encrypted in TSU-SAMS. Raw video is not stored.</p>
</div>
<script src="<?= e(base_url('assets/js/app.js')) ?>"></script>
<script src="<?= e(base_url('assets/js/face.js')) ?>"></script>
<script>
(async function () {
  const video = document.getElementById('cam');
  const status = document.getElementById('status');
  const btn = document.getElementById('captureBtn');
  try {
    await TSUFace.startCamera(video);
    status.textContent = 'Camera live. Hold still; three frames will be sent to InsightFace.';
    btn.disabled = false;
  } catch (err) {
    status.textContent = 'Camera failed: ' + err.message;
  }
  btn.addEventListener('click', async () => {
    btn.disabled = true;
    status.textContent = 'Capturing samples and enrolling with ArcFace…';
    const frames = await TSUFace.captureBurst(video, 3, 400);
    const body = new FormData();
    body.append('_csrf', TSU.csrf);
    body.append('snapshot', frames[0]);
    frames.forEach((f, i) => body.append('snapshots[]', f));
    const res = await fetch(<?= json_encode(base_url('register/face')) ?>, { method: 'POST', body, credentials: 'same-origin' });
    const json = await res.json().catch(() => ({ ok: false, message: 'Unexpected response' }));
    if (json.ok) {
      status.textContent = 'ArcFace template stored.';
      TSUFace.stopCamera();
      window.location = json.redirect;
    } else {
      status.textContent = json.message || 'Enrollment failed.';
      btn.disabled = false;
    }
  });
})();
</script>
