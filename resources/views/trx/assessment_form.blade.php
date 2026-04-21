@extends('layouts.opendata')

@section('content')
<div class="period-theme-wrap">
  <div class="period-theme-shell assessment-shell">
    <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-3">
      <div>
        <h1 class="h5 period-title mb-1">Assessment Form</h1>
        <div class="period-subtitle" id="formMeta">Loading assessment...</div>
      </div>
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <a class="btn od-btn-outline" href="{{ route('dashboard') }}">Back to Dashboard</a>
        <button class="btn od-btn-outline" type="button" id="btnRefreshForm">Refresh</button>
        <button class="btn od-btn-primary" type="button" id="btnSaveForm">Save</button>
        <button class="btn od-btn-primary" type="button" id="btnSubmitForm">Submit</button>
      </div>
    </div>

    <div class="period-hint mb-3" id="formHint" style="display:none;"></div>
    <div class="alert alert-danger d-none mb-3" id="formError"></div>

    <ul class="nav nav-tabs mb-3" id="assessmentTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="entry-tab" data-bs-toggle="tab" data-bs-target="#entry-pane" type="button" role="tab" aria-controls="entry-pane" aria-selected="true">Entry</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="summary-tab" data-bs-toggle="tab" data-bs-target="#summary-pane" type="button" role="tab" aria-controls="summary-pane" aria-selected="false">Summary</button>
      </li>
    </ul>

    <div class="tab-content" id="assessmentTabContent">
      <div class="tab-pane fade show active" id="entry-pane" role="tabpanel" aria-labelledby="entry-tab">
        <div class="period-table-card mb-3">
          <div class="period-table-toolbar assessment-entry-toolbar">
            <div class="assessment-entry-filters">
              <div class="assessment-filter-item">
                <label class="form-label mb-0 small text-muted assessment-filter-label" for="entrySectionFilter">
                  Section
                  <span
                    class="assessment-help-icon"
                    aria-label="Section help"
                    data-bs-toggle="tooltip"
                    data-bs-placement="top"
                    data-bs-custom-class="assessment-help-tooltip"
                    title="The highest grouping level in the assessment. By default, the filter is set to All Sections. Selecting a specific Section will refresh the assessment rows to show only records under that Section, and the next filter will be reset to All Categories."
                  ><i class="fa-solid fa-circle-question" aria-hidden="true"></i></span>
                </label>
                <select class="form-select form-select-sm" id="entrySectionFilter"></select>
              </div>
              <div class="assessment-filter-item">
                <label class="form-label mb-0 small text-muted assessment-filter-label" for="entryCategoryFilter">
                  Category
                  <span
                    class="assessment-help-icon"
                    aria-label="Category help"
                    data-bs-toggle="tooltip"
                    data-bs-placement="top"
                    data-bs-custom-class="assessment-help-tooltip"
                    title="A sub-group under the selected Section. By default, the filter is set to All Categories. Selecting a specific Category will refresh the assessment rows to show only records under that Category within the chosen Section."
                  ><i class="fa-solid fa-circle-question" aria-hidden="true"></i></span>
                </label>
                <select class="form-select form-select-sm" id="entryCategoryFilter"></select>
              </div>
            </div>
            <div class="assessment-entry-meta">
              <div class="form-check ms-1">
              <input class="form-check-input" type="checkbox" id="entryUnfinishedOnly">
              <label class="form-check-label small text-muted" for="entryUnfinishedOnly">Unfinished only (coverage/openness/URL)</label>
              </div>
              <span class="small text-muted" id="entryFilterInfo">Rows: 0</span>
            </div>
          </div>
          <div class="table-responsive">
            <table class="table period-table align-top mb-0 assessment-table">
              <thead>
                <tr>
                  <th style="width:420px;">Dimension</th>
                  <th style="width:360px;">Coverage</th>
                  <th style="width:360px;">Openness</th>
                  <th style="min-width:240px; max-width:360px;">Evidence & Notes</th>
                </tr>
              </thead>
              <tbody id="detailRows">
                <tr><td colspan="4" class="text-muted">Loading rows...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="tab-pane fade" id="summary-pane" role="tabpanel" aria-labelledby="summary-tab">
        <div class="period-table-card mb-3">
          <div class="period-table-toolbar d-flex align-items-center justify-content-between gap-3 flex-wrap">
            <strong class="assessment-toolbar-title">Section Summary</strong>
            <span class="text-muted small">Computed by server after save.</span>
          </div>
          <div class="table-responsive">
            <table class="table period-table align-middle mb-0 assessment-summary-table">
              <thead>
                <tr>
                  <th style="vertical-align: middle; max-width:430px;" rowspan="2" class="summary-vr">Section</th>
                  <th style="width:90px; vertical-align: middle;" rowspan="2" class="summary-vr">Progress</th>
                  <th style="width:340px;" colspan="3" class="text-center summary-vr">Coverage</th>
                  <th style="width:160px;" colspan="3" class="text-center summary-vr">Opennes</th>
                  <th style="width:160px;">Overall Score</th>
                </tr>
                <tr>
                  <th style="width:130px;">Max Score</th>
                  <th style="width:130px;">Actual Score</th>
                  <th style="width:120px;" class="summary-vr">Sub Score</th>
                  <th style="width:130px;">Max Score</th>
                  <th style="width:130px;">Actual Score</th>
                  <th style="width:120px;" class="summary-vr">Sub Score</th>
                  <th></th>
                </tr>
              </thead>
              <tbody id="summaryRows">
                <tr><td colspan="9" class="text-muted">Loading summary...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade period-dialog" id="entryNavigatorDialog" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <div>
              <h5 class="modal-title mb-0">Row Navigator</h5>
              <div class="period-dialog-subtitle">Displayed rows are based on active Section/Category/Unfinished filters.</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-0">
            <div id="1" class="table-responsive" style="margin:10px; box-sizing:border-box; max-width:100%; max-height:calc(100vh - 220px); overflow:auto;">
              <table id="tblNavigator" class="table period-table align-middle mb-0">
                <thead>
                  <tr>
                    <th style="width:90px;">No</th>
                    <th>Section</th>
                    <th>Category</th>
                    <th>Indicator</th>
                    <th>Aggregation</th>
                    <th style="width:100px; text-align:right;">Action</th>
                  </tr>
                </thead>
                <tbody id="navigatorRows">
                  <tr><td colspan="6" class="text-muted">No rows available.</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="assessment-nav-dock" id="navigatorDock">
      <div class="assessment-nav-panel" aria-hidden="true">
        <button class="assessment-nav-action assessment-nav-top" type="button" id="btnNavTop" title="Go to first filtered row">
          <i class="fa-solid fa-angles-up" aria-hidden="true"></i>
          <span>Top</span>
        </button>
        <button class="assessment-nav-action assessment-nav-jump" type="button" id="btnNavJump" title="Open row navigator dialog">
          <i class="fa-solid fa-right-left" aria-hidden="true"></i>
          <span>Go To</span>
        </button>
        <button class="assessment-nav-action assessment-nav-bottom" type="button" id="btnNavBottom" title="Go to last filtered row">
          <i class="fa-solid fa-angles-down" aria-hidden="true"></i>
          <span>Bottom</span>
        </button>
      </div>
      <button class="assessment-fab-nav" type="button" id="btnNavMain" aria-label="Navigator actions" title="Navigator">
        <span class="assessment-fab-icon" aria-hidden="true">
          <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
        </span>
        <span class="assessment-fab-label">Navigator</span>
      </button>
    </div>
  </div>
