@extends('layouts.opendata')
@section('content')
<h1 class="h5">Sections</h1>
<div class="d-flex gap-2 mb-2">
  <button class="btn btn-primary btn-sm" id="btnAdd">Add</button>
</div>
<div class="table-responsive">
  <table class="table table-sm table-striped" id="tbl">
    <thead>
      <tr>
        <th>ID</th><th>Title</th><th>Description</th><th>Active</th><th>Created</th><th>Modified</th><th></th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>
</div>

<!-- Modal -->
<div class="modal fade" id="mdl" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Section</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <input type="hidden" id="id">
        <div class="mb-2"><label class="form-label">Title</label><input class="form-control" id="title" maxlength="50"></div>
        <div class="mb-2"><label class="form-label">Description</label><input class="form-control" id="desc" maxlength="300"></div>
        <div class="form-check"><input class="form-check-input" type="checkbox" id="active" checked><label class="form-check-label" for="active">Active</label></div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" id="btnSave">Save</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
const tblBody = document.querySelector('#tbl tbody');
const mdl = new bootstrap.Modal(document.getElementById('mdl'));

async function load(){
  const j = await odFetch('/api/adm/sections');
  tblBody.innerHTML = '';
  (j.data||[]).forEach(r => {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${r.id}</td>
      <td>${r.title}</td>
      <td>${r.description}</td>
      <td>${r.active ? 'Yes' : 'No'}</td>
      <td>${r.created_date ?? ''}</td>
      <td>${r.modified_date ?? ''}</td>
      <td class="text-end">
        <button class="btn btn-outline-primary btn-sm" data-act="edit" data-id="${r.id}">Edit</button>
        <button class="btn btn-outline-danger btn-sm" data-act="del" data-id="${r.id}">Delete</button>
      </td>`;
    tblBody.appendChild(tr);
  });
}

function openForm(row={}){
  document.getElementById('id').value = row.id || '';
  document.getElementById('title').value = row.title || '';
  document.getElementById('desc').value = row.description || '';
  document.getElementById('active').checked = (row.active ?? true);
  mdl.show();
}

document.getElementById('btnAdd').addEventListener('click', () => openForm({active:true}));

tblBody.addEventListener('click', async (e)=>{
  const btn = e.target.closest('button');
  if(!btn) return;
  const id = btn.getAttribute('data-id');
  const act = btn.getAttribute('data-act');
  if(act==='edit'){
    const j = await odFetch('/api/adm/sections');
    const row = (j.data||[]).find(x=>String(x.id)===String(id));
    openForm(row||{});
  }
  if(act==='del'){
    if(!confirm('Delete this section?')) return;
    try{ await odFetch(`/api/adm/section/${id}`, {method:'DELETE'}); await load(); }
    catch(err){ odToast(err.message); }
  }
});

document.getElementById('btnSave').addEventListener('click', async ()=>{
  const payload = {
    id: document.getElementById('id').value || null,
    title: document.getElementById('title').value,
    description: document.getElementById('desc').value,
    active: document.getElementById('active').checked,
  };
  try{
    await odFetch('/api/adm/section', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)});
    mdl.hide();
    await load();
  }catch(err){ odToast(err.message); }
});

load();
</script>
@endpush
