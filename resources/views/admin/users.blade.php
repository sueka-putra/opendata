@extends('layouts.opendata')
@section('content')
<div class="period-theme-wrap">
  <div class="period-theme-shell">
    <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-3">
      <div>
        <h1 class="h5 period-title">Users</h1>
        <div class="period-subtitle">Manage OpenData WGDSA contacts.</div>
      </div>
      <div class="d-flex gap-2">
        <button class="btn od-btn-outline" id="btnGenerate">Generate</button>
        <button class="btn od-btn-primary" id="btnAdd">Add</button>
      </div>
    </div>

    <div class="period-table-card">
      <div class="period-table-toolbar users-filter-row">
        <input type="text" tabindex="-1" autocomplete="username" class="users-autofill-trap" aria-hidden="true">
        <input type="password" tabindex="-1" autocomplete="new-password" class="users-autofill-trap" aria-hidden="true">
        <label class="users-filter-label" for="countryFilter">Country</label>
        <div class="users-country-wrap">
          <select class="form-select form-select-sm users-country-select" id="countryFilter">
            <option value=""></option>
          </select>
        </div>
        <label class="users-filter-label" for="usersFilterKeyword">Search</label>
        <input class="form-control form-control-sm users-search-input" id="usersFilterKeyword" name="filter_keyword_text" type="search" placeholder="name or email" value="" autocomplete="new-password" autocapitalize="off" autocorrect="off" spellcheck="false" data-lpignore="true">
      </div>
      <div class="table-responsive">
        <table class="table period-table align-middle mb-0" id="tbl">
          <thead>
            <tr>
              <th style="width:54px;" class="text-center">
                <input type="checkbox" id="selectAllUsers" aria-label="Select all users">
              </th>
              <th style="width:220px;">Email</th>
              <th style="width:80px;">Title</th>
              <th style="width:180px;">Name</th>
              <th style="width:180px;">Country</th>
              <th style="width:150px;"></th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="modal fade period-dialog" id="mdl" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">User</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <input type="hidden" id="id">
        <div class="row g-2">
          <div class="col-12"><label class="form-label">Email</label><input class="form-control" id="email" maxlength="100"></div>
          <div class="col-md-6"><label class="form-label">Country</label><select class="form-select" id="country_code"></select></div>
          <div class="col-md-6"><label class="form-label">Person Name</label><input class="form-control" id="person_name" maxlength="60"></div>
          <div class="col-md-4"><label class="form-label">Title</label><select class="form-select" id="title"><option value="">-</option><option value="Mr.">Mr.</option><option value="Mrs.">Mrs.</option><option value="Ms.">Ms.</option></select></div>
          <div class="col-md-8"><label class="form-label">Agency</label><input class="form-control" id="agency" maxlength="300"></div>
          <div class="col-12"><label class="form-label">Remarks</label><input class="form-control" id="remarks" maxlength="200"></div>
          <div class="col-md-6"><label class="form-label">Password</label><input class="form-control" id="password" type="password" minlength="8" maxlength="100" placeholder="Min. 8 characters"></div>
          <div class="col-md-6"><label class="form-label">Confirm Password</label><input class="form-control" id="password_confirmation" type="password" minlength="8" maxlength="100"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn od-btn-outline" data-bs-dismiss="modal">Cancel</button>
        <button class="btn od-btn-primary" id="btnSave">Save</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade period-dialog" id="mdlMessage" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title" id="msgTitle">Information</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <p class="mb-0" id="msgBody"></p>
      </div>
      <div class="modal-footer">
        <button class="btn od-btn-primary" data-bs-dismiss="modal">OK</button>
      </div>
    </div>
  </div>
</div>

@endsection

@push('styles')
<style>
  .users-filter-row {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    flex-wrap: nowrap;
  }

  .users-filter-label {
    font-size: 0.82rem;
    font-weight: 700;
    color: #425066;
    white-space: nowrap;
    margin: 0;
  }

  .users-country-wrap {
    position: relative;
    min-width: 245px;
  }

  .users-autofill-trap {
    position: absolute !important;
    left: -99999px !important;
    width: 1px !important;
    height: 1px !important;
    opacity: 0 !important;
    pointer-events: none !important;
  }

  .users-country-wrap::after {
    content: '\25BE';
    position: absolute;
    right: 0.65rem;
    top: 50%;
    transform: translateY(-50%);
    color: #24538f;
    font-size: 0.72rem;
    pointer-events: none;
  }

  .users-country-select {
    appearance: none;
    border: 1px solid #b9cee8;
    background: linear-gradient(180deg, #f8fbff 0%, #eef5ff 100%);
    color: #21334d;
    font-weight: 600;
    padding-right: 1.9rem;
    border-radius: 0.5rem;
  }

  .users-country-select:focus {
    border-color: #2b76e5;
    box-shadow: 0 0 0 0.2rem rgba(43, 118, 229, 0.2);
  }

  .users-search-input {
    min-width: 300px;
    max-width: 420px;
    border-color: #c7d3e2;
  }

  #msgBody {
    white-space: pre-line;
  }

  @media (max-width: 767.98px) {
    .users-filter-row {
      flex-wrap: wrap;
    }

    .users-country-wrap,
    .users-search-input {
      width: 100%;
      max-width: none;
    }
  }