</div>
@endsection

@push('styles')
<style>
  .assessment-summary-table {
    --summary-divider-color: rgba(44, 96, 167, 0.35);
  }

  .assessment-summary-table .summary-vr {
    border-right: 1px solid var(--summary-divider-color) !important;
  }

  .assessment-summary-table thead th {
    text-align: center;
  }

  .assessment-summary-table thead th:first-child {
    text-align: left;
  }

  .assessment-summary-table tbody td.summary-num {
    text-align: right;
  }

  .assessment-summary-table tbody tr.summary-weighted-row td {
    text-align: left !important;
  }

  .assessment-summary-table tbody tr.summary-weighted-row td.summary-num {
    text-align: right !important;
  }

  .assessment-metric-rows {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
  }

  .assessment-metric-row {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.35rem;
  }

  .assessment-metric-row-final {
    grid-template-columns: minmax(0, 1fr);
  }

  .assessment-metric {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.4rem;
    border: 1px solid #d9e4f4;
    border-radius: 0.45rem;
    background: #f7faff;
    padding: 0.22rem 0.4rem;
    line-height: 1.2;
  }

  .assessment-metric span {
    font-size: 0.72rem;
    font-weight: 600;
    color: #4d5d78;
  }

  .assessment-metric strong {
    font-size: 0.78rem;
    color: #1f314f;
    white-space: nowrap;
  }

  .assessment-metric.assessment-metric-final {
    background: #eef5ff;
    border-color: #c9d9f1;
  }

  #tblNavigator tbody tr.navigator-row-complete td {
    background-color: #d9ecff;
  }

  #tblNavigator tbody tr.navigator-row-partial td {
    background-color: rgb(229, 246, 234);
  }

  /*
  #tblNavigator tbody tr.navigator-row-partial .navigator-jump-btn {
    border-color: #ffffff;
    color: #ffffff;
  }

  
  #tblNavigator tbody tr.navigator-row-partial .navigator-jump-btn:hover,
  #tblNavigator tbody tr.navigator-row-partial .navigator-jump-btn:focus {
    background-color: #ffffff;
    color: #1f6f3d;
  }
    */

  #tblNavigator tbody tr.navigator-row-empty td {
    background-color: #ffffff;
  }

  .assessment-nav-dock {
    position: fixed;
    right: 1rem;
    bottom: 1rem;
    z-index: 1050;
    display: inline-flex;
    flex-direction: column;
    align-items: flex-end;
  }

  .assessment-nav-dock .assessment-fab-nav {
    position: relative;
    top: auto;
    right: auto;
    left: auto;
    bottom: auto;
    z-index: 2;
    min-width: 116px;
    justify-content: center;
    border-radius: 999px;
    padding: 9px 14px;
    background: #2b76e5;
    box-shadow: 0 8px 20px rgba(16, 73, 160, 0.36);
  }

  .assessment-nav-panel {
    position: absolute;
    right: 0;
    bottom: 20px;
    width: 116px;
    padding: 10px 0 18px;
    border-radius: 10px;
    background: #5e97ea;
    box-shadow: 0 8px 20px rgba(16, 73, 160, 0.25);
    opacity: 0;
    transform: translateY(14px) scale(0.96);
    transform-origin: bottom right;
    pointer-events: none;
    transition: opacity 180ms ease, transform 180ms ease;
    z-index: 1;
  }

  .assessment-nav-action {
    width: 116px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border: 0;
    background: #5e97ea;
    color: #ffffff;
    padding: 6px 12px;
    font-size: 0.8rem;
    line-height: 1;
    box-shadow: none;
    opacity: 0;
    transform: translateY(8px);
    pointer-events: none;
    transition: opacity 180ms ease, transform 180ms ease;
    border-right: 1px solid rgba(255, 255, 255, 0.18);
    border-left: 1px solid rgba(255, 255, 255, 0.18);
  }

  .assessment-nav-action span {
    font-size: 0.72rem;
    font-weight: 600;
    letter-spacing: 0.01em;
  }

  .assessment-nav-panel .assessment-nav-action.assessment-nav-top {
    border-top-left-radius: 10px;
    border-top-right-radius: 10px;
  }

  .assessment-nav-panel .assessment-nav-action.assessment-nav-bottom {
    border-bottom-left-radius: 10px;
    border-bottom-right-radius: 10px;
  }

  .assessment-nav-dock:hover .assessment-nav-panel,
  .assessment-nav-dock.is-open .assessment-nav-panel,
  .assessment-nav-dock:focus-within .assessment-nav-panel {
    opacity: 1;
    transform: translateY(0) scale(1);
    pointer-events: auto;
  }

  .assessment-nav-dock:hover .assessment-nav-action,
  .assessment-nav-dock.is-open .assessment-nav-action,
  .assessment-nav-dock:focus-within .assessment-nav-action {
    opacity: 1;
    transform: translateX(0) translateY(0) scale(1);
    pointer-events: auto;
  }

  .assessment-nav-action.assessment-nav-top {
    transition-delay: 0ms;
  }

  .assessment-nav-action.assessment-nav-jump {
    transition-delay: 30ms;
  }

  .assessment-nav-action.assessment-nav-bottom {
    transition-delay: 60ms;
  }

  .assessment-nav-dock .assessment-fab-icon {
    width: auto;
    height: auto;
    font-size: 0.8rem;
  }

  @media (max-width: 991.98px) {
    .assessment-nav-dock {
      right: 0.75rem;
      bottom: 0.75rem;
    }

    .assessment-nav-dock .assessment-fab-label {
      display: inline;
    }

    .assessment-nav-action,
    .assessment-nav-panel,
    .assessment-nav-dock .assessment-fab-nav {
      width: 110px;
      min-width: 110px;
    }
  }
