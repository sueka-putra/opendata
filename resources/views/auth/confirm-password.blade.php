<x-auth-themed-layout page-title="Confirm Password" heading="Confirm Password" description="This is a secure area of the application. Please confirm your password before continuing.">
    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

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

        <button type="submit" class="submit-btn">Confirm</button>
    </form>
</x-auth-themed-layout>