</style>
@endpush

@push('scripts')
<script>
const tblBody = document.querySelector('#tbl tbody');
const mdl = new bootstrap.Modal(document.getElementById('mdl'));
const mdlMessage = new bootstrap.Modal(document.getElementById('mdlMessage'));
const flashStatus = @json(session('status'));
const profileEditBase = @json(route('profile.edit'));
const countryFilter = document.getElementById('countryFilter');
const searchInput = document.getElementById('usersFilterKeyword');
const btnGenerate = document.getElementById('btnGenerate');
const btnAdd = document.getElementById('btnAdd');
const selectAllUsers = document.getElementById('selectAllUsers');
const countries = [
  { code: '00', name: 'ASEAN Secretariat' },
  { code: 'BN', name: 'Brunei Darussalam' },
  { code: 'KH', name: 'Cambodia' },
  { code: 'ID', name: 'Indonesia' },
  { code: 'LA', name: 'Lao PDR' },
  { code: 'MY', name: 'Malaysia' },
  { code: 'MM', name: 'Myanmar' },
  { code: 'PH', name: 'Philippines' },
  { code: 'SG', name: 'Singapore' },
  { code: 'TH', name: 'Thailand' },
  { code: 'TL', name: 'Timor-Leste' },
  { code: 'VN', name: 'Viet Nam' },
];
let usersCache = [];
let loadTimer = null;
let selectedUserIds = new Set();
let isGenerating = false;

function clearSearchInput() {
  if (!searchInput) return;
  searchInput.value = '';
}

function showMessage(title, message){
  document.getElementById('msgTitle').textContent = title;
  document.getElementById('msgBody').textContent = message;
  mdlMessage.show();
}

function initCountryOptions(){
  const select = document.getElementById('country_code');
  select.innerHTML = countries
    .map((c) => `<option value="${c.code}">${c.name}</option>`)
    .join('');
}

function initCountryFilterOptions(){
  countryFilter.innerHTML = [
    '<option value=""></option>',
    ...countries.map((c) => `<option value="${c.code}">${c.name}</option>`),
  ].join('');
}

function countryName(code){
  const found = countries.find((c) => c.code === code);
  return found ? found.name : (code || '');
}

async function load(){
  const j = await odFetch('/api/adm/users');
  usersCache = j.data || [];
  applyClientFilters();
}

