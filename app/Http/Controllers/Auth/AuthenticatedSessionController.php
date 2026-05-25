<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\AssessmentCountry;
use App\Models\AssessmentPeriod;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        /** @var User|null $user */
        $user = $request->user();
        if ($user && !(bool) ($user->must_change_password ?? false)) {
            $welcomeCookieName = 'od_welcome_dialog_shown_u'.$user->id;
            $welcomeCookieValue = (string) $request->cookie($welcomeCookieName, '');
            if ($welcomeCookieValue !== '1') {
                $payload = $this->buildWelcomeDialogPayload($user);
                $payload['cookie_name'] = $welcomeCookieName;
                $request->session()->put('welcome_dialog_payload', $payload);
            }
        }

        $defaultRoute = ($user && $user->isAdmin()) ? 'trx.periods' : 'trx.active';
        if ($user && (bool) ($user->must_change_password ?? false)) {
            return redirect()->route('password.edit');
        }

        return redirect()->route($defaultRoute);
    }

    private function buildWelcomeDialogPayload(User $user): array
    {
        $countryCode = trim((string) ($user->country_code ?? ''));
        $todayUtc = Carbon::now('UTC')->toDateString();

        $activePeriod = null;
        if ($countryCode !== '') {
            $activePeriod = AssessmentPeriod::query()
                ->where('active', 1)
                ->where(function ($q) use ($todayUtc) {
                    $q->whereNull('due_date')
                        ->orWhereDate('due_date', '>=', $todayUtc);
                })
                ->whereExists(function ($q) use ($countryCode) {
                    $q->selectRaw('1')
                        ->from('od_trx_assessment_countries as ac')
                        ->whereColumn('ac.period_id', 'od_trx_assessment_periods.id')
                        ->where('ac.country_code', $countryCode);
                })
                ->orderByDesc('year')
                ->orderByDesc('id')
                ->first();
        }

        if (!$activePeriod) {
            return [
                'has_active_assessment' => false,
                'dashboard_url' => route('dashboard'),
            ];
        }

        $assessmentCountryExists = AssessmentCountry::query()
            ->where('period_id', (int) $activePeriod->id)
            ->where('country_code', $countryCode)
            ->exists();

        if (!$assessmentCountryExists) {
            return [
                'has_active_assessment' => false,
                'dashboard_url' => route('dashboard'),
            ];
        }

        return [
            'has_active_assessment' => true,
            'assessment_name' => (string) ($activePeriod->description ?: ($activePeriod->title ?? 'Assessment')),
            'status' => 'Open',
            'deadline_date' => optional($activePeriod->due_date)->format('d M Y'),
            'take_assessment_url' => route('trx.form', [
                'periodid' => (int) $activePeriod->id,
                'country_code' => $countryCode,
            ]),
            'view_quick_guide_url' => route('trx.form', [
                'periodid' => (int) $activePeriod->id,
                'country_code' => $countryCode,
                'open_quick_guide' => 1,
            ]),
            'dashboard_url' => route('dashboard'),
        ];
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
