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

function odEnsureDialogHost() {
  let host = document.getElementById('od-dialog-host');
  if (host) return host;

  host = document.createElement('div');
  host.id = 'od-dialog-host';
  host.innerHTML = `
    <div class="modal fade" id="odAlertModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="odAlertTitle">Message</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body" id="odAlertBody"></div>
          <div class="modal-footer">
            <button type="button" class="btn od-btn-primary" data-bs-dismiss="modal">OK</button>
          </div>
        </div>
      </div>
    </div>
    <div class="modal fade" id="odConfirmModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="odConfirmTitle">Confirmation</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body" id="odConfirmBody"></div>
          <div class="modal-footer">
            <button type="button" class="btn od-btn-outline" id="odConfirmCancel" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn od-btn-primary" id="odConfirmOk">Yes</button>
          </div>
        </div>
      </div>
    </div>
  `;
  document.body.appendChild(host);
  return host;
}

function odAlert(message, title = 'Message') {
  odEnsureDialogHost();
  const modalEl = document.getElementById('odAlertModal');
  const titleEl = document.getElementById('odAlertTitle');
  const bodyEl = document.getElementById('odAlertBody');
  titleEl.textContent = title;
  bodyEl.textContent = message || '';
  const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
  modal.show();
}

function odConfirm(message, title = 'Confirmation', options = {}) {
  odEnsureDialogHost();
  const modalEl = document.getElementById('odConfirmModal');
  const titleEl = document.getElementById('odConfirmTitle');
  const bodyEl = document.getElementById('odConfirmBody');
  const okBtn = document.getElementById('odConfirmOk');
  const cancelBtn = document.getElementById('odConfirmCancel');
  const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
  titleEl.textContent = title;
  cancelBtn.style.minWidth = '88px';
  okBtn.style.minWidth = '88px';
  okBtn.style.background = '#2b76e5';
  okBtn.style.borderColor = '#2b76e5';
  okBtn.textContent = 'Yes';
  bodyEl.innerHTML = '';
  bodyEl.style.whiteSpace = 'normal';

  const messageText = String(message || '');
  if (options && options.boldFirstLine) {
    const [firstLine, ...restLines] = messageText.split('\n');
    const firstLineEl = document.createElement('div');
    firstLineEl.style.fontWeight = '700';
    firstLineEl.textContent = firstLine || '';
    bodyEl.appendChild(firstLineEl);

    if (restLines.length > 0) {
      const restEl = document.createElement('div');
      restEl.style.whiteSpace = 'pre-line';
      restEl.textContent = restLines.join('\n');
      bodyEl.appendChild(restEl);
    }
  } else {
    if (options && options.smallSecondBlock) {
      const parts = messageText.split('\n\n');
      const firstBlock = parts.shift() || '';
      const secondBlock = parts.join('\n\n');

      const firstEl = document.createElement('div');
      firstEl.style.whiteSpace = 'pre-line';
      firstEl.textContent = firstBlock;
      bodyEl.appendChild(firstEl);

      if (secondBlock.trim() !== '') {
        const secondEl = document.createElement('div');
        secondEl.style.whiteSpace = 'pre-line';
        secondEl.style.fontSize = '0.9em';
        secondEl.style.color = '#5f6b7a';
        secondEl.style.marginTop = '0.45rem';
        secondEl.textContent = secondBlock;
        bodyEl.appendChild(secondEl);
      }
    } else {
      bodyEl.style.whiteSpace = 'pre-line';
      bodyEl.textContent = messageText;
    }
  }

  return new Promise((resolve) => {
    let done = false;

    const cleanup = () => {
      okBtn.removeEventListener('click', onOk);
      cancelBtn.removeEventListener('click', onCancel);
      modalEl.removeEventListener('hidden.bs.modal', onHidden);
    };

    const finalize = (value) => {
      if (done) return;
      done = true;
      cleanup();
      resolve(value);
    };

    const onOk = () => {
      finalize(true);
      modal.hide();
    };

    const onCancel = () => finalize(false);
    const onHidden = () => finalize(false);

    okBtn.addEventListener('click', onOk);
    cancelBtn.addEventListener('click', onCancel);
    modalEl.addEventListener('hidden.bs.modal', onHidden, { once: true });
    modal.show();
  });
}

function odToast(message) {
  odAlert(message, 'Notice');
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

(function setupSidebarMenuTooltips() {
  if (!window.bootstrap?.Tooltip) return;

  const menuItems = document.querySelectorAll('.od-menu .od-menu-link[title]');
  menuItems.forEach((el) => {
    if (el.dataset.odTooltipInit === '1') return;
    el.setAttribute('data-bs-toggle', 'tooltip');
    el.setAttribute('data-bs-placement', 'right');
    el.setAttribute('data-bs-custom-class', 'od-menu-tooltip');
    bootstrap.Tooltip.getOrCreateInstance(el, {
      trigger: 'hover focus',
      placement: 'right',
      customClass: 'od-menu-tooltip',
      boundary: 'viewport',
      delay: { show: 120, hide: 60 },
    });
    el.dataset.odTooltipInit = '1';
  });
})();
