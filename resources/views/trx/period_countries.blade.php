@extends('layouts.opendata')
@section('content')
<div class="period-theme-wrap">
  <div class="period-theme-shell">
    <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-3">
      <div>
        <h1 class="h5 mb-1">Assessment Period Participants</h1>
        <div class="text-muted small" id="periodMeta">Loading period...</div>
      </div>
      <div class="d-flex gap-2">
        <a class="btn od-btn-outline" href="{{ route('trx.periods') }}">Back to Periods</a>
        <button class="btn od-btn-primary" type="button" id="btnRefreshCountries">Refresh</button>
      </div>
    </div>

    <div class="period-table-card mt-3">
      <div class="table-responsive">
        <table class="table period-table align-middle mb-0">
          <thead>
            <tr>
              <th style="width: 80px;">No</th>
              <th>Participant</th>
              <th style="width: 180px;">Status</th>
              <th style="width: 120px;">Progress</th>
              <th style="width: 170px;">Coverage Sub Score</th>
              <th style="width: 170px;">Opennes Sub Score</th>
              <th style="width: 140px;">Overall Score</th>
              <th style="width: 240px;">Last Modified</th>
              <th style="width: 120px; text-align: right;">Action</th>
            </tr>
          </thead>
          <tbody id="tbCountries">
            <tr><td colspan="9" class="text-muted">Loading...</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  const periodId = @json((int) request()->route('periodId'));

  function fmtDateTime(value) {
    if (!value) return '-';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return String(value);
    return d.toLocaleString();
  }

  function statusBadge(submitted, isPeriodOpen = true) {
    if (submitted === true || submitted === 1 || submitted === '1') {
      return '<span class="od-badge od-badge-submission-submitted">Submitted</span>';
    }
    if (!isPeriodOpen) {
      return '<span class="od-badge od-badge-submission-not-submitted">Not-Submitted</span>';
    }
    return '<span class="od-badge od-badge-submission-progress">In-progress</span>';
  }

  function fmtPercent(value) {
    const num = Number(value ?? 0);
    if (Number.isNaN(num)) return '0%';
    return `${num.toFixed(2)}%`;
  }

  function fmtRatio(value) {
    const num = Number(value ?? 0);
    if (Number.isNaN(num)) return '0.00';
    return num.toFixed(2);
  }

  function renderPeriodMeta(period) {
    const meta = document.getElementById('periodMeta');
    if (!period) {
      meta.textContent = 'Period not found.';
      return;
    }

    const state = (period.active === true || period.active === 1 || period.active === '1')
      ? 'Open'
      : 'Completed';
    meta.textContent = `Period ${period.year ?? '-'} | ${state} | ${period.description || '-'}`;
  }

  function renderCountries(countries) {
    const tbody = document.getElementById('tbCountries');
    if (!Array.isArray(countries) || countries.length === 0) {
      tbody.innerHTML = '<tr><td colspan="9" class="text-muted">No participants found for this period.</td></tr>';
      return;
    }

    const sorted = [...countries].sort((a, b) => String(a.country_name || '').localeCompare(String(b.country_name || '')));
    const isOpen = window.__periodIsOpen === true;

    tbody.innerHTML = sorted.map((row, idx) => `
      <tr>
        <td>${idx + 1}</td>
        <td class="fw-semibold">${row.country_name || row.country_code || '-'}</td>
        <td>${statusBadge(row.is_submitted, isOpen)}</td>
        <td>${fmtPercent(row.progress)}</td>
        <td>${fmtRatio(row.coverage_sub_score_ratio)}</td>
        <td>${fmtRatio(row.opennes_sub_score_ratio)}</td>
        <td>${fmtRatio(row.overall_score_ratio)}</td>
        <td class="text-muted small">${fmtDateTime(row.modified_date)}</td>
        <td class="text-end">
          <a class="btn btn-sm btn-outline-dark" href="/trx/form?periodid=${encodeURIComponent(periodId)}&country_code=${encodeURIComponent(row.country_code)}">${isOpen ? 'Open' : 'View'}</a>
        </td>
      </tr>
    `).join('');
  }

  async function loadCountries() {
    const tbody = document.getElementById('tbCountries');
    tbody.innerHTML = '<tr><td colspan="9" class="text-muted">Loading...</td></tr>';

    try {
      const response = await odFetch(`/api/trx/countries/${periodId}`);
      const payload = response.data || {};
      window.__periodIsOpen = payload.period && (payload.period.active === true || payload.period.active === 1 || payload.period.active === '1');
      renderPeriodMeta(payload.period || null);
      renderCountries(payload.countries || []);
    } catch (err) {
      document.getElementById('periodMeta').textContent = 'Failed to load period data.';
      tbody.innerHTML = `<tr><td colspan="9" class="text-danger">${err.message || 'Failed to load participants.'}</td></tr>`;
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('btnRefreshCountries').addEventListener('click', loadCountries);
    loadCountries();
  });
</script>
@endpush
