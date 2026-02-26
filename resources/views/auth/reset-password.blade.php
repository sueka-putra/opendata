<x-auth-themed-layout page-title="Reset Password" heading="Reset Password" description="Create a new password for your account.">
    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="field">
            <label for="email">Email</label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email', $request->email) }}"
                required
                autofocus
                autocomplete="username"
                placeholder="Enter your email address"
            >
            @foreach ($errors->get('email') as $message)
                <div class="error">{{ $message }}</div>
            @endforeach
        </div>

        <div class="field">
            <label for="password">New Password</label>
            <input
                id="password"
                type="password"
                name="password"
                required
                autocomplete="new-password"
                placeholder="Enter a new password"
            >
            @foreach ($errors->get('password') as $message)
                <div class="error">{{ $message }}</div>
            @endforeach
        </div>

        <div class="field">
            <label for="password_confirmation">Confirm Password</label>
            <input
                id="password_confirmation"
                type="password"
                name="password_confirmation"
                required
                autocomplete="new-password"
                placeholder="Re-enter the new password"
            >
            @foreach ($errors->get('password_confirmation') as $message)
                <div class="error">{{ $message }}</div>
            @endforeach
        </div>

        <button type="submit" class="submit-btn">Reset Password</button>
    </form>
</x-auth-themed-layout>
