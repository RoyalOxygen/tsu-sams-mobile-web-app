(function () {
  let stream = null;

  async function startCamera(video) {
    stream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } },
      audio: false,
    });
    video.srcObject = stream;
    await video.play();
  }

  function stopCamera() {
    if (stream) stream.getTracks().forEach((t) => t.stop());
    stream = null;
  }

  function snapshot(video, quality) {
    const c = document.createElement('canvas');
    c.width = video.videoWidth || 640;
    c.height = video.videoHeight || 480;
    c.getContext('2d').drawImage(video, 0, 0, c.width, c.height);
    return c.toDataURL('image/jpeg', quality || 0.85);
  }

  async function captureBurst(video, count, delayMs) {
    const frames = [];
    const n = count || 3;
    const wait = delayMs || 350;
    for (let i = 0; i < n; i++) {
      frames.push(snapshot(video, 0.85));
      if (i < n - 1) {
        await new Promise((r) => setTimeout(r, wait));
      }
    }
    return frames;
  }

  window.TSUFace = { startCamera, stopCamera, snapshot, captureBurst };
})();
