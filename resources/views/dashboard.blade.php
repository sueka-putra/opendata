@extends('layouts.opendata')

@section('content')
<div class="period-theme-wrap">
  <div class="period-theme-shell">
    <div id="activeAssessmentCard" class="period-table-card dashboard-active-card mb-3 d-none">
      <div class="dashboard-active-card-body">
        <div>
          <div class="dashboard-active-card-title">Active Assessment</div>
          <div id="activeAssessmentText" class="dashboard-active-card-text"></div>
        </div>
        <a id="activeAssessmentBtn" class="btn od-btn-primary" href="#">Take Assessment</a>
      </div>
    </div>

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
              <th style="width:130px;">Period</th>
              <th style="width:130px;">Status</th>
              <th style="width:120px;">Progress</th>
              <th style="width:170px;">Coverage Sub Score</th>
              <th style="width:170px;">Opennes Sub Score</th>
              <th style="width:140px;">Overall Score</th>
              <th style="width:110px; text-align: right;">Action</th>
            </tr>
          </thead>
          <tbody id="assessmentRows">
            <tr><td colspan="9" class="text-muted">Loading data...</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="dashboard-charts-grid mt-3">
      <div class="period-table-card dashboard-chart-card">
        <div class="dashboard-chart-head">
          <div>
            <h2 class="dashboard-chart-title">Score Trend Overview</h2>
            <p class="dashboard-chart-subtitle">Coverage, Opennes, and Overall score across assessment periods.</p>
          </div>
        </div>
        <div class="dashboard-chart-body">
          <canvas id="historyScoreChart"></canvas>
        </div>
      </div>
      <div class="period-table-card dashboard-chart-card">
        <div class="dashboard-chart-head">
          <div class="dashboard-chart-head-row">
          <div>
            <h2 class="dashboard-chart-title">Section Score Comparison</h2>
            <p class="dashboard-chart-subtitle">Overall score per section, grouped by year.</p>
          </div>
          </div>
        </div>
        <div class="dashboard-chart-body">
          <canvas id="sectionScoreChart"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('styles')
