<h1 class="h5 mb-1"><?= e($sess['code']) ?></h1>
<p class="muted"><?= e($sess['title']) ?> • <?= e($sess['venue_name']) ?></p>

<?php if ($already): ?>
  <div class="alert alert-info">You already recorded attendance for this lecture.</div>
  <a class="btn btn-outline" href="<?= e(base_url('student')) ?>">Back to dashboard</a>
<?php else: ?>
<div class="card">
  <div class="map-box mb-3">
    <div class="geo-ring"></div>
    <div class="map-pin"><i class="bi bi-geo-alt-fill"></i></div>
  </div>
  <p class="small mb-1">Venue: <?= e($sess['building_name']) ?></p>
  <p class="small muted font-tabular mb-0">Target <?= e((string)$sess['latitude']) ?>, <?= e((string)$sess['longitude']) ?> • <?= (int)$sess['radius'] ?>m</p>
  <p id="geoStatus" class="small mt-2 mb-0">Requesting GPS lock…</p>
</div>
<div class="camera-frame mb-3">
  <video id="cam" autoplay playsinline muted></video>
  <div class="scan-overlay"></div>
</div>
<p id="status" class="small text-center muted">Preparing biometric capture…</p>
<button id="goBtn" class="btn btn-success" disabled>Verify face + GPS and record</button>
<?php endif; ?>

<?php if (!$already): ?>
<script src="<?= e(base_url('assets/js/app.js')) ?>"></script>
<script src="<?= e(base_url('assets/js/geo.js')) ?>"></script>
<script src="<?= e(base_url('assets/js/face.js')) ?>"></script>
<script>
(async function () {
  const status = document.getElementById('status');
  const geoStatus = document.getElementById('geoStatus');
  const btn = document.getElementById('goBtn');
  const video = document.getElementById('cam');
  const venue = { lat: <?= json_encode((float)$sess['latitude']) ?>, lng: <?= json_encode((float)$sess['longitude']) ?>, r: <?= (int)$sess['radius'] ?> };
  let coords = null;
  try {
    coords = await TSUGeo.locate();
    const d = TSUGeo.haversine(coords.lat, coords.lng, venue.lat, venue.lng);
    geoStatus.textContent = 'GPS accuracy ±' + Math.round(coords.acc) + 'm. Distance to venue: ' + Math.round(d) + 'm.';
    if (d > venue.r) geoStatus.textContent += ' You appear outside the geofence.';
  } catch (e) {
    geoStatus.textContent = 'Enable location services to continue.';
  }
  try {
    await TSUFace.startCamera(video);
    status.textContent = 'Face the camera, then submit. ArcFace will match your enrolled template.';
    btn.disabled = false;
  } catch (e) {
    status.textContent = e.message;
  }
  btn.addEventListener('click', async () => {
    if (!coords) { status.textContent = 'GPS not available.'; return; }
    btn.disabled = true;
    status.textContent = 'Running ArcFace match and geofence check…';
    const snap = TSUFace.snapshot(video);
    const body = new FormData();
    body.append('_csrf', TSU.csrf);
    body.append('latitude', coords.lat);
    body.append('longitude', coords.lng);
    body.append('snapshot', snap);
    const url = <?= json_encode(base_url('student/attendance/' . $sess['id'])) ?>;
    const res = await fetch(url, { method: 'POST', body, credentials: 'same-origin' });
    const json = await res.json().catch(() => ({ ok: false, message: 'Unexpected response' }));
    if (json.ok) {
      TSUFace.stopCamera();
      window.location = json.redirect;
    } else {
      status.textContent = json.message || 'Verification failed.';
      if (json.code === 'geo' || json.code === 'face') {
        setTimeout(() => { window.location = <?= json_encode(base_url('student/attendance/fail')) ?> + '?m=' + encodeURIComponent(json.message); }, 1200);
      }
      btn.disabled = false;
    }
  });
})();
</script>
<?php endif; ?>
