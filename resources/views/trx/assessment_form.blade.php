@extends('layouts.opendata')

@section('content')
@php
  $uploadTemplateUrl = '/template/ACSS%20Open%20Data%20Assessment%20Tools.xlsx';
  $backUrl = route('dashboard');
  $referer = (string) request()->headers->get('referer', '');
  if ($referer !== '') {
    $parts = parse_url($referer);
    $host = isset($parts['host']) ? (string) $parts['host'] : '';
    $path = isset($parts['path']) ? (string) $parts['path'] : '';
    $query = isset($parts['query']) ? ('?' . $parts['query']) : '';
    $isSameHost = $host !== '' && strcasecmp($host, request()->getHost()) === 0;
    $isRelative = $host === '' && str_starts_with($referer, '/');
    $isSafePath = $path !== '' && str_starts_with($path, '/') && !str_starts_with($path, '//');
    $isNotFormPath = $path !== request()->getPathInfo();
    if (($isSameHost || $isRelative) && $isSafePath && $isNotFormPath) {
      $backUrl = $path . $query;
    }
  }
  $templateCandidates = [
    public_path('template/ACSS Open Data Assessment Tools.xlsx') => '/template/ACSS%20Open%20Data%20Assessment%20Tools.xlsx',
    public_path('templates/ACSS Open Data Assessment Tools.xlsx') => '/templates/ACSS%20Open%20Data%20Assessment%20Tools.xlsx',
    public_path('templates/ACSS Open Data Assesment Tools.xlsx') => '/templates/ACSS%20Open%20Data%20Assesment%20Tools.xlsx',
  ];
  foreach ($templateCandidates as $path => $url) {
    if (file_exists($path)) {
      $uploadTemplateUrl = $url;
      break;
    }
  }
@endphp
<div class="period-theme-wrap">
  <div class="period-theme-shell assessment-shell">
    <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-3">
      <div>
        <h1 class="h5 period-title mb-1">Assessment Form</h1>
        <div class="period-subtitle" id="formMeta">Loading assessment...</div>
      </div>
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <a class="btn od-btn-outline assessment-form-action-btn" id="btnBackForm" href="{{ route('dashboard') }}">Back</a>
        <div class="dropdown">
          <button class="btn od-btn-primary dropdown-toggle assessment-form-action-btn" type="button" id="btnUploadMenu" data-bs-toggle="dropdown" aria-expanded="false">Upload</button>
          <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="btnUploadMenu">
            <li><a class="dropdown-item" id="btnDownloadTemplate" href="{{ $uploadTemplateUrl }}" download>Download Template</a></li>
            <li><button class="dropdown-item" type="button" id="btnUploadTemplateOpen">Upload Template</button></li>
          </ul>
        </div>
        <button class="btn od-btn-primary assessment-form-action-btn" type="button" id="btnExportForm">Export</button>
        <button class="btn od-btn-primary assessment-form-action-btn" type="button" id="btnSaveForm">Save</button>
        <button class="btn od-btn-primary assessment-form-action-btn" type="button" id="btnSubmitForm">Submit</button>
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
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="log-tab" data-bs-toggle="tab" data-bs-target="#log-pane" type="button" role="tab" aria-controls="log-pane" aria-selected="false">Log</button>
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
              <span class="small text-muted" id="entryFilterInfo" style="display: none;">Rows: 0</span>
            </div>
          </div>
          <div class="table-responsive">
            <table class="table period-table align-top mb-0 assessment-table">
              <thead>
                <tr>
                  <th style="width:420px;">Dimension</th>
                  <th style="width:360px;">Coverage</th>
                  <th style="width:360px;">Openness</th>
                  <th style="width:300px; max-width:350px;">Evidence & Notes</th>
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
            <span class="text-muted small">&nbsp;</span>
          </div>
          <div class="table-responsive">
            <table class="table period-table align-middle mb-0 assessment-summary-table">
              <thead>
                <tr>
                  <th style="vertical-align: middle; max-width:430px;" rowspan="2" class="summary-vr">Section</th>
                  <th style="width:90px; vertical-align: middle;" rowspan="2" class="summary-vr">Progress</th>
                  <th style="width:340px;" colspan="3" class="text-center summary-vr">Coverage</th>
                  <th style="width:160px;" colspan="3" class="text-center summary-vr">Opennes</th>
                  <th style="width:160px;" rowspan="2">Overall Score</th>
                </tr>
                <tr>
                  <th style="width:130px;">Max Score</th>
                  <th style="width:130px;">Actual Score</th>
                  <th style="width:120px;" class="summary-vr">Sub Score</th>
                  <th style="width:130px;">Max Score</th>
                  <th style="width:130px;">Actual Score</th>
                  <th style="width:120px;" class="summary-vr">Sub Score</th>
                </tr>
              </thead>
              <tbody id="summaryRows">
                <tr><td colspan="9" class="text-muted">Loading summary...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="tab-pane fade" id="log-pane" role="tabpanel" aria-labelledby="log-tab">
        <div class="period-table-card mb-3">
          <div class="period-table-toolbar d-flex align-items-center justify-content-between gap-3 flex-wrap">
            <strong class="assessment-toolbar-title">Assessment Log</strong>
            <span class="text-muted small">&nbsp;</span>
          </div>
          <div class="table-responsive">
            <table class="table period-table align-middle mb-0">
              <thead>
                <tr>
                  <th style="width:220px;">Tanggal dan waktu</th>
                  <th style="width:320px;">Actor</th>
                  <th>Action/Text</th>
                </tr>
              </thead>
              <tbody id="logRows">
                <tr><td colspan="3" class="text-muted">Loading logs...</td></tr>
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

    <div class="modal fade period-dialog" id="uploadTemplateDialog" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title mb-0">Upload Assessment Template</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p class="small text-muted mb-2">Upload Excel template, then data from sheet <strong>Input</strong> will be mapped by <strong>Code (column A)</strong>.</p>
            <div id="uploadDropzone" class="assessment-upload-dropzone">
              <input type="file" id="uploadTemplateInput" class="d-none" accept=".xlsx,.xls">
              <div class="assessment-upload-icon"><i class="fa-solid fa-file-arrow-up"></i></div>
              <p class="mb-1 fw-semibold">Drop Excel file here</p>
              <p class="mb-2 small text-muted">or click to browse</p>
              <p id="uploadTemplateFileName" class="small mb-0 text-muted">No file selected.</p>
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn od-btn-outline" type="button" data-bs-dismiss="modal">Cancel</button>
            <button class="btn od-btn-primary" type="button" id="btnUploadTemplateProcess">Process File</button>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade period-dialog" id="uploadResultDialog" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title mb-0">Upload Result</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p class="small text-muted mb-2" id="uploadResultSummary">Processed.</p>
            <div class="table-responsive" id="uploadUnmatchedWrap">
              <table class="table table-sm period-table align-middle mb-0">
                <thead>
                  <tr>
                    <th style="width:110px;">Template Row</th>
                    <th style="width:140px;">Code</th>
                    <th>Reason</th>
                  </tr>
                </thead>
                <tbody id="uploadUnmatchedRows">
                  <tr><td colspan="3" class="text-muted">No unmatched rows.</td></tr>
                </tbody>
              </table>
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn od-btn-primary" type="button" data-bs-dismiss="modal">OK</button>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade period-dialog assessment-help-wizard-modal" id="helpWizardDialog" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title mb-0">Quick Assistance</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="assessment-help-layout">
              <div class="assessment-help-outline-wrap">
                <div class="assessment-help-outline-title">Outline</div>
                <div id="helpWizardOutline" class="assessment-help-outline-list"></div>
              </div>
              <div class="assessment-help-detail-wrap">
                <div class="small text-muted mb-2" id="helpWizardStepMeta">Step 1 of 1</div>
                <h6 class="mb-2" id="helpWizardStepTitle">Welcome</h6>
                <p class="mb-2" id="helpWizardStepDescription">This wizard guides you through the main actions in this page.</p>
                <div class="assessment-help-hint-box small mb-0" id="helpWizardTargetHint">
                  Focus area will be highlighted on the page.
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn od-btn-outline" type="button" id="btnHelpWizardPrev">Previous</button>
            <button class="btn od-btn-primary" type="button" id="btnHelpWizardNext">Next</button>
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

    <button class="assessment-fab-help" type="button" id="btnHelpWizard" aria-label="Open help wizard" title="Help">
      <span class="assessment-fab-icon" aria-hidden="true">
        <i class="fa-solid fa-circle-question" aria-hidden="true"></i>
      </span>
      <span class="assessment-fab-label">Help</span>
    </button>
  </div>
</div>
@endsection

