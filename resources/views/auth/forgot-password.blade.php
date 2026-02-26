<x-auth-themed-layout page-title="Forgot Password" heading="Forgot Password" description="Enter your email and we will send you a password reset link.">
    <x-auth-session-status class="status" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="field">
            <label for="email">Email</label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="username"
                placeholder="Enter your email address"
            >
            @foreach ($errors->get('email') as $message)
                <div class="error">{{ $message }}</div>
            @endforeach
        </div>

        <button type="submit" class="submit-btn">Email Password Reset Link</button>
    </form>

    <div style="margin-top: 14px; text-align: center;">
        <a class="link" href="{{ route('login') }}">Back to Sign In</a>
    </div>
</x-auth-themed-layout>
