<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Open Data Portal' }}</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/opendata.css') }}" rel="stylesheet">
    <link href="{{ asset('css/trx-shared.css') }}" rel="stylesheet">
</head>
<body class="od-app">
<nav class="navbar navbar-expand-lg navbar-light od-navbar">
    <div class="container-fluid">
        <a class="navbar-brand" href="{{ route('dashboard') }}">Open Data Portal</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav" aria-controls="nav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="{{ route('trx.country_list') }}">Assessments</a></li>
                @if(auth()->user()?->isAdmin())
                    <li class="nav-item"><a class="nav-link" href="{{ route('trx.periods') }}">Periods</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Administration</a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('adm.sections') }}">Sections</a></li>
                            <li><a class="dropdown-item" href="{{ route('adm.categories') }}">Categories</a></li>
                            <li><a class="dropdown-item" href="{{ route('adm.indicators') }}">Indicators</a></li>
                            <li><a class="dropdown-item" href="{{ route('adm.sub_indicators') }}">Sub-indicators</a></li>
                            <li><a class="dropdown-item" href="{{ route('adm.users') }}">Users</a></li>
                        </ul>
                    </li>
                @endif
            </ul>

            <div class="d-flex align-items-center gap-2">
                <div class="d-none d-lg-flex align-items-center gap-1">
                    <button class="od-icon-btn" type="button" aria-label="Translate">&#127760;</button>
                    <button class="od-icon-btn" type="button" aria-label="Theme">&#9728;</button>
                    <button class="od-icon-btn" type="button" aria-label="Notification">&#128276;</button>
                </div>
                <div class="dropdown">
                    <button class="od-profile-trigger" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="od-avatar">
                            {{ strtoupper(substr(auth()->user()?->name ?? 'U', 0, 1)) }}
                        </span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        <div class="od-user-head d-flex align-items-center gap-2">
                            <span class="od-avatar">
                                {{ strtoupper(substr(auth()->user()?->name ?? 'U', 0, 1)) }}
                            </span>
                            <div>
                                <p class="od-user-name">{{ auth()->user()?->name ?? '-' }}</p>
                                <p class="od-user-email">{{ auth()->user()?->email ?? '-' }}</p>
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
    </div>
</nav>

<main class="container-fluid py-3">
    @yield('content')
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/opendata.js') }}"></script>
@stack('scripts')
</body>
</html>
