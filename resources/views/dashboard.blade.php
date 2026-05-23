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
              <th>Assessments</th>
              <th style="width:130px;">Period Status</th>
              <th style="width:130px;">Progress</th>
              <th style="width:120px;">Progress</th>
              <th style="width:170px;">Coverage Sub Score</th>
              <th style="width:170px;">Opennes Sub Score</th>
              <th style="width:140px;">Overall Score</th>
              <th id="dashboardActionColHeader" style="width:110px; text-align: right;">Action</th>
            </tr>
          </thead>
          <tbody id="assessmentRows">
            <tr><td colspan="8" class="text-muted">Loading data...</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="dashboard-charts-grid mt-3">
      <div id="dashboardHistoryChartCard" class="period-table-card dashboard-chart-card">
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
      <div id="dashboardSectionChartCard" class="period-table-card dashboard-chart-card">
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
<button class="dashboard-fab-help" type="button" id="btnDashboardHelpWizard" aria-label="Open dashboard help" title="Help">
  <span class="dashboard-fab-help-icon" aria-hidden="true">
    <i class="fa-solid fa-circle-question" aria-hidden="true"></i>
  </span>
  <span class="dashboard-fab-help-label">Help</span>
</button>
<div class="modal fade period-dialog assessment-help-wizard-modal" id="dashboardHelpWizardDialog" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title mb-0">Quick Guides</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="assessment-help-layout">
          <div class="assessment-help-outline-wrap">
            <div class="assessment-help-outline-title">Outline</div>
            <div id="dashboardHelpWizardOutline" class="assessment-help-outline-list"></div>
          </div>
          <div class="assessment-help-detail-wrap">
            <div class="small text-muted mb-2" id="dashboardHelpWizardStepMeta">Step 1 of 1</div>
            <h6 class="mb-2" id="dashboardHelpWizardStepTitle">Welcome</h6>
            <p class="mb-2" id="dashboardHelpWizardStepDescription">This wizard guides you through the main actions in this page.</p>
            <div class="assessment-help-hint-box small mb-0" id="dashboardHelpWizardTargetHint">
              Focus area will be highlighted on the page.
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn od-btn-outline" type="button" id="btnDashboardHelpWizardPrev">Previous</button>
        <button class="btn od-btn-primary" type="button" id="btnDashboardHelpWizardNext">Next</button>
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
    border: 1px solid #4f8ff0;
    box-shadow:
      0 10px 26px rgba(38, 94, 182, 0.22),
      0 0 0 1px rgba(96, 151, 241, 0.25) inset;
    background:
      radial-gradient(120% 140% at 100% -20%, rgba(126, 184, 255, 0.35), transparent 56%),
      radial-gradient(120% 120% at -8% 118%, rgba(40, 118, 255, 0.3), transparent 54%),
      linear-gradient(145deg, #edf5ff 0%, #dcecff 52%, #d2e6ff 100%);
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

  .dashboard-fab-help {
    position: fixed;
    right: 1rem;
    bottom: 1rem;
    z-index: 1049;
    min-width: 116px;
    border: 1px solid #2b76e5;
    border-radius: 999px;
    background: #ffffff;
    color: #2b76e5;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.45rem;
    padding: 9px 14px;
    box-shadow: 0 8px 20px rgba(16, 73, 160, 0.18);
    font-size: 0.8rem;
    font-weight: 600;
    line-height: 1;
    transition: background-color 160ms ease, color 160ms ease, border-color 160ms ease, transform 160ms ease;
  }

  .dashboard-fab-help:hover,
  .dashboard-fab-help:focus-visible {
    background: #2b76e5;
    color: #ffffff;
    border-color: #2b76e5;
    transform: translateY(-1px);
  }

  .dashboard-fab-help-icon {
    width: auto;
    height: auto;
    font-size: 1rem;
  }

  .dashboard-fab-help-label {
    font-size: 0.8rem;
    font-weight: 600;
  }

  .assessment-help-wizard-modal {
    pointer-events: none;
    z-index: 3000 !important;
  }

  .assessment-help-wizard-modal .modal-dialog {
    margin: 0;
    position: fixed;
    top: 0.9rem;
    right: 1rem;
    width: min(740px, calc(100vw - 2rem));
    max-width: min(740px, calc(100vw - 2rem));
    transform: none !important;
    z-index: 3001;
  }

  .assessment-help-wizard-modal .modal-dialog:not(.is-positioned) {
    visibility: hidden;
  }

  .assessment-help-wizard-modal .modal-content {
    border: 2px solid #2563eb;
    border-radius: 8px;
    box-shadow: 0 8px 22px rgba(37, 99, 235, 0.2);
    background: #f8fbff;
  }

  .assessment-help-wizard-modal .modal-header {
    border-bottom: 1px solid #bfdbfe;
    background: #dbeafe;
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
  }

  .assessment-help-wizard-modal .modal-title {
    color: #1d4ed8;
    font-size: 0.95rem;
    font-weight: 700;
    letter-spacing: 0.01em;
  }

  .assessment-help-layout {
    display: flex;
    flex-wrap: wrap;
    gap: 0.7rem;
  }

  .assessment-help-outline-wrap {
    flex: 0 0 250px;
    max-width: 250px;
  }

  .assessment-help-outline-title {
    font-size: 0.74rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #335f9f;
    margin-bottom: 0.48rem;
  }

  .assessment-help-outline-list {
    border: 1px solid #d5e5ff;
    border-radius: 8px;
    background: #ffffff;
    max-height: 280px;
    overflow: auto;
    padding: 0.28rem;
  }

  .assessment-help-outline-item {
    border-radius: 6px;
    padding: 0.34rem 0.5rem;
    cursor: pointer;
    font-size: 0.78rem;
    color: #21406b;
  }

  .assessment-help-outline-item:hover {
    background: #eff6ff;
  }

  .assessment-help-outline-item.is-active {
    background: #dbeafe;
    color: #1d4ed8;
    font-weight: 700;
  }

  .assessment-help-detail-wrap {
    flex: 1 1 320px;
    max-width: calc(100% - 260px);
  }

  .assessment-help-hint-box {
    border: 1px solid #c7dcff;
    border-radius: 8px;
    background: #eef5ff;
    color: #2e4f7e;
    padding: 0.42rem 0.52rem;
    line-height: 1.35;
  }

  .assessment-help-wizard-modal .modal-footer {
    border-top: 1px solid #bfdbfe;
    background: #eaf2ff;
    border-bottom-left-radius: 8px;
    border-bottom-right-radius: 8px;
    padding-top: 0.62rem;
    padding-bottom: 0.62rem;
  }

  .assessment-wizard-highlight {
    z-index: 1061;
    outline: 3px solid rgba(37, 99, 235, 0.95);
    outline-offset: 3px;
    box-shadow: 0 0 0 8px rgba(59, 130, 246, 0.25);
    border-radius: 10px;
  }

  @media (max-width: 1199.98px) {
    .dashboard-charts-grid {
      grid-template-columns: 1fr;
    }

    .dashboard-active-card-body .btn {
      width: 100%;
    }

    .dashboard-fab-help {
      right: 0.75rem;
      bottom: 0.75rem;
      min-width: 110px;
      padding: 8px 12px;
    }
  }

  @media (max-width: 991.98px) {
    .assessment-help-wizard-modal .modal-dialog {
      right: 0.6rem;
      top: 0.6rem;
      width: calc(100vw - 1.2rem);
      max-width: calc(100vw - 1.2rem);
    }

    .assessment-help-outline-wrap,
    .assessment-help-detail-wrap {
      flex: 1 1 auto;
      max-width: 100%;
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
const btnDashboardHelpWizard = document.getElementById('btnDashboardHelpWizard');
const dashboardHelpWizardDialogEl = document.getElementById('dashboardHelpWizardDialog');
const dashboardHelpWizardModal = dashboardHelpWizardDialogEl && window.bootstrap?.Modal
  ? new bootstrap.Modal(dashboardHelpWizardDialogEl, { backdrop: false, keyboard: true })
  : null;
const dashboardHelpWizardState = { stepIndex: 0 };
const dashboardHelpWizardSteps = [
  {
    title: 'Active Assessment Card',
    description: 'If an assessment period is currently open, this card will appear at the top of the page. Click Take Assessment to go directly to the active assessment form.',
    selector: '#activeAssessmentCard',
    hint: 'This is the quickest way to continue working on the current assessment.',
  },
  {
    title: 'Assessment Histories Table',
    description: 'This table shows the list of assessment periods together with the period status, submission status, completion progress, and score summary.',
    selector: '#assessmentRows',
    hint: 'Use this table to track your assessment records across different periods.',
  },
  {
    title: 'Action Column (Assess/View)',
    description: 'Use Assess to continue or open the active assessment period. Use View to review assessments from completed periods.',
    selector: '#dashboardActionColHeader',
    hint: 'Choose Assess for ongoing work and View for reference only.',
  },
  {
    title: 'Score Trend Overview Chart',
    description: 'This chart shows the trend of Coverage, Openness, and Overall Score across assessment periods.',
    selector: '#dashboardHistoryChartCard',
    hint: 'Use this chart to quickly see whether your scores are improving over time.',
  },
  {
    title: 'Section Score Comparison Chart',
    description: 'This chart compares section-level scores across assessment periods, helping you identify areas that have improved or may need further attention.',
    selector: '#dashboardSectionChartCard',
    hint: 'Useful for spotting strong sections and sections that may need follow-up.',
  },
  {
    title: 'Help Button',
    description: 'Click the Help button anytime to reopen this dashboard guide and review the key page features again.',
    selector: '#btnDashboardHelpWizard',
    hint: 'Helpful for first-time users and whenever you need a quick reminder.',
  },
];

function esc(input) {
  return String(input ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function clearDashboardHelpWizardHighlight() {
  document.querySelectorAll('.assessment-wizard-highlight').forEach((el) => {
    el.classList.remove('assessment-wizard-highlight');
  });
}

function resolveDashboardHelpWizardTarget(step) {
  if (!step?.selector) return null;
  return document.querySelector(step.selector);
}

function scrollDashboardHelpWizardTargetIntoView(target) {
  if (!target || typeof target.scrollIntoView !== 'function') return;
  const style = window.getComputedStyle(target);
  if (style.position === 'fixed' || style.position === 'sticky') return;
  target.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' });
}

function positionDashboardHelpWizardDialog(target = null) {
  const dialog = dashboardHelpWizardDialogEl?.querySelector('.modal-dialog');
  if (!dialog) return;
  dialog.classList.add('is-positioned');
  if (!target) {
    dialog.style.left = '';
    dialog.style.top = '0.9rem';
    dialog.style.right = '1rem';
    return;
  }

  const margin = 12;
  const viewportWidth = window.innerWidth;
  const viewportHeight = window.innerHeight;
  const dialogWidth = dialog.offsetWidth || Math.min(740, viewportWidth - (margin * 2));
  const dialogHeight = dialog.offsetHeight || 380;
  const rect = target.getBoundingClientRect();
  const spaceRight = viewportWidth - rect.right;
  const spaceLeft = rect.left;

  let left = null;
  let right = null;
  if (spaceRight >= dialogWidth + margin) {
    left = rect.right + margin;
  } else if (spaceLeft >= dialogWidth + margin) {
    left = rect.left - dialogWidth - margin;
  } else {
    right = margin;
  }

  let top = rect.top;
  const spaceAbove = rect.top;
  const spaceBelow = viewportHeight - rect.bottom;
  if (spaceAbove >= dialogHeight + margin) {
    top = rect.top - dialogHeight - margin;
  } else if (spaceBelow >= dialogHeight + margin) {
    top = rect.bottom + margin;
  } else if (spaceBelow >= spaceAbove) {
    top = Math.max(margin, rect.bottom + margin);
  } else {
    top = Math.max(margin, rect.top - dialogHeight - margin);
  }
  if (top + dialogHeight > viewportHeight - margin) top = viewportHeight - dialogHeight - margin;
  if (top < margin) top = margin;

  if (left !== null) {
    const maxLeft = viewportWidth - dialogWidth - margin;
    dialog.style.left = `${Math.max(margin, Math.min(left, maxLeft))}px`;
    dialog.style.right = 'auto';
  } else {
    dialog.style.left = '';
    dialog.style.right = `${right ?? margin}px`;
  }
  dialog.style.top = `${Math.round(top)}px`;
}

function renderDashboardHelpWizardOutline(activeIndex = 0) {
  const outlineEl = document.getElementById('dashboardHelpWizardOutline');
  if (!outlineEl) return;
  outlineEl.innerHTML = dashboardHelpWizardSteps.map((step, index) => {
    const activeClass = index === activeIndex ? ' is-active' : '';
    return `<div class="assessment-help-outline-item${activeClass}" data-step-index="${index}">${index + 1}. ${esc(step.title || 'Step')}</div>`;
  }).join('');
}

function renderDashboardHelpWizardStep() {
  if (!dashboardHelpWizardModal || !dashboardHelpWizardSteps.length) return;
  const maxIndex = dashboardHelpWizardSteps.length - 1;
  const safeIndex = Math.min(Math.max(Number(dashboardHelpWizardState.stepIndex) || 0, 0), maxIndex);
  dashboardHelpWizardState.stepIndex = safeIndex;
  const step = dashboardHelpWizardSteps[safeIndex];

  const stepMetaEl = document.getElementById('dashboardHelpWizardStepMeta');
  const titleEl = document.getElementById('dashboardHelpWizardStepTitle');
  const descEl = document.getElementById('dashboardHelpWizardStepDescription');
  const hintEl = document.getElementById('dashboardHelpWizardTargetHint');
  const prevBtn = document.getElementById('btnDashboardHelpWizardPrev');
  const nextBtn = document.getElementById('btnDashboardHelpWizardNext');

  if (stepMetaEl) stepMetaEl.textContent = `Step ${safeIndex + 1} of ${dashboardHelpWizardSteps.length}`;
  if (titleEl) titleEl.textContent = step.title || 'Help';
  if (descEl) descEl.textContent = step.description || '';
  if (hintEl) hintEl.textContent = step.hint || '';
  if (prevBtn) prevBtn.disabled = safeIndex <= 0;
  if (nextBtn) nextBtn.textContent = safeIndex >= maxIndex ? 'Finish' : 'Next';

  renderDashboardHelpWizardOutline(safeIndex);
  clearDashboardHelpWizardHighlight();
  const target = resolveDashboardHelpWizardTarget(step);
  if (!target) {
    if (hintEl) hintEl.textContent = 'Target area is not available yet in the current page state.';
    positionDashboardHelpWizardDialog(null);
    return;
  }
  target.classList.add('assessment-wizard-highlight');
  scrollDashboardHelpWizardTargetIntoView(target);
  positionDashboardHelpWizardDialog(target);
  window.setTimeout(() => positionDashboardHelpWizardDialog(target), 220);
}

function openDashboardHelpWizard(startIndex = 0) {
  if (!dashboardHelpWizardModal || !dashboardHelpWizardSteps.length) return;
  dashboardHelpWizardState.stepIndex = Number(startIndex) || 0;
  const step = dashboardHelpWizardSteps[dashboardHelpWizardState.stepIndex] || null;
  const target = resolveDashboardHelpWizardTarget(step);
  positionDashboardHelpWizardDialog(target);
  dashboardHelpWizardDialogEl?.querySelector('.modal-dialog')?.classList.add('is-positioned');
  dashboardHelpWizardModal.show();
  window.setTimeout(() => {
    renderDashboardHelpWizardStep();
  }, 40);
}

function displayPeriodYear(rawYear) {
  const n = Number(rawYear);
  if (!Number.isFinite(n)) return String(rawYear ?? '-');
  return String(n + 1);
}

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
    rowContainer.innerHTML = '<tr><td colspan="8" class="text-muted">No assessment data found.</td></tr>';
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

  activeAssessmentText.textContent = `You have an active Assessment ${periodTitle}`;
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
  const labels = ordered.map((r) => displayPeriodYear(r.year));
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
  const labels = sortedRows.map((r) => displayPeriodYear(r.year));
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
            title: (items) => `Period ${items[0]?.label || '-'}`,
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
    rowContainer.innerHTML = `<tr><td colspan="8" class="text-danger">${error.message}</td></tr>`;
  }
}

if (countryFilter) {
  countryFilter.addEventListener('change', loadAssessments);
}

if (btnDashboardHelpWizard) {
  btnDashboardHelpWizard.addEventListener('click', () => {
    openDashboardHelpWizard(0);
  });
}

document.getElementById('btnDashboardHelpWizardPrev')?.addEventListener('click', () => {
  dashboardHelpWizardState.stepIndex -= 1;
  renderDashboardHelpWizardStep();
});

document.getElementById('btnDashboardHelpWizardNext')?.addEventListener('click', () => {
  const lastIndex = dashboardHelpWizardSteps.length - 1;
  if (dashboardHelpWizardState.stepIndex >= lastIndex) {
    dashboardHelpWizardModal?.hide();
    return;
  }
  dashboardHelpWizardState.stepIndex += 1;
  renderDashboardHelpWizardStep();
});

document.addEventListener('click', (event) => {
  const outlineItem = event.target.closest('#dashboardHelpWizardOutline .assessment-help-outline-item');
  if (!outlineItem) return;
  const idx = Number(outlineItem.dataset.stepIndex);
  if (!Number.isFinite(idx)) return;
  dashboardHelpWizardState.stepIndex = idx;
  renderDashboardHelpWizardStep();
});

dashboardHelpWizardDialogEl?.addEventListener('hidden.bs.modal', () => {
  clearDashboardHelpWizardHighlight();
  dashboardHelpWizardDialogEl.querySelector('.modal-dialog')?.classList.remove('is-positioned');
});

window.addEventListener('resize', () => {
  if (!dashboardHelpWizardDialogEl?.classList.contains('show')) return;
  const currentStep = dashboardHelpWizardSteps[dashboardHelpWizardState.stepIndex] || null;
  const target = resolveDashboardHelpWizardTarget(currentStep);
  positionDashboardHelpWizardDialog(target);
});

loadAssessments();
</script>
@endpush
