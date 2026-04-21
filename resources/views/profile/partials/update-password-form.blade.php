<section>
    <header class="mb-3">
        <h2 class="h6 mb-1 profile-section-title">Change Password</h2>
        <p class="mb-0 profile-section-subtitle">Update the user password.</p>
    </header>

    <form method="post" action="{{ route('profile.password') }}">
        @csrf
        @method('patch')

        @if($targetUserId)
            <input type="hidden" name="user_id" value="{{ $targetUserId }}">
        @endif

        @if(!$isAdmin || !$isManagingOtherUser)
            <div class="mb-3">
                <label class="form-label profile-form-label" for="update_password_current_password">Current Password</label>
                <input
                    id="update_password_current_password"
                    name="current_password"
                    type="password"
                    class="form-control @if($errors->updatePassword->has('current_password')) is-invalid @endif"
                    autocomplete="current-password"
                >
                @if($errors->updatePassword->has('current_password'))
                    <div class="invalid-feedback">{{ $errors->updatePassword->first('current_password') }}</div>
                @endif
            </div>
        @endif

        <div class="mb-3">
            <label class="form-label profile-form-label" for="update_password_password">New Password</label>
            <input
                id="update_password_password"
                name="password"
                type="password"
                class="form-control @if($errors->updatePassword->has('password')) is-invalid @endif"
                autocomplete="new-password"
            >
            @if($errors->updatePassword->has('password'))
                <div class="invalid-feedback">{{ $errors->updatePassword->first('password') }}</div>
            @endif
        </div>

        <div class="mb-3">
            <label class="form-label profile-form-label" for="update_password_password_confirmation">Confirm Password</label>
            <input
                id="update_password_password_confirmation"
                name="password_confirmation"
                type="password"
                class="form-control @if($errors->updatePassword->has('password_confirmation')) is-invalid @endif"
                autocomplete="new-password"
            >
            @if($errors->updatePassword->has('password_confirmation'))
                <div class="invalid-feedback">{{ $errors->updatePassword->first('password_confirmation') }}</div>
            @endif
        </div>

        <div class="d-flex align-items-center gap-2">
            <button type="submit" class="btn od-btn-primary">Save</button>
            @if (session('status') === 'password-updated')
                <span class="small text-muted">Saved.</span>
            @endif
        </div>
    </form>
</section>
