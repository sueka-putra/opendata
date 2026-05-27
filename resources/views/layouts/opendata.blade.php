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
    $hideSidebarForForcedPasswordChange = (bool) ($authUser?->must_change_password ?? false);
    $welcomeDialogPayload = session()->pull('welcome_dialog_payload');
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
            @unless($hideSidebarForForcedPasswordChange)
                <button class="od-sidebar-toggle" type="button" data-od-sidebar-toggle aria-label="Toggle menu">
                    <i class="fa-solid fa-bars"></i>
                </button>
            @endunless
            <a class="od-brand" href="{{ route('dashboard') }}">
                <img src="{{ asset('img/ASEANStats.jpg') }}" alt="ASEANstats">
            </a>
        </div>
    </div>
</header>

<div class="od-shell {{ $hideSidebarForForcedPasswordChange ? 'od-shell-no-sidebar' : '' }}">
    @unless($hideSidebarForForcedPasswordChange)
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
            <!--p class="od-menu-section">Assessment</p-->
            <a class="od-menu-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}" title="Dashboard">
                <i class="fa-solid fa-chart-pie"></i><span>Dashboard</span>
            </a>
            @if((int)($authUser?->isSelected ?? 0) === 1)
            <a class="od-menu-link {{ request()->routeIs('trx.delegation.*') ? 'active' : '' }}" href="{{ route('trx.delegation.index') }}" title="Delegation">
                <i class="fa-solid fa-user-group"></i><span>Delegation</span>
            </a>
            @endif
            <a class="od-menu-link {{ request()->routeIs('password.edit') ? 'active' : '' }}" href="{{ route('password.edit') }}" title="Change Password">
                <i class="fa-solid fa-key"></i><span>Change Password</span>
            </a>
            <form method="POST" action="{{ route('logout') }}" class="od-menu-form">
                @csrf
                <button type="submit" class="od-menu-link od-menu-link-btn" title="Sign-out">
                    <i class="fa-solid fa-right-from-bracket"></i><span>Sign-out</span>
                </button>
            </form>

            @if($isAdmin)
            <hr/>
            <p class="od-menu-section mt-2">Admin</p>
            <a class="od-menu-link {{ request()->routeIs('trx.periods') ? 'active' : '' }}" href="{{ route('trx.periods') }}" title="Periods">
                <i class="fa-solid fa-calendar-days"></i><span>Periods</span>
            </a>
            <hr/>
            <a class="od-menu-link {{ request()->routeIs('adm.sections') ? 'active' : '' }}" href="{{ route('adm.sections') }}" title="Sections">
                <i class="fa-solid fa-layer-group"></i><span>Sections</span>
            </a>
            <a class="od-menu-link {{ request()->routeIs('adm.categories') ? 'active' : '' }}" href="{{ route('adm.categories') }}" title="Categories">
                <i class="fa-solid fa-tags"></i><span>Categories</span>
            </a>
            <a class="od-menu-link {{ request()->routeIs('adm.indicators') ? 'active' : '' }}" href="{{ route('adm.indicators') }}" title="Indicators">
                <i class="fa-solid fa-chart-line"></i><span>Indicators</span>
            </a>
            <a class="od-menu-link {{ request()->routeIs('adm.sub_indicators') ? 'active' : '' }}" href="{{ route('adm.sub_indicators') }}" title="Aggregations">
                <i class="fa-solid fa-list-check"></i><span>Aggregations</span>
            </a>
            <hr/>
            <a class="od-menu-link {{ request()->routeIs('adm.users') ? 'active' : '' }}" href="{{ route('adm.users') }}" title="Users">
                <i class="fa-solid fa-users"></i><span>Users</span>
            </a>
            @endif
        </nav>
    </aside>
    @endunless

    <main class="od-main">
        @yield('content')
    </main>
</div>

@if(is_array($welcomeDialogPayload))
<style>
    .od-welcome-modal .modal-dialog {
        max-width: 700px;
    }
    .od-welcome-modal .modal-content {
        border: 0;
        border-radius: 24px;
        overflow: hidden;
        background: #f8fafc;
        box-shadow: 0 24px 64px rgba(15, 23, 42, 0.22);
    }
    .od-welcome-head {
        position: relative;
        padding: 1.25rem 1.25rem 0.25rem;
        text-align: center;
    }
    .od-welcome-close {
        position: absolute;
        top: 0.85rem;
        right: 0.9rem;
        border: 0;
        background: transparent;
        color: #94a3b8;
        font-size: 1.15rem;
        line-height: 1;
        cursor: pointer;
    }
    .od-welcome-icon {
        width: 78px;
        height: 78px;
        border-radius: 999px;
        margin: 0 auto 0.9rem;
        display: grid;
        place-items: center;
        background: #dbe7ff;
        color: #2563eb;
        font-size: 1.65rem;
    }
    .od-welcome-title {
        margin: 0;
        font-size: 2rem;
        font-weight: 700;
        color: #1f2937;
    }
    .od-welcome-body {
        padding: 0.35rem 1.25rem 1.25rem;
    }
    .od-welcome-info-card {
        border-radius: 14px;
        padding: 1rem 1.1rem;
        background: linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%);
        color: #fff;
        margin-bottom: 1rem;
    }
    .od-welcome-info-title {
        font-size: 1.08rem;
        font-weight: 700;
        margin-bottom: 0.3rem;
    }
    .od-welcome-info-row {
        font-size: 0.94rem;
        opacity: 0.96;
        margin-bottom: 0.16rem;
    }
    .od-welcome-copy {
        margin: 0;
        color: #475569;
        font-size: 0.95rem;
        line-height: 1.5;
    }
    .od-welcome-footer {
        padding: 0 1.25rem 1.25rem;
    }
    .od-welcome-actions {
        display: grid;
        gap: 0.6rem;
    }
    .od-welcome-btn-main {
        display: inline-flex;
        width: 100%;
        justify-content: center;
        align-items: center;
        gap: 0.5rem;
        text-decoration: none;
        border-radius: 12px;
        background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
        color: #fff;
        font-weight: 600;
        padding: 0.72rem 1rem;
    }
    .od-welcome-btn-main:hover {
        color: #fff;
        filter: brightness(0.98);
    }
    .od-welcome-btn-alt {
        display: inline-flex;
        width: 100%;
        justify-content: center;
        align-items: center;
        gap: 0.5rem;
        text-decoration: none;
        border-radius: 12px;
        background: #e2e8f0;
        color: #1e3a8a;
        font-weight: 600;
        padding: 0.72rem 1rem;
    }
    .od-welcome-btn-alt:hover {
        color: #1e3a8a;
        background: #dbe4ef;
    }
    .od-welcome-note {
        margin: 0.7rem 1.25rem 1.1rem;
        padding: 0.55rem 0.8rem;
        border-radius: 10px;
        background: #eef2f7;
        color: #64748b;
        font-size: 0.84rem;
        text-align: center;
    }