function applyClientFilters(){
  const selectedCountry = String(countryFilter?.value || '').trim();
  const keyword = String(searchInput?.value || '').trim().toLowerCase();

  const filtered = usersCache.filter((row) => {
    if (selectedCountry && String(row.country_code || '').toUpperCase() !== selectedCountry.toUpperCase()) {
      return false;
    }
    if (!keyword) return true;
    const personName = String(row.person_name || '').toLowerCase();
    const email = String(row.email || '').toLowerCase();
    return personName.includes(keyword) || email.includes(keyword);
  });

  tblBody.innerHTML = '';
  filtered.forEach(r => {
    const isChecked = selectedUserIds.has(Number(r.id)) ? 'checked' : '';
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td class="text-center"><input type="checkbox" class="user-row-check" data-user-id="${r.id}" ${isChecked} aria-label="Select user ${r.email ?? ''}"></td>
      <td>${r.email ?? ''}</td>
      <td>${r.title ?? ''}</td>
      <td>${r.person_name ?? ''}</td>
      <td>${countryName(r.country_code)}</td>
      <td class="text-end">
        <a class="btn btn-outline-primary btn-sm" href="${profileEditBase}?user_id=${r.id}">Edit</a>
      </td>`;
    tblBody.appendChild(tr);
  });
  syncSelectAllState();
}

function syncSelectAllState(){
  if (!selectAllUsers) return;
  const visibleChecks = Array.from(document.querySelectorAll('.user-row-check'));
  if (!visibleChecks.length) {
    selectAllUsers.checked = false;
    selectAllUsers.indeterminate = false;
    return;
  }

  const checkedCount = visibleChecks.filter((el) => el.checked).length;
  selectAllUsers.checked = checkedCount > 0 && checkedCount === visibleChecks.length;
  selectAllUsers.indeterminate = checkedCount > 0 && checkedCount < visibleChecks.length;
}

function buildGenerateSummary(data){
  const selected = Number(data?.selected || 0);
  const updated = Number(data?.updated || 0);
  const emailsSent = Number(data?.emails_sent || 0);
  const failed = Array.isArray(data?.failed) ? data.failed : [];
  const lines = [
    `Selected users: ${selected}`,
    `Passwords generated: ${updated}`,
    `Emails sent: ${emailsSent}`,
    `Failed: ${failed.length}`,
  ];
  if (failed.length > 0) {
    lines.push('');
    lines.push('Failed users:');
    failed.forEach((item) => {
      const id = item?.id ?? '-';
      const email = item?.email ?? '-';
      const reason = item?.reason ?? 'Unknown error';
      lines.push(`- [${id}] ${email}: ${reason}`);
    });
  }
  return lines.join('\n');
}

function openForm(row={}){
  document.getElementById('id').value = row.id || '';
  document.getElementById('email').value = row.email || '';
  document.getElementById('country_code').value = row.country_code || '';
  document.getElementById('person_name').value = row.person_name || '';
  document.getElementById('title').value = row.title || '';
  document.getElementById('agency').value = row.agency || '';
  document.getElementById('remarks').value = row.remarks || '';
  document.getElementById('password').value = '';
  document.getElementById('password_confirmation').value = '';
  mdl.show();
}

btnAdd.addEventListener('click', () => openForm({}));
countryFilter.addEventListener('change', applyClientFilters);
searchInput.addEventListener('input', () => {
  if (loadTimer) window.clearTimeout(loadTimer);
  loadTimer = window.setTimeout(applyClientFilters, 300);
});
selectAllUsers.addEventListener('change', (event) => {
  const checked = !!event.target.checked;
  document.querySelectorAll('.user-row-check').forEach((el) => {
    const userId = Number(el.getAttribute('data-user-id'));
    el.checked = checked;
    if (checked) selectedUserIds.add(userId);
    else selectedUserIds.delete(userId);
  });
  syncSelectAllState();
});

tblBody.addEventListener('change', (event) => {
  const checkbox = event.target.closest('.user-row-check');
  if (!checkbox) return;
  const userId = Number(checkbox.getAttribute('data-user-id'));
  if (checkbox.checked) selectedUserIds.add(userId);
  else selectedUserIds.delete(userId);
  syncSelectAllState();
});

btnGenerate.addEventListener('click', async () => {
  if (isGenerating) return;
  const userIds = Array.from(selectedUserIds).filter((id) => Number.isInteger(id) && id > 0);
  if (!userIds.length) {
    showMessage('Validation', 'Please select at least one user.');
    return;
  }

  const confirmed = await odConfirm(
    `Generate temporary passwords for ${userIds.length} selected user(s)?`,
    'Generate Temporary Passwords'
  );
  if (!confirmed) return;

  isGenerating = true;
  btnGenerate.disabled = true;
  btnGenerate.textContent = 'Generating...';
  try {
    const response = await odFetch('/api/adm/users/generate-temp-passwords', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ user_ids: userIds }),
    });
    await load();
    selectedUserIds = new Set();
    syncSelectAllState();
    showMessage('Generate Result', buildGenerateSummary(response?.data || {}));
  } catch (err) {
    showMessage('Generate Failed', err.message || 'Temporary password generation failed.');
  } finally {
    isGenerating = false;
    btnGenerate.disabled = false;
    btnGenerate.textContent = 'Generate';
  }
});

document.getElementById('btnSave').addEventListener('click', async ()=>{
  const id = document.getElementById('id').value || null;
  const payload = {
    email: document.getElementById('email').value.trim(),
    country_code: document.getElementById('country_code').value.trim(),
    person_name: document.getElementById('person_name').value.trim(),
    title: document.getElementById('title').value.trim(),
    agency: document.getElementById('agency').value.trim(),
    remarks: document.getElementById('remarks').value.trim(),
    password: document.getElementById('password').value,
    password_confirmation: document.getElementById('password_confirmation').value,
  };

  if (!payload.country_code || !payload.person_name || !payload.email) {
    showMessage('Validation', 'Email, Country, dan Person Name wajib diisi.');
    return;
  }
  if (!id && (!payload.password || payload.password.length < 8)) {
    showMessage('Validation', 'Password wajib diisi minimal 8 karakter untuk user baru.');
    return;
  }
  if (payload.password || payload.password_confirmation) {
    if (payload.password.length < 8) {
      showMessage('Validation', 'Password minimal 8 karakter.');
      return;
    }
    if (payload.password !== payload.password_confirmation) {
      showMessage('Validation', 'Konfirmasi password tidak sama.');
      return;
    }
  }
  if (!payload.password) {
    delete payload.password;
    delete payload.password_confirmation;
  }

  try{
    if (id) {
      await odFetch(`/api/adm/user/${id}`, {method:'PUT', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)});
    } else {
      await odFetch('/api/adm/user', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)});
    }
    mdl.hide();
    await load();
    showMessage('Success', 'User berhasil disimpan.');
  }catch(err){ showMessage('Save Failed', err.message); }
});

initCountryOptions();
initCountryFilterOptions();
clearSearchInput();
window.setTimeout(clearSearchInput, 0);
window.setTimeout(clearSearchInput, 250);
window.addEventListener('load', clearSearchInput, { once: true });
load();
if (flashStatus === 'user-deleted') {
  showMessage('Deleted', 'User deleted successfully.');
}
</script>
@endpush
