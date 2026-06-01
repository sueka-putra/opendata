<x-auth-themed-layout page-title="Sign In" heading="Sign In" description="Please enter your account details">
    <x-auth-session-status class="status" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="field">
            <label for="email">Email or Username</label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="username"
                placeholder="Enter your email or username"
            >
            @foreach ($errors->get('email') as $message)
                <div class="error">{{ $message }}</div>
            @endforeach
        </div>

        <div class="field">
            <label for="password">Password</label>
            <input
                id="password"
                type="password"
                name="password"
                required
                autocomplete="current-password"
                placeholder="Enter your password"
            >
            @foreach ($errors->get('password') as $message)
                <div class="error">{{ $message }}</div>
            @endforeach
        </div>

        <div class="row">
            <label class="remember" for="remember_me">
                <input id="remember_me" type="checkbox" name="remember">
                <span>Remember me</span>
            </label>
            @if (Route::has('password.request'))
                <a class="link" href="{{ route('password.request') }}">Forgot Password?</a>
            @endif
        </div>

        <button type="submit" class="submit-btn">Sign In</button>
        <a class="help-center-link" href="/help?topic=login" target="helpCenterTab">Help center</a>
    </form>
</x-auth-themed-layout>
