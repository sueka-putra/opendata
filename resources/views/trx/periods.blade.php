@extends('layouts.opendata')

@section('content')

<div class="period-theme-wrap">
  <div class="period-theme-shell">
    <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-3">
      <div>
        <h1 class="h5 period-title">Assessment Period List</h1>
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
              <th style="width: 210px;">Closed Time</th>
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
          <h5 class="modal-title mb-0" id="periodDialogTitle">Create/Close Assessment Period [3.3.2]</h5>
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
          </div>
          <div class="period-config-card">
            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
              <strong class="small">Assessment Configuration Rows</strong>
              <button type="button" class="btn btn-sm od-btn-outline" id="btnAddConfigRow">Add Row</button>
            </div>
            <div class="table-responsive">
              <table class="table table-sm mb-0" id="tblConfigRows">
                <thead>
                  <tr>
                    <th>Section</th>
                    <th>Category</th>
                    <th>Indicator</th>
                    <th>Dissagregation</th>
                    <th style="width: 90px;">Action</th>
                  </tr>
                </thead>
                <tbody></tbody>
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
              <p class="period-view-label">Closed</p>
              <p class="period-view-value" id="viewPeriodClosed">-</p>
            </div>
          </div>
          <div class="period-view-item">
            <p class="period-view-label">Description</p>
            <p class="period-view-value" id="viewPeriodDescription">-</p>
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
    masters: null,
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

    if (hasActive) {
      btn.disabled = true;
      hint.textContent = 'Create is disabled because an active assessment period already exists.';
      hint.style.display = 'block';
      return;
    }

    btn.disabled = false;
    hint.textContent = 'Only one active assessment period is allowed at a time.';
    hint.style.display = 'none';
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
        : '<span class="od-badge od-badge-close">Close</span>';

      const periodId = p.id;
      const countriesUrl = `/trx/period/${periodId}/countries`;

      return `
        <tr>
          <td class="fw-semibold">${p.year ?? '-'}</td>
          <td>${statusBadge}</td>
          <td>
            <button class="btn btn-sm btn-outline-primary" type="button" data-action="view-period" data-period-id="${periodId}">View</button>
            <a class="btn btn-sm btn-outline-dark ms-1" href="${countriesUrl}">Countries</a>
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

  function masterOptions(items) {
    return (items || [])
      .filter((x) => x.active === true || x.active === 1 || x.active === '1')
      .map((x) => `<option value="${x.id}">${x.title}</option>`)
      .join('');
  }

  function createConfigRow(initial = {}) {
    const tr = document.createElement('tr');
    const sections = masterOptions(periodState.masters.sections);
    const categories = masterOptions(periodState.masters.categories);
    const indicators = masterOptions(periodState.masters.indicators);
    const subIndicators = masterOptions(periodState.masters.subIndicators);

    tr.innerHTML = `
      <td><select class="form-select form-select-sm" data-field="section">${sections}</select></td>
      <td><select class="form-select form-select-sm" data-field="category">${categories}</select></td>
      <td><select class="form-select form-select-sm" data-field="indicator">${indicators}</select></td>
      <td><select class="form-select form-select-sm" data-field="dissagregation">${subIndicators}</select></td>
      <td><button type="button" class="btn btn-sm btn-outline-danger" data-action="remove-config-row">Remove</button></td>
    `;

    if (initial.section) tr.querySelector('[data-field="section"]').value = String(initial.section);
    if (initial.category) tr.querySelector('[data-field="category"]').value = String(initial.category);
    if (initial.indicator) tr.querySelector('[data-field="indicator"]').value = String(initial.indicator);
    if (initial.dissagregation) tr.querySelector('[data-field="dissagregation"]').value = String(initial.dissagregation);

    return tr;
  }

  function collectConfigRows() {
    return Array.from(document.querySelectorAll('#tblConfigRows tbody tr')).map((tr) => ({
      section: Number(tr.querySelector('[data-field="section"]').value),
      category: Number(tr.querySelector('[data-field="category"]').value),
      indicator: Number(tr.querySelector('[data-field="indicator"]').value),
      dissagregation: Number(tr.querySelector('[data-field="dissagregation"]').value),
    }));
  }

  async function ensureMastersLoaded() {
    if (periodState.masters) return;

    const [sections, categories, indicators, subIndicators] = await Promise.all([
      odFetch('/api/adm/sections'),
      odFetch('/api/adm/categories'),
      odFetch('/api/adm/indicators'),
      odFetch('/api/adm/sub-indicators'),
    ]);

    periodState.masters = {
      sections: sections.data || [],
      categories: categories.data || [],
      indicators: indicators.data || [],
      subIndicators: subIndicators.data || [],
    };
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
    if (document.getElementById('btnCreate').disabled) return;

    setDialogError('');
    setDialogMode('create');
    document.getElementById('periodYear').value = new Date().getFullYear();
    document.getElementById('periodDescription').value = '';

    try {
      await ensureMastersLoaded();
      const tbody = document.querySelector('#tblConfigRows tbody');
      tbody.innerHTML = '';
      tbody.appendChild(createConfigRow());
      periodDialog.show();
    } catch (err) {
      setDialogError(err.message || 'Failed to load period master data.');
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
      : '<span class="od-badge od-badge-close">Close</span>';
    document.getElementById('viewPeriodCreated').textContent = fmtDateTime(period.created_date || period.created_at);
    document.getElementById('viewPeriodClosed').textContent = open
      ? '-'
      : fmtDateTime(period.closed_date || period.modified_date);
    document.getElementById('viewPeriodDescription').textContent = period.description || '-';
    const viewRows = document.getElementById('viewConfigRows');
    viewRows.innerHTML = '<tr><td colspan="5" class="text-muted">Loading...</td></tr>';

    const closeBtn = document.getElementById('btnDialogSubmitClose');
    closeBtn.disabled = !open;
    closeBtn.textContent = open ? 'Close Period' : 'Period Already Closed';

    periodDialog.show();

    try {
      const j = await odFetch(`/api/trx/period/${periodId}/rows`);
      const rows = Array.isArray(j.data) ? j.data : [];
      if (!rows.length) {
        viewRows.innerHTML = '<tr><td colspan="5" class="text-muted">No configuration rows found.</td></tr>';
        return;
      }

      viewRows.innerHTML = rows.map((r, idx) => `
        <tr>
          <td>${idx + 1}</td>
          <td>${r.section_title || '-'}</td>
          <td>${r.category_title || '-'}</td>
          <td>${r.indicator_title || '-'}</td>
          <td>${r.disaggregation_title || '-'}</td>
        </tr>
      `).join('');
    } catch (err) {
      viewRows.innerHTML = `<tr><td colspan="5" class="text-danger">${err.message || 'Failed to load period configuration rows.'}</td></tr>`;
    }
  }

  async function submitCreatePeriod() {
    setDialogError('');

    const year = Number(document.getElementById('periodYear').value);
    const description = document.getElementById('periodDescription').value.trim();
    const rows = collectConfigRows();

    if (!year || year < 2000 || year > 2100) {
      setDialogError('Year must be between 2000 and 2100.');
      return;
    }
    if (!description) {
      setDialogError('Description is required.');
      return;
    }
    if (rows.length === 0) {
      setDialogError('At least one configuration row is required.');
      return;
    }
    if (rows.some((r) => !r.section || !r.category || !r.indicator || !r.dissagregation)) {
      setDialogError('All configuration row fields are required.');
      return;
    }

    try {
      const btn = document.getElementById('btnDialogSubmitCreate');
      btn.disabled = true;
      await odFetch('/api/trx/period', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ year, description, rows }),
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
    document.getElementById('btnAddConfigRow').addEventListener('click', () => {
      document.querySelector('#tblConfigRows tbody').appendChild(createConfigRow());
    });

    document.querySelector('#tblConfigRows tbody').addEventListener('click', (e) => {
      const btn = e.target.closest('[data-action="remove-config-row"]');
      if (!btn) return;
      const tbody = document.querySelector('#tblConfigRows tbody');
      if (tbody.querySelectorAll('tr').length === 1) return;
      btn.closest('tr').remove();
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

