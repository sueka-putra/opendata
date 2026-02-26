<x-auth-themed-layout page-title="Verify Email" heading="Verify Email" description="Check your inbox and click the verification link to continue.">
    @if (session('status') == 'verification-link-sent')
        <div class="status">
            A new verification link has been sent to your email address.
        </div>
    @endif

    <p class="lead" style="margin-top: 0; font-size: 18px;">
        Thanks for signing up. If you did not receive the email, you can request another verification link below.
    </p>

    <div class="actions-stack">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="submit-btn">Resend Verification Email</button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="secondary-btn">Log Out</button>
        </form>
    </div>
</x-auth-themed-layout>
