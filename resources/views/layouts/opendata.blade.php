<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Open Data Portal' }}</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">
    <link href="{{ asset('css/opendata.css') }}" rel="stylesheet">
    <link href="{{ asset('css/trx-shared.css') }}" rel="stylesheet">
    @stack('styles')
    <link rel="icon" type="image/x-icon" href="/img/opendata3.png">
</head>
<body class="od-app">
@php
    $authUser = auth()->user();
    $isAdmin = $authUser?->isAdmin();
    $initial = strtoupper(substr($authUser?->name ?? 'U', 0, 1));
    $adminCode = (string) config('opendata.admin_country_code', '00');
    $countryName = \App\Models\Country::query()
        ->where('code', (string) ($authUser?->country_code ?? ''))
        ->value('name');
    if (!$countryName && (string) ($authUser?->country_code ?? '') === $adminCode) {
        $countryName = 'ASEANstats';
    }
@endphp

<header class="od-topbar">
    <div class="od-topbar-inner">
        <div class="d-flex align-items-center gap-2">
            <button class="od-sidebar-toggle" type="button" data-od-sidebar-toggle aria-label="Toggle menu">
                <i class="fa-solid fa-bars"></i>
            </button>
            <a class="od-brand" href="{{ route('dashboard') }}">
                <img src="{{ asset('img/ASEAN_Logo_small.png') }}" alt="ASEANstats">
            </a>
        </div>

        <div class="d-flex align-items-center gap-2">
            <div class="dropdown">
                <button class="od-profile-trigger" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="od-avatar">{{ $initial }}</span>
                </button>
                <div class="dropdown-menu dropdown-menu-end">
                    <div class="od-user-head d-flex align-items-center gap-2">
                        <span class="od-avatar">{{ $initial }}</span>
                        <div>
                            <p class="od-user-name">{{ $authUser?->name ?? '-' }}</p>
                            <p class="od-user-email">{{ $authUser?->email ?? '-' }}</p>
                        </div>
                    </div>
                    <a class="dropdown-item od-dropdown-action" href="{{ route('profile.edit') }}">My Profile</a>
                    <div class="od-logout-wrap">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="od-btn-logout" type="submit">Logout</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<div class="od-shell">
    <aside class="od-sidebar">
        <div class="od-user-card">
            <div class="od-user-card-head">
                <span class="od-avatar">{{ $initial }}</span>
                <div class="od-user-text">
                    <p class="od-user-name">{{ $authUser?->name ?? '-' }}</p>
                    <p class="od-user-email">{{ $authUser?->email ?? '-' }}</p>
                </div>
            </div>
            <span class="od-role-chip">{{ $countryName ?? '-' }}</span>
        </div>

        <nav class="od-menu">
            <p class="od-menu-section">Assessment</p>
            <a class="od-menu-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                <i class="fa-solid fa-chart-pie"></i><span>Histories</span>
            </a>
            <a class="od-menu-link {{ request()->routeIs('profile.edit') ? 'active' : '' }}" href="{{ route('profile.edit') }}">
                <i class="fa-solid fa-user"></i><span>Profile</span>
            </a>
            <form method="POST" action="{{ route('logout') }}" class="od-menu-form">
                @csrf
                <button type="submit" class="od-menu-link od-menu-link-btn">
                    <i class="fa-solid fa-right-from-bracket"></i><span>Sign-out</span>
                </button>
            </form>

            @if($isAdmin)
            <p class="od-menu-section mt-2">Admin</p>
            <a class="od-menu-link {{ request()->routeIs('trx.periods') ? 'active' : '' }}" href="{{ route('trx.periods') }}">
                <i class="fa-solid fa-calendar-days"></i><span>Periods</span>
            </a>
            <hr/>
            <a class="od-menu-link {{ request()->routeIs('adm.sections') ? 'active' : '' }}" href="{{ route('adm.sections') }}">
                <i class="fa-solid fa-layer-group"></i><span>Sections</span>
            </a>
            <a class="od-menu-link {{ request()->routeIs('adm.categories') ? 'active' : '' }}" href="{{ route('adm.categories') }}">
                <i class="fa-solid fa-tags"></i><span>Categories</span>
            </a>
            <a class="od-menu-link {{ request()->routeIs('adm.indicators') ? 'active' : '' }}" href="{{ route('adm.indicators') }}">
                <i class="fa-solid fa-chart-line"></i><span>Indicators</span>
            </a>
            <a class="od-menu-link {{ request()->routeIs('adm.sub_indicators') ? 'active' : '' }}" href="{{ route('adm.sub_indicators') }}">
                <i class="fa-solid fa-list-check"></i><span>Sub-indicators</span>
            </a>
            <hr/>
            <a class="od-menu-link {{ request()->routeIs('adm.users') ? 'active' : '' }}" href="{{ route('adm.users') }}">
                <i class="fa-solid fa-users"></i><span>Users</span>
            </a>
            @endif
        </nav>
    </aside>

    <main class="od-main">
        @yield('content')
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/opendata.js') }}"></script>
@stack('scripts')
</body>
</html>
