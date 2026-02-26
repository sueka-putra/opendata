// Shared JS per spec

async function odFetch(url, options = {}) {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  const headers = Object.assign({
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  }, options.headers || {});
  if (csrf) headers['X-CSRF-TOKEN'] = csrf;

  const res = await fetch(url, Object.assign({}, options, { headers }));
  const json = await res.json().catch(() => ({}));
  if (!res.ok || json.status === 'error') {
    const msg = json.message || `Request failed (${res.status})`;
    throw new Error(msg);
  }
  return json;
}

function odToast(message) {
  alert(message);
}
