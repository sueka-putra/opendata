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

    <div class="modal fade period-dialog" id="uploadTemplateDialog" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Upload Template</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p class="small text-muted mb-2" id="uploadTemplateCaption">Select participant and upload Excel template.</p>
            <p class="small mb-2"><span class="text-muted">Mapping mode:</span> <span id="uploadMappingMode" class="fw-semibold text-secondary">-</span></p>
            <div id="uploadDebugWrap" class="small text-muted mb-2" style="display:none;">
              <div class="fw-semibold mb-1">Debug Parser</div>
              <pre id="uploadDebugText" class="mb-0 p-2 border rounded bg-light" style="max-height:180px; overflow:auto; white-space:pre-wrap;"></pre>
            </div>
            <div id="uploadDropzone" class="assessment-upload-dropzone">
              <input type="file" id="uploadTemplateInput" class="d-none" accept=".xlsx,.xls">
              <div class="assessment-upload-icon"><i class="fa-solid fa-file-arrow-up"></i></div>
              <p class="mb-1 fw-semibold">Click or drop file here</p>
              <p id="uploadTemplateFileName" class="small mb-0 text-muted">No file selected.</p>
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn od-btn-outline" type="button" data-bs-dismiss="modal">Cancel</button>
            <button class="btn od-btn-primary" type="button" id="btnUploadTemplateProcess">Upload</button>
          </div>
        </div>
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
              <th style="width: 220px; text-align: right;">Action</th>
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

