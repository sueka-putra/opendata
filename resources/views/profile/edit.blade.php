@extends('layouts.opendata')

@section('content')
<div class="period-theme-wrap">
    <div class="period-theme-shell profile-shell">
        <div class="mb-3">
            <h1 class="h5 period-title mb-1">{{ $isManagingOtherUser ? 'User Profile' : 'My Profile' }}</h1>
            <div class="period-subtitle">Manage profile information, password, and user lifecycle.</div>
        </div>

        <div class="profile-card mb-3">
            @include('profile.partials.update-profile-information-form')
        </div>

        <div class="profile-card mb-3">
            @include('profile.partials.update-password-form')
        </div>

        @if($isAdmin)
            <div class="profile-card">
                @include('profile.partials.delete-user-form')
            </div>
        @endif
    </div>
</div>
@endsection