</style>
<div class="modal fade period-dialog" id="welcomeDialog" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false" data-cookie-name="{{ $welcomeDialogPayload['cookie_name'] ?? '' }}">
    <div class="modal-dialog modal-dialog-centered od-welcome-modal">
        <div class="modal-content">
            <div class="od-welcome-head">
                <button type="button" class="od-welcome-close" data-bs-dismiss="modal" data-welcome-allow-close="1" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
                <div class="od-welcome-icon"><i class="fa-solid fa-flag-checkered"></i></div>
                <h5 class="od-welcome-title">Welcome to the Open Data Assessment Portal</h5>
            </div>
            <div class="od-welcome-body">
                @if(!empty($welcomeDialogPayload['has_active_assessment']))
                    <div class="od-welcome-info-card">
                        <div class="od-welcome-info-title">{{ $welcomeDialogPayload['assessment_name'] ?? '-' }}</div>
                        <div class="od-welcome-info-row">Status: {{ $welcomeDialogPayload['status'] ?? 'Open' }}</div>
                        <div class="od-welcome-info-row">Available until: {{ $welcomeDialogPayload['deadline_date'] ?? '-' }}</div>
                    </div>
                    <p class="od-welcome-copy">
                        This assessment is currently open for submission. You can proceed directly to the assessment form or open the Quick Guide for a brief walkthrough before you begin.
                    </p>
                @else
                    <p class="od-welcome-copy">
                        There is currently no active assessment period open for submission. You may still review your previous assessment records and results from the dashboard.
                    </p>
                @endif
            </div>
            <div class="od-welcome-footer">
                <div class="od-welcome-actions">
                @if(!empty($welcomeDialogPayload['has_active_assessment']))
                    <a class="od-welcome-btn-main" href="{{ $welcomeDialogPayload['take_assessment_url'] ?? route('dashboard') }}">
                        <i class="fa-solid fa-play"></i>
                        <span>Take Assessment</span>
                    </a>
                    <a class="od-welcome-btn-alt" href="{{ $welcomeDialogPayload['view_quick_guide_url'] ?? ($welcomeDialogPayload['take_assessment_url'] ?? route('dashboard')) }}">
                        <i class="fa-solid fa-book-open"></i>
                        <span>View Quick Guide</span>
                    </a>
                @else
                    <a class="od-welcome-btn-main" href="{{ $welcomeDialogPayload['dashboard_url'] ?? route('dashboard') }}">
                        <i class="fa-solid fa-gauge-high"></i>
                        <span>Go to Dashboard</span>
                    </a>
                @endif
                </div>
            </div>
            @if(!empty($welcomeDialogPayload['has_active_assessment']))
            <div class="od-welcome-note">
                You can reopen the Quick Guide anytime from the Help button on the right bottom of this page.
            </div>
            @endif
        </div>
    </div>
</div>
@endif

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/opendata.js') }}"></script>
@if(is_array($welcomeDialogPayload))
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const modalEl = document.getElementById('welcomeDialog');
        if (!modalEl) return;
        const cookieName = String(modalEl.dataset.cookieName || '').trim();
        if (cookieName) {
            const maxAgeSeconds = 60 * 60 * 24 * 365 * 5;
            document.cookie = `${cookieName}=1; max-age=${maxAgeSeconds}; path=/; samesite=lax`;
        }
        let allowClose = false;
        modalEl.addEventListener('click', (event) => {
            const trigger = event.target.closest('[data-welcome-allow-close="1"]');
            if (trigger) {
                allowClose = true;
            }
        });
        modalEl.addEventListener('hide.bs.modal', (event) => {
            if (!allowClose) {
                event.preventDefault();
                return;
            }
            allowClose = false;
        });

        const modal = bootstrap.Modal.getOrCreateInstance(modalEl, {
            backdrop: 'static',
            keyboard: false,
        });
        modal.show();
    });
</script>
@endif
@stack('scripts')
</body>
</html>
