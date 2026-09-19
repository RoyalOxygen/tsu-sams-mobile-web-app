(function () {
  const csrf = document.querySelector('meta[name="csrf-token"]');
  window.TSU = window.TSU || {};
  TSU.csrf = csrf ? csrf.getAttribute('content') : '';

  TSU.postForm = async function (url, data) {
    const body = new FormData();
    body.append('_csrf', TSU.csrf);
    Object.keys(data).forEach((k) => body.append(k, data[k]));
    const res = await fetch(url, { method: 'POST', body, credentials: 'same-origin' });
    const json = await res.json().catch(() => ({ ok: false, message: 'Unexpected response' }));
    if (!res.ok && !json.message) json.message = 'Request failed';
    return json;
  };

  document.querySelectorAll('[data-confirm]').forEach((el) => {
    el.addEventListener('click', (e) => {
      if (!confirm(el.getAttribute('data-confirm'))) e.preventDefault();
    });
  });
})();