<style>
  .dashboard-charts-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
  }

  .dashboard-active-card {
    border: 1px solid #c9ddff;
    background:
      radial-gradient(120% 120% at 100% -10%, rgba(56, 124, 255, 0.16), transparent 58%),
      linear-gradient(180deg, #fafdff 0%, #f2f8ff 100%);
  }

  .dashboard-active-card-body {
    padding: 14px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    flex-wrap: wrap;
  }

  .dashboard-active-card-title {
    font-size: 0.84rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #28589a;
    margin-bottom: 2px;
  }

  .dashboard-active-card-text {
    color: #2b4268;
    font-weight: 500;
  }

  .dashboard-chart-card {
    border-radius: 14px;
    overflow: hidden;
    background:
      radial-gradient(120% 120% at 100% -10%, rgba(85, 160, 255, 0.15), transparent 55%),
      radial-gradient(120% 120% at -5% 120%, rgba(30, 111, 210, 0.12), transparent 50%),
      #ffffff;
  }

  .dashboard-chart-head {
    padding: 14px 16px 4px;
    border-bottom: 1px solid rgba(196, 212, 234, 0.6);
  }

  .dashboard-chart-head-row {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 10px;
    flex-wrap: wrap;
  }

  .dashboard-chart-title {
    margin: 0;
    font-size: 0.98rem;
    font-weight: 800;
    color: #2c4268;
  }

  .dashboard-chart-subtitle {
    margin: 2px 0 0;
    font-size: 0.8rem;
    color: #617799;
  }

  .dashboard-chart-body {
    padding: 10px 12px 12px;
    min-height: 290px;
  }

  .dashboard-chart-body canvas {
    width: 100% !important;
    height: 260px !important;
  }

  @media (max-width: 1199.98px) {
    .dashboard-charts-grid {
      grid-template-columns: 1fr;
    }

    .dashboard-active-card-body .btn {
      width: 100%;
    }
  }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
const rowContainer = document.getElementById('assessmentRows');
const countryFilter = document.getElementById('countryFilter');
const activeAssessmentCard = document.getElementById('activeAssessmentCard');
const activeAssessmentText = document.getElementById('activeAssessmentText');
const activeAssessmentBtn = document.getElementById('activeAssessmentBtn');
let historyScoreChart = null;
let sectionScoreChart = null;
let latestRows = [];

function submissionBadge(submitted, periodOpen = true) {
  if (submitted === true || submitted === 1 || submitted === '1') {
    return '<span class="od-badge od-badge-submission-submitted">Submitted</span>';
  }
  if (!periodOpen) {
    return '<span class="od-badge od-badge-submission-not-submitted">Not-Submitted</span>';
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
    rowContainer.innerHTML = '<tr><td colspan="9" class="text-muted">No assessment data found.</td></tr>';
    return;
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

  rowContainer.innerHTML = rows.map((r) => {
    const periodOpen = isOpenPeriod(r);
    const statusBadge = submissionBadge(r.is_submitted, periodOpen);
    const periodBadge = periodOpen
      ? '<span class="od-badge od-badge-open">Open</span>'
      : '<span class="od-badge od-badge-close">Completed</span>';
    const formUrl = `/trx/form?periodid=${encodeURIComponent(r.period_id)}&country_code=${encodeURIComponent(r.country_code)}`;
    const stext = periodOpen ? 'Assess' : 'View';

    return `
      <tr>
        <td>${r.year}</td>
        <td>${r.description || '-'}</td>
        <td>${periodBadge}</td>
        <td>${statusBadge}</td>
        <td>${fmtPercent(r.progress)}</td>
        <td>${fmtRatio(r.coverage_sub_score_ratio)}</td>
        <td>${fmtRatio(r.opennes_sub_score_ratio)}</td>
        <td>${fmtRatio(r.overall_score_ratio)}</td>
        <td class="text-end"><a class="btn btn-sm btn-outline-dark" href="${formUrl}" style="width:70px;" >${stext}</a></td>
      </tr>
    `;
  }).join('');
}

function renderActiveAssessmentCard(rows) {
  if (!activeAssessmentCard || !activeAssessmentText || !activeAssessmentBtn) return;

  const activeRow = rows.find((r) => isOpenPeriod(r));
  if (!activeRow) {
    activeAssessmentCard.classList.add('d-none');
    activeAssessmentBtn.setAttribute('href', '#');
    return;
  }

  const periodTitle = String(activeRow.description || '-');
  const referenceYear = String(activeRow.year || '-');
  const formUrl = `/trx/form?periodid=${encodeURIComponent(activeRow.period_id)}&country_code=${encodeURIComponent(activeRow.country_code)}`;

  activeAssessmentText.textContent = `There is currently an active Assessment ${periodTitle} for reference year ${referenceYear}.`;
  activeAssessmentBtn.setAttribute('href', formUrl);
  activeAssessmentCard.classList.remove('d-none');
}

function chartPalette() {
  return {
    coverage: '#3b82f6',
    openness: '#0ea5a4',
    overall: '#7c3aed',
  };
}

function toPercentLabel(v) {
  const n = Number(v ?? 0);
  return `${(Number.isFinite(n) ? n : 0).toFixed(1)}%`;
}

function destroyCharts() {
  if (historyScoreChart) {
    historyScoreChart.destroy();
    historyScoreChart = null;
  }
  if (sectionScoreChart) {
    sectionScoreChart.destroy();
    sectionScoreChart = null;
  }
}

function renderHistoryScoreChart(rows) {
  const canvas = document.getElementById('historyScoreChart');
  if (!canvas || !window.Chart) return;
  const ctx = canvas.getContext('2d');
  const colors = chartPalette();
  const ordered = [...rows].sort((a, b) => Number(a.year || 0) - Number(b.year || 0));
  const labels = ordered.map((r) => `${r.year}`);
  const coverage = ordered.map((r) => Number(r.coverage_sub_score_ratio ?? 0) * 100);
  const openness = ordered.map((r) => Number(r.opennes_sub_score_ratio ?? 0) * 100);
  const overall = ordered.map((r) => Number(r.overall_score_ratio ?? 0) * 100);

  const covGradient = ctx.createLinearGradient(0, 0, 0, 280);
  covGradient.addColorStop(0, 'rgba(59,130,246,0.35)');
  covGradient.addColorStop(1, 'rgba(59,130,246,0.02)');

  const openGradient = ctx.createLinearGradient(0, 0, 0, 280);
  openGradient.addColorStop(0, 'rgba(14,165,164,0.32)');
  openGradient.addColorStop(1, 'rgba(14,165,164,0.02)');

  historyScoreChart = new Chart(ctx, {
    type: 'line',
    data: {
      labels,
      datasets: [
        {
          label: 'Coverage Sub Score',
          data: coverage,
          borderColor: colors.coverage,
          backgroundColor: covGradient,
          fill: true,
          tension: 0.35,
          borderWidth: 2.2,
          pointRadius: 4,
          pointHoverRadius: 5,
        },
        {
          label: 'Opennes Sub Score',
          data: openness,
          borderColor: colors.openness,
          backgroundColor: openGradient,
          fill: true,
          tension: 0.35,
          borderWidth: 2.2,
          pointRadius: 4,
          pointHoverRadius: 5,
        },
        {
          label: 'Overall Score',
          data: overall,
          borderColor: colors.overall,
          backgroundColor: 'rgba(124,58,237,0.08)',
          fill: false,
          tension: 0.3,
          borderDash: [5, 4],
          borderWidth: 2.4,
          pointRadius: 3.6,
          pointHoverRadius: 5,
        },
      ],
    },
    options: {
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: { position: 'bottom' },
        tooltip: {
          callbacks: {
            label: (ctx2) => `${ctx2.dataset.label}: ${toPercentLabel(ctx2.parsed.y)}`,
          },
        },
      },
      scales: {
        y: {
          beginAtZero: true,
          max: 100,
          ticks: { callback: (v) => `${v}%` },
          grid: { color: 'rgba(146,169,204,0.25)' },
        },
        x: {
          grid: { display: false },
        },
      },
    },
  });
}

function renderSectionScoreChart(rows) {
  const canvas = document.getElementById('sectionScoreChart');
  if (!canvas || !window.Chart) return;
  const ctx = canvas.getContext('2d');
  const sortedRows = [...rows].sort((a, b) => Number(a.year || 0) - Number(b.year || 0));
  const labels = sortedRows.map((r) => String(r.year ?? '-'));
  const sectionSet = new Set();
  sortedRows.forEach((r) => {
    (Array.isArray(r.section_scores) ? r.section_scores : []).forEach((s) => {
      sectionSet.add(String(s.section_title || `Section ${s.section_id || ''}`));
    });
  });
  const sectionTitles = Array.from(sectionSet);

  function colorFromSection(sectionTitle) {
    let hash = 0;
    for (let i = 0; i < sectionTitle.length; i += 1) {
      hash = sectionTitle.charCodeAt(i) + ((hash << 5) - hash);
    }
    const hue = Math.abs(hash) % 360;
    return `hsla(${hue}, 72%, 52%, 0.82)`;
  }

  const datasets = sectionTitles.map((sectionTitle) => {
    const dataPoints = sortedRows.map((r) => {
      const sections = Array.isArray(r.section_scores) ? r.section_scores : [];
      const sectionRow = sections.find((s) => String(s.section_title || `Section ${s.section_id || ''}`) === sectionTitle);
      if (!sectionRow) return 0;
      const coverage = Number(sectionRow.coverage_sub_score_ratio ?? 0);
      const openness = Number(sectionRow.opennes_sub_score_ratio ?? 0);
      const overallSection = ((0.5 * coverage) + (0.5 * openness)) * 100;
      return Number.isFinite(overallSection) ? overallSection : 0;
    });
    const fillColor = colorFromSection(sectionTitle);
    return {
      label: sectionTitle,
      data: dataPoints,
      backgroundColor: fillColor,
      borderColor: fillColor.replace('0.82', '1'),
      borderWidth: 1,
      borderRadius: 7,
      borderSkipped: false,
    };
  });

  sectionScoreChart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels,
      datasets,
    },
    options: {
      maintainAspectRatio: false,
      plugins: {
        legend: { position: 'bottom', labels: { boxWidth: 14, usePointStyle: true, pointStyle: 'rectRounded' } },
        tooltip: {
          callbacks: {
            title: (items) => `Year ${items[0]?.label || '-'}`,
            label: (ctx2) => `${ctx2.dataset.label}: ${toPercentLabel(ctx2.parsed.y)}`,
          },
        },
      },
      scales: {
        y: {
          beginAtZero: true,
          max: 100,
          ticks: { callback: (v) => `${v}%` },
          grid: { color: 'rgba(146,169,204,0.24)' },
        },
        x: {
          ticks: { autoSkip: false },
          grid: { display: false },
        },
      },
    },
  });
}

function renderCharts(rows) {
  destroyCharts();
  if (!rows.length) return;
  renderHistoryScoreChart(rows);
  renderSectionScoreChart(rows);
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

    latestRows = data.rows || [];
    renderRows(latestRows);
    renderActiveAssessmentCard(latestRows);
    renderCharts(latestRows);
  } catch (error) {
    latestRows = [];
    if (activeAssessmentCard) activeAssessmentCard.classList.add('d-none');
    destroyCharts();
    rowContainer.innerHTML = `<tr><td colspan="9" class="text-danger">${error.message}</td></tr>`;
  }
}

if (countryFilter) {
  countryFilter.addEventListener('change', loadAssessments);
}

loadAssessments();
</script>
@endpush
