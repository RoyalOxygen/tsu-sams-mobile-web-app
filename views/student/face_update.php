<h1 class="h4">Update face biometric</h1>
<p class="muted">Recapture three ArcFace samples to replace your stored template for <?= e($student['matric_no']) ?>.</p>
<?php if ($face): ?>
  <p class="small muted">Current template enrolled <?= e($face['enrolled_at']) ?><?= !empty($face['updated_at']) ? ' • last updated ' . e($face['updated_at']) : '' ?>.</p>
<?php else: ?>
  <p class="small muted">No face template on file. Capture one to enable face login and attendance.</p>
<?php endif; ?>
<div class="camera-frame mb-3">
  <video id="cam" autoplay playsinline muted></video>
  <div class="scan-overlay"></div>
</div>
<p id="status" class="small text-center muted">Opening camera…</p>
<button id="captureBtn" class="btn btn-success" disabled>Capture and replace template</button>
<p class="footer-note">512-d ArcFace embeddings are encrypted. Raw video is not stored.</p>
<script src="<?= e(base_url('assets/js/app.js')) ?>"></script>
<script src="<?= e(base_url('assets/js/face.js')) ?>"></script>
<script>
(async function () {
  const video = document.getElementById('cam');
  const status = document.getElementById('status');
  const btn = document.getElementById('captureBtn');
  try {
    await TSUFace.startCamera(video);
    status.textContent = 'Camera live. Hold still; three frames will replace your template.';
    btn.disabled = false;
  } catch (err) {
    status.textContent = 'Camera failed: ' + err.message;
  }
  btn.addEventListener('click', async () => {
    btn.disabled = true;
    status.textContent = 'Capturing samples and updating ArcFace template…';
    const frames = await TSUFace.captureBurst(video, 3, 400);
    const body = new FormData();
    body.append('_csrf', TSU.csrf);
    body.append('snapshot', frames[0]);
    frames.forEach((f) => body.append('snapshots[]', f));
    const res = await fetch(<?= json_encode(base_url('student/face')) ?>, { method: 'POST', body, credentials: 'same-origin' });
    const json = await res.json().catch(() => ({ ok: false, message: 'Unexpected response' }));
    if (json.ok) {
      status.textContent = 'Template updated.';
      TSUFace.stopCamera();
      window.location = json.redirect;
    } else {
      status.textContent = json.message || 'Update failed.';
      btn.disabled = false;
    }
  });
})();
</script>
