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
        <button class="btn od-btn-primary" id="btnAdd">Add</button>
      </div>
    </div>

    <div class="period-table-card">
      <div class="table-responsive">
        <table class="table period-table align-middle mb-0" id="tbl">
          <thead>
            <tr>
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

@push('scripts')
<script>
const tblBody = document.querySelector('#tbl tbody');
const mdl = new bootstrap.Modal(document.getElementById('mdl'));
const mdlMessage = new bootstrap.Modal(document.getElementById('mdlMessage'));
const flashStatus = @json(session('status'));
const profileEditBase = @json(route('profile.edit'));
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
  { code: 'VN', name: 'Viet Nam' },
];
let usersCache = [];

function showMessage(title, message){
  document.getElementById('msgTitle').textContent = title;
  document.getElementById('msgBody').textContent = message;
  mdlMessage.show();
}

function initCountryOptions(){
  const select = document.getElementById('country_code');
  select.innerHTML = countries
    .map((c) => `<option value="${c.code}">${c.code} ${c.name}</option>`)
    .join('');
}

function countryName(code){
  const found = countries.find((c) => c.code === code);
  return found ? found.name : (code || '');
}

async function load(){
  const j = await odFetch('/api/adm/users');
  usersCache = j.data || [];
  tblBody.innerHTML = '';
  usersCache.forEach(r => {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${r.email ?? ''}</td>
      <td>${r.title ?? ''}</td>
      <td>${r.person_name ?? ''}</td>
      <td>${countryName(r.country_code)}</td>
      <td class="text-end">
        <a class="btn btn-outline-primary btn-sm" href="${profileEditBase}?user_id=${r.id}">Edit</a>
      </td>`;
    tblBody.appendChild(tr);
  });
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

document.getElementById('btnAdd').addEventListener('click', () => openForm({}));

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
  if (!id) {
    const exists = usersCache.some((u) => String(u.email || '').toLowerCase() === payload.email.toLowerCase());
    if (exists) {
      showMessage('Email Sudah Terdaftar', 'Email sudah digunakan. Gunakan email lain atau edit user yang sudah ada.');
      return;
    }
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
load();
if (flashStatus === 'user-deleted') {
  showMessage('Deleted', 'User berhasil dihapus.');
}
</script>
@endpush
