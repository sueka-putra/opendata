@extends('layouts.opendata')

@section('content')
<div class="period-theme-wrap">
  <div class="period-theme-shell">
    <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-3">
      <div>
        <h1 class="h5 period-title mb-1">Assessment Histories</h1>
      </div>
      <div class="d-flex align-items-center gap-2">
        @if(auth()->user()?->isAdmin())
          <label class="form-label mb-0" for="countryFilter">Country</label>
          <select class="form-select form-select-sm" id="countryFilter" style="min-width:240px;"></select>
        @endif
      </div>
    </div>

    <div class="period-table-card">
      <div class="table-responsive" style="padding: 10px;">
        <table class="table period-table align-middle mb-0">
          <thead>
            <tr>
              <th style="width:90px;">Year</th>
              <th>Description</th>
              <th style="width:160px;">Country</th>
              <th style="width:130px;">Status</th>
              <th style="width:130px;">Period</th>
              <th style="width:110px; text-align: right;">Action</th>
            </tr>
          </thead>
          <tbody id="assessmentRows">
            <tr><td colspan="6" class="text-muted">Loading data...</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
const rowContainer = document.getElementById('assessmentRows');
const countryFilter = document.getElementById('countryFilter');

function submissionBadge(submitted) {
  if (submitted === true || submitted === 1 || submitted === '1') {
    return '<span class="od-badge od-badge-submission-submitted">Submitted</span>';
  }
  return '<span class="od-badge od-badge-submission-progress">In-progress</span>';
}

function isOpenPeriod(row) {
  if (row.active === true || row.active === 1 || row.active === '1') {
    return true;
  }

  const status = String(row.status || '').toUpperCase();
  return status === 'OPEN' || status === 'ACTIVE';
}

function renderRows(rows) {
  if (!rows.length) {
    rowContainer.innerHTML = '<tr><td colspan="6" class="text-muted">No assessment data found.</td></tr>';
    return;
  }

  rowContainer.innerHTML = rows.map((r) => {
    const statusBadge = submissionBadge(r.is_submitted);
    const periodBadge = isOpenPeriod(r)
      ? '<span class="od-badge od-badge-open">Open</span>'
      : '<span class="od-badge od-badge-close">Completed</span>';
    const formUrl = `/trx/form?periodid=${encodeURIComponent(r.period_id)}&country_code=${encodeURIComponent(r.country_code)}`;
    var stext = (r.active) ? 'Open' : 'View';

    return `
      <tr>
        <td>${r.year}</td>
        <td>${r.description || '-'}</td>
        <td>${r.country_name || r.country_code}</td>
        <td>${statusBadge}</td>
        <td>${periodBadge}</td>
        <td class="text-end"><a class="btn btn-sm btn-outline-dark" href="${formUrl}" style="width:70px;" >${stext}</a></td>
      </tr>
    `;
  }).join('');
}

async function loadAssessments() {
  try {
    let url = '/api/trx/dashboard-assessments';
    if (countryFilter && countryFilter.value) {
      url += `?country_code=${encodeURIComponent(countryFilter.value)}`;
    }

    const response = await odFetch(url);
    const data = response.data || {};

    if (countryFilter && Array.isArray(data.country_options)) {
      const current = data.selected_country || '';
      countryFilter.innerHTML = data.country_options
        .map((opt) => `<option value="${opt.code}">${opt.name}</option>`)
        .join('');
      if (current) countryFilter.value = current;
    }

    renderRows(data.rows || []);
  } catch (error) {
    rowContainer.innerHTML = `<tr><td colspan="6" class="text-danger">${error.message}</td></tr>`;
  }
}

if (countryFilter) {
  countryFilter.addEventListener('change', loadAssessments);
}

loadAssessments();
</script>
@endpush
