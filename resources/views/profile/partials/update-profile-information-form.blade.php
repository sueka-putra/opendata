<section>
    <header class="mb-3">
        <h2 class="h6 mb-1 profile-section-title">Profile Information</h2>
        <p class="mb-0 profile-section-subtitle">Update user identity fields.</p>
    </header>

    <form method="post" action="{{ route('profile.update') }}">
        @csrf
        @method('patch')

        @if($targetUserId)
            <input type="hidden" name="user_id" value="{{ $targetUserId }}">
        @endif

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label profile-form-label" for="email">Email</label>
                <input
                    id="email"
                    type="email"
                    class="form-control @error('email') is-invalid @enderror"
                    value="{{ $user->email }}"
                    readonly
                    disabled
                >
                @error('email')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label profile-form-label" for="country_code">Country</label>
                <select
                    id="country_code"
                    name="country_code"
                    class="form-select @error('country_code') is-invalid @enderror"
                    @disabled(!$isAdmin)
                >
                    @foreach($countryOptions as $country)
                        <option value="{{ $country['code'] }}" @selected(old('country_code', $user->country_code) === $country['code'])>
                            {{ $country['code'] }} {{ $country['name'] }}
                        </option>
                    @endforeach
                </select>
                @error('country_code')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <label class="form-label profile-form-label" for="title">Title</label>
                <select id="title" name="title" class="form-select @error('title') is-invalid @enderror">
                    <option value="">-</option>
                    <option value="Mr." @selected(old('title', $user->title) === 'Mr.')>Mr.</option>
                    <option value="Mrs." @selected(old('title', $user->title) === 'Mrs.')>Mrs.</option>
                    <option value="Ms." @selected(old('title', $user->title) === 'Ms.')>Ms.</option>
                </select>
                @error('title')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-9">
                <label class="form-label profile-form-label" for="person_name">Person Name</label>
                <input
                    id="person_name"
                    name="person_name"
                    type="text"
                    class="form-control @error('person_name') is-invalid @enderror"
                    value="{{ old('person_name', $user->person_name) }}"
                    maxlength="60"
                    required
                >
                @error('person_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label profile-form-label" for="remarks">Remark</label>
            <textarea
                id="remarks"
                name="remarks"
                class="form-control @error('remarks') is-invalid @enderror"
                rows="4"
                maxlength="200"
            >{{ old('remarks', $user->remarks) }}</textarea>
            @error('remarks')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="d-flex align-items-center gap-2">
            <button type="submit" class="btn od-btn-primary">Save</button>
            @if (session('status') === 'profile-updated')
                <span class="small text-muted">Saved.</span>
            @endif
        </div>
    </form>
</section>