@push('styles')
<style>
  .assessment-summary-table {
    --summary-divider-color: rgba(44, 96, 167, 0.35);
  }

  .assessment-form-action-btn {
    width: 104px;
    min-width: 104px;
    white-space: nowrap;
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
    color: #000000;
    white-space: nowrap;
  }

  .assessment-metric.assessment-metric-final {
    background: #dbe9fb;
    border-color: #c9d9f1;
  }

  .assessment-metric.assessment-metric-final span {
    font-size: 0.9rem;
    color: #334a6b;
  }

  .assessment-metric.assessment-metric-final strong {
    font-size: 0.9rem;
    color: #172a45;
  }

  .assessment-score-stack {
    display: flex;
    flex-direction: column;
    min-height: 100%;
  }

  .assessment-score-footer {
    margin-top: auto;
    padding-top: 0.5rem;
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
    bottom: 4rem;
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

  .assessment-fab-help {
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
    gap: 8px;
    padding: 9px 14px;
    box-shadow: 0 8px 20px rgba(16, 73, 160, 0.18);
    font-size: 0.8rem;
    font-weight: 600;
    line-height: 1;
    transition: background-color 160ms ease, color 160ms ease, border-color 160ms ease, transform 160ms ease;
  }

  .assessment-fab-help:hover,
  .assessment-fab-help:focus-visible {
    background: #2b76e5;
    color: #ffffff;
    border-color: #2b76e5;
    transform: translateY(-1px);
  }

  .assessment-fab-help .assessment-fab-icon {
    width: auto;
    height: auto;
    font-size: 1rem;
  }

  .assessment-fab-help .assessment-fab-label {
    font-size: 0.8rem;
    font-weight: 600;
  }

  .assessment-upload-dropzone {
    border: 1px dashed #9cb6d9;
    border-radius: 12px;
    background: #f7faff;
    text-align: center;
    padding: 1.25rem;
    cursor: pointer;
    transition: border-color 0.18s ease, background-color 0.18s ease;
  }

  .assessment-upload-dropzone:hover,
  .assessment-upload-dropzone.is-drag-over {
    border-color: #2b76e5;
    background: #edf4ff;
  }

  .assessment-upload-icon {
    font-size: 1.25rem;
    color: #2b76e5;
    margin-bottom: 0.5rem;
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
    width: min(840px, calc(100vw - 1.2rem));
    max-width: min(840px, calc(100vw - 1.2rem));
    pointer-events: auto;
    transform: none !important;
    z-index: 3001;
  }

  .assessment-help-wizard-modal .modal-dialog:not(.is-positioned) {
    visibility: hidden;
  }

  .assessment-help-wizard-modal .modal-content {
    border: 2px solid #d97706;
    border-radius: 8px;
    box-shadow: 0 8px 22px rgba(180, 83, 9, 0.2);
    background: #fffaf2;
  }

  .assessment-help-wizard-modal .modal-header {
    border-bottom: 1px solid #f3c78a;
    background: #ffedd5;
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
  }

  .assessment-help-wizard-modal .modal-title {
    color: #9a3412;
    font-size: 0.95rem;
    font-weight: 700;
    letter-spacing: 0.01em;
  }

  .assessment-help-wizard-modal .modal-body {
    padding-top: 0.85rem;
    padding-bottom: 0.8rem;
  }

  .assessment-help-detail-link {
    color: #1d4ed8;
    font-weight: 600;
    text-decoration: underline;
    text-underline-offset: 2px;
  }

  .assessment-help-detail-link:hover,
  .assessment-help-detail-link:focus-visible {
    color: #1e40af;
  }

  .assessment-help-layout {
    display: flex;
    gap: 0.7rem;
  }

  .assessment-help-outline-wrap {
    flex: 0 0 30%;
    max-width: 30%;
    border: 1px solid #f3c78a;
    border-radius: 6px;
    background: #fff6ea;
    padding: 0.5rem;
  }

  .assessment-help-outline-title {
    color: #9a3412;
    font-weight: 700;
    font-size: 0.78rem;
    margin-bottom: 0.35rem;
  }

  .assessment-help-outline-list {
    display: flex;
    flex-direction: column;
    gap: 0.22rem;
    max-height: 242px;
    overflow: auto;
    padding-right: 0.2rem;
  }

  .assessment-help-outline-item {
    border-radius: 4px;
    padding: 0.25rem 0.36rem;
    font-size: 0.73rem;
    line-height: 1.25;
    color: #7c2d12;
    background: #fffbf5;
    border: 1px solid #f4d2a4;
    cursor: pointer;
    transition: background-color 160ms ease, border-color 160ms ease, color 160ms ease;
  }

  .assessment-help-outline-item:hover {
    background: #ffedd5;
    border-color: #f59e0b;
  }

  .assessment-help-outline-item.is-active {
    background: #fed7aa;
    border-color: #f59e0b;
    color: #8a2e0b;
    font-weight: 700;
  }

  .assessment-help-detail-wrap {
    flex: 0 0 70%;
    max-width: 70%;
    min-width: 0;
  }

  .assessment-help-hint-box {
    border: 1px solid #f0b873;
    border-radius: 6px;
    background: #fff3df;
    color: #9a3412;
    padding: 0.42rem 0.52rem;
    line-height: 1.35;
  }

  .assessment-help-wizard-modal .modal-footer {
    border-top: 1px solid #f3c78a;
    background: #fff1dd;
    border-bottom-left-radius: 8px;
    border-bottom-right-radius: 8px;
    padding-top: 0.62rem;
    padding-bottom: 0.62rem;
  }

  .assessment-wizard-highlight {
    z-index: 1061;
    outline: 3px solid rgba(230, 126, 34, 0.92);
    outline-offset: 3px;
    box-shadow: 0 0 0 8px rgba(230, 126, 34, 0.22);
    border-radius: 0.4rem;
    animation: assessment-wizard-pulse 1200ms ease-in-out infinite;
  }

  @keyframes assessment-wizard-pulse {
    0% {
      outline-color: rgba(230, 126, 34, 0.88);
      box-shadow: 0 0 0 8px rgba(230, 126, 34, 0.22);
    }
    50% {
      outline-color: rgba(217, 119, 6, 0.98);
      box-shadow: 0 0 0 11px rgba(245, 158, 11, 0.17);
    }
    100% {
      outline-color: rgba(230, 126, 34, 0.88);
      box-shadow: 0 0 0 8px rgba(230, 126, 34, 0.22);
    }
  }

  @media (max-width: 991.98px) {
    .assessment-help-wizard-modal .modal-dialog {
      right: 0.6rem;
      top: 0.6rem;
      width: calc(100vw - 1.2rem);
      max-width: calc(100vw - 1.2rem);
    }

    .assessment-nav-dock {
      right: 0.75rem;
      bottom: 4.6rem;
    }

    .assessment-fab-help {
      right: 0.75rem;
      bottom: 0.75rem;
      min-width: 110px;
      padding: 8px 12px;
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

    .assessment-help-layout {
      flex-direction: column;
      gap: 0.55rem;
    }

    .assessment-help-outline-wrap,
    .assessment-help-detail-wrap {
      flex: 1 1 auto;
      max-width: 100%;
    }
  }

  #btnHelpWizardNext {
    background: #ea7a14;
    border-color: #d26809;
    color: #fff;
  }

  #btnHelpWizardNext:hover,
  #btnHelpWizardNext:focus-visible {
    background: #cf680b;
    border-color: #ba5b04;
    color: #fff;
  }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script>
  const params = new URLSearchParams(window.location.search);
  let navigatorModal = null;
  let uploadTemplateModal = null;
  let uploadResultModal = null;
  let helpWizardModal = null;
  let uploadTemplateFile = null;
  const TEMPLATE_TEXT_MAX_LENGTH = 3000;
  const HELP_WIZARD_STORAGE_KEY = 'od.trx.form.help_wizard_seen.v1';
  const helpEntryDetailUrl = @json(route('help'));
  const helpWizardState = {
    stepIndex: 0,
  };
  const pageState = {
    backUrl: @json($backUrl),
    periodId: Number(params.get('periodid') || 0),
    countryCode: String(params.get('country_code') || '').trim(),
    period: null,
    assessmentCountry: null,
    detailMeta: null,
    detail: [],
    summary: [],
    logs: [],
    summaryLocked: {},
    weightedScore: null,
    editable: false,
    isAseanstatsStaff: false,
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
  const helpWizardStepsBase = [
    {
      title: 'Header & Period Context',
      description: 'Check this area first to confirm period, reference year, form mode (editable/read-only), and selected country.',
      selector: '#formMeta',
      tab: 'entry',
      hint: 'Always verify this context before entering data.',
    },
    {
      title: 'Entry Filters',
      description: 'Use Section and Category filters to narrow the row list. Changing Section resets Category to keep filters consistent.',
      selector: '.assessment-entry-filters',
      tab: 'entry',
      hint: 'Start from Section, then drill down with Category.',
    },
    {
      title: 'Unfinished-only View',
      description: 'Turn on this checkbox to focus only on rows that are not fully complete yet.',
      selector: '#entryUnfinishedOnly',
      tab: 'entry',
      hint: 'Useful for follow-up before save or submit.',
    },
    {
      title: 'Row Navigator',
      description: 'Use Navigator to jump quickly to top, bottom, or a specific row from the Go To dialog.',
      selector: '#btnNavMain',
      tab: 'entry',
      hint: 'Best for large forms with many rows.',
    },
    {
      title: 'Help Button',
      description: 'Use Help button anytime to reopen this wizard and walk through the page guidance again.',
      selector: '#btnHelpWizard',
      tab: 'entry',
      hint: 'This helps onboard new users and refresh workflow reminders.',
    },
    {
      title: 'Assessment Entry Table',
      description: 'Fill Series, Coverage, Openness, and Evidence fields per row. Tooltips explain scoring logic for each field.',
      detailLink: true,
      detailTopic: 'entry',
      selector: '.assessment-table',
      tab: 'entry',
      hint: 'Rows are scored automatically and summary updates in real time.',
    },
    {
      title: 'Summary Tab',
      description: 'Open Summary tab to review progress and weighted scores by section before final submission.',
      detailLink: true,
      detailTopic: 'summary',
      selector: '#summaryRows',
      tab: 'summary',
      hint: 'Use this as validation checkpoint after data entry.',
    },
    {
      title: 'Assessment Log',
      description: 'Open Log tab to review timeline history of saves, submissions, and relevant activity notes.',
      selector: '#logRows',
      tab: 'log',
      hint: 'Use this for audit trail and progress tracking.',
    },
    {
      title: 'Upload Feature',
      description: 'Use Upload menu to download template and upload prepared data mapped by assessment row code.',
      selector: '#btnUploadMenu',
      tab: 'entry',
      hint: 'Uploading can update values in bulk, so verify data before saving.',
    },
    {
      title: 'Save',
      description: 'Use Save to store current changes as draft. You can continue editing after that and save again anytime for next updates.',
      selector: '#btnSaveForm',
      tab: 'entry',
      hint: 'Save often while working so progress is not lost.',
    },
    {
      title: 'Submit',
      description: 'Submit stores the final assessment and marks the end of data entry. After submit, the assessment is locked and no further manual edits or template uploads are allowed.',
      selector: '#btnSubmitForm',
      tab: 'entry',
      hint: 'If revisions are needed after submit, contact ASEANstats to unlock it while the period is still Open.',
    },
  ];
  let helpWizardSteps = [];

  function buildHelpWizardSteps() {
    const isEditable = !!pageState.editable;
    const uploadBtn = document.getElementById('btnUploadMenu');
    const saveBtn = document.getElementById('btnSaveForm');
    const submitBtn = document.getElementById('btnSubmitForm');
    const isUploadVisible = !!uploadBtn && uploadBtn.offsetParent !== null;
    const isSaveVisible = !!saveBtn && saveBtn.offsetParent !== null;
    const isSubmitVisible = !!submitBtn && submitBtn.offsetParent !== null;
    const isUploadEnabled = !!uploadBtn && !uploadBtn.disabled;
    const isSaveEnabled = !!saveBtn && !saveBtn.disabled;
    const isSubmitEnabled = !!submitBtn && !submitBtn.disabled;

    const includeUploadStep = isEditable && isUploadVisible && isUploadEnabled;
    const includeSaveStep = isEditable && isSaveVisible && isSaveEnabled;
    const includeSubmitStep = isEditable && isSubmitVisible && isSubmitEnabled;

    helpWizardSteps = helpWizardStepsBase.filter((step) => {
      if (step.selector === '#btnUploadMenu') return includeUploadStep;
      if (step.selector === '#btnSaveForm') return includeSaveStep;
      if (step.selector === '#btnSubmitForm') return includeSubmitStep;
      return true;
    });
  }

  function intersectionArea(rectA, rectB) {
    const left = Math.max(rectA.left, rectB.left);
    const right = Math.min(rectA.right, rectB.right);
    const top = Math.max(rectA.top, rectB.top);
    const bottom = Math.min(rectA.bottom, rectB.bottom);
    const width = Math.max(0, right - left);
    const height = Math.max(0, bottom - top);
    return width * height;
  }

  function rectDistance(rectA, rectB) {
    const dx = Math.max(rectB.left - rectA.right, rectA.left - rectB.right, 0);
    const dy = Math.max(rectB.top - rectA.bottom, rectA.top - rectB.bottom, 0);
    return Math.hypot(dx, dy);
  }

  function positionHelpWizardDialog(target = null) {
    const dialog = document.querySelector('#helpWizardDialog .modal-dialog');
    if (!dialog) return;

    dialog.style.left = '';
    dialog.style.right = '';
    dialog.style.top = '';
    dialog.style.bottom = '';

    const dialogRect = dialog.getBoundingClientRect();
    const viewportWidth = window.innerWidth;
    const viewportHeight = window.innerHeight;
    const edge = 12;
    const width = Math.max(220, dialogRect.width || 420);
    const height = Math.max(180, dialogRect.height || 260);

    const placements = [];
    const pushPlacement = (top, left) => {
      placements.push({
        top: Math.max(edge, Math.min(viewportHeight - height - edge, top)),
        left: Math.max(edge, Math.min(viewportWidth - width - edge, left)),
      });
    };

    let best = placements[0];
    const targetRect = target?.getBoundingClientRect?.() || null;
    const gap = 12;

    if (targetRect) {
      const centerX = targetRect.left + (targetRect.width / 2);
      const centerY = targetRect.top + (targetRect.height / 2);
      pushPlacement(targetRect.top, targetRect.right + gap);
      pushPlacement(targetRect.top, targetRect.left - width - gap);
      pushPlacement(targetRect.bottom + gap, centerX - (width / 2));
      pushPlacement(targetRect.top - height - gap, centerX - (width / 2));
      pushPlacement(centerY - (height / 2), targetRect.right + gap);
      pushPlacement(centerY - (height / 2), targetRect.left - width - gap);
    }

    pushPlacement(edge, viewportWidth - width - edge);
    pushPlacement(edge, edge);
    pushPlacement(viewportHeight - height - edge, viewportWidth - width - edge);
    pushPlacement(viewportHeight - height - edge, edge);

    if (!placements.length) return;
    best = placements[0];
    let bestScore = Number.POSITIVE_INFINITY;

    const paddedTargetRect = targetRect
      ? {
          left: targetRect.left - 6,
          top: targetRect.top - 6,
          right: targetRect.right + 6,
          bottom: targetRect.bottom + 6,
        }
      : null;

    placements.forEach((candidate, index) => {
      const candidateRect = {
        left: candidate.left,
        top: candidate.top,
        right: candidate.left + width,
        bottom: candidate.top + height,
      };
      const overlap = paddedTargetRect ? intersectionArea(candidateRect, paddedTargetRect) : 0;
      const distance = paddedTargetRect ? rectDistance(candidateRect, paddedTargetRect) : 0;
      const score = overlap > 0 ? (overlap * 10000) + distance + (index * 0.01) : distance + (index * 0.01);
      if (score < bestScore) {
        bestScore = score;
        best = candidate;
      }
    });

    dialog.style.left = `${best.left}px`;
    dialog.style.top = `${best.top}px`;
    dialog.style.right = 'auto';
    dialog.style.bottom = 'auto';
  }

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

  function fmtDateTime(value) {
    if (!value) return '-';
    const raw = String(value).trim();
    if (!raw) return '-';
    const hasTimezone = /(?:Z|[+\-]\d{2}:\d{2})$/i.test(raw);
    const utcIso = hasTimezone ? raw : raw.replace(' ', 'T') + 'Z';
    const dt = new Date(utcIso);
    if (Number.isNaN(dt.getTime())) return String(value);
    return dt.toLocaleString('en-GB', {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit',
      hour12: false,
    });
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

  function clearHelpWizardHighlight() {
    document.querySelectorAll('.assessment-wizard-highlight').forEach((el) => {
      el.classList.remove('assessment-wizard-highlight');
    });
  }

  function renderHelpWizardOutline(activeIndex = 0) {
    const outlineEl = document.getElementById('helpWizardOutline');
    if (!outlineEl) return;
    outlineEl.innerHTML = helpWizardSteps.map((step, index) => {
      const activeClass = index === activeIndex ? ' is-active' : '';
      return `<div class="assessment-help-outline-item${activeClass}" data-step-index="${index}">${index + 1}. ${esc(step.title || 'Step')}</div>`;
    }).join('');
  }

  function jumpHelpWizardStep(index) {
    const nextIndex = Number(index);
    if (!Number.isFinite(nextIndex)) return;
    if (nextIndex < 0 || nextIndex >= helpWizardSteps.length) return;
    helpWizardState.stepIndex = nextIndex;
    renderHelpWizardStep();
  }

  function activateAssessmentTab(tabName) {
    let tabId = 'entry-tab';
    if (tabName === 'summary') tabId = 'summary-tab';
    if (tabName === 'log') tabId = 'log-tab';
    const tabEl = document.getElementById(tabId);
    if (!tabEl) return;
    bootstrap.Tab.getOrCreateInstance(tabEl).show();
  }

  function resolveHelpWizardTarget(step) {
    if (!step?.selector) return null;
    const target = document.querySelector(step.selector);
    if (!target) return null;

    if (step.selector === '.assessment-table') {
      const detailRows = [...document.querySelectorAll('#detailRows tr[data-row-id]')];
      if (detailRows.length > 0) {
        const visibleRow = detailRows.find((row) => {
          const rect = row.getBoundingClientRect();
          return rect.bottom > 100 && rect.top < (window.innerHeight - 120);
        });
        return visibleRow || detailRows[0];
      }
    }

    return target;
  }

  function scrollHelpWizardTargetIntoView(target) {
    if (!target) return;
    const rect = target.getBoundingClientRect();
    const topMargin = 90;
    const bottomMargin = 140;
    const viewportTop = topMargin;
    const viewportBottom = window.innerHeight - bottomMargin;
    let deltaY = 0;

    if (rect.top < viewportTop) {
      deltaY = rect.top - viewportTop;
    } else if (rect.bottom > viewportBottom) {
      deltaY = rect.bottom - viewportBottom;
    }

    if (Math.abs(deltaY) > 1) {
      window.scrollBy({ top: deltaY, behavior: 'smooth' });
    }
  }

  function releaseHelpWizardBodyLock() {
    document.body.classList.remove('modal-open');
    document.body.style.removeProperty('overflow');
    document.body.style.removeProperty('padding-right');
  }

  function renderHelpWizardStep() {
    if (!helpWizardModal) return;
    buildHelpWizardSteps();
    if (!helpWizardSteps.length) return;

    const maxIndex = helpWizardSteps.length - 1;
    const safeIndex = Math.min(Math.max(Number(helpWizardState.stepIndex) || 0, 0), maxIndex);
    helpWizardState.stepIndex = safeIndex;

    const step = helpWizardSteps[safeIndex];
    activateAssessmentTab(step.tab || 'entry');

    const stepMetaEl = document.getElementById('helpWizardStepMeta');
    const titleEl = document.getElementById('helpWizardStepTitle');
    const descEl = document.getElementById('helpWizardStepDescription');
    const hintEl = document.getElementById('helpWizardTargetHint');
    const prevBtn = document.getElementById('btnHelpWizardPrev');
    const nextBtn = document.getElementById('btnHelpWizardNext');

    stepMetaEl.textContent = `Step ${safeIndex + 1} of ${helpWizardSteps.length}`;
    titleEl.textContent = step.title || 'Help';
    if (step.detailLink) {
      const text = esc(step.description || '');
      const topic = encodeURIComponent(String(step.detailTopic || 'entry'));
      const detailUrl = esc(`${helpEntryDetailUrl || '#'}?topic=${topic}`);
      descEl.innerHTML = `${text} <a class="assessment-help-detail-link" href="${detailUrl}" target="_blank" rel="noopener noreferrer">See more detail</a>.`;
    } else {
      descEl.textContent = step.description || '';
    }
    hintEl.textContent = step.hint || 'Focus area will be highlighted on the page.';
    prevBtn.disabled = safeIndex === 0;
    nextBtn.textContent = safeIndex === maxIndex ? 'Finish' : 'Next';
    renderHelpWizardOutline(safeIndex);

    clearHelpWizardHighlight();
    const immediateTarget = resolveHelpWizardTarget(step);
    positionHelpWizardDialog(immediateTarget);

    window.setTimeout(() => {
      const target = resolveHelpWizardTarget(step);
      if (!target) {
        hintEl.textContent = 'Target area not available yet in current state.';
        positionHelpWizardDialog(null);
        return;
      }
      target.classList.add('assessment-wizard-highlight');
      scrollHelpWizardTargetIntoView(target);
      positionHelpWizardDialog(target);
      window.setTimeout(() => positionHelpWizardDialog(target), 260);
    }, 180);
  }

  function openHelpWizard(startIndex = 0) {
    if (!helpWizardModal) return;
    buildHelpWizardSteps();
    if (!helpWizardSteps.length) return;
    helpWizardState.stepIndex = Number(startIndex) || 0;
    const dialogEl = document.getElementById('helpWizardDialog');
    if (dialogEl?.classList.contains('show')) {
      renderHelpWizardStep();
      return;
    }
    const targetStep = helpWizardSteps[helpWizardState.stepIndex] || null;
    activateAssessmentTab(targetStep?.tab || 'entry');
    const target = resolveHelpWizardTarget(targetStep);
    positionHelpWizardDialog(target);
    dialogEl?.querySelector('.modal-dialog')?.classList.add('is-positioned');
    helpWizardModal.show();
  }

  function maybeAutoOpenHelpWizard() {
    if (!helpWizardModal) return;
    if (!pageState.periodId) return;

    let shouldOpen = false;
    try {
      shouldOpen = localStorage.getItem(HELP_WIZARD_STORAGE_KEY) !== '1';
      if (shouldOpen) {
        localStorage.setItem(HELP_WIZARD_STORAGE_KEY, '1');
      }
    } catch (error) {
      shouldOpen = false;
    }

    if (!shouldOpen) return;
    window.setTimeout(() => {
      openHelpWizard(0);
    }, 600);
  }

  function boolFlag(v) {
    return v === true || v === 1 || v === '1';
  }

  function isAseanstatsOnlyLockedRow(row) {
    return boolFlag(row?.aseanstats_only) && !pageState.isAseanstatsStaff;
  }

  function applyAseanstatsOnlyDefaults(row) {
    if (!isAseanstatsOnlyLockedRow(row)) return;
    row.series = 'NA';
    row.machine_readability = -1;
    row.proprietary = -1;
    row.download_options = -1;
    row.metadata = -1;
    row.term_of_use = -1;
  }

  function parseMetric(raw, fallback = null) {
    const val = String(raw ?? '').trim();
    if (!val) return fallback;
    const num = Number(val);
    return Number.isNaN(num) ? fallback : num;
  }

  function clampOpennessMetric(value) {
    const num = parseMetric(value, 0) || 0;
    return num < 0 ? 0 : num;
  }

  function applySeriesNaToOpenness(row) {
    if (!row) return;
    const isNa = String(row.series ?? '').trim().toUpperCase() === 'NA';
    const fields = ['machine_readability', 'proprietary', 'download_options', 'metadata', 'term_of_use'];

    if (isNa) {
      fields.forEach((f) => {
        row[f] = -1;
      });
      return;
    }

    fields.forEach((f) => {
      if (Number(parseMetric(row[f], null)) === -1) {
        row[f] = null;
      }
    });
  }

  function parseSeriesYears(raw) {
    const text = String(raw ?? '').trim();
    if (!text || text.toUpperCase() === 'NA') return [];

    const parts = text.split(',').map((p) => p.trim()).filter(Boolean);
    const years = [];

    for (const part of parts) {
      const yearMatch = part.match(/^\d{4}$/);
      if (yearMatch) {
        years.push(Number(part));
        continue;
      }

      const rangeMatch = part.match(/^(\d{4})\s*-\s*(\d{4})$/);
      if (rangeMatch) {
        let start = Number(rangeMatch[1]);
        let end = Number(rangeMatch[2]);
        if (start > end) [start, end] = [end, start];
        for (let year = start; year <= end; year += 1) {
          years.push(year);
        }
        continue;
      }

      const fiscalMatch = part.match(/^(\d{4})\s*\/\s*(\d{4})$/);
      if (fiscalMatch) {
        years.push(Math.max(Number(fiscalMatch[1]), Number(fiscalMatch[2])));
      }
    }

    return [...new Set(years)].sort((a, b) => a - b);
  }

  function computeCoverageFromSeries(series, referenceYear) {
    const raw = String(series ?? '').trim();
    if (raw.toUpperCase() === 'NA') {
      return {
        count_all: null,
        count_5: null,
        count_10: null,
        c1: null,
        c2: null,
        c3: null,
        c: 0,
        is_na: true,
      };
    }

    const years = parseSeriesYears(raw);
    const countAll = years.length;
    const year = Number(referenceYear) || new Date().getFullYear();
    const last5 = Array.from({ length: 5 }, (_, idx) => year - 4 + idx);
    const last10 = Array.from({ length: 10 }, (_, idx) => year - 9 + idx);
    const count5 = years.filter((y) => last5.includes(y)).length;
    const count10 = years.filter((y) => last10.includes(y)).length;
    const c1 = countAll > 0 ? 1 : 0;
    const c2 = count5 > 2 ? 1 : (count5 > 1 ? 0.5 : 0);
    const c3 = count10 > 5 ? 1 : (count10 > 2 ? 0.5 : 0);

    return {
      count_all: countAll,
      count_5: count5,
      count_10: count10,
      c1,
      c2,
      c3,
      c: c1 + c2 + c3,
      is_na: false,
    };
  }

  function computeRowOpenness(row) {
    return clampOpennessMetric(row.machine_readability)
      + clampOpennessMetric(row.proprietary)
      + clampOpennessMetric(row.download_options)
      + clampOpennessMetric(row.metadata)
      + clampOpennessMetric(row.term_of_use);
  }

  function classifyRowProgress(row) {
    const seriesRaw = String(row.series ?? '').trim();
    const urlsRaw = String(row.urls ?? '').trim();
    const remarksRaw = String(row.remarks ?? '').trim();
    const isNA = seriesRaw.toUpperCase() === 'NA';
    const opennessFields = [
      row.machine_readability,
      row.proprietary,
      row.download_options,
      row.metadata,
      row.term_of_use,
    ];
    const hasAnyOpenness = opennessFields.some((v) => v !== null && v !== undefined && String(v).trim() !== '');
    const hasAnyInput = seriesRaw !== '' || urlsRaw !== '' || remarksRaw !== '' || hasAnyOpenness;

    if (!hasAnyInput) return 'empty';

    const isCoverageFilled = seriesRaw !== '';
    const isOpennessFilled = isNA || hasAnyOpenness;
    const isUrlFilled = isNA || urlsRaw !== '';
    if (isCoverageFilled && isOpennessFilled && isUrlFilled) return 'completed';

    return 'in_progress';
  }

  function recomputeLocalScores() {
    const detailRows = Array.isArray(pageState.detail) ? pageState.detail : [];
    const referenceYear = Number(pageState.period?.year) || new Date().getFullYear();

    detailRows.forEach((row) => {
      applyAseanstatsOnlyDefaults(row);
      applySeriesNaToOpenness(row);
      const cov = computeCoverageFromSeries(row.series, referenceYear);
      row.count_all = cov.count_all;
      row.count_5 = cov.count_5;
      row.count_10 = cov.count_10;
      row.c1 = cov.c1;
      row.c2 = cov.c2;
      row.c3 = cov.c3;
      row.c = cov.c;
      row.o = cov.is_na ? 0 : computeRowOpenness(row);
    });

    const bySection = new Map();
    detailRows.forEach((row) => {
      const key = String(row.section_id ?? '');
      if (!key) return;
      if (!bySection.has(key)) bySection.set(key, []);
      bySection.get(key).push(row);
    });

    const summaries = [];
    bySection.forEach((sectionRows, sectionId) => {
      const eligible = sectionRows.filter((row) => String(row.series ?? '').trim().toUpperCase() !== 'NA');
      const coverageMax = eligible.length * 3;
      const opennessMax = eligible.length * 5;
      let coverageActual = 0;
      let opennessActual = 0;
      let completed = 0;
      let inProgress = 0;

      sectionRows.forEach((row) => {
        const cov = computeCoverageFromSeries(row.series, referenceYear);
        const progress = classifyRowProgress(row);
        if (progress === 'completed') completed += 1;
        if (progress === 'in_progress') inProgress += 1;

        if (!cov.is_na) {
          coverageActual += Number(cov.c || 0);
          opennessActual += computeRowOpenness(row);
        }
      });

      const coverageSubRatio = coverageMax > 0 ? (coverageActual / coverageMax) : 0;
      const opennessSubRatio = opennessMax > 0 ? (opennessActual / opennessMax) : 0;
      const overallRatio = (0.5 * coverageSubRatio) + (0.5 * opennessSubRatio);
      const progressPct = sectionRows.length > 0
        ? (((0.5 * inProgress) + completed) / sectionRows.length) * 100
        : 0;

      summaries.push({
        section_id: Number(sectionId),
        section: {
          title: sectionLabel(sectionRows[0]),
        },
        total_rows: sectionRows.length,
        in_progress_rows: inProgress,
        completed_rows: completed,
        progress: Math.round(progressPct * 100) / 100,
        coverage_max_score: coverageMax,
        coverage_actual_score: coverageActual,
        coverage_sub_score_ratio: Math.round(coverageSubRatio * 1000000) / 1000000,
        opennes_max_score: opennessMax,
        opennes_actual_score: opennessActual,
        opennes_sub_score_ratio: Math.round(opennessSubRatio * 1000000) / 1000000,
        overall_score_ratio: Math.round(overallRatio * 1000000) / 1000000,
      });
    });

    const lockedBySection = pageState.summaryLocked || {};
    const mergedSummaries = [];
    const visibleKeys = new Set();

    summaries.forEach((s) => {
      const key = String(s.section_id);
      visibleKeys.add(key);
      const locked = lockedBySection[key] || {};
      const coverageMax = Number(s.coverage_max_score || 0) + Number(locked.coverage_max_score || 0);
      const coverageActual = Number(s.coverage_actual_score || 0) + Number(locked.coverage_actual_score || 0);
      const opennessMax = Number(s.opennes_max_score || 0) + Number(locked.opennes_max_score || 0);
      const opennessActual = Number(s.opennes_actual_score || 0) + Number(locked.opennes_actual_score || 0);
      const coverageSubRatio = coverageMax > 0 ? (coverageActual / coverageMax) : 0;
      const opennessSubRatio = opennessMax > 0 ? (opennessActual / opennessMax) : 0;
      const overallRatio = (0.5 * coverageSubRatio) + (0.5 * opennessSubRatio);

      mergedSummaries.push({
        ...s,
        section: {
          title: s.section?.title || locked.section_title || `Section ${key}`,
        },
        coverage_max_score: coverageMax,
        coverage_actual_score: coverageActual,
        coverage_sub_score_ratio: Math.round(coverageSubRatio * 1000000) / 1000000,
        opennes_max_score: opennessMax,
        opennes_actual_score: opennessActual,
        opennes_sub_score_ratio: Math.round(opennessSubRatio * 1000000) / 1000000,
        overall_score_ratio: Math.round(overallRatio * 1000000) / 1000000,
      });
    });

    Object.entries(lockedBySection).forEach(([key, locked]) => {
      if (visibleKeys.has(key)) return;

      const coverageMax = Number(locked.coverage_max_score || 0);
      const coverageActual = Number(locked.coverage_actual_score || 0);
      const opennessMax = Number(locked.opennes_max_score || 0);
      const opennessActual = Number(locked.opennes_actual_score || 0);
      const coverageSubRatio = coverageMax > 0 ? (coverageActual / coverageMax) : 0;
      const opennessSubRatio = opennessMax > 0 ? (opennessActual / opennessMax) : 0;
      const overallRatio = (0.5 * coverageSubRatio) + (0.5 * opennessSubRatio);

      mergedSummaries.push({
        section_id: Number(key),
        section: { title: locked.section_title || `Section ${key}` },
        total_rows: 0,
        in_progress_rows: 0,
        completed_rows: 0,
        progress: Number(locked.progress || 0),
        coverage_max_score: coverageMax,
        coverage_actual_score: coverageActual,
        coverage_sub_score_ratio: Math.round(coverageSubRatio * 1000000) / 1000000,
        opennes_max_score: opennessMax,
        opennes_actual_score: opennessActual,
        opennes_sub_score_ratio: Math.round(opennessSubRatio * 1000000) / 1000000,
        overall_score_ratio: Math.round(overallRatio * 1000000) / 1000000,
      });
    });

    mergedSummaries.sort((a, b) => a.section_id - b.section_id);
    pageState.summary = mergedSummaries;

    let weightedCoverage = 0;
    let weightedOpenness = 0;
    if (mergedSummaries.length > 0) {
      mergedSummaries.forEach((s) => {
        weightedCoverage += s.coverage_sub_score_ratio;
        weightedOpenness += s.opennes_sub_score_ratio;
      });
      weightedCoverage = weightedCoverage / mergedSummaries.length;
      weightedOpenness = weightedOpenness / mergedSummaries.length;
    }
    pageState.weightedScore = {
      coverage_sub_score_ratio: Math.round(weightedCoverage * 1000000) / 1000000,
      opennes_sub_score_ratio: Math.round(weightedOpenness * 1000000) / 1000000,
      overall_score_ratio: Math.round(((0.5 * weightedCoverage) + (0.5 * weightedOpenness)) * 1000000) / 1000000,
    };
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

    const years = new Set();

    for (const token of tokens) {
      const rangeMatch = token.match(/^(\d{4})\s*-\s*(\d{4})$/);
      if (rangeMatch) {
        let start = Number(rangeMatch[1]);
        let end = Number(rangeMatch[2]);
        if (start > end) {
          [start, end] = [end, start];
        }
        for (let year = start; year <= end; year += 1) {
          years.add(year);
        }
        continue;
      }

      if (/^\d{4}$/.test(token)) {
        years.add(Number(token));
        continue;
      }

      return original;
    }

    return [...years].sort((a, b) => a - b).join(',');
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
    return classifyRowProgress(row) !== 'completed';
  }

  function navigatorRowStatus(row) {
    const progress = classifyRowProgress(row);
    if (progress === 'completed') return 'complete';
    if (progress === 'in_progress') return 'partial';
    return 'empty';
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
    const uploadBtn = document.getElementById('btnUploadMenu');
    const uploadWrap = uploadBtn?.closest('.dropdown');
    const exportBtn = document.getElementById('btnExportForm');
    const saveBtn = document.getElementById('btnSaveForm');
    const submitBtn = document.getElementById('btnSubmitForm');
    const canExport = pageState.isAseanstatsStaff;

    if (!pageState.period || !pageState.assessmentCountry) {
      meta.textContent = 'Assessment information unavailable.';
      hint.style.display = 'none';
      if (uploadWrap) uploadWrap.style.display = 'none';
      uploadBtn.disabled = true;
      exportBtn.style.display = canExport ? '' : 'none';
      exportBtn.disabled = true;
      saveBtn.style.display = 'none';
      submitBtn.style.display = 'none';
      saveBtn.disabled = true;
      submitBtn.disabled = true;
      return;
    }

    const periodOpen = boolFlag(pageState.period.active);
    const isSubmitted = boolFlag(pageState.assessmentCountry.is_submitted);
    pageState.editable = periodOpen && !isSubmitted;

    const modeText = periodOpen ? 'Open' : 'Completed';
    const periodTitle = String(pageState.period.title || pageState.period.description || '-').trim() || '-';
    const countryName = String(pageState.assessmentCountry.country_name || pageState.assessmentCountry.country_code || '-').trim() || '-';
    meta.textContent = `${periodTitle} | Reference Year: ${pageState.period.year ?? '-'} | Status: ${modeText} | ${countryName}`;

    if (periodOpen) {
      hint.className = 'period-hint mb-3';
      hint.textContent = isSubmitted
        ? 'Assessment already submitted. Form is now read-only. Contact ASEANstats if revisions are required.'
        : 'Period is open. Fill data, click Save, then Submit when finalized.';
      hint.style.display = 'block';
      if (uploadWrap) uploadWrap.style.display = isSubmitted ? 'none' : '';
      uploadBtn.disabled = isSubmitted;
      exportBtn.style.display = canExport ? '' : 'none';
      exportBtn.disabled = !canExport;
      saveBtn.style.display = isSubmitted ? 'none' : '';
      submitBtn.style.display = isSubmitted ? 'none' : '';
      saveBtn.disabled = isSubmitted;
      submitBtn.disabled = isSubmitted;
      submitBtn.textContent = isSubmitted ? 'Submitted' : 'Submit';
    } else {
      hint.className = 'period-hint mb-3';
      hint.textContent = 'Period is completed. Assessment is read-only.';
      hint.style.display = 'block';
      if (uploadWrap) uploadWrap.style.display = 'none';
      uploadBtn.disabled = true;
      exportBtn.style.display = canExport ? '' : 'none';
      exportBtn.disabled = !canExport;
      saveBtn.style.display = 'none';
      submitBtn.style.display = 'none';
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
      const optValue = (typeof opt === 'object') ? opt.value : opt;
      const optLabel = (typeof opt === 'object') ? opt.label : opt;
      const selected = hasValue && Number(value) === Number(optValue) ? 'selected' : '';
      return `<option value="${optValue}" ${selected}>${optLabel}</option>`;
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
      const isAseanstatsLocked = isAseanstatsOnlyLockedRow(r);
      const isSeriesNa = String(r.series ?? '').trim().toUpperCase() === 'NA';
      const cLabel = isSeriesNa ? '0' : ((r.c === null || r.c === undefined) ? 'N/A' : fmtNumber(r.c));
      const oLabel = isSeriesNa ? '0' : ((r.o === null || r.o === undefined) ? 'N/A' : fmtNumber(r.o));
      const c1Label = isSeriesNa ? 'NA' : fmtNumber(r.c1);
      const c2Label = isSeriesNa ? 'NA' : fmtNumber(r.c2);
      const c3Label = isSeriesNa ? 'NA' : fmtNumber(r.c3);
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
            <div class="assessment-score-stack">
            <div class="assessment-field-wrap">
              <label class="form-label form-label-sm mb-1">Series ${helpIconText(fieldTooltips.series, 'Series help')}</label>
              <textarea class="form-control form-control-sm assessment-input"
                data-row-id="${r.row_id}" data-field="series" rows="3" ${disabled || isAseanstatsLocked ? 'disabled' : ''}>${esc(r.series || '')}</textarea>
            </div>
            <div class="assessment-metric-rows mt-2">
              <div class="assessment-metric-row">
                <div class="assessment-metric" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="assessment-help-tooltip" title="All: Total number of valid years entered in Series."><span>All</span><strong>${fmtNumber(r.count_all, 0)}</strong></div>
                <div class="assessment-metric" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="assessment-help-tooltip" title="5: Number of entered years that fall within the last 5 years based on reference year."><span>5</span><strong>${fmtNumber(r.count_5, 0)}</strong></div>
                <div class="assessment-metric" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="assessment-help-tooltip" title="10: Number of entered years that fall within the last 10 years based on reference year."><span>10</span><strong>${fmtNumber(r.count_10, 0)}</strong></div>
              </div>
              <div class="assessment-metric-row">
                <div class="assessment-metric"><span>C1</span><strong>${c1Label}</strong></div>
                <div class="assessment-metric"><span>C2</span><strong>${c2Label}</strong></div>
                <div class="assessment-metric"><span>C3</span><strong>${c3Label}</strong></div>
              </div>
            </div>
            <div class="assessment-score-footer">
              <div class="assessment-metric assessment-metric-final"><span>Coverage Sub Score</span><strong>${cLabel}</strong></div>
            </div>
            </div>
          </td>
          <td>
            <div class="assessment-score-stack">
            <div class="assessment-openness-grid">
              <div class="assessment-openness-item">
                <label class="form-label form-label-sm mb-1">Machine Readability ${helpIconText(fieldTooltips.machine_readability, 'Machine Readability help')}</label>
                ${opennessSelect('machine_readability', r.machine_readability, r.row_id, [0, 1, { value: -1, label: 'NA' }], disabled || isAseanstatsLocked || isSeriesNa)}
              </div>
              <div class="assessment-openness-item">
                <label class="form-label form-label-sm mb-1">Proprietary ${helpIconText(fieldTooltips.proprietary, 'Proprietary help')}</label>
                ${opennessSelect('proprietary', r.proprietary, r.row_id, [0, 1, { value: -1, label: 'NA' }], disabled || isAseanstatsLocked || isSeriesNa)}
              </div>
              <div class="assessment-openness-item">
                <label class="form-label form-label-sm mb-1">Download Options ${helpIconText(fieldTooltips.download_options, 'Download Options help')}</label>
                ${opennessSelect('download_options', r.download_options, r.row_id, [0, 0.5, 1, { value: -1, label: 'NA' }], disabled || isAseanstatsLocked || isSeriesNa)}
              </div>
              <div class="assessment-openness-item">
                <label class="form-label form-label-sm mb-1">Metadata ${helpIconText(fieldTooltips.metadata, 'Metadata help')}</label>
                ${opennessSelect('metadata', r.metadata, r.row_id, [0, 0.5, 1, { value: -1, label: 'NA' }], disabled || isAseanstatsLocked || isSeriesNa)}
              </div>
              <div class="assessment-openness-item">
                <label class="form-label form-label-sm mb-1">Term of Use ${helpIconText(fieldTooltips.term_of_use, 'Term of Use help')}</label>
                ${opennessSelect('term_of_use', r.term_of_use, r.row_id, [0, 0.5, 1, { value: -1, label: 'NA' }], disabled || isAseanstatsLocked || isSeriesNa)}
              </div>
            </div>
            <div class="assessment-score-footer">
              <div class="assessment-metric assessment-metric-final"><span>Opennes Sub Score</span><strong>${oLabel}</strong></div>
            </div>
            </div>
          </td>
          <td>
            <div class="assessment-field-wrap mb-2">
              <label class="form-label form-label-sm mb-1">Relevant URL ${helpIconText(fieldTooltips.urls, 'Relevant URL help')}</label>
              <textarea class="form-control form-control-sm assessment-input"
                data-row-id="${r.row_id}" data-field="urls" rows="3" ${disabled || isAseanstatsLocked ? 'disabled' : ''}>${esc(r.urls || '')}</textarea>
            </div>
            <div class="assessment-field-wrap">
              <label class="form-label form-label-sm mb-1">Remark ${helpIconText(fieldTooltips.remarks, 'Remark help')}</label>
              <textarea class="form-control form-control-sm assessment-input"
                data-row-id="${r.row_id}" data-field="remarks" rows="3" ${disabled || isAseanstatsLocked ? 'disabled' : ''}>${esc(r.remarks || '')}</textarea>
            </div>
          </td>
        </tr>
      `;
    }).join('');

    syncSubScoreAlignment(tbody);
    initTooltips(tbody);
  }

  function syncSubScoreAlignment(scopeEl) {
    const root = scopeEl || document;
    const tableRows = root.querySelectorAll('tr[data-row-id]');
    tableRows.forEach((row) => {
      const stacks = row.querySelectorAll('.assessment-score-stack');
      if (!stacks || stacks.length < 2) return;
      stacks.forEach((stack) => {
        stack.style.minHeight = '';
      });
      const heights = Array.from(stacks).map((stack) => stack.offsetHeight || 0);
      const maxHeight = Math.max(...heights, 0);
      if (maxHeight <= 0) return;
      stacks.forEach((stack) => {
        stack.style.minHeight = `${maxHeight}px`;
      });
    });
  }

  function normalizeCode(raw) {
    return String(raw ?? '').trim().toUpperCase();
  }

  function exportNumeric(value, digits = null) {
    if (value === null || value === undefined || value === '') return null;
    const num = parseMetric(value, null);
    if (num === null || num === undefined || Number.isNaN(num)) return null;
    if (digits === null) return num;
    return Number(num.toFixed(digits));
  }

  function exportOpennessValue(value) {
    const num = parseMetric(value, null);
    if (num === null || num === undefined) return null;
    if (num === -1) return 'NA';
    return exportNumeric(num, 2);
  }

  function buildExportRows() {
    const rows = getFilteredDetailRows();
    return rows.map((r) => {
      const isSeriesNa = String(r.series ?? '').trim().toUpperCase() === 'NA';
      const c1Label = isSeriesNa ? 'NA' : exportNumeric(r.c1, 2);
      const c2Label = isSeriesNa ? 'NA' : exportNumeric(r.c2, 2);
      const c3Label = isSeriesNa ? 'NA' : exportNumeric(r.c3, 2);
      const coverageSubScore = isSeriesNa ? 0 : exportNumeric(r.c, 2);
      const opennessSubScore = isSeriesNa ? 0 : exportNumeric(r.o, 2);

      return [
        sectionLabel(r),
        categoryLabel(r),
        r.indicator || r.indicator_title || '-',
        r.aggregation || r.aggregation_title || '-',
        String(r.series ?? ''),
        exportNumeric(r.count_all, 0),
        exportNumeric(r.count_5, 0),
        exportNumeric(r.count_10, 0),
        c1Label,
        c2Label,
        c3Label,
        coverageSubScore,
        exportOpennessValue(r.machine_readability),
        exportOpennessValue(r.proprietary),
        exportOpennessValue(r.download_options),
        exportOpennessValue(r.metadata),
        exportOpennessValue(r.term_of_use),
        opennessSubScore,
        String(r.urls ?? ''),
        String(r.remarks ?? ''),
      ];
    });
  }

  function exportFormToExcel() {
    hideError();
    const rows = buildExportRows();
    if (!rows.length) {
      odToast('No rows available to export for current filters.');
      return;
    }
    if (!window.XLSX) {
      showError('Excel exporter not available.');
      return;
    }

    const headers = [
      'Section',
      'Category',
      'Indicator',
      'Disaggregation',
      'Series',
      'Count All',
      'Count5',
      'Count last 10 years',
      'C1',
      'C2',
      'C3',
      'Coverage Sub Score',
      'Machine Readibility',
      'Non-Proprietary',
      'Download Options',
      'Metadata Availability',
      'Term of User',
      'Opennes Sub Score',
      'URL',
      'Remark',
    ];

    const worksheet = XLSX.utils.aoa_to_sheet([headers, ...rows]);
    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, 'Assessment');

    const periodYear = pageState.period?.year ? String(pageState.period.year) : 'period';
    const countryCode = pageState.assessmentCountry?.country_code
      ? String(pageState.assessmentCountry.country_code)
      : 'country';
    const now = new Date();
    const stamp = [
      String(now.getFullYear()),
      String(now.getMonth() + 1).padStart(2, '0'),
      String(now.getDate()).padStart(2, '0'),
      String(now.getHours()).padStart(2, '0'),
      String(now.getMinutes()).padStart(2, '0'),
    ].join('');
    const fileName = `assessment_export_${periodYear}_${countryCode}_${stamp}.xlsx`;

    XLSX.writeFile(workbook, fileName);
  }

  function cellText(raw) {
    if (raw === null || raw === undefined) return '';
    return String(raw).trim();
  }

  function parseTemplateMetric(raw, allowedValues) {
    const text = String(raw ?? '').trim();
    if (!text) return null;
    if (text.toUpperCase() === 'NA') return -1;
    const val = Number(text);
    if (Number.isNaN(val)) return null;
    return allowedValues.includes(val) ? val : null;
  }

  function setUploadTemplateFile(file) {
    uploadTemplateFile = file || null;
    const label = document.getElementById('uploadTemplateFileName');
    if (!label) return;
    label.textContent = uploadTemplateFile ? uploadTemplateFile.name : 'No file selected.';
  }

  function showUploadResultDialog(result) {
    const summaryEl = document.getElementById('uploadResultSummary');
    const unmatchedWrap = document.getElementById('uploadUnmatchedWrap');
    const tbody = document.getElementById('uploadUnmatchedRows');
    const isSuccess = result?.success !== false;
    const uploaded = Number(result?.uploaded ?? 0);
    const matched = Number(result?.matched ?? 0);
    const unmatched = Array.isArray(result?.unmatched) ? result.unmatched : [];
    const truncatedUrls = Number(result?.truncated?.urls ?? 0);
    const truncatedRemarks = Number(result?.truncated?.remarks ?? 0);
    const skipped = Math.max(0, uploaded - matched);

    if (!isSuccess) {
      summaryEl.textContent = `Template processing failed: ${result?.error || 'Unexpected runtime error.'}`;
      if (unmatchedWrap) unmatchedWrap.style.display = 'none';
      tbody.innerHTML = '';
      uploadResultModal.show();
      return;
    }

    const truncateNotes = [];
    if (truncatedUrls > 0) truncateNotes.push(`${truncatedUrls} URL value(s) were truncated to ${TEMPLATE_TEXT_MAX_LENGTH} characters`);
    if (truncatedRemarks > 0) truncateNotes.push(`${truncatedRemarks} remark value(s) were truncated to ${TEMPLATE_TEXT_MAX_LENGTH} characters`);
    const truncateText = truncateNotes.length ? ` ${truncateNotes.join('; ')}.` : '';
    const successPrefix = pageState.isAseanstatsStaff
      ? `Template "${result?.sheetName || 'Input'}" processed successfully. Uploaded: ${uploaded}, matched: ${matched}, unmatched: ${skipped}.`
      : `Template "${result?.sheetName || 'Input'}" processed successfully.`;
    summaryEl.textContent = `${successPrefix} Data has been populated on screen. Click Save to persist changes to the database.${truncateText}`;

    if (!pageState.isAseanstatsStaff) {
      if (unmatchedWrap) unmatchedWrap.style.display = 'none';
      tbody.innerHTML = '';
      uploadResultModal.show();
      return;
    }

    if (unmatchedWrap) unmatchedWrap.style.display = '';
    if (!unmatched.length) {
      tbody.innerHTML = '<tr><td colspan="3" class="text-muted">No unmatched rows.</td></tr>';
    } else {
      tbody.innerHTML = unmatched.map((row) => `
        <tr>
          <td>${row.source_row ? `#${esc(row.source_row)}` : '-'}</td>
          <td>${esc(row.code || '-')}</td>
          <td>${esc(row.reason || 'Unmatched')}</td>
        </tr>
      `).join('');
    }

    uploadResultModal.show();
  }

  function normalizeTemplateHeader(raw) {
    return String(raw ?? '')
      .trim()
      .toLowerCase()
      .replace(/[^a-z0-9]/g, '');
  }

  function truncateTemplateText(raw, maxLength = TEMPLATE_TEXT_MAX_LENGTH) {
    const text = cellText(raw);
    if (text.length <= maxLength) {
      return { value: text, truncated: false };
    }
    return { value: text.slice(0, maxLength), truncated: true };
  }

  async function applyTemplateRows(file) {
    if (!file) throw new Error('Please select an Excel file first.');
    if (!window.XLSX) throw new Error('Excel parser not available.');

    const buffer = await file.arrayBuffer();
    const workbook = XLSX.read(buffer, { type: 'array' });
    const sheetName = workbook.SheetNames.includes('Input') ? 'Input' : workbook.SheetNames[0];
    if (!sheetName) throw new Error('Template has no worksheet.');

    const sheet = workbook.Sheets[sheetName];
    const rows = XLSX.utils.sheet_to_json(sheet, { header: 1, defval: '' });
    if (!Array.isArray(rows) || rows.length < 1) throw new Error('Template sheet is empty.');

    // Some templates include title/blank rows before the real header row.
    const scanLimit = Math.min(rows.length, 20);
    const headerRowIndex = rows.findIndex((r, idx) => {
      if (idx >= scanLimit) return false;
      return normalizeTemplateHeader(r?.[0]) === 'code';
    });
    if (headerRowIndex < 0) {
      throw new Error('The first column header must be "Code".');
    }
    const headerRow = rows[headerRowIndex] || [];
    const firstHeader = normalizeTemplateHeader(headerRow[0]);
    if (firstHeader !== 'code') {
      throw new Error('The first column header must be "Code".');
    }

    const nextHeaderRow = rows[headerRowIndex + 1] || [];
    const requiredHeaderAliases = new Map([
      ['series', ['series', 'coverageseries']],
      ['machine_readability', ['machinereadilibility', 'machinereadibility', 'machinereadability']],
      ['proprietary', ['proprietary', 'nonproprietary']],
      ['download_options', ['downloadoptions']],
      ['metadata', ['metadata', 'metadataavailability']],
      ['term_of_use', ['termofuse', 'termofuser']],
      ['url', ['url', 'urls', 'relevanturl', 'relevanturls']],
      ['remark', ['remark', 'remarks', 'note', 'notes']],
    ]);

    const requiredLookup = new Set();
    requiredHeaderAliases.forEach((aliases) => aliases.forEach((a) => requiredLookup.add(a)));
    const nextRowHasHeaderTokens = nextHeaderRow.some((col) => requiredLookup.has(normalizeTemplateHeader(col)));

    const headerIndexByKey = new Map();
    function registerHeaderRow(row) {
      row.forEach((col, idx) => {
        const key = normalizeTemplateHeader(col);
        if (!key || headerIndexByKey.has(key)) return;
        headerIndexByKey.set(key, idx);
      });
    }
    registerHeaderRow(headerRow);
    if (nextRowHasHeaderTokens) {
      registerHeaderRow(nextHeaderRow);
    }

    function findHeaderIndex(keys) {
      for (const key of keys) {
        if (headerIndexByKey.has(key)) return Number(headerIndexByKey.get(key));
      }
      return -1;
    }

    const indexOrFallback = (aliases, fallback) => {
      const idx = findHeaderIndex(aliases);
      return idx >= 0 ? idx : fallback;
    };

    // Fallback indexes follow the known template layout.
    const seriesIdx = indexOrFallback(requiredHeaderAliases.get('series'), 5);
    const machineReadabilityIdx = indexOrFallback(requiredHeaderAliases.get('machine_readability'), 13);
    const proprietaryIdx = indexOrFallback(requiredHeaderAliases.get('proprietary'), 14);
    const downloadOptionsIdx = indexOrFallback(requiredHeaderAliases.get('download_options'), 15);
    const metadataIdx = indexOrFallback(requiredHeaderAliases.get('metadata'), 16);
    const termOfUseIdx = indexOrFallback(requiredHeaderAliases.get('term_of_use'), 17);
    const urlIdx = indexOrFallback(requiredHeaderAliases.get('url'), 19);
    const remarkIdx = indexOrFallback(requiredHeaderAliases.get('remark'), 20);

    const parsedByCode = new Map();
    let truncatedUrls = 0;
    let truncatedRemarks = 0;

    const dataStartIndex = headerRowIndex + (nextRowHasHeaderTokens ? 2 : 1);
    for (let i = dataStartIndex; i < rows.length; i += 1) {
      const xRow = rows[i] || [];
      const code = normalizeCode(xRow[0]);
      if (!code || !code.includes('.')) continue;

      const series = normalizeSeriesYears(seriesIdx >= 0 ? cellText(xRow[seriesIdx]) : '');
      const urlsResult = truncateTemplateText(urlIdx >= 0 ? xRow[urlIdx] : '');
      const remarksResult = truncateTemplateText(remarkIdx >= 0 ? xRow[remarkIdx] : '');
      if (urlsResult.truncated) truncatedUrls += 1;
      if (remarksResult.truncated) truncatedRemarks += 1;

      const parsed = {
        code,
        source_row: i + 1,
        series,
        machine_readability: machineReadabilityIdx >= 0 ? parseTemplateMetric(xRow[machineReadabilityIdx], [-1, 0, 1]) : null,
        proprietary: proprietaryIdx >= 0 ? parseTemplateMetric(xRow[proprietaryIdx], [-1, 0, 1]) : null,
        download_options: downloadOptionsIdx >= 0 ? parseTemplateMetric(xRow[downloadOptionsIdx], [-1, 0, 0.5, 1]) : null,
        metadata: metadataIdx >= 0 ? parseTemplateMetric(xRow[metadataIdx], [-1, 0, 0.5, 1]) : null,
        term_of_use: termOfUseIdx >= 0 ? parseTemplateMetric(xRow[termOfUseIdx], [-1, 0, 0.5, 1]) : null,
        urls: urlsResult.value,
        remarks: remarksResult.value,
      };

      if (String(series).trim().toUpperCase() === 'NA') {
        parsed.machine_readability = -1;
        parsed.proprietary = -1;
        parsed.download_options = -1;
        parsed.metadata = -1;
        parsed.term_of_use = -1;
      }

      parsedByCode.set(code, parsed);
    }
    return {
      sheetName,
      parsedRows: [...parsedByCode.values()],
      truncated: {
        urls: truncatedUrls,
        remarks: truncatedRemarks,
      },
    };
  }

  function applyParsedRowsToScreen(parsedRows) {
    const sourceRows = Array.isArray(pageState.detail) ? pageState.detail : [];
    const rowByCode = new Map();
    sourceRows.forEach((row) => {
      const key = normalizeCode(row.prefix);
      if (!key || rowByCode.has(key)) return;
      rowByCode.set(key, row);
    });

    let matched = 0;
    const unmatched = [];

    (parsedRows || []).forEach((parsed) => {
      const code = normalizeCode(parsed?.code);
      const row = rowByCode.get(code);
      if (!row) {
        unmatched.push({
          code,
          source_row: parsed?.source_row || null,
          reason: 'Code not mapped to current form rows.',
        });
        return;
      }

      matched += 1;
      if (isAseanstatsOnlyLockedRow(row)) {
        applyAseanstatsOnlyDefaults(row);
        row.urls = '';
        row.remarks = '';
        return;
      }

      row.series = String(parsed.series ?? '').trim();
      row.machine_readability = parsed.machine_readability ?? null;
      row.proprietary = parsed.proprietary ?? null;
      row.download_options = parsed.download_options ?? null;
      row.metadata = parsed.metadata ?? null;
      row.term_of_use = parsed.term_of_use ?? null;
      row.urls = String(parsed.urls ?? '');
      row.remarks = String(parsed.remarks ?? '');
      applySeriesNaToOpenness(row);
    });

    recomputeLocalScores();
    renderSummaryRows();
    renderFilterControls();
    renderDetailRows();

    return { matched, unmatched };
  }

  async function openUploadTemplateDialog() {
    hideError();
    if (!pageState.editable) {
      odToast('Period is completed. Upload is disabled.');
      return;
    }
    const confirmed = await odConfirm(
      'Uploading the template will overwrite the data currently displayed on the screen. Are you sure you want to continue?',
      'Confirm Upload Template'
    );
    if (!confirmed) return;

    setUploadTemplateFile(null);
    const uploadInput = document.getElementById('uploadTemplateInput');
    if (uploadInput) uploadInput.value = '';
    uploadTemplateModal.show();
  }

  async function processUploadTemplate() {
    hideError();
    if (!pageState.editable) {
      odToast('Period is completed. Upload is disabled.');
      return;
    }

    const btn = document.getElementById('btnUploadTemplateProcess');
    btn.disabled = true;

    try {
      const result = await applyTemplateRows(uploadTemplateFile);
      result.success = true;
      result.uploaded = Array.isArray(result.parsedRows) ? result.parsedRows.length : 0;
      const applied = applyParsedRowsToScreen(result.parsedRows);
      result.matched = Number(applied?.matched || 0);
      result.unmatched = Array.isArray(applied?.unmatched) ? applied.unmatched : [];

      uploadTemplateModal.hide();
      showUploadResultDialog(result);
    } catch (err) {
      uploadTemplateModal.hide();
      showUploadResultDialog({
        success: false,
        error: err.message || 'Unexpected runtime error.',
        uploaded: 0,
        matched: 0,
        unmatched: [],
      });
    } finally {
      btn.disabled = false;
    }
  }

  function setRowField(rowId, field, value) {
    const row = pageState.detail.find((r) => String(r.row_id) === String(rowId));
    if (!row) return;
    if (isAseanstatsOnlyLockedRow(row)) {
      applyAseanstatsOnlyDefaults(row);
      return;
    }
    const opennessFields = ['machine_readability', 'proprietary', 'download_options', 'metadata', 'term_of_use'];
    if (opennessFields.includes(field) && String(row.series ?? '').trim().toUpperCase() === 'NA') {
      row[field] = -1;
      return;
    }
    row[field] = value;
    if (field === 'series') {
      applySeriesNaToOpenness(row);
    }
  }

  function bindEntryInputSync() {
    const tbody = document.getElementById('detailRows');

    function commitSeriesValue(target) {
      const normalized = normalizeSeriesYears(target.value);
      if (target.value !== normalized) {
        target.value = normalized;
      }
      setRowField(target.dataset.rowId, target.dataset.field, target.value);
      recomputeLocalScores();
      renderSummaryRows();
      renderDetailRows();
    }

    tbody.addEventListener('input', (event) => {
      const target = event.target;
      if (!target.classList.contains('assessment-input')) return;
      setRowField(target.dataset.rowId, target.dataset.field, target.value);
      recomputeLocalScores();
      renderSummaryRows();
    });

    tbody.addEventListener('keydown', (event) => {
      const target = event.target;
      if (!target.classList.contains('assessment-input')) return;
      if (target.dataset.field !== 'series') return;
      if (event.key !== 'Tab' || event.shiftKey) return;

      event.preventDefault();
      const rowId = target.dataset.rowId;
      commitSeriesValue(target);

      setTimeout(() => {
        const next = document.querySelector(`.assessment-input[data-row-id="${rowId}"][data-field="machine_readability"]`);
        if (next) next.focus();
      }, 0);
    });

    tbody.addEventListener('change', (event) => {
      const target = event.target;
      if (!target.classList.contains('assessment-input')) return;

      if (target.dataset.field === 'series') {
        commitSeriesValue(target);
        return;
      }

      setRowField(target.dataset.rowId, target.dataset.field, target.value);
      recomputeLocalScores();
      renderSummaryRows();
      renderDetailRows();
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

  function renderLogRows() {
    const tbody = document.getElementById('logRows');
    const logs = Array.isArray(pageState.logs) ? pageState.logs : [];
    if (!logs.length) {
      tbody.innerHTML = '<tr><td colspan="3" class="text-muted">No logs found.</td></tr>';
      return;
    }

    tbody.innerHTML = logs.map((row) => {
      const actorName = String(row.actor_name || '-').trim() || '-';
      const actorEmail = String(row.actor_email || '').trim();
      const actorLabel = `${actorName} (${actorEmail || '-'})`;
      return `
        <tr>
          <td>${esc(fmtDateTime(row.event_date))}</td>
          <td>${esc(actorLabel)}</td>
          <td>${esc(String(row.action_text || '-'))}</td>
        </tr>
      `;
    }).join('');
  }

  async function loadLogs() {
    const logBody = document.getElementById('logRows');
    logBody.innerHTML = '<tr><td colspan="3" class="text-muted">Loading logs...</td></tr>';
    if (!pageState.periodId) {
      logBody.innerHTML = '<tr><td colspan="3" class="text-danger">Invalid period.</td></tr>';
      return;
    }

    try {
      let url = `/api/trx/form/logs?periodid=${encodeURIComponent(pageState.periodId)}`;
      if (pageState.countryCode) {
        url += `&country_code=${encodeURIComponent(pageState.countryCode)}`;
      }
      const response = await odFetch(url);
      pageState.logs = Array.isArray(response?.data?.logs) ? response.data.logs : [];
      renderLogRows();
    } catch (err) {
      logBody.innerHTML = `<tr><td colspan="3" class="text-danger">${esc(err.message || 'Failed to load logs.')}</td></tr>`;
    }
  }

  async function loadForm() {
    hideError();
    const detailBody = document.getElementById('detailRows');
    const summaryBody = document.getElementById('summaryRows');
    const logBody = document.getElementById('logRows');
    detailBody.innerHTML = '<tr><td colspan="4" class="text-muted">Loading rows...</td></tr>';
    summaryBody.innerHTML = '<tr><td colspan="9" class="text-muted">Loading summary...</td></tr>';
    logBody.innerHTML = '<tr><td colspan="3" class="text-muted">Loading logs...</td></tr>';

    if (!pageState.periodId) {
      showError('Missing query parameter: periodid');
      detailBody.innerHTML = '<tr><td colspan="4" class="text-danger">Invalid period.</td></tr>';
      summaryBody.innerHTML = '<tr><td colspan="9" class="text-danger">Invalid period.</td></tr>';
      logBody.innerHTML = '<tr><td colspan="3" class="text-danger">Invalid period.</td></tr>';
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
      pageState.isAseanstatsStaff = boolFlag(data.viewer?.is_aseanstats_staff);
      pageState.detailMeta = data.detail_meta || null;
      pageState.detail = (data.detail || []).map((row, index) => ({
        ...row,
        _row_no: index + 1,
      }));
      pageState.summary = [];
      pageState.summaryLocked = {};
      pageState.weightedScore = null;

      const serverSummaries = Array.isArray(data.summary) ? data.summary : [];
      recomputeLocalScores();

      const visibleBySection = new Map((pageState.summary || []).map((s) => [String(s.section_id), s]));
      const lockedBySection = {};
      serverSummaries.forEach((s) => {
        const key = String(s.section_id ?? '');
        if (!key) return;
        const visible = visibleBySection.get(key);
        const coverageMax = Math.max(0, Number(s.coverage_max_score || 0) - Number(visible?.coverage_max_score || 0));
        const coverageActual = Math.max(0, Number(s.coverage_actual_score || 0) - Number(visible?.coverage_actual_score || 0));
        const opennessMax = Math.max(0, Number(s.opennes_max_score || 0) - Number(visible?.opennes_max_score || 0));
        const opennessActual = Math.max(0, Number(s.opennes_actual_score || 0) - Number(visible?.opennes_actual_score || 0));
        lockedBySection[key] = {
          section_title: s.section?.title || s.section_title || null,
          progress: Number(s.progress || 0),
          coverage_max_score: coverageMax,
          coverage_actual_score: coverageActual,
          opennes_max_score: opennessMax,
          opennes_actual_score: opennessActual,
        };
      });
      pageState.summaryLocked = lockedBySection;
      recomputeLocalScores();

      renderMeta();
      renderSummaryRows();
      renderFilterControls();
      renderDetailRows();
      await loadLogs();
      maybeAutoOpenHelpWizard();
    } catch (err) {
      renderMeta();
      summaryBody.innerHTML = `<tr><td colspan="9" class="text-danger">${esc(err.message || 'Failed to load summary.')}</td></tr>`;
      detailBody.innerHTML = `<tr><td colspan="4" class="text-danger">${esc(err.message || 'Failed to load details.')}</td></tr>`;
      logBody.innerHTML = `<tr><td colspan="3" class="text-danger">${esc(err.message || 'Failed to load logs.')}</td></tr>`;
      showError(err.message || 'Failed to load assessment');
    }
  }

  function collectRowsPayload() {
    const sourceRows = Array.isArray(pageState.detail) ? pageState.detail : [];
    return sourceRows.map((r) => {
      const isLocked = isAseanstatsOnlyLockedRow(r);
      const series = isLocked ? 'NA' : String(r.series ?? '');
      const isSeriesNa = String(series).trim().toUpperCase() === 'NA';
      return {
        row_id: r.row_id,
        series,
        machine_readability: isSeriesNa ? -1 : parseMetric(r.machine_readability, null),
        proprietary: isSeriesNa ? -1 : parseMetric(r.proprietary, null),
        download_options: isSeriesNa ? -1 : parseMetric(r.download_options, null),
        metadata: isSeriesNa ? -1 : parseMetric(r.metadata, null),
        term_of_use: isSeriesNa ? -1 : parseMetric(r.term_of_use, null),
        urls: isLocked ? '' : String(r.urls ?? ''),
        remarks: isLocked ? '' : String(r.remarks ?? ''),
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

    const confirmed = await odConfirm(
      'Are you sure you want to submit this assessment?\n\nOnce the assessment is submitted, it will be locked and can no longer be edited. If any revisions are needed, please contact ASEANstats to request access for further changes.',
      'Submit Assessment',
      { smallSecondBlock: true }
    );
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

  function updateNavigatorVisibility() {
    const navigatorDock = document.getElementById('navigatorDock');
    const entryTab = document.getElementById('entry-tab');
    const isEntryActive = entryTab?.classList.contains('active');
    if (!navigatorDock) return;
    navigatorDock.style.display = isEntryActive ? '' : 'none';
    if (!isEntryActive) {
      navigatorDock.classList.remove('is-open');
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    const backBtn = document.getElementById('btnBackForm');
    if (backBtn) {
      backBtn.setAttribute('href', pageState.backUrl || '/dashboard');
    }

    navigatorModal = new bootstrap.Modal(document.getElementById('entryNavigatorDialog'));
    uploadTemplateModal = new bootstrap.Modal(document.getElementById('uploadTemplateDialog'));
    uploadResultModal = new bootstrap.Modal(document.getElementById('uploadResultDialog'));
    const helpWizardDialogEl = document.getElementById('helpWizardDialog');
    helpWizardModal = new bootstrap.Modal(helpWizardDialogEl, {
      backdrop: false,
      focus: false,
      keyboard: true,
    });
    initTooltips(document);
    const navigatorDock = document.getElementById('navigatorDock');
    const uploadDropzone = document.getElementById('uploadDropzone');
    const uploadInput = document.getElementById('uploadTemplateInput');

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
    document.getElementById('entry-tab').addEventListener('shown.bs.tab', updateNavigatorVisibility);
    document.getElementById('summary-tab').addEventListener('shown.bs.tab', updateNavigatorVisibility);
    document.getElementById('log-tab').addEventListener('shown.bs.tab', updateNavigatorVisibility);
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
    document.getElementById('btnUploadTemplateOpen').addEventListener('click', openUploadTemplateDialog);
    document.getElementById('btnUploadTemplateProcess').addEventListener('click', processUploadTemplate);
    document.getElementById('btnExportForm').addEventListener('click', exportFormToExcel);
    document.getElementById('btnSaveForm').addEventListener('click', saveForm);
    document.getElementById('btnSubmitForm').addEventListener('click', submitForm);
    document.getElementById('btnHelpWizard').addEventListener('click', () => {
      openHelpWizard(0);
    });
    document.getElementById('btnHelpWizardPrev').addEventListener('click', () => {
      helpWizardState.stepIndex -= 1;
      renderHelpWizardStep();
    });
    document.getElementById('btnHelpWizardNext').addEventListener('click', () => {
      const lastIndex = helpWizardSteps.length - 1;
      if (helpWizardState.stepIndex >= lastIndex) {
        helpWizardModal.hide();
        return;
      }
      helpWizardState.stepIndex += 1;
      renderHelpWizardStep();
    });
    document.getElementById('helpWizardOutline')?.addEventListener('click', (event) => {
      const item = event.target.closest('.assessment-help-outline-item[data-step-index]');
      if (!item) return;
      jumpHelpWizardStep(item.dataset.stepIndex);
    });
    helpWizardDialogEl.addEventListener('shown.bs.modal', () => {
      releaseHelpWizardBodyLock();
      helpWizardDialogEl.querySelector('.modal-dialog')?.classList.add('is-positioned');
      renderHelpWizardStep();
    });
    helpWizardDialogEl.addEventListener('hidden.bs.modal', () => {
      releaseHelpWizardBodyLock();
      clearHelpWizardHighlight();
      activateAssessmentTab('entry');
      helpWizardDialogEl.querySelector('.modal-dialog')?.classList.remove('is-positioned');
    });

    uploadDropzone.addEventListener('click', () => uploadInput.click());
    uploadInput.addEventListener('change', (event) => {
      const [file] = event.target.files || [];
      setUploadTemplateFile(file || null);
    });
    uploadDropzone.addEventListener('dragover', (event) => {
      event.preventDefault();
      uploadDropzone.classList.add('is-drag-over');
    });
    uploadDropzone.addEventListener('dragleave', () => {
      uploadDropzone.classList.remove('is-drag-over');
    });
    uploadDropzone.addEventListener('drop', (event) => {
      event.preventDefault();
      uploadDropzone.classList.remove('is-drag-over');
      const [file] = event.dataTransfer?.files || [];
      if (!file) return;
      setUploadTemplateFile(file);
      uploadInput.value = '';
    });

    updateNavigatorVisibility();
    loadForm();

    window.addEventListener('resize', () => {
      const tbody = document.getElementById('detailRows');
      if (!tbody) return;
      syncSubScoreAlignment(tbody);
      if (helpWizardDialogEl?.classList.contains('show')) {
        const currentStep = helpWizardSteps[helpWizardState.stepIndex] || null;
        const currentTarget = resolveHelpWizardTarget(currentStep);
        positionHelpWizardDialog(currentTarget);
      }
    });
  });
</script>
@endpush
