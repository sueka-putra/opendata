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

(function setupSidebarToggle() {
  const toggleButton = document.querySelector('[data-od-sidebar-toggle]');
  if (!toggleButton) return;

  const collapsedKey = 'od.sidebar.collapsed';
  const mq = window.matchMedia('(max-width: 992px)');

  function applyDesktopState() {
    if (mq.matches) return;
    const collapsed = localStorage.getItem(collapsedKey) === '1';
    document.body.classList.toggle('od-sidebar-collapsed', collapsed);
  }

  applyDesktopState();
  mq.addEventListener('change', applyDesktopState);

  toggleButton.addEventListener('click', () => {
    if (mq.matches) {
      document.body.classList.toggle('od-sidebar-open');
      return;
    }

    const next = !document.body.classList.contains('od-sidebar-collapsed');
    document.body.classList.toggle('od-sidebar-collapsed', next);
    localStorage.setItem(collapsedKey, next ? '1' : '0');
  });
})();
