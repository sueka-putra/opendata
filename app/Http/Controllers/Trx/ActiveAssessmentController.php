<?php

namespace App\Http\Controllers\Trx;

use App\Http\Controllers\Controller;
use App\Models\AssessmentCountry;
use App\Models\AssessmentPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ActiveAssessmentController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        $user = $request->user();
        $countryCode = (string) ($user->country_code ?? '');

        if ($countryCode === '') {
            return redirect()->route('dashboard');
        }

        $activePeriod = AssessmentPeriod::query()
            ->where('active', 1)
            ->orderByDesc('year')
            ->orderByDesc('id')
            ->first();

        if (!$activePeriod) {
            return redirect()->route('dashboard');
        }

        $hasAssessmentCountry = AssessmentCountry::query()
            ->where('period_id', $activePeriod->id)
            ->where('country_code', $countryCode)
            ->exists();

        if (!$hasAssessmentCountry) {
            return redirect()->route('dashboard');
        }

        return redirect()->route('trx.form', [
            'periodid' => $activePeriod->id,
            'country_code' => $countryCode,
        ]);
    }
}

