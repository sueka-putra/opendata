@extends('layouts.opendata')

@push('styles')
<style>
  .delegation-pass-wrap { display: flex; align-items: stretch; border: 1px solid #cbd5e1; border-radius: 10px; overflow: hidden; background: #fff; }
  .delegation-pass-icon { width: 42px; display: grid; place-items: center; color: #334155; background: #f8fafc; border-right: 1px solid #e2e8f0; }
  .delegation-pass-input { border: 0; box-shadow: none !important; padding: .72rem .82rem; flex: 1 1 auto; min-width: 0; }
  .delegation-pass-toggle { width: 44px; border: 0; background: #fff; color: #64748b; }
</style>
@endpush

@section('content')
<div class="period-theme-wrap">
  <div class="period-theme-shell">
    <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-3">
      <div>
        <h1 class="h5 period-title mb-1">Delegation</h1>
        <div class="period-subtitle">You can add colleagues to the delegation list so they can help complete the assessment. If two or more delegates are assigned, please make sure they do not edit the assessment at the same time, because their changes can overwrite each other.</div>
      </div>
      <button type="button" class="btn od-btn-primary" data-bs-toggle="modal" data-bs-target="#delegationAddDialog">Add Delegation</button>
    </div>

    @if (session('status') === 'delegation-created')
      <div class="alert alert-success">Delegation user created successfully.</div>
    @endif
    @if (session('status') === 'delegation-deleted')
      <div class="alert alert-success">Delegation user deleted successfully.</div>
    @endif
    @if (session('status') === 'delegation-updated')
      <div class="alert alert-success">Delegation user updated successfully.</div>
    @endif
    @error('delegation')
      <div class="alert alert-danger">{{ $message }}</div>
    @enderror

    <div class="row g-3">
      <div class="col-12">
        <div class="period-table-card">
          <div class="table-responsive">
            <table class="table period-table align-middle mb-0">
              <thead>
                <tr>
                  <th style="width:80px;">No</th>
                  <th>Email</th>
                  <th>Name</th>
                  <th style="width:120px;">Default</th>
                  <th style="width:210px;" class="text-end">Action</th>
                </tr>
              </thead>
              <tbody>
                @forelse($delegations as $idx => $row)
                  <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>{{ $row->email }}</td>
                    <td>{{ $row->person_name }}</td>
                    <td>
                      @if((int)($row->isSelected ?? 0) === 1)
                        <span class="badge text-bg-success">Yes</span>
                      @else
                        <span class="badge text-bg-secondary">No</span>
                      @endif
                    </td>
                    <td class="text-end">
                      <div class="d-inline-flex gap-2">
                        @if((int)($row->isSelected ?? 0) === 0)
                          <button
                            type="button"
                            class="btn btn-sm btn-outline-primary btn-delegation-edit"
                            data-delegation-id="{{ (int) $row->id }}"
                            data-delegation-name="{{ $row->person_name }}"
                            data-delegation-email="{{ $row->email }}"
                            data-bs-toggle="modal"
                            data-bs-target="#delegationEditDialog"
                          >
                            Edit
                          </button>
                          <form method="POST" action="{{ route('trx.delegation.destroy', ['id' => (int) $row->id]) }}" onsubmit="return confirm('Delete this delegation user?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                          </form>
                        @endif
                      </div>
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="5" class="text-muted">No delegation users found.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade period-dialog" id="delegationAddDialog" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title mb-0">Add New Delegation</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="{{ route('trx.delegation.store') }}">
        @csrf
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" maxlength="100" required>
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="mb-3">
            <label class="form-label">Name</label>
            <input type="text" name="person_name" class="form-control @error('person_name') is-invalid @enderror" value="{{ old('person_name') }}" maxlength="60" required>
            @error('person_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <div class="delegation-pass-wrap">
              <span class="delegation-pass-icon"><i class="fa-solid fa-key"></i></span>
              <input type="password" name="password" id="delegationAddPassword" class="form-control delegation-pass-input @error('password') is-invalid @enderror" minlength="8" maxlength="100" required>
              <button class="delegation-pass-toggle btn-toggle-password" type="button" data-target="#delegationAddPassword" aria-label="Toggle password visibility"><i class="fa-regular fa-eye"></i></button>
            </div>
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="mb-0">
            <label class="form-label">Confirm Password</label>
            <div class="delegation-pass-wrap">
              <span class="delegation-pass-icon"><i class="fa-solid fa-key"></i></span>
              <input type="password" name="password_confirmation" id="delegationAddPasswordConfirmation" class="form-control delegation-pass-input" minlength="8" maxlength="100" required>
              <button class="delegation-pass-toggle btn-toggle-password" type="button" data-target="#delegationAddPasswordConfirmation" aria-label="Toggle password visibility"><i class="fa-regular fa-eye"></i></button>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn od-btn-outline" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn od-btn-primary">Add Delegation</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade period-dialog" id="delegationEditDialog" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title mb-0">Edit Delegation</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" id="delegationEditForm">
        @csrf
        @method('PUT')
        <div class="modal-body">
          <div class="small text-muted mb-3" id="delegationEditEmail">-</div>
          <div class="mb-3">
            <label class="form-label">Name</label>
            <input type="text" name="person_name" id="delegationEditName" class="form-control" maxlength="60" required>
          </div>
          <div class="mb-3">
            <label class="form-label">New Password (optional)</label>
            <div class="delegation-pass-wrap">
              <span class="delegation-pass-icon"><i class="fa-solid fa-key"></i></span>
              <input type="password" name="password" id="delegationEditPassword" class="form-control delegation-pass-input" minlength="8" maxlength="100">
              <button class="delegation-pass-toggle btn-toggle-password" type="button" data-target="#delegationEditPassword" aria-label="Toggle password visibility"><i class="fa-regular fa-eye"></i></button>
            </div>
          </div>
          <div class="mb-0">
            <label class="form-label">Confirm New Password</label>
            <div class="delegation-pass-wrap">
              <span class="delegation-pass-icon"><i class="fa-solid fa-key"></i></span>
              <input type="password" name="password_confirmation" id="delegationEditPasswordConfirmation" class="form-control delegation-pass-input" minlength="8" maxlength="100">
              <button class="delegation-pass-toggle btn-toggle-password" type="button" data-target="#delegationEditPasswordConfirmation" aria-label="Toggle password visibility"><i class="fa-regular fa-eye"></i></button>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn od-btn-outline" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn od-btn-primary">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('click', (event) => {
  const toggleBtn = event.target.closest('.btn-toggle-password');
  if (toggleBtn) {
    const targetSelector = toggleBtn.getAttribute('data-target');
    const input = targetSelector ? document.querySelector(targetSelector) : null;
    if (input) {
      const isPassword = input.getAttribute('type') === 'password';
      input.setAttribute('type', isPassword ? 'text' : 'password');
      toggleBtn.innerHTML = isPassword ? '<i class="fa-regular fa-eye-slash"></i>' : '<i class="fa-regular fa-eye"></i>';
    }
    return;
  }

  const btn = event.target.closest('.btn-delegation-edit');
  if (!btn) return;

  const id = btn.getAttribute('data-delegation-id');
  const name = btn.getAttribute('data-delegation-name') || '';
  const email = btn.getAttribute('data-delegation-email') || '-';
  const form = document.getElementById('delegationEditForm');
  const nameInput = document.getElementById('delegationEditName');
  const emailText = document.getElementById('delegationEditEmail');
  const passInput = document.getElementById('delegationEditPassword');
  const passConfInput = document.getElementById('delegationEditPasswordConfirmation');

  if (!form || !nameInput || !emailText || !id) return;
  form.setAttribute('action', `/trx/delegation/${encodeURIComponent(id)}`);
  nameInput.value = name;
  emailText.textContent = email;
  if (passInput) passInput.value = '';
  if (passConfInput) passConfInput.value = '';
});
</script>
@endpush
