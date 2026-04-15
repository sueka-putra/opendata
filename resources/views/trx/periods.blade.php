@extends('layouts.opendata')

@section('content')

<div class="period-theme-wrap">
  <div class="period-theme-shell">
    <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-3">
      <div>
        <h1 class="h5 period-title">Assessment Period Histories</h1>
        <div class="period-subtitle">Manage assessment periods for the Open Data Portal.</div>
      </div>
      <div class="d-flex gap-2">
        <button class="btn od-btn-primary" id="btnCreate" type="button">Create</button>
        <button class="btn od-btn-outline" id="btnRefresh" type="button">Refresh</button>
      </div>
    </div>

    <div class="period-hint mb-3" id="periodHint" style="display: none">
      Only one active assessment period is allowed at a time.
    </div>

    <div class="period-table-card mt-3">
      <div class="period-table-toolbar">
        <input class="form-control period-search" type="text" placeholder="Search Order">
      </div>
      <div class="table-responsive">
        <table class="table period-table align-middle mb-0">
          <thead>
            <tr>
              <th style="width: 120px;">Year</th>
              <th style="width: 140px;">Status</th>
              <th style="width: 240px;">Actions</th>
              <th style="width: 210px;">Create Time</th>
              <th style="width: 210px;">Completed Time</th>
            </tr>
          </thead>
          <tbody id="tbPeriods">
            <tr><td colspan="5" class="text-muted">Loading...</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="modal fade period-dialog" id="periodDialog" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <h5 class="modal-title mb-0" id="periodDialogTitle">Create/Close Assessment Period</h5>
          <div class="period-dialog-subtitle" id="periodDialogSubtitle"></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="periodDialogError" class="alert alert-danger d-none mb-3"></div>

        <div id="periodCreateBlock">
          <div>
            <div class="row g-3 mb-3">
              <div class="col-md-4">
                <label class="form-label" for="periodYear">Year</label>
                <input type="number" class="form-control" id="periodYear" min="2000" max="2100" placeholder="e.g. 2026">
              </div>
            </div>
            <div class="row g-3 mb-3">
              <div class="col-md-8">
                <label class="form-label" for="periodDescription">Description</label>
                <input type="text" class="form-control" id="periodDescription" maxlength="300" placeholder="Assessment period description">
              </div>
            </div>
            <div class="row g-3 mb-3">
              <div class="col-md-8">
                <label class="form-label" for="periodConfig">Configuration</label>
                <select class="form-select" id="periodConfig"></select>
                <div class="form-text" id="periodConfigHint">Choose configuration used by this assessment period.</div>
              </div>
            </div>
          </div>
          <div class="period-config-card">
            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
              <strong class="small">Selected Configuration Rows</strong>
            </div>
            <div class="table-responsive">
              <table class="table table-sm mb-0" id="tblConfigRows">
                <thead>
                  <tr>
                    <th style="width: 70px;">No</th>
                    <th>Section</th>
                    <th>Category</th>
                    <th>Indicator</th>
                    <th>Dissagregation</th>
                  </tr>
                </thead>
                <tbody>
                  <tr><td colspan="5" class="text-muted">Select a configuration to preview rows.</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div id="periodViewBlock" class="d-none">
          <div class="period-view-grid mb-3">
            <div class="period-view-item">
              <p class="period-view-label">Year</p>
              <p class="period-view-value" id="viewPeriodYear">-</p>
            </div>
            <div class="period-view-item">
              <p class="period-view-label">Status</p>
              <p class="period-view-value" id="viewPeriodStatus">-</p>
            </div>
            <div class="period-view-item">
              <p class="period-view-label">Created</p>
              <p class="period-view-value" id="viewPeriodCreated">-</p>
            </div>
            <div class="period-view-item">
              <p class="period-view-label">Completed</p>
              <p class="period-view-value" id="viewPeriodClosed">-</p>
            </div>
          </div>
          <div class="period-view-item">
            <p class="period-view-label">Description</p>
            <p class="period-view-value" id="viewPeriodDescription">-</p>
          </div>
          <div class="period-view-item mt-2">
            <p class="period-view-label">Configuration</p>
            <p class="period-view-value" id="viewPeriodConfiguration">-</p>
          </div>
          <div class="period-config-card mt-3">
            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
              <strong class="small">Configuration Rows</strong>
            </div>
            <div class="table-responsive">
              <table class="table table-sm mb-0">
                <thead>
                  <tr>
                    <th style="width: 70px;">No</th>
                    <th>Section</th>
                    <th>Category</th>
                    <th>Indicator</th>
                    <th>Disaggregation</th>
                  </tr>
                </thead>
                <tbody id="viewConfigRows">
                  <tr><td colspan="5" class="text-muted">Loading...</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn od-btn-outline" type="button" data-bs-dismiss="modal">Cancel</button>
        <button class="btn od-btn-primary d-none" type="button" id="btnDialogSubmitCreate">Create Period</button>
        <button class="btn od-btn-primary d-none" type="button" id="btnDialogSubmitClose">Close Period</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  const endpoint = '/api/trx/periods';
  const periodState = {
    periods: [],
    configurations: null,
    selectedPeriod: null,
    mode: 'create',
  };

  let periodDialog;

  function fmtDateTime(value) {
    if (!value) return '-';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return String(value);
    return d.toLocaleString();
  }

  function isOpenPeriod(period) {
    if (period.active === true || period.active === 1 || period.active === '1') {
      return true;
    }

    const status = String(period.status || '').toUpperCase();
    return status === 'OPEN' || status === 'ACTIVE';
  }

  function setCreateAvailability(hasActive) {
    const btn = document.getElementById('btnCreate');
    const hint = document.getElementById('periodHint');
    hint.style.display = 'none';

    if (hasActive) {
      btn.classList.add('d-none');
      return;
    }

    btn.classList.remove('d-none');
  }

  function renderRows(periods) {
    const tb = document.getElementById('tbPeriods');

    if (!Array.isArray(periods) || periods.length === 0) {
      tb.innerHTML = '<tr><td colspan="5" class="text-muted">No assessment periods found.</td></tr>';
      periodState.periods = [];
      setCreateAvailability(false);
      return;
    }

    const hasActive = periods.some(isOpenPeriod);
    setCreateAvailability(hasActive);
    periodState.periods = periods;

    tb.innerHTML = periods.map((p) => {
      const isOpen = isOpenPeriod(p);
      const statusBadge = isOpen
        ? '<span class="od-badge od-badge-open">Open</span>'
        : '<span class="od-badge od-badge-close">Completed</span>';

      const periodId = p.id;
      const countriesUrl = `/trx/period/${periodId}/countries`;

      return `
        <tr>
          <td class="fw-semibold">${p.year ?? '-'}</td>
          <td>${statusBadge}</td>
          <td>
            <div class="period-actions">
              <span class="period-setup-slot">
                ${isOpen
                  ? `<button class="btn btn-sm btn-outline-primary" type="button" data-action="view-period" data-period-id="${periodId}">Setup</button>`
                  : '<span class="btn btn-sm btn-outline-primary period-setup-placeholder" aria-hidden="true">Setup</span>'}
              </span>
              <a class="btn btn-sm btn-outline-dark" href="${countriesUrl}">Participants</a>
            </div>
          </td>
          <td class="text-muted small">${fmtDateTime(p.created_date || p.created_at)}</td>
          <td class="text-muted small">${isOpen ? '-' : fmtDateTime(p.closed_date || p.modified_date)}</td>
        </tr>
      `;
    }).join('');
  }

  function setDialogError(message = '') {
    const el = document.getElementById('periodDialogError');
    if (!message) {
      el.classList.add('d-none');
      el.textContent = '';
      return;
    }
    el.classList.remove('d-none');
    el.textContent = message;
  }

  function renderConfigRows(rows, targetSelector) {
    const tbody = document.querySelector(targetSelector);
    if (!tbody) return;

    if (!Array.isArray(rows) || rows.length === 0) {
      tbody.innerHTML = '<tr><td colspan="5" class="text-muted">No configuration rows found.</td></tr>';
      return;
    }

    tbody.innerHTML = rows.map((r, idx) => `
      <tr>
        <td>${r.seq_no ?? (idx + 1)}</td>
        <td>${r.section_title || '-'}</td>
        <td>${r.category_title || '-'}</td>
        <td>${r.indicator_title || '-'}</td>
        <td>${r.disaggregation_title || '-'}</td>
      </tr>
    `).join('');
  }

  async function ensureConfigurationsLoaded() {
    if (periodState.configurations) return;

    const response = await odFetch('/api/trx/configurations');
    const configs = Array.isArray(response.data) ? response.data : [];
    periodState.configurations = configs;
  }

  async function previewConfiguration(configId) {
    const tbody = document.querySelector('#tblConfigRows tbody');
    const hint = document.getElementById('periodConfigHint');

    if (!configId) {
      hint.textContent = 'Choose configuration used by this assessment period.';
      tbody.innerHTML = '<tr><td colspan="5" class="text-muted">Select a configuration to preview rows.</td></tr>';
      return;
    }

    const selected = (periodState.configurations || []).find((cfg) => String(cfg.id) === String(configId));
    if (selected) {
      hint.textContent = `${selected.description || '-'} (rows: ${selected.row_count ?? 0})`;
    }

    tbody.innerHTML = '<tr><td colspan="5" class="text-muted">Loading...</td></tr>';
    const response = await odFetch(`/api/trx/configuration/${configId}/rows`);
    renderConfigRows(response.data || [], '#tblConfigRows tbody');
  }

  function setDialogMode(mode) {
    periodState.mode = mode;

    const createBlock = document.getElementById('periodCreateBlock');
    const viewBlock = document.getElementById('periodViewBlock');
    const createBtn = document.getElementById('btnDialogSubmitCreate');
    const closeBtn = document.getElementById('btnDialogSubmitClose');
    const subtitle = document.getElementById('periodDialogSubtitle');

    if (mode === 'create') {
      createBlock.classList.remove('d-none');
      viewBlock.classList.add('d-none');
      createBtn.classList.remove('d-none');
      closeBtn.classList.add('d-none');
      subtitle.textContent = 'Create new assessment period configuration.';
      return;
    }

    createBlock.classList.add('d-none');
    viewBlock.classList.remove('d-none');
    createBtn.classList.add('d-none');
    closeBtn.classList.remove('d-none');
    subtitle.textContent = 'View period status and close active period.';
  }

  async function openCreateDialog() {
    if (document.getElementById('btnCreate').classList.contains('d-none')) return;

    setDialogError('');
    setDialogMode('create');
    document.getElementById('periodYear').value = new Date().getFullYear();
    document.getElementById('periodDescription').value = '';

    try {
      await ensureConfigurationsLoaded();
      const configSelect = document.getElementById('periodConfig');
      const options = (periodState.configurations || []).map((cfg) => (
        `<option value="${cfg.id}">${cfg.title}</option>`
      )).join('');
      configSelect.innerHTML = options;

      if (!periodState.configurations || periodState.configurations.length === 0) {
        setDialogError('No assessment configuration found. Please create configuration first.');
        document.querySelector('#tblConfigRows tbody').innerHTML = '<tr><td colspan="5" class="text-muted">No configuration available.</td></tr>';
      } else {
        await previewConfiguration(configSelect.value);
      }

      periodDialog.show();
    } catch (err) {
      setDialogError(err.message || 'Failed to load period configuration data.');
      periodDialog.show();
    }
  }
  async function openViewDialog(periodId) {
    const period = periodState.periods.find((p) => String(p.id) === String(periodId));
    if (!period) return;

    periodState.selectedPeriod = period;
    setDialogError('');
    setDialogMode('view');

    const open = isOpenPeriod(period);
    document.getElementById('viewPeriodYear').textContent = period.year ?? '-';
    document.getElementById('viewPeriodStatus').innerHTML = open
      ? '<span class="od-badge od-badge-open">Open</span>'
      : '<span class="od-badge od-badge-close">Completed</span>';
    document.getElementById('viewPeriodCreated').textContent = fmtDateTime(period.created_date || period.created_at);
    document.getElementById('viewPeriodClosed').textContent = open
      ? '-'
      : fmtDateTime(period.closed_date || period.modified_date);
    document.getElementById('viewPeriodDescription').textContent = period.description || '-';
    document.getElementById('viewPeriodConfiguration').textContent = period.config_title || '-';
    const viewRows = document.getElementById('viewConfigRows');
    viewRows.innerHTML = '<tr><td colspan="5" class="text-muted">Loading...</td></tr>';

    const closeBtn = document.getElementById('btnDialogSubmitClose');
    closeBtn.disabled = !open;
    closeBtn.textContent = open ? 'Close Period' : 'Period Already Completed';

    periodDialog.show();

    try {
      const j = await odFetch(`/api/trx/period/${periodId}/rows`);
      const rows = Array.isArray(j.data) ? j.data : [];
      renderConfigRows(rows, '#viewConfigRows');
    } catch (err) {
      viewRows.innerHTML = `<tr><td colspan="5" class="text-danger">${err.message || 'Failed to load period configuration rows.'}</td></tr>`;
    }
  }

  async function submitCreatePeriod() {
    setDialogError('');

    const year = Number(document.getElementById('periodYear').value);
    const description = document.getElementById('periodDescription').value.trim();
    const configId = Number(document.getElementById('periodConfig').value);

    if (!year || year < 2000 || year > 2100) {
      setDialogError('Year must be between 2000 and 2100.');
      return;
    }
    if (!description) {
      setDialogError('Description is required.');
      return;
    }
    if (!configId) {
      setDialogError('Configuration is required.');
      return;
    }

    try {
      const btn = document.getElementById('btnDialogSubmitCreate');
      btn.disabled = true;
      await odFetch('/api/trx/period', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ year, description, config_id: configId }),
      });
      periodDialog.hide();
      await loadPeriods();
    } catch (err) {
      setDialogError(err.message || 'Failed to create period.');
    } finally {
      document.getElementById('btnDialogSubmitCreate').disabled = false;
    }
  }

  async function submitClosePeriod() {
    if (!periodState.selectedPeriod) return;
    setDialogError('');

    const periodId = periodState.selectedPeriod.id;
    const confirmed = confirm(`Close assessment period ${periodState.selectedPeriod.year}?`);
    if (!confirmed) return;

    try {
      const btn = document.getElementById('btnDialogSubmitClose');
      btn.disabled = true;
      await odFetch(`/api/trx/period/${periodId}`, { method: 'PUT' });
      periodDialog.hide();
      await loadPeriods();
    } catch (err) {
      setDialogError(err.message || 'Failed to close period.');
    } finally {
      document.getElementById('btnDialogSubmitClose').disabled = false;
    }
  }

  async function loadPeriods() {
    const tb = document.getElementById('tbPeriods');
    tb.innerHTML = '<tr><td colspan="5" class="text-muted">Loading...</td></tr>';

    try {
      const res = await fetch(endpoint, {
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      });

      const body = await res.json().catch(() => ({}));
      if (!res.ok) {
        throw new Error(body.message || `Failed to load periods (${res.status})`);
      }

      const periods = Array.isArray(body.data) ? body.data : [];
      renderRows(periods);
    } catch (err) {
      tb.innerHTML = `<tr><td colspan="5" class="text-danger">${err.message}</td></tr>`;
      setCreateAvailability(false);
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    periodDialog = new bootstrap.Modal(document.getElementById('periodDialog'));

    document.getElementById('btnCreate').addEventListener('click', openCreateDialog);
    document.getElementById('btnRefresh').addEventListener('click', loadPeriods);
    document.getElementById('btnDialogSubmitCreate').addEventListener('click', submitCreatePeriod);
    document.getElementById('btnDialogSubmitClose').addEventListener('click', submitClosePeriod);
    document.getElementById('periodConfig').addEventListener('change', (e) => {
      previewConfiguration(e.target.value).catch((err) => {
        const tbody = document.querySelector('#tblConfigRows tbody');
        tbody.innerHTML = `<tr><td colspan="5" class="text-danger">${err.message || 'Failed to load configuration rows.'}</td></tr>`;
      });
    });

    document.getElementById('tbPeriods').addEventListener('click', (e) => {
      const btn = e.target.closest('[data-action="view-period"]');
      if (!btn) return;
      openViewDialog(btn.getAttribute('data-period-id'));
    });

    loadPeriods();
  });
</script>
@endpush
