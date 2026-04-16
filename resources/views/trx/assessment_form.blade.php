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
                <label class="form-label mb-0 small text-muted" for="entrySectionFilter">Section</label>
                <select class="form-select form-select-sm" id="entrySectionFilter"></select>
              </div>
              <div class="assessment-filter-item">
                <label class="form-label mb-0 small text-muted" for="entryCategoryFilter">Category</label>
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
                  <th style="width:480px;">Dimension</th>
                  <th style="width:340px;">Coverage</th>
                  <th style="width:360px;">Openness</th>
                  <th style="min-width:240px;">Evidence & Notes</th>
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
            <table class="table period-table align-middle mb-0">
              <thead>
                <tr>
                  <th style="width:80px;">No</th>
                  <th>Section</th>
                  <th style="width:160px;">Coverage</th>
                  <th style="width:160px;">Openness</th>
                  <th style="width:160px;">Overall</th>
                </tr>
              </thead>
              <tbody id="summaryRows">
                <tr><td colspan="5" class="text-muted">Loading summary...</td></tr>
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
            <div class="table-responsive">
              <table class="table period-table align-middle mb-0">
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

    <button class="assessment-fab-nav" type="button" id="btnOpenNavigator" aria-label="Open row navigator" title="Open row navigator">
      <span class="assessment-fab-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" focusable="false">
          <path d="M10.5 3a7.5 7.5 0 1 0 4.72 13.33l4.72 4.72 1.06-1.06-4.72-4.72A7.5 7.5 0 0 0 10.5 3Zm0 1.5a6 6 0 1 1 0 12 6 6 0 0 1 0-12Z"></path>
        </svg>
      </span>
      <span class="assessment-fab-label">Navigator</span>
    </button>
  </div>
