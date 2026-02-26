@extends('layouts.opendata')

@section('content')
<style>
  :root {
    --od-page-shell: #eceef5;
    --od-panel: #f5f6fb;
    --od-panel-2: #ffffff;
    --od-text: #2e3345;
    --od-soft: #7c8295;
    --od-border: #dde1ec;
    --od-line: #e8ebf4;
    --od-accent: #8b5cf6;
  }
  .period-theme-wrap {
    border-radius: 14px;
    padding: 20px;
    background: var(--od-page-shell);
    border: 1px solid var(--od-border);
  }
  .period-theme-shell {
    border-radius: 12px;
    background: var(--od-panel);
    padding: 16px;
    border: 1px solid var(--od-border);
  }
  .period-title {
    margin: 0;
    color: var(--od-text);
    font-weight: 700;
  }
  .period-subtitle {
    color: var(--od-soft);
    font-size: 0.9rem;
  }
  .od-btn-primary {
    border: 0;
    color: #fff;
    font-weight: 700;
    border-radius: 8px;
    background: #ff4d5e;
  }
  .od-btn-primary:hover {
    color: #fff;
    filter: brightness(0.98);
  }
  .od-btn-outline {
    border: 1px solid #ccd2e3;
    color: #59607a;
    border-radius: 8px;
    background: #fff;
    font-weight: 600;
  }
  .od-btn-outline:hover {
    color: #41475d;
    border-color: #b6bdd2;
    background: #fff;
  }
  .period-hint {
    background: #fff;
    border: 1px solid var(--od-border);
    color: var(--od-soft);
    border-radius: 10px;
    padding: 10px 12px;
    font-size: 0.9rem;
  }
  .period-table-card {
    background: var(--od-panel-2);
    border: 1px solid var(--od-border);
    border-radius: 12px;
    overflow: hidden;
  }
  .period-table-toolbar {
    padding: 14px;
    border-bottom: 1px solid var(--od-line);
    background: #fff;
  }
  .period-search {
    max-width: 220px;
    border-radius: 8px;
    border: 1px solid #d4d9e8;
    color: #5f667f;
    background: #f9fafe;
  }
  .period-table th {
    background: #f1f3f9;
    color: #525a70;
    font-size: 0.88rem;
    font-weight: 700;
    border-bottom: 1px solid var(--od-line);
  }
  .period-table td {
    color: #343950;
    vertical-align: middle;
    border-bottom: 1px solid var(--od-line);
  }
  .period-table tr:last-child td {
    border-bottom: 0;
  }
  .od-badge {
    display: inline-block;
    border-radius: 999px;
    padding: 5px 12px;
    font-size: 0.75rem;
    font-weight: 700;
  }
  .od-badge-open {
    background: #ddf4e5;
    color: #2a8f4f;
  }
  .od-badge-close {
    background: #eceff7;
    color: #616984;
  }
  .period-table .btn-outline-primary {
    border-color: #c6b6fa;
    color: var(--od-accent);
  }
  .period-table .btn-outline-primary:hover {
    background: #f2ecff;
    color: #7448db;
    border-color: #b9a4f7;
  }
  .period-table .btn-outline-dark {
    border-color: #c9cfdf;
    color: #4f566f;
  }
  .period-table .btn-outline-dark:hover {
    background: #eef1f8;
    color: #3e445a;
    border-color: #b8bfd2;
  }
</style>

<div class="period-theme-wrap">
  <div class="period-theme-shell">
    <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-3">
      <div>
        <h1 class="h5 period-title">Assessment Period List</h1>
        <div class="period-subtitle">This screen is intended for ASEANstats (admin) only.</div>
      </div>
      <div class="d-flex gap-2">
        <a class="btn od-btn-primary" id="btnCreate" href="/trx/period">Create</a>
        <button class="btn od-btn-outline" id="btnRefresh" type="button">Refresh</button>
      </div>
    </div>

    <div class="period-hint mb-3" id="periodHint">
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
@endsection

@push('scripts')
<script>
  const endpoint = '/api/trx/periods';

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
      btn.classList.add('disabled');
      btn.setAttribute('aria-disabled', 'true');
      btn.addEventListener('click', preventCreateWhenActive);
      hint.textContent = 'Create is disabled because an active assessment period already exists.';
      return;
    }

    btn.classList.remove('disabled');
    btn.removeAttribute('aria-disabled');
    btn.removeEventListener('click', preventCreateWhenActive);
    hint.textContent = 'Only one active assessment period is allowed at a time.';
  }

  function preventCreateWhenActive(e) {
    e.preventDefault();
  }

  function renderRows(periods) {
    const tb = document.getElementById('tbPeriods');

    if (!Array.isArray(periods) || periods.length === 0) {
      tb.innerHTML = '<tr><td colspan="5" class="text-muted">No assessment periods found.</td></tr>';
      setCreateAvailability(false);
      return;
    }

    const hasActive = periods.some(isOpenPeriod);
    setCreateAvailability(hasActive);

    tb.innerHTML = periods.map((p) => {
      const isOpen = isOpenPeriod(p);
      const statusBadge = isOpen
        ? '<span class="od-badge od-badge-open">Open</span>'
        : '<span class="od-badge od-badge-close">Close</span>';

      const periodId = p.id;
      const viewUrl = `/trx/period/${periodId}/countries`;
      const editUrl = `/trx/period?periodId=${periodId}`;

      return `
        <tr>
          <td class="fw-semibold">${p.year ?? '-'}</td>
          <td>${statusBadge}</td>
          <td>
            <a class="btn btn-sm btn-outline-primary" href="${viewUrl}">View</a>
            <a class="btn btn-sm btn-outline-dark ms-1" href="${editUrl}">Edit</a>
          </td>
          <td class="text-muted small">${fmtDateTime(p.created_date || p.created_at)}</td>
          <td class="text-muted small">${isOpen ? '-' : fmtDateTime(p.closed_date)}</td>
        </tr>
      `;
    }).join('');
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
    document.getElementById('btnRefresh').addEventListener('click', loadPeriods);
    loadPeriods();
  });
</script>
@endpush