</style>
@endpush

@push('scripts')
<script>
  const params = new URLSearchParams(window.location.search);
  let navigatorModal = null;
  const pageState = {
    periodId: Number(params.get('periodid') || 0),
    countryCode: String(params.get('country_code') || '').trim(),
    period: null,
    assessmentCountry: null,
    detailMeta: null,
    detail: [],
    summary: [],
    weightedScore: null,
    editable: false,
    filters: {
      sectionId: '',
      categoryId: '',
      unfinishedOnly: false,
    },
  };
  const fieldTooltips = {
    series: 'Coverage input. Use comma-separated years (for example: 2019,2020,2021) or NA for not applicable. Scoring: C1=1 if any year exists; C2=1 for at least 3 of last 5 years, 0.5 for 1-2 years, 0 for none; C3=1 for at least 6 of last 10 years, 0.5 for 3-5 years, 0 for 2 or fewer.',
    machine_readability: 'O1 - Machine Readability. Score 1 if data is machine-readable (for example XLSX/CSV/JSON/XML/TXT), otherwise 0.',
    proprietary: 'O2 - Non-Proprietary. Score 1 if data is available in non-proprietary formats (for example XLSX/CSV/XML/TXT/JSON), otherwise 0.',
    download_options: 'O3 - Download Options. Score 1 if bulk download and API/user-selectable download are available; 0.5 if only one of those options exists; 0 if none.',
    metadata: 'O4 - Metadata Availability. Score 1 if all required metadata fields are present; 0.5 if at least 5 fields are present; 0 if 4 or fewer.',
    term_of_use: 'O5 - Terms of Use. Score 1 for open terms, 0.5 for semi-restrictive terms, 0 for restrictive/no terms.',
    urls: 'Provide URL(s) for the assessed dataset(s), aligned with the selected indicator/disaggregation.',
    remarks: 'Add supporting notes, clarifications, or context for the values entered in this row.',
  };

  function esc(value) {
    return String(value ?? '')
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#39;');
  }

  function fmtNumber(value, digits = 2) {
    if (value === null || value === undefined || value === '') return '-';
    const num = Number(value);
    if (Number.isNaN(num)) return '-';
    return Number.isInteger(num) ? String(num) : num.toFixed(digits);
  }

  function helpIconText(text, label = 'Field help') {
    const clean = String(text || '').trim();
    if (!clean) return '';
    return `
      <span class="assessment-help-icon"
        aria-label="${esc(label)}"
        data-bs-toggle="tooltip"
        data-bs-trigger="hover"
        data-bs-placement="top"
        data-bs-custom-class="assessment-help-tooltip"
        title="${esc(clean)}"
      ><i class="fa-solid fa-circle-question" aria-hidden="true"></i></span>
    `;
  }

  function initTooltips(scope = document) {
    scope.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
      if (el.dataset.odTooltipInit === '1') return;
      new bootstrap.Tooltip(el, { trigger: 'hover' });
      el.dataset.odTooltipInit = '1';
    });
  }

  function boolFlag(v) {
    return v === true || v === 1 || v === '1';
  }

  function parseMetric(raw, fallback = null) {
    const val = String(raw ?? '').trim();
    if (!val) return fallback;
    const num = Number(val);
    return Number.isNaN(num) ? fallback : num;
  }

  function opennessFieldsComplete(row) {
    const fields = [
      row.machine_readability,
      row.proprietary,
      row.download_options,
      row.metadata,
      row.term_of_use,
    ];
    return fields.every((v) => v !== null && v !== undefined && String(v).trim() !== '');
  }

  function normalizeSeriesYears(raw) {
    const original = String(raw ?? '').trim();
    if (!original) return '';

    const tokens = original.split(',').map((token) => token.trim()).filter(Boolean);
    if (!tokens.length) return '';

    const years = [];

    for (const token of tokens) {
      const rangeMatch = token.match(/^(\d{4})\s*-\s*(\d{4})$/);
      if (rangeMatch) {
        let start = Number(rangeMatch[1]);
        let end = Number(rangeMatch[2]);
        if (start > end) {
          [start, end] = [end, start];
        }
        for (let year = start; year <= end; year += 1) {
          years.push(String(year));
        }
        continue;
      }

      if (/^\d{4}$/.test(token)) {
        years.push(token);
        continue;
      }

      return original;
    }

    return years.join(',');
  }

  function sectionLabel(row) {
    return row.section || row.section_title || '-';
  }

  function categoryLabel(row) {
    return row.category || row.category_title || '-';
  }

  function rowPermanentNo(row) {
    return Number(row._row_no || 0);
  }

  function isRowUnfinished(row) {
    const seriesRaw = String(row.series ?? '').trim();
    const isCoverageFilled = seriesRaw !== '';
    const isNA = seriesRaw.toUpperCase() === 'NA';
    const hasUrl = String(row.urls ?? '').trim() !== '';

    const isOpennessFilled = isNA || opennessFieldsComplete(row);
    const isUrlFilled = isNA || hasUrl;

    return !isCoverageFilled || !isOpennessFilled || !isUrlFilled;
  }

  function navigatorRowStatus(row) {
    const seriesRaw = String(row.series ?? '').trim();
    const urlsRaw = String(row.urls ?? '').trim();
    const remarksRaw = String(row.remarks ?? '').trim();
    const isNA = seriesRaw.toUpperCase() === 'NA';

    const hasAnyOpenness = [
      row.machine_readability,
      row.proprietary,
      row.download_options,
      row.metadata,
      row.term_of_use,
    ].some((v) => v !== null && v !== undefined && String(v).trim() !== '');
    const hasAnyInput = seriesRaw !== '' || urlsRaw !== '' || remarksRaw !== '' || hasAnyOpenness;
    if (!hasAnyInput) return 'empty';

    const isCoverageFilled = seriesRaw !== '';
    const isOpennessFilled = isNA || opennessFieldsComplete(row);
    const isUrlFilled = isNA || urlsRaw !== '';
    if (isCoverageFilled && isOpennessFilled && isUrlFilled) return 'complete';

    return 'partial';
  }

  function getFilteredDetailRows() {
    const rows = Array.isArray(pageState.detail) ? pageState.detail : [];
    const sectionId = String(pageState.filters.sectionId || '');
    const categoryId = String(pageState.filters.categoryId || '');
    const unfinishedOnly = pageState.filters.unfinishedOnly === true;

    return rows.filter((r) => {
      if (sectionId && String(r.section_id) !== sectionId) return false;
      if (categoryId && String(r.category_id) !== categoryId) return false;
      if (unfinishedOnly && !isRowUnfinished(r)) return false;
      return true;
    });
  }

  function renderFilterControls() {
    const sectionSelect = document.getElementById('entrySectionFilter');
    const categorySelect = document.getElementById('entryCategoryFilter');
    const unfinishedCheckbox = document.getElementById('entryUnfinishedOnly');
    const rows = Array.isArray(pageState.detail) ? pageState.detail : [];

    const sectionMap = new Map();
    rows.forEach((r) => {
      const key = String(r.section_id ?? '');
      if (!key || sectionMap.has(key)) return;
      sectionMap.set(key, sectionLabel(r));
    });
    const sectionOpts = [...sectionMap.entries()];
    sectionSelect.innerHTML = `<option value="">All Sections</option>${sectionOpts.map(([id, label]) => `<option value="${esc(id)}">${esc(label)}</option>`).join('')}`;

    const allowedRows = pageState.filters.sectionId
      ? rows.filter((r) => String(r.section_id) === String(pageState.filters.sectionId))
      : rows;
    const categoryMap = new Map();
    allowedRows.forEach((r) => {
      const key = String(r.category_id ?? '');
      if (!key || categoryMap.has(key)) return;
      categoryMap.set(key, categoryLabel(r));
    });
    const categoryOpts = [...categoryMap.entries()];
    categorySelect.innerHTML = `<option value="">All Categories</option>${categoryOpts.map(([id, label]) => `<option value="${esc(id)}">${esc(label)}</option>`).join('')}`;

    if (pageState.filters.sectionId && !sectionMap.has(String(pageState.filters.sectionId))) {
      pageState.filters.sectionId = '';
    }
    if (pageState.filters.categoryId && !categoryMap.has(String(pageState.filters.categoryId))) {
      pageState.filters.categoryId = '';
    }

    sectionSelect.value = String(pageState.filters.sectionId || '');
    categorySelect.value = String(pageState.filters.categoryId || '');
    unfinishedCheckbox.checked = pageState.filters.unfinishedOnly === true;
  }

  function renderNavigatorRows() {
    const tbody = document.getElementById('navigatorRows');
    const rows = getFilteredDetailRows();

    if (!rows.length) {
      tbody.innerHTML = '<tr><td colspan="6" class="text-muted">No rows match active filters.</td></tr>';
      return;
    }

    tbody.innerHTML = rows.map((r) => {
      const rowNo = rowPermanentNo(r);
      const statusClass = `navigator-row-${navigatorRowStatus(r)}`;
      return `
        <tr class="${statusClass}">
          <td>#${rowNo}</td>
          <td>${esc(sectionLabel(r))}</td>
          <td>${esc(categoryLabel(r))}</td>
          <td>${esc(r.indicator || r.indicator_title || '-')}</td>
          <td>${esc(r.aggregation || r.aggregation_title || '-')}</td>
          <td class="text-end"><button type="button" class="btn btn-sm btn-outline-dark navigator-jump-btn" data-row-id="${r.row_id}">Go</button></td>
        </tr>
      `;
    }).join('');
  }

  function jumpToRow(rowId) {
    const target = document.querySelector(`#detailRows tr[data-row-id="${rowId}"]`);
    if (!target) return;
    target.scrollIntoView({ behavior: 'smooth', block: 'center' });
    target.classList.add('assessment-row-focus');
    setTimeout(() => target.classList.remove('assessment-row-focus'), 1400);
  }

  function jumpToFilteredEdge(position = 'top') {
    const rows = getFilteredDetailRows();
    if (!rows.length) return;
    const targetRow = position === 'bottom' ? rows[rows.length - 1] : rows[0];
    jumpToRow(targetRow.row_id);
  }

  function renderMeta() {
    const meta = document.getElementById('formMeta');
    const hint = document.getElementById('formHint');
    const saveBtn = document.getElementById('btnSaveForm');
    const submitBtn = document.getElementById('btnSubmitForm');

    if (!pageState.period || !pageState.assessmentCountry) {
      meta.textContent = 'Assessment information unavailable.';
      hint.style.display = 'none';
      saveBtn.disabled = true;
      submitBtn.disabled = true;
      return;
    }

    const periodOpen = boolFlag(pageState.period.active);
    const isSubmitted = boolFlag(pageState.assessmentCountry.is_submitted);
    pageState.editable = periodOpen;

    const modeText = periodOpen ? 'Open' : 'Completed';
    const submitText = isSubmitted ? 'Submitted' : 'In-progress';
    const totalRows = Number(pageState.detailMeta?.total_rows ?? pageState.detail?.length ?? 0);
    meta.textContent = `Period ${pageState.period.year} | ${pageState.assessmentCountry.country_code} | ${modeText} | ${submitText} | Rows: ${totalRows}`;

    if (periodOpen) {
      hint.className = 'period-hint mb-3';
      hint.textContent = isSubmitted
        ? 'Assessment already submitted. You can still review and update while period is open.'
        : 'Period is open. Fill data, click Save, then Submit when finalized.';
      hint.style.display = 'block';
      saveBtn.disabled = false;
      submitBtn.disabled = false;
      submitBtn.textContent = isSubmitted ? 'Submitted' : 'Submit';
    } else {
      hint.className = 'period-hint mb-3';
      hint.textContent = 'Period is completed. Assessment is read-only.';
      hint.style.display = 'block';
      saveBtn.disabled = true;
      submitBtn.disabled = true;
      submitBtn.textContent = 'Submit';
    }
  }

  function renderSummaryRows() {
    const tbody = document.getElementById('summaryRows');
    const rows = Array.isArray(pageState.summary) ? pageState.summary : [];
    const weighted = pageState.weightedScore || null;

    function fmtPercentInt(value) {
      if (value === null || value === undefined || value === '') return '-';
      const num = Number(value);
      if (Number.isNaN(num)) return '-';
      return `${Math.round(num)}%`;
    }

    function fmt2(value) {
      if (value === null || value === undefined || value === '') return '-';
      const num = Number(value);
      if (Number.isNaN(num)) return '-';
      return num.toFixed(2);
    }

    if (!rows.length) {
      tbody.innerHTML = '<tr><td colspan="9" class="text-muted">No summary available.</td></tr>';
      return;
    }

    const sectionRowsHtml = rows.map((s, idx) => `
      <tr>
        <td class="summary-vr">${esc(s.section?.title || (s.section_id ? `Section ${s.section_id}` : 'Overall'))}</td>
        <td class="summary-vr summary-num">${fmtPercentInt(s.progress)}</td>
        <td class="summary-num">${fmt2(s.coverage_max_score)}</td>
        <td class="summary-num">${fmt2(s.coverage_actual_score)}</td>
        <td class="summary-vr summary-num">${fmt2(s.coverage_sub_score_ratio)}</td>
        <td class="summary-num">${fmt2(s.opennes_max_score)}</td>
        <td class="summary-num">${fmt2(s.opennes_actual_score)}</td>
        <td class="summary-vr summary-num">${fmt2(s.opennes_sub_score_ratio)}</td>
        <td class="summary-num">${fmt2(s.overall_score_ratio)}</td>
      </tr>
    `).join('');

    const weightedRowHtml = `
      <tr class="table-light fw-semibold summary-weighted-row">
        <td colspan="2" class="summary-vr">Weighted Score</td>
        <td colspan="2">Coverage weighted sub score:</td>
        <td class="summary-vr summary-num">${fmt2(weighted?.coverage_sub_score_ratio)}</td>
        <td colspan="2">Opennes weighted sub score:</td>
        <td class="summary-vr summary-num">${fmt2(weighted?.opennes_sub_score_ratio)}</td>
        <td class="summary-num">${fmt2(weighted?.overall_score_ratio)}</td>
      </tr>
    `;

    tbody.innerHTML = `${sectionRowsHtml}${weightedRowHtml}`;
  }

  function opennessSelect(field, value, rowId, options, disabled) {
    const hasValue = value !== null && value !== undefined && String(value).trim() !== '';
    const emptyOption = `<option value="" ${hasValue ? '' : 'selected'}>-- Select --</option>`;
    const opts = options.map((opt) => {
      const selected = hasValue && Number(value) === Number(opt) ? 'selected' : '';
      return `<option value="${opt}" ${selected}>${opt}</option>`;
    }).join('');

    return `
      <select class="form-select form-select-sm assessment-input"
        data-row-id="${rowId}" data-field="${field}" ${disabled ? 'disabled' : ''}>
        ${emptyOption}${opts}
      </select>
    `;
  }

  function renderDetailRows() {
    const tbody = document.getElementById('detailRows');
    const rows = getFilteredDetailRows();
    const disabled = !pageState.editable;
    const totalRows = Array.isArray(pageState.detail) ? pageState.detail.length : 0;
    const info = document.getElementById('entryFilterInfo');
    info.textContent = `Rows: ${rows.length} / ${totalRows}`;

    if (!rows.length) {
      tbody.innerHTML = '<tr><td colspan="4" class="text-muted">No detail rows found.</td></tr>';
      return;
    }

    tbody.innerHTML = rows.map((r, idx) => {
      const cLabel = (r.c === null || r.c === undefined) ? 'N/A' : fmtNumber(r.c);
      const oLabel = (r.o === null || r.o === undefined) ? 'N/A' : fmtNumber(r.o);
      const rowNo = rowPermanentNo(r) || (idx + 1);
      const rowClass = (rowNo % 2 === 0) ? 'assessment-row-even' : 'assessment-row-odd';

      return `
        <tr class="${rowClass}" data-row-id="${r.row_id}">
          <td>
            <div class="assessment-dimension">
              <div class="assessment-index">#${rowNo}</div>
              <div class="assessment-dimension-item"><span>Section</span><strong>${esc(r.section || r.section_title || '-')}</strong></div>
              <div class="assessment-dimension-item"><span>Category</span><strong>${esc(r.category || r.category_title || '-')}</strong></div>
              <div class="assessment-dimension-item"><span>Indicator</span><strong>${esc(r.indicator || r.indicator_title || '-')}</strong></div>
              <div class="assessment-dimension-item"><span>Dissagregation</span><strong>${esc(r.aggregation || r.aggregation_title || '-')}</strong></div>
            </div>
          </td>
          <td>
            <div class="assessment-field-wrap">
              <label class="form-label form-label-sm mb-1">Series ${helpIconText(fieldTooltips.series, 'Series help')}</label>
              <textarea class="form-control form-control-sm assessment-input"
                data-row-id="${r.row_id}" data-field="series" rows="3" ${disabled ? 'disabled' : ''}>${esc(r.series || '')}</textarea>
            </div>
            <div class="assessment-metric-rows mt-2">
              <div class="assessment-metric-row">
                <div class="assessment-metric"><span>All</span><strong>${fmtNumber(r.count_all, 0)}</strong></div>
                <div class="assessment-metric"><span>5</span><strong>${fmtNumber(r.count_5, 0)}</strong></div>
                <div class="assessment-metric"><span>10</span><strong>${fmtNumber(r.count_10, 0)}</strong></div>
              </div>
              <div class="assessment-metric-row">
                <div class="assessment-metric"><span>C1</span><strong>${fmtNumber(r.c1)}</strong></div>
                <div class="assessment-metric"><span>C2</span><strong>${fmtNumber(r.c2)}</strong></div>
                <div class="assessment-metric"><span>C3</span><strong>${fmtNumber(r.c3)}</strong></div>
              </div>
              <div class="assessment-metric-row assessment-metric-row-final">
                <div class="assessment-metric assessment-metric-final"><span>Coverage</span><strong>${cLabel}</strong></div>
              </div>
            </div>
          </td>
          <td>
            <div class="assessment-openness-grid">
              <div class="assessment-openness-item">
                <label class="form-label form-label-sm mb-1">Machine Readability ${helpIconText(fieldTooltips.machine_readability, 'Machine Readability help')}</label>
                ${opennessSelect('machine_readability', r.machine_readability, r.row_id, [0, 1], disabled)}
              </div>
              <div class="assessment-openness-item">
                <label class="form-label form-label-sm mb-1">Proprietary ${helpIconText(fieldTooltips.proprietary, 'Proprietary help')}</label>
                ${opennessSelect('proprietary', r.proprietary, r.row_id, [0, 1], disabled)}
              </div>
              <div class="assessment-openness-item">
                <label class="form-label form-label-sm mb-1">Download Options ${helpIconText(fieldTooltips.download_options, 'Download Options help')}</label>
                ${opennessSelect('download_options', r.download_options, r.row_id, [0, 0.5, 1], disabled)}
              </div>
              <div class="assessment-openness-item">
                <label class="form-label form-label-sm mb-1">Metadata ${helpIconText(fieldTooltips.metadata, 'Metadata help')}</label>
                ${opennessSelect('metadata', r.metadata, r.row_id, [0, 0.5, 1], disabled)}
              </div>
              <div class="assessment-openness-item">
                <label class="form-label form-label-sm mb-1">Term of Use ${helpIconText(fieldTooltips.term_of_use, 'Term of Use help')}</label>
                ${opennessSelect('term_of_use', r.term_of_use, r.row_id, [0, 0.5, 1], disabled)}
              </div>
            </div>
            <div class="assessment-openness-total mt-2">O: <strong>${oLabel}</strong></div>
          </td>
          <td>
            <div class="assessment-field-wrap mb-2">
              <label class="form-label form-label-sm mb-1">Relevant URL ${helpIconText(fieldTooltips.urls, 'Relevant URL help')}</label>
              <textarea class="form-control form-control-sm assessment-input"
                data-row-id="${r.row_id}" data-field="urls" rows="3" ${disabled ? 'disabled' : ''}>${esc(r.urls || '')}</textarea>
            </div>
            <div class="assessment-field-wrap">
              <label class="form-label form-label-sm mb-1">Remark ${helpIconText(fieldTooltips.remarks, 'Remark help')}</label>
              <textarea class="form-control form-control-sm assessment-input"
                data-row-id="${r.row_id}" data-field="remarks" rows="3" ${disabled ? 'disabled' : ''}>${esc(r.remarks || '')}</textarea>
            </div>
          </td>
        </tr>
      `;
    }).join('');

    initTooltips(tbody);
  }

  function setRowField(rowId, field, value) {
    const row = pageState.detail.find((r) => String(r.row_id) === String(rowId));
    if (!row) return;
    row[field] = value;
  }

  function bindEntryInputSync() {
    const tbody = document.getElementById('detailRows');
    tbody.addEventListener('input', (event) => {
      const target = event.target;
      if (!target.classList.contains('assessment-input')) return;
      setRowField(target.dataset.rowId, target.dataset.field, target.value);
    });
    tbody.addEventListener('change', (event) => {
      const target = event.target;
      if (!target.classList.contains('assessment-input')) return;
      setRowField(target.dataset.rowId, target.dataset.field, target.value);
      if (pageState.filters.unfinishedOnly) {
        renderDetailRows();
      }
    });
    tbody.addEventListener('focusout', (event) => {
      const target = event.target;
      if (!target.classList.contains('assessment-input')) return;
      if (target.dataset.field !== 'series') return;

      const normalized = normalizeSeriesYears(target.value);
      if (target.value !== normalized) {
        target.value = normalized;
      }
      setRowField(target.dataset.rowId, target.dataset.field, target.value);
    });
  }

  function showError(message) {
    const box = document.getElementById('formError');
    box.textContent = message || 'Unexpected error';
    box.classList.remove('d-none');
  }

  function hideError() {
    document.getElementById('formError').classList.add('d-none');
  }

  async function loadForm() {
    hideError();
    const detailBody = document.getElementById('detailRows');
    const summaryBody = document.getElementById('summaryRows');
    detailBody.innerHTML = '<tr><td colspan="4" class="text-muted">Loading rows...</td></tr>';
    summaryBody.innerHTML = '<tr><td colspan="9" class="text-muted">Loading summary...</td></tr>';

    if (!pageState.periodId) {
      showError('Missing query parameter: periodid');
      detailBody.innerHTML = '<tr><td colspan="4" class="text-danger">Invalid period.</td></tr>';
      summaryBody.innerHTML = '<tr><td colspan="9" class="text-danger">Invalid period.</td></tr>';
      return;
    }

    try {
      let url = `/api/trx/form?periodid=${encodeURIComponent(pageState.periodId)}`;
      if (pageState.countryCode) {
        url += `&country_code=${encodeURIComponent(pageState.countryCode)}`;
      }

      const response = await odFetch(url);
      const data = response.data || {};
      pageState.period = data.period || null;
      pageState.assessmentCountry = data.assessment_country || null;
      pageState.detailMeta = data.detail_meta || null;
      pageState.detail = (data.detail || []).map((row, index) => ({
        ...row,
        _row_no: index + 1,
      }));
      pageState.summary = data.summary || [];
      pageState.weightedScore = data.weighted_score || null;

      renderMeta();
      renderSummaryRows();
      renderFilterControls();
      renderDetailRows();
    } catch (err) {
      renderMeta();
      summaryBody.innerHTML = `<tr><td colspan="9" class="text-danger">${esc(err.message || 'Failed to load summary.')}</td></tr>`;
      detailBody.innerHTML = `<tr><td colspan="4" class="text-danger">${esc(err.message || 'Failed to load details.')}</td></tr>`;
      showError(err.message || 'Failed to load assessment');
    }
  }

  function collectRowsPayload() {
    const sourceRows = Array.isArray(pageState.detail) ? pageState.detail : [];
    return sourceRows.map((r) => {
      return {
        row_id: r.row_id,
        series: String(r.series ?? ''),
        machine_readability: parseMetric(r.machine_readability, null),
        proprietary: parseMetric(r.proprietary, null),
        download_options: parseMetric(r.download_options, null),
        metadata: parseMetric(r.metadata, null),
        term_of_use: parseMetric(r.term_of_use, null),
        urls: String(r.urls ?? ''),
        remarks: String(r.remarks ?? ''),
      };
    });
  }

  async function saveForm() {
    hideError();
    if (!pageState.editable) return;
    if (!pageState.assessmentCountry) return;

    const btnSave = document.getElementById('btnSaveForm');
    const btnSubmit = document.getElementById('btnSubmitForm');
    btnSave.disabled = true;
    btnSubmit.disabled = true;

    try {
      await odFetch('/api/trx/form', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          periodid: pageState.periodId,
          countryid: pageState.assessmentCountry.id,
          rows: collectRowsPayload(),
        }),
      });
      await loadForm();
      odToast('Assessment saved.');
    } catch (err) {
      showError(err.message || 'Failed to save assessment');
    } finally {
      renderMeta();
    }
  }

  async function submitForm() {
    hideError();
    if (!pageState.editable) return;
    if (!pageState.assessmentCountry) return;

    const confirmed = confirm('Submit this assessment? You can still update while period remains open.');
    if (!confirmed) return;

    const btnSave = document.getElementById('btnSaveForm');
    const btnSubmit = document.getElementById('btnSubmitForm');
    btnSave.disabled = true;
    btnSubmit.disabled = true;

    try {
      await odFetch('/api/trx/form/submit', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          periodid: pageState.periodId,
          countryid: pageState.assessmentCountry.id,
        }),
      });
      await loadForm();
      odToast('Assessment submitted.');
    } catch (err) {
      showError(err.message || 'Failed to submit assessment');
    } finally {
      renderMeta();
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    navigatorModal = new bootstrap.Modal(document.getElementById('entryNavigatorDialog'));
    initTooltips(document);
    const navigatorDock = document.getElementById('navigatorDock');

    document.getElementById('entrySectionFilter').addEventListener('change', (event) => {
      pageState.filters.sectionId = String(event.target.value || '');
      pageState.filters.categoryId = '';
      renderFilterControls();
      renderDetailRows();
    });
    document.getElementById('entryCategoryFilter').addEventListener('change', (event) => {
      pageState.filters.categoryId = String(event.target.value || '');
      renderDetailRows();
    });
    document.getElementById('entryUnfinishedOnly').addEventListener('change', (event) => {
      pageState.filters.unfinishedOnly = event.target.checked;
      renderDetailRows();
    });
    document.getElementById('btnNavMain').addEventListener('click', () => {
      navigatorDock.classList.toggle('is-open');
    });
    document.getElementById('btnNavTop').addEventListener('click', () => {
      jumpToFilteredEdge('top');
    });
    document.getElementById('btnNavJump').addEventListener('click', () => {
      navigatorDock.classList.remove('is-open');
      renderNavigatorRows();
      navigatorModal.show();
    });
    document.getElementById('btnNavBottom').addEventListener('click', () => {
      jumpToFilteredEdge('bottom');
    });
    navigatorDock.addEventListener('mouseleave', () => {
      navigatorDock.classList.remove('is-open');
    });
    document.getElementById('navigatorRows').addEventListener('click', (event) => {
      const btn = event.target.closest('.navigator-jump-btn');
      if (!btn) return;
      const rowId = btn.dataset.rowId;
      navigatorModal.hide();
      setTimeout(() => jumpToRow(rowId), 200);
    });
    bindEntryInputSync();
    document.getElementById('btnRefreshForm').addEventListener('click', loadForm);
    document.getElementById('btnSaveForm').addEventListener('click', saveForm);
    document.getElementById('btnSubmitForm').addEventListener('click', submitForm);
    loadForm();
  });
</script>
@endpush