</div>
@endsection

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
    editable: false,
    filters: {
      sectionId: '',
      categoryId: '',
      unfinishedOnly: false,
    },
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

  function boolFlag(v) {
    return v === true || v === 1 || v === '1';
  }

  function parseMetric(raw, fallback = 0) {
    const val = String(raw ?? '').trim();
    if (!val) return fallback;
    const num = Number(val);
    return Number.isNaN(num) ? fallback : num;
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

    const metrics = [
      Number(row.machine_readability ?? 0),
      Number(row.proprietary ?? 0),
      Number(row.download_options ?? 0),
      Number(row.metadata ?? 0),
      Number(row.term_of_use ?? 0),
    ];
    const hasAnyOpenness = metrics.some((v) => !Number.isNaN(v) && v > 0);
    const isOpennessFilled = isNA || hasAnyOpenness;
    const isUrlFilled = isNA || hasUrl;

    return !isCoverageFilled || !isOpennessFilled || !isUrlFilled;
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
      return `
        <tr>
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

    if (!rows.length) {
      tbody.innerHTML = '<tr><td colspan="5" class="text-muted">No summary available.</td></tr>';
      return;
    }

    tbody.innerHTML = rows.map((s, idx) => `
      <tr>
        <td>${idx + 1}</td>
        <td>${esc(s.section?.title || (s.section_id ? `Section ${s.section_id}` : 'Overall'))}</td>
        <td>${fmtNumber(s.coverage_sub_score)}</td>
        <td>${fmtNumber(s.opennes_sub_score)}</td>
        <td>${fmtNumber(s.overall_score)}</td>
      </tr>
    `).join('');
  }

  function opennessSelect(field, value, rowId, options, disabled) {
    const opts = options.map((opt) => {
      const selected = Number(value) === Number(opt) ? 'selected' : '';
      return `<option value="${opt}" ${selected}>${opt}</option>`;
    }).join('');

    return `
      <select class="form-select form-select-sm assessment-input"
        data-row-id="${rowId}" data-field="${field}" ${disabled ? 'disabled' : ''}>
        ${opts}
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
              <label class="form-label form-label-sm mb-1">Series</label>
              <textarea class="form-control form-control-sm assessment-input"
                data-row-id="${r.row_id}" data-field="series" rows="3" ${disabled ? 'disabled' : ''}>${esc(r.series || '')}</textarea>
            </div>
            <div class="assessment-metric-grid mt-2">
              <div class="assessment-metric"><span>All</span><strong>${fmtNumber(r.count_all, 0)}</strong></div>
              <div class="assessment-metric"><span>5</span><strong>${fmtNumber(r.count_5, 0)}</strong></div>
              <div class="assessment-metric"><span>10</span><strong>${fmtNumber(r.count_10, 0)}</strong></div>
              <div class="assessment-metric"><span>C1</span><strong>${fmtNumber(r.c1)}</strong></div>
              <div class="assessment-metric"><span>C2</span><strong>${fmtNumber(r.c2)}</strong></div>
              <div class="assessment-metric"><span>C3</span><strong>${fmtNumber(r.c3)}</strong></div>
              <div class="assessment-metric assessment-metric-final"><span>C</span><strong>${cLabel}</strong></div>
            </div>
          </td>
          <td>
            <div class="assessment-openness-grid">
              <div class="assessment-openness-item">
                <label class="form-label form-label-sm mb-1">Machine Readability</label>
                ${opennessSelect('machine_readability', r.machine_readability, r.row_id, [0, 1], disabled)}
              </div>
              <div class="assessment-openness-item">
                <label class="form-label form-label-sm mb-1">Proprietary</label>
                ${opennessSelect('proprietary', r.proprietary, r.row_id, [0, 1], disabled)}
              </div>
              <div class="assessment-openness-item">
                <label class="form-label form-label-sm mb-1">Download Options</label>
                ${opennessSelect('download_options', r.download_options, r.row_id, [0, 0.5, 1], disabled)}
              </div>
              <div class="assessment-openness-item">
                <label class="form-label form-label-sm mb-1">Metadata</label>
                ${opennessSelect('metadata', r.metadata, r.row_id, [0, 0.5, 1], disabled)}
              </div>
              <div class="assessment-openness-item">
                <label class="form-label form-label-sm mb-1">Term of Use</label>
                ${opennessSelect('term_of_use', r.term_of_use, r.row_id, [0, 0.5, 1], disabled)}
              </div>
            </div>
            <div class="assessment-openness-total mt-2">O: <strong>${oLabel}</strong></div>
          </td>
          <td>
            <div class="assessment-field-wrap mb-2">
              <label class="form-label form-label-sm mb-1">Relevant URL</label>
              <textarea class="form-control form-control-sm assessment-input"
                data-row-id="${r.row_id}" data-field="urls" rows="3" ${disabled ? 'disabled' : ''}>${esc(r.urls || '')}</textarea>
            </div>
            <div class="assessment-field-wrap">
              <label class="form-label form-label-sm mb-1">Remark</label>
              <textarea class="form-control form-control-sm assessment-input"
                data-row-id="${r.row_id}" data-field="remarks" rows="3" ${disabled ? 'disabled' : ''}>${esc(r.remarks || '')}</textarea>
            </div>
          </td>
        </tr>
      `;
    }).join('');
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
    summaryBody.innerHTML = '<tr><td colspan="5" class="text-muted">Loading summary...</td></tr>';

    if (!pageState.periodId) {
      showError('Missing query parameter: periodid');
      detailBody.innerHTML = '<tr><td colspan="4" class="text-danger">Invalid period.</td></tr>';
      summaryBody.innerHTML = '<tr><td colspan="5" class="text-danger">Invalid period.</td></tr>';
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

      renderMeta();
      renderSummaryRows();
      renderFilterControls();
      renderDetailRows();
    } catch (err) {
      renderMeta();
      summaryBody.innerHTML = `<tr><td colspan="5" class="text-danger">${esc(err.message || 'Failed to load summary.')}</td></tr>`;
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
        machine_readability: parseMetric(r.machine_readability, 0),
        proprietary: parseMetric(r.proprietary, 0),
        download_options: parseMetric(r.download_options, 0),
        metadata: parseMetric(r.metadata, 0),
        term_of_use: parseMetric(r.term_of_use, 0),
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
    document.getElementById('btnOpenNavigator').addEventListener('click', () => {
      renderNavigatorRows();
      navigatorModal.show();
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