@push('styles')
<style>
  .assessment-upload-dropzone {
    border: 1.5px dashed #9cb5d6;
    border-radius: 12px;
    background: #f8fbff;
    min-height: 180px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    gap: 6px;
    cursor: pointer;
    transition: border-color .15s ease, background-color .15s ease;
    padding: 16px;
  }
  .assessment-upload-dropzone:hover,
  .assessment-upload-dropzone.is-drag-over {
    border-color: #2c60a7;
    background: #edf4fd;
  }
  .assessment-upload-icon {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #eaf0f8;
    color: #2c60a7;
    font-size: 1.25rem;
    margin-bottom: 4px;
  }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script>
  const periodId = @json((int) request()->route('periodId'));
  let uploadTemplateModal = null;
  let uploadTemplateFile = null;
  let selectedUploadCountry = null;
  let uploadMappingMode = 'unknown';
  let uploadDebugInfo = null;

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
          ${(isOpen && (row.is_submitted === true || row.is_submitted === 1 || row.is_submitted === '1'))
            ? `<button class="btn btn-sm btn-outline-warning me-1 btn-unlock-country" type="button" data-country-id="${escAttr(row.assessment_country_id || '')}" data-country-name="${escAttr(row.country_name || row.country_code || '')}">Unlock</button>`
            : ''}
          ${isOpen ? `<button class="btn btn-sm btn-outline-secondary me-1 btn-upload-country" type="button" data-country-id="${escAttr(row.assessment_country_id || '')}" data-country-code="${escAttr(row.country_code || '')}" data-country-name="${escAttr(row.country_name || row.country_code || '')}">Upload</button>` : ''}
          <button class="btn btn-sm btn-outline-primary me-1 btn-print-country" type="button" data-country-code="${escAttr(row.country_code || '')}" data-country-name="${escAttr(row.country_name || row.country_code || '')}">Print</button>
          <a class="btn btn-sm btn-outline-dark" href="/trx/form?periodid=${encodeURIComponent(periodId)}&country_code=${encodeURIComponent(row.country_code)}">${isOpen ? 'Open' : 'View'}</a>
        </td>
      </tr>
    `).join('');
  }

  function escAttr(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/"/g, '&quot;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');
  }

  function fmt2(value) {
    const num = Number(value ?? 0);
    if (Number.isNaN(num)) return '0.00';
    return num.toFixed(2);
  }

  function sanitizeFilePart(value) {
    const raw = String(value || 'country');
    const cleaned = raw.replace(/[^a-zA-Z0-9_-]+/g, '_').replace(/_+/g, '_').replace(/^_|_$/g, '');
    return cleaned || 'country';
  }

  function downloadSummaryPng(countryName, summaryRows, weightedScore) {
    const rows = [...(Array.isArray(summaryRows) ? summaryRows : [])]
      .sort((a, b) => Number(a.section_id || 0) - Number(b.section_id || 0));
    if (!rows.length) {
      odAlert('Summary is empty. Nothing to print.', 'Print Summary');
      return;
    }

    const cols = [360, 140, 150, 155, 140, 150, 155, 120];
    const pad = 18;
    const topPad = 78;
    const hHead1 = 62;
    const hHead2 = 56;
    const hRow = 54;
    const hWeighted = 56;
    const tableWidth = cols.reduce((a, b) => a + b, 0);
    const tableHeight = hHead1 + hHead2 + (rows.length * hRow) + hWeighted;
    const width = tableWidth + (pad * 2);
    const height = topPad + tableHeight + 20;

    const canvas = document.createElement('canvas');
    const dpr = Math.max(1, window.devicePixelRatio || 1);
    canvas.width = Math.floor(width * dpr);
    canvas.height = Math.floor(height * dpr);
    canvas.style.width = `${width}px`;
    canvas.style.height = `${height}px`;
    const ctx = canvas.getContext('2d');
    ctx.scale(dpr, dpr);
    ctx.textBaseline = 'middle';

    const bg = '#ffffff';
    const headA = '#ead8cb';
    const headB = '#efccaf';
    const weightedBg = '#e8ab7e';
    const border = '#8c8c8c';
    const titleColor = '#f2b300';
    const text = '#111111';

    function cell(x, y, w, h, fill, label, opts = {}) {
      ctx.fillStyle = fill;
      ctx.fillRect(x, y, w, h);
      ctx.strokeStyle = border;
      ctx.lineWidth = 1;
      ctx.strokeRect(x, y, w, h);
      if (label !== undefined && label !== null) {
        ctx.fillStyle = text;
        ctx.font = `${opts.weight || 700} ${opts.size || 18}px "Segoe UI", Arial, sans-serif`;
        ctx.textAlign = opts.align || 'center';
        const tx = opts.align === 'left' ? x + 10 : opts.align === 'right' ? (x + w - 10) : (x + (w / 2));
        const raw = String(label);
        const lines = raw.includes('\n') ? raw.split('\n') : [raw];
        const lineHeight = opts.lineHeight || Math.max(18, (opts.size || 18) + 4);
        const startY = y + (h / 2) - ((lines.length - 1) * lineHeight / 2);
        lines.forEach((line, idx) => {
          ctx.fillText(line, tx, startY + (idx * lineHeight));
        });
      }
    }

    const x0 = pad;
    const y0 = topPad;
    const x = [x0];
    for (let i = 0; i < cols.length; i += 1) x.push(x[i] + cols[i]);

    ctx.fillStyle = bg;
    ctx.fillRect(0, 0, width, height);
    ctx.fillStyle = titleColor;
    ctx.font = '700 54px "Segoe UI", Arial, sans-serif';
    ctx.textAlign = 'center';
    ctx.fillText(`ACSS Open Data Score: ${countryName || '-'}`, width / 2, 36);

    cell(x[0], y0, cols[0], hHead1 + hHead2, headA, 'Sections', { size: 24 });
    cell(x[1], y0, cols[1] + cols[2] + cols[3], hHead1, headA, 'Coverage', { size: 24 });
    cell(x[4], y0, cols[4] + cols[5] + cols[6], hHead1, headA, 'Opennes', { size: 24 });
    cell(x[7], y0, cols[7], hHead1 + hHead2, headB, 'Overall\nScore', { size: 24, lineHeight: 28 });

    cell(x[1], y0 + hHead1, cols[1], hHead2, headA, 'Max Score', { size: 22 });
    cell(x[2], y0 + hHead1, cols[2], hHead2, headA, 'Actual Score', { size: 22 });
    cell(x[3], y0 + hHead1, cols[3], hHead2, headB, 'Sub Score', { size: 22 });
    cell(x[4], y0 + hHead1, cols[4], hHead2, headA, 'Max Score', { size: 22 });
    cell(x[5], y0 + hHead1, cols[5], hHead2, headA, 'Actual Score', { size: 22 });
    cell(x[6], y0 + hHead1, cols[6], hHead2, headB, 'Sub Score', { size: 22 });

    let y = y0 + hHead1 + hHead2;
    rows.forEach((r) => {
      const fill = bg;
      cell(x[0], y, cols[0], hRow, fill, r.section?.title || `Section ${r.section_id || ''}`, { size: 20, weight: 600, align: 'left' });
      cell(x[1], y, cols[1], hRow, fill, fmt2(r.coverage_max_score), { size: 20, weight: 500, align: 'right' });
      cell(x[2], y, cols[2], hRow, fill, fmt2(r.coverage_actual_score), { size: 20, weight: 500, align: 'right' });
      cell(x[3], y, cols[3], hRow, fill, fmt2(r.coverage_sub_score_ratio), { size: 20, weight: 700, align: 'right' });
      cell(x[4], y, cols[4], hRow, fill, fmt2(r.opennes_max_score), { size: 20, weight: 500, align: 'right' });
      cell(x[5], y, cols[5], hRow, fill, fmt2(r.opennes_actual_score), { size: 20, weight: 500, align: 'right' });
      cell(x[6], y, cols[6], hRow, fill, fmt2(r.opennes_sub_score_ratio), { size: 20, weight: 700, align: 'right' });
      cell(x[7], y, cols[7], hRow, fill, fmt2(r.overall_score_ratio), { size: 20, weight: 700, align: 'right' });
      y += hRow;
    });

    cell(x[0], y, cols[0], hWeighted, weightedBg, 'Weighted Score', { size: 22, weight: 700, align: 'left' });
    cell(x[1], y, cols[1] + cols[2], hWeighted, weightedBg, 'Coverage weighted sub score:', { size: 18, weight: 700 });
    cell(x[3], y, cols[3], hWeighted, weightedBg, fmt2(weightedScore?.coverage_sub_score_ratio), { size: 22, weight: 700, align: 'right' });
    cell(x[4], y, cols[4] + cols[5], hWeighted, weightedBg, 'Opennes weighted sub score:', { size: 18, weight: 700 });
    cell(x[6], y, cols[6], hWeighted, weightedBg, fmt2(weightedScore?.opennes_sub_score_ratio), { size: 22, weight: 700, align: 'right' });
    cell(x[7], y, cols[7], hWeighted, weightedBg, fmt2(weightedScore?.overall_score_ratio), { size: 22, weight: 700, align: 'right' });

    const a = document.createElement('a');
    a.href = canvas.toDataURL('image/png');
    a.download = `acss_open_data_score_${sanitizeFilePart(countryName)}.png`;
    document.body.appendChild(a);
    a.click();
    a.remove();
  }

  async function printCountrySummary(countryCode, countryName, btn) {
    if (!countryCode) return;
    const original = btn?.innerHTML || 'Print';
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = 'Printing...';
    }
    try {
      const url = `/api/trx/form?periodid=${encodeURIComponent(periodId)}&country_code=${encodeURIComponent(countryCode)}`;
      const response = await odFetch(url);
      const data = response.data || {};
      const rows = Array.isArray(data.summary) ? data.summary : [];
      const weighted = data.weighted_score || null;
      const titleName = data.assessment_country?.country_name || countryName || countryCode;
      downloadSummaryPng(titleName, rows, weighted);
    } catch (err) {
      odAlert(err?.message || 'Failed to print summary.', 'Print Summary');
    } finally {
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = original;
      }
    }
  }

  async function unlockCountry(assessmentCountryId, countryName, btn) {
    if (!assessmentCountryId) return;
    const ok = window.confirm(`Unlock submission for ${countryName || 'this country'}?`);
    if (!ok) return;

    const original = btn?.innerHTML || 'Unlock';
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = 'Unlocking...';
    }

    try {
      await odFetch(`/api/trx/countries/${encodeURIComponent(assessmentCountryId)}/unlock`, { method: 'POST' });
      await loadCountries();
    } catch (err) {
      odAlert(err?.message || 'Failed to unlock submission.', 'Unlock');
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = original;
      }
    }
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

  function normalizeTemplateHeader(value) {
    return String(value ?? '').toLowerCase().replace(/[^a-z0-9]+/g, '');
  }

  function normalizeCode(value) {
    return String(value ?? '').trim().toUpperCase();
  }

  function cellText(value) {
    return String(value ?? '').trim();
  }

  function parseTemplateMetric(value, allowed) {
    if (value === null || value === undefined || value === '') return null;
    const num = Number(value);
    if (!Number.isFinite(num)) return null;
    const rounded = Math.round(num * 100) / 100;
    for (const candidate of allowed) {
      if (Math.abs(rounded - candidate) < 0.0001) return candidate;
    }
    return null;
  }

  function parseSummaryNumber(value) {
    const n = Number(value);
    return Number.isFinite(n) ? n : null;
  }

  function parseSummaryRatio(value) {
    const num = parseSummaryNumber(value);
    if (num === null) return null;
    if (num > 1 && num <= 100) return Math.round((num / 100) * 1000000) / 1000000;
    return Math.round(num * 1000000) / 1000000;
  }

  async function parseInputRowsFromWorkbook(workbook, codePrefixes = []) {
    const sheetName = workbook.SheetNames.includes('Input') ? 'Input' : workbook.SheetNames[0];
    if (!sheetName) {
      const dbg = { input_sheet: null, reason: 'no-worksheet' };
      setUploadDebug(dbg);
      throw new Error('Template has no worksheet.');
    }
    const sheet = workbook.Sheets[sheetName];
    const rows = XLSX.utils.sheet_to_json(sheet, { header: 1, defval: '' });
    if (!Array.isArray(rows) || rows.length < 1) {
      const dbg = { input_sheet: sheetName, reason: 'input-sheet-empty', input_total_rows: Array.isArray(rows) ? rows.length : 0 };
      setUploadDebug(dbg);
      throw new Error('Input sheet is empty.');
    }

    const headerIndexByKey = new Map();
    const collectHeader = (row) => row.forEach((col, idx) => {
      const key = normalizeTemplateHeader(col);
      if (key && !headerIndexByKey.has(key)) headerIndexByKey.set(key, idx);
    });
    collectHeader(rows[2] || []);
    collectHeader(rows[3] || []);

    const findIdx = (aliases, fallback) => {
      for (const k of aliases) if (headerIndexByKey.has(k)) return Number(headerIndexByKey.get(k));
      return fallback;
    };

    const idxSeries = findIdx(['series', 'coverageseries'], 5);
    const idxMachine = findIdx(['machinereadilibility', 'machinereadibility', 'machinereadability'], 13);
    const idxProp = findIdx(['proprietary', 'nonproprietary'], 14);
    const idxDownload = findIdx(['downloadoptions'], 15);
    const idxMetadata = findIdx(['metadata', 'metadataavailability'], 16);
    const idxTerm = findIdx(['termofuse', 'termofuser'], 17);
    const idxUrl = findIdx(['url', 'urls', 'relevanturl', 'relevanturls'], 19);
    const idxRemark = findIdx(['remark', 'remarks', 'note', 'notes'], 20);

    const codePattern = /^[A-Z]{2}\.\d{3}\.\d{3}\.\d{3}$/;
    const scanned = [];
    let seenDataRow = false;
    let emptyStreak = 0;
    const hardLimit = Math.min(rows.length, 1200);
    let rowsChecked = 0;
    let rowsWithSignal = 0;
    let stoppedBy = '';
    for (let i = 4; i < hardLimit; i += 1) {
      rowsChecked += 1;
      const x = rows[i] || [];
      const b = cellText(x[1]);
      const c = cellText(x[2]);
      const d = cellText(x[3]);
      const e = cellText(x[4]);
      const f = cellText(x[5]);
      const n = cellText(x[13]);
      const o = cellText(x[14]);
      const p = cellText(x[15]);
      const q = cellText(x[16]);
      const r = cellText(x[17]);
      const t = cellText(x[19]);
      const u = cellText(x[20]);
      const hasSignal = !!(b || c || d || e || f || n || o || p || q || r || t || u || cellText(x[0]));

      if (b.toLowerCase() === 'total') {
        stoppedBy = `total at row ${i + 1}`;
        break;
      }
      if (!hasSignal) {
        if (seenDataRow) {
          emptyStreak += 1;
          if (emptyStreak >= 5) {
            stoppedBy = `empty-streak(5) after row ${i + 1}`;
            break;
          }
        }
        continue;
      }
      rowsWithSignal += 1;
      seenDataRow = true;
      emptyStreak = 0;

      const series = cellText(x[idxSeries]);
      scanned.push({
        code_raw: normalizeCode(x[0]),
        source_row: i + 1,
        series,
        machine_readability: parseTemplateMetric(x[idxMachine], [-1, 0, 1]),
        proprietary: parseTemplateMetric(x[idxProp], [-1, 0, 1]),
        download_options: parseTemplateMetric(x[idxDownload], [-1, 0, 0.5, 1]),
        metadata: parseTemplateMetric(x[idxMetadata], [-1, 0, 0.5, 1]),
        term_of_use: parseTemplateMetric(x[idxTerm], [-1, 0, 0.5, 1]),
        urls: cellText(x[idxUrl]),
        remarks: cellText(x[idxRemark]),
      });
    }

    const parsedByCode = scanned
      .filter((r) => codePattern.test(r.code_raw))
      .map((r) => ({ ...r, code: r.code_raw }));
    const debug = {
      input_sheet: sheetName,
      input_total_rows: rows.length,
      scan_start_excel_row: 5,
      scan_hard_limit_excel_row: hardLimit,
      scan_rows_checked: rowsChecked,
      scan_rows_with_signal: rowsWithSignal,
      scan_rows_collected: scanned.length,
      scan_stop_reason: stoppedBy || 'eof',
      valid_codes_in_col_a: parsedByCode.length,
      fallback_codes_from_db: 0,
      fallback_rows_assigned: 0,
    };
    if (parsedByCode.length) {
      return {
        mode: 'code-based',
        rows: parsedByCode.map(({ code_raw, ...rest }) => rest),
        debug,
      };
    }

    const fallbackCodes = (Array.isArray(codePrefixes) ? codePrefixes : [])
      .map((v) => normalizeCode(v))
      .filter((v) => codePattern.test(v));
    debug.fallback_codes_from_db = fallbackCodes.length;
    if (!scanned.length) {
      setUploadDebug({
        ...debug,
        reason: 'no-valid-detail-rows',
        country_code: selectedUploadCountry?.countryCode || '',
        db_prefix_count: fallbackCodes.length,
      });
      throw new Error('No valid detail rows found in Input sheet.');
    }

    let assigned = [];
    if (fallbackCodes.length > 0) {
      const limit = Math.min(scanned.length, fallbackCodes.length);
      for (let i = 0; i < limit; i += 1) {
        assigned.push({
          ...scanned[i],
          code: fallbackCodes[i],
        });
      }
      debug.fallback_rows_assigned = assigned.length;
    } else {
      assigned = scanned.map((row, i) => ({
        ...row,
        code: `__ORDER__${i + 1}`,
      }));
      debug.fallback_rows_assigned = assigned.length;
      debug.reason = 'fallback-by-order-token';
    }
    return {
      mode: 'order-based (fallback)',
      rows: assigned.map(({ code_raw, ...rest }) => rest),
      debug,
    };
  }

  function setUploadMappingMode(modeText, tone = 'secondary') {
    uploadMappingMode = String(modeText || 'unknown');
    const el = document.getElementById('uploadMappingMode');
    if (!el) return;
    el.classList.remove('text-secondary', 'text-success', 'text-warning');
    el.classList.add(`text-${tone}`);
    el.textContent = uploadMappingMode;
  }

  function setUploadDebug(info) {
    uploadDebugInfo = info || null;
    const wrap = document.getElementById('uploadDebugWrap');
    const el = document.getElementById('uploadDebugText');
    if (!wrap || !el) return;
    if (!uploadDebugInfo) {
      wrap.style.display = 'none';
      el.textContent = '';
      return;
    }
    wrap.style.display = '';
    el.textContent = JSON.stringify(uploadDebugInfo, null, 2);
  }

  async function fetchCodePrefixesByCountry(assessmentCountryId) {
    const id = Number(assessmentCountryId || 0);
    if (!id) return [];
    const url = `/api/trx/countries/template-prefixes/${encodeURIComponent(id)}`;
    const response = await odFetch(url);
    const prefixes = Array.isArray(response?.data?.prefixes) ? response.data.prefixes : [];
    return prefixes
      .map((r) => normalizeCode(r))
      .filter((v) => !!v);
  }

  function detectSummarySheetName(sheetNames) {
    if (!Array.isArray(sheetNames) || !sheetNames.length) return null;
    const exact = sheetNames.find((n) => normalizeTemplateHeader(n) === 'summaryreport');
    if (exact) return exact;
    return sheetNames.find((n) => normalizeTemplateHeader(n).includes('summary')) || null;
  }

  async function parseSummaryFromWorkbook(workbook) {
    const summarySheetName = detectSummarySheetName(workbook.SheetNames);
    if (!summarySheetName) throw new Error('Summary Report sheet not found.');
    const sheet = workbook.Sheets[summarySheetName];
    const rows = XLSX.utils.sheet_to_json(sheet, { header: 1, defval: '' });
    if (!Array.isArray(rows) || rows.length < 1) throw new Error('Summary Report sheet is empty.');

    const headerRow = rows[5 - 1] || [];
    let sectionsColIdx = headerRow.findIndex((v) => normalizeTemplateHeader(v) === 'sections');
    if (sectionsColIdx < 0) {
      const fallbackRow = rows[4 - 1] || [];
      sectionsColIdx = fallbackRow.findIndex((v) => normalizeTemplateHeader(v) === 'sections');
    }
    if (sectionsColIdx < 0) {
      sectionsColIdx = 1; // default B
    }

    const idxCoverageMax = sectionsColIdx + 1; // C
    const idxCoverageActual = sectionsColIdx + 2; // D
    const idxCoverageSub = sectionsColIdx + 3; // E
    const idxOpennesMax = sectionsColIdx + 4; // F
    const idxOpennesActual = sectionsColIdx + 5; // G
    const idxOpennesSub = sectionsColIdx + 6; // H
    const idxOverall = sectionsColIdx + 7; // I

    const sections = [];
    for (let excelRow = 6; excelRow <= 9; excelRow += 1) {
      const row = rows[excelRow - 1] || [];
      const coverageMax = parseSummaryNumber(row[idxCoverageMax]);
      const coverageActual = parseSummaryNumber(row[idxCoverageActual]);
      const coverageSub = parseSummaryRatio(row[idxCoverageSub]);
      const opennesMax = parseSummaryNumber(row[idxOpennesMax]);
      const opennesActual = parseSummaryNumber(row[idxOpennesActual]);
      const opennesSub = parseSummaryRatio(row[idxOpennesSub]);
      const overall = parseSummaryRatio(row[idxOverall]);
      sections.push({
        coverage_max_score: coverageMax ?? 0,
        coverage_actual_score: coverageActual ?? 0,
        coverage_sub_score_ratio: coverageSub ?? 0,
        opennes_max_score: opennesMax ?? 0,
        opennes_actual_score: opennesActual ?? 0,
        opennes_sub_score_ratio: opennesSub ?? 0,
        overall_score_ratio: overall ?? 0,
      });
    }

    const weightedRow = rows[10 - 1] || [];
    const weighted = {
      coverage_sub_score_ratio: parseSummaryRatio(weightedRow[idxCoverageSub]) ?? 0,
      opennes_sub_score_ratio: parseSummaryRatio(weightedRow[idxOpennesSub]) ?? 0,
      overall_score_ratio: parseSummaryRatio(weightedRow[idxOverall]) ?? 0,
    };

    if (!sections.length) {
      throw new Error('Failed to parse summary sections from Summary Report sheet.');
    }
    if (!weighted) {
      throw new Error('Failed to parse Weighted Score from Summary Report sheet.');
    }
    return { sections, weighted, sheetName: summarySheetName };
  }

  function setUploadTemplateFile(file) {
    uploadTemplateFile = file || null;
    const label = document.getElementById('uploadTemplateFileName');
    if (label) label.textContent = uploadTemplateFile ? uploadTemplateFile.name : 'No file selected.';
  }

  async function openUploadTemplateDialog(countryId, countryCode, countryName) {
    if (!window.__periodIsOpen) {
      odAlert('Period is completed. Upload is disabled.', 'Upload');
      return;
    }
    selectedUploadCountry = {
      id: Number(countryId || 0),
      countryCode: String(countryCode || ''),
      name: countryName || '-',
    };
    const ok = await odConfirm(
      `Upload template for ${selectedUploadCountry.name}? Data and summary for this participant will be overwritten by the uploaded file.`,
      'Confirm Upload Template'
    );
    if (!ok) return;

    setUploadTemplateFile(null);
    setUploadMappingMode('-', 'secondary');
    setUploadDebug(null);
    const input = document.getElementById('uploadTemplateInput');
    if (input) input.value = '';
    const caption = document.getElementById('uploadTemplateCaption');
    if (caption) caption.textContent = `Target participant: ${selectedUploadCountry.name}`;
    uploadTemplateModal.show();
  }

  async function processUploadTemplate() {
    if (!selectedUploadCountry?.id) throw new Error('Country target not selected.');
    if (!uploadTemplateFile) throw new Error('Please select an Excel file first.');
    if (!window.XLSX) throw new Error('Excel parser not available.');

    const buffer = await uploadTemplateFile.arrayBuffer();
    const workbook = XLSX.read(buffer, { type: 'array' });
    const codePrefixes = await fetchCodePrefixesByCountry(selectedUploadCountry.id);
    setUploadDebug({
      stage: 'before-parse',
      country_code: selectedUploadCountry.countryCode || '',
      db_prefix_count: Array.isArray(codePrefixes) ? codePrefixes.length : 0,
    });
    const parsedInput = await parseInputRowsFromWorkbook(workbook, codePrefixes);
    const parsedRows = Array.isArray(parsedInput?.rows) ? parsedInput.rows : [];
    const mappingMode = String(parsedInput?.mode || 'unknown');
    setUploadDebug({
      ...(parsedInput?.debug || {}),
      country_code: selectedUploadCountry.countryCode || '',
      db_prefix_count: Array.isArray(codePrefixes) ? codePrefixes.length : 0,
      parsed_rows_final: parsedRows.length,
    });
    setUploadMappingMode(mappingMode, mappingMode.includes('fallback') ? 'warning' : 'success');
    const parsedSummary = await parseSummaryFromWorkbook(workbook);

    const response = await odFetch('/api/trx/countries/upload-template', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        periodid: periodId,
        countryid: selectedUploadCountry.id,
        rows: parsedRows,
        summary: parsedSummary,
      }),
    });

    const result = response?.data || {};
    const uploaded = Number(result.uploaded || 0);
    const matched = Number(result.matched || 0);
    const unmatched = Math.max(0, uploaded - matched);
    odToast(`Template uploaded for ${selectedUploadCountry.name}. Mapping: ${mappingMode}. Uploaded: ${uploaded}, matched: ${matched}, unmatched: ${unmatched}.`);
    await loadCountries();
  }

  document.addEventListener('DOMContentLoaded', () => {
    uploadTemplateModal = new bootstrap.Modal(document.getElementById('uploadTemplateDialog'));
    const uploadDropzone = document.getElementById('uploadDropzone');
    const uploadInput = document.getElementById('uploadTemplateInput');
    const uploadProcessBtn = document.getElementById('btnUploadTemplateProcess');

    uploadDropzone.addEventListener('click', () => uploadInput.click());
    uploadInput.addEventListener('change', (event) => {
      const file = event.target.files?.[0] || null;
      setUploadTemplateFile(file);
    });
    uploadDropzone.addEventListener('dragover', (event) => {
      event.preventDefault();
      uploadDropzone.classList.add('is-drag-over');
    });
    uploadDropzone.addEventListener('dragleave', () => uploadDropzone.classList.remove('is-drag-over'));
    uploadDropzone.addEventListener('drop', (event) => {
      event.preventDefault();
      uploadDropzone.classList.remove('is-drag-over');
      const file = event.dataTransfer?.files?.[0] || null;
      if (!file) return;
      uploadInput.value = '';
      setUploadTemplateFile(file);
    });
    uploadProcessBtn.addEventListener('click', async () => {
      uploadProcessBtn.disabled = true;
      try {
        await processUploadTemplate();
        uploadTemplateModal.hide();
      } catch (err) {
        if (uploadDebugInfo) {
          console.error('Upload template parser debug:', uploadDebugInfo);
        }
        odAlert(err?.message || 'Failed to process upload template.', 'Upload Template');
      } finally {
        uploadProcessBtn.disabled = false;
      }
    });

    document.getElementById('btnRefreshCountries').addEventListener('click', loadCountries);
    document.getElementById('tbCountries').addEventListener('click', (event) => {
      const unlockBtn = event.target.closest('.btn-unlock-country');
      if (unlockBtn) {
        unlockCountry(unlockBtn.dataset.countryId || '', unlockBtn.dataset.countryName || '', unlockBtn);
        return;
      }
      const uploadBtn = event.target.closest('.btn-upload-country');
      if (uploadBtn) {
        openUploadTemplateDialog(
          uploadBtn.dataset.countryId || '',
          uploadBtn.dataset.countryCode || '',
          uploadBtn.dataset.countryName || ''
        );
        return;
      }
      const btn = event.target.closest('.btn-print-country');
      if (!btn) return;
      printCountrySummary(btn.dataset.countryCode || '', btn.dataset.countryName || '', btn);
    });
    loadCountries();
  });
</script>
@endpush
