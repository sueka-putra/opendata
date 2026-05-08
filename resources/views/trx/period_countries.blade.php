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
    document.getElementById('tbCountries').addEventListener('click', (event) => {
      const btn = event.target.closest('.btn-print-country');
      if (!btn) return;
      printCountrySummary(btn.dataset.countryCode || '', btn.dataset.countryName || '', btn);
    });
    loadCountries();
  });
</script>
@endpush
