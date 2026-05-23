@extends('layouts.opendata')

@section('content')
@push('styles')
<style>
    .cp-wrap { max-width: 860px; margin: 0 auto; }
    .cp-wrap.period-theme-shell.profile-shell {
        background: transparent;
        border: 0;
        box-shadow: none;
        padding: 0;
    }
    .cp-card { border-radius: 16px; overflow: hidden; background: #ffffff; box-shadow: 0 16px 40px rgba(15, 23, 42, 0.12); border: 1px solid #e2e8f0; }
    .cp-head { background: linear-gradient(135deg, #2563eb 0%, #4f8df6 100%); color: #fff; padding: 1rem 1.25rem; font-weight: 500; font-size: 1.95rem; display: flex; align-items: center; gap: .65rem; }
    .cp-body { padding: 1.35rem 1.5rem 1.5rem; }
    .cp-alert { border: 1px solid #9de3c5; background: #e8f9f1; color: #0f6a47; border-left: 4px solid #10b981; border-radius: 12px; padding: .85rem 1rem; margin-bottom: 1rem; display: flex; align-items: center; gap: .6rem; }
    .cp-help { color: #475569; margin-bottom: 1.2rem; font-size: .96rem; }
    .cp-label { font-weight: 700; color: #51657f; margin-bottom: .45rem; }
    .cp-field { margin-bottom: 1rem; }
    .cp-input-wrap { display: flex; align-items: stretch; border: 1px solid #cbd5e1; border-radius: 10px; overflow: hidden; background: #fff; }
    .cp-input-icon { width: 42px; display: grid; place-items: center; color: #334155; background: #f8fafc; border-right: 1px solid #e2e8f0; }
    .cp-input { border: 0; box-shadow: none !important; padding: .72rem .82rem; flex: 1 1 auto; min-width: 0; }
    .cp-input-toggle { width: 44px; border: 0; background: #fff; color: #64748b; }
    .cp-hint { color: #64748b; font-size: .85rem; margin-top: .3rem; }
    .cp-sep { border-top: 1px solid #dbe4ef; margin: .6rem 0 1.1rem; }
    .cp-submit { width: 100%; border: 0; border-radius: 10px; padding: .78rem 1rem; font-weight: 700; background: linear-gradient(135deg, #2563eb 0%, #4f8df6 100%); color: #fff; }
    .cp-cancel { display: inline-flex; align-items: center; gap: .4rem; color: #64748b; text-decoration: none; font-weight: 600; margin: .9rem auto 0; }
    .cp-tips { margin-top: 1.25rem; border-radius: 12px; border: 1px solid #b9e7f5; background: #eaf8ff; padding: .95rem 1rem; color: #0f4d67; }
    .cp-tips-title { font-weight: 700; margin-bottom: .35rem; display: flex; align-items: center; gap: .45rem; }
</style>
@endpush
<div class="period-theme-wrap">
    <div class="period-theme-shell profile-shell cp-wrap">
        <div class="cp-card">
            <div class="cp-head">
                <i class="fa-solid fa-key"></i>
                <span>Change Password</span>
            </div>
            <div class="cp-body">
                @if (session('status') === 'password-updated')
                    <div class="cp-alert">
                        <i class="fa-solid fa-circle-check"></i>
                        <span>Password changed successfully!</span>
                    </div>
                @endif

                <p class="cp-help">Enter your current password and choose a new password. Your password must be at least 8 characters long.</p>

                <form method="post" action="{{ route('profile.password') }}">
                    @csrf
                    @method('patch')

                    @if($targetUserId)
                        <input type="hidden" name="user_id" value="{{ $targetUserId }}">
                    @endif

                    @if(!$isAdmin || !$isManagingOtherUser)
                        <div class="cp-field">
                            <label class="form-label cp-label" for="cp_current_password">Current Password</label>
                            <div class="cp-input-wrap">
                                <span class="cp-input-icon"><i class="fa-solid fa-lock"></i></span>
                                <input id="cp_current_password" name="current_password" type="password" class="form-control cp-input @if($errors->updatePassword->has('current_password')) is-invalid @endif" placeholder="Enter your current password" autocomplete="current-password">
                                <button class="cp-input-toggle" type="button" data-toggle-pass="cp_current_password"><i class="fa-regular fa-eye"></i></button>
                            </div>
                            @if($errors->updatePassword->has('current_password'))
                                <div class="invalid-feedback d-block">{{ $errors->updatePassword->first('current_password') }}</div>
                            @endif
                        </div>
                    @endif

                    <div class="cp-sep"></div>

                    <div class="cp-field">
                        <label class="form-label cp-label" for="cp_password">New Password</label>
                        <div class="cp-input-wrap">
                            <span class="cp-input-icon"><i class="fa-solid fa-key"></i></span>
                            <input id="cp_password" name="password" type="password" class="form-control cp-input @if($errors->updatePassword->has('password')) is-invalid @endif" placeholder="Enter your new password" autocomplete="new-password">
                            <button class="cp-input-toggle" type="button" data-toggle-pass="cp_password"><i class="fa-regular fa-eye"></i></button>
                        </div>
                        <div class="cp-hint">Minimum 8 characters</div>
                        @if($errors->updatePassword->has('password'))
                            <div class="invalid-feedback d-block">{{ $errors->updatePassword->first('password') }}</div>
                        @endif
                    </div>

                    <div class="cp-field">
                        <label class="form-label cp-label" for="cp_password_confirmation">Confirm New Password</label>
                        <div class="cp-input-wrap">
                            <span class="cp-input-icon"><i class="fa-solid fa-key"></i></span>
                            <input id="cp_password_confirmation" name="password_confirmation" type="password" class="form-control cp-input @if($errors->updatePassword->has('password_confirmation')) is-invalid @endif" placeholder="Confirm your new password" autocomplete="new-password">
                            <button class="cp-input-toggle" type="button" data-toggle-pass="cp_password_confirmation"><i class="fa-regular fa-eye"></i></button>
                        </div>
                        <div class="cp-hint">Re-enter your new password</div>
                        @if($errors->updatePassword->has('password_confirmation'))
                            <div class="invalid-feedback d-block">{{ $errors->updatePassword->first('password_confirmation') }}</div>
                        @endif
                    </div>

                    <button type="submit" class="cp-submit"><i class="fa-solid fa-floppy-disk"></i> Change Password</button>
                </form>

                <div class="text-center">
                    <a class="cp-cancel" href="{{ route('dashboard') }}"><i class="fa-solid fa-xmark"></i><span>Cancel</span></a>
                </div>

                <div class="cp-tips">
                    <div class="cp-tips-title"><i class="fa-solid fa-shield-halved"></i><span>Security Tips:</span></div>
                    <div class="small">
                        * Use a strong password with a mix of letters, numbers, and symbols<br>
                        * Don't reuse passwords from other accounts<br>
                        * Keep your password confidential
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-toggle-pass]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const inputId = String(btn.getAttribute('data-toggle-pass') || '');
                const input = document.getElementById(inputId);
                if (!input) return;
                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                btn.innerHTML = isPassword ? '<i class="fa-regular fa-eye-slash"></i>' : '<i class="fa-regular fa-eye"></i>';
            });
        });
    });
</script>
@endpush
