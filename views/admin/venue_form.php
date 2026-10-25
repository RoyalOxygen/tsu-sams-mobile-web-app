<h1 class="h4"><?= $venue ? 'Edit venue' : 'Create venue' ?></h1>
<p class="muted">Capture GPS while physically at the lecture hall, then set the attendance radius (default 100m).</p>
<div class="card">
  <div class="map-box mb-3">
    <div class="geo-ring"></div>
    <div class="map-pin"><i class="bi bi-geo-alt-fill"></i></div>
  </div>
  <button class="btn btn-outline mb-3" type="button" id="captureGps">Capture my current GPS</button>
  <p id="gpsMsg" class="small muted"></p>
  <form method="post" action="<?= e(base_url('admin/venues')) ?>">
    <?= csrf_field() ?>
    <?php if ($venue): ?><input type="hidden" name="id" value="<?= (int)$venue['id'] ?>"><?php endif; ?>
    <div class="mb-3">
      <label class="form-label">Venue name</label>
      <input class="form-control" name="name" required value="<?= e($venue['name'] ?? '') ?>" placeholder="Lecture Theatre A">
    </div>
    <div class="mb-3">
      <label class="form-label">Building name</label>
      <input class="form-control" name="building_name" required value="<?= e($venue['building_name'] ?? '') ?>">
    </div>
    <div class="grid-2 mb-3">
      <div>
        <label class="form-label">Latitude</label>
        <input class="form-control font-tabular" id="lat" name="latitude" required value="<?= e((string)($venue['latitude'] ?? '8.8932')) ?>">
      </div>
      <div>
        <label class="form-label">Longitude</label>
        <input class="form-control font-tabular" id="lng" name="longitude" required value="<?= e((string)($venue['longitude'] ?? '11.3596')) ?>">
      </div>
    </div>
    <div class="grid-2 mb-3">
      <div>
        <label class="form-label">Radius (metres)</label>
        <input class="form-control" type="number" name="radius" min="10" value="<?= (int)($venue['radius'] ?? 100) ?>">
      </div>
      <div>
        <label class="form-label">Capacity</label>
        <input class="form-control" type="number" name="capacity" value="<?= e((string)($venue['capacity'] ?? '')) ?>">
      </div>
    </div>
    <div class="form-check mb-3">
      <input class="form-check-input" type="checkbox" name="is_active" id="act" <?= empty($venue) || $venue['is_active'] ? 'checked' : '' ?>>
      <label class="form-check-label" for="act">Active</label>
    </div>
    <button class="btn btn-primary" type="submit">Save venue</button>
  </form>
</div>
<script>
document.getElementById('captureGps').addEventListener('click', async () => {
  const msg = document.getElementById('gpsMsg');
  msg.textContent = 'Locating…';
  try {
    const c = await TSUGeo.locate();
    document.getElementById('lat').value = c.lat.toFixed(7);
    document.getElementById('lng').value = c.lng.toFixed(7);
    msg.textContent = 'Captured with accuracy ±' + Math.round(c.acc) + 'm.';
  } catch (e) {
    msg.textContent = 'Unable to read GPS. Enter coordinates manually.';
  }
});
</script>
