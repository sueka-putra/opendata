<?php

namespace App\Http\Controllers\Api\Trx;

use App\Http\Controllers\Concerns\JsonEnvelope;
use App\Http\Controllers\Controller;
use App\Models\AssessmentCountry;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DashboardAssessmentApiController extends Controller
{
    use JsonEnvelope;

    public function index(Request $request)
    {
        $user = $request->user();
        $adminCode = (string) config('opendata.admin_country_code', '00');
        $isAdmin = $user->isAdmin();

        $countryOptions = $this->countryOptions($adminCode);
        $selectedCountry = (string) $request->query('country_code', $user->country_code);

        if (!$isAdmin) {
            $selectedCountry = (string) $user->country_code;
        } elseif (!$countryOptions->pluck('code')->contains($selectedCountry)) {
            $selectedCountry = $adminCode;
        }

        $rows = AssessmentCountry::query()
            ->join('od_trx_assessment_periods as p', 'od_trx_assessment_countries.period_id', '=', 'p.id')
            ->leftJoin('countries as c', 'od_trx_assessment_countries.country_code', '=', 'c.code')
            ->where('od_trx_assessment_countries.country_code', $selectedCountry)
            ->orderByDesc('p.year')
            ->orderByDesc('p.id')
            ->get([
                'od_trx_assessment_countries.id as assessment_country_id',
                'od_trx_assessment_countries.period_id',
                'od_trx_assessment_countries.country_code',
                'od_trx_assessment_countries.is_submitted',
                'od_trx_assessment_countries.modified_date',
                'p.year',
                'p.description',
                'p.active',
                'c.name as country_name',
            ])
            ->map(function ($row) use ($adminCode) {
                $countryName = $row->country_name;
                if (!$countryName && $row->country_code === $adminCode) {
                    $countryName = 'ASEANstats';
                }

                return [
                    'assessment_country_id' => (int) $row->assessment_country_id,
                    'period_id' => (int) $row->period_id,
                    'year' => (int) $row->year,
                    'description' => (string) ($row->description ?? ''),
                    'country_code' => (string) $row->country_code,
                    'country_name' => (string) ($countryName ?? ''),
                    'is_submitted' => (bool) $row->is_submitted,
                    'active' => (bool) $row->active,
                    'modified_date' => $row->modified_date,
                ];
            })
            ->values();

        return $this->ok([
            'is_admin' => $isAdmin,
            'selected_country' => $selectedCountry,
            'country_options' => $isAdmin ? $countryOptions->values()->all() : [],
            'rows' => $rows,
        ]);
    }

    private function countryOptions(string $adminCode): Collection
    {
        $defaults = collect([
            ['code' => $adminCode, 'name' => 'ASEANstats'],
            ['code' => 'BN', 'name' => 'Brunei Darussalam'],
            ['code' => 'KH', 'name' => 'Cambodia'],
            ['code' => 'ID', 'name' => 'Indonesia'],
            ['code' => 'LA', 'name' => 'Lao PDR'],
            ['code' => 'MY', 'name' => 'Malaysia'],
            ['code' => 'MM', 'name' => 'Myanmar'],
            ['code' => 'PH', 'name' => 'Philippines'],
            ['code' => 'SG', 'name' => 'Singapore'],
            ['code' => 'TH', 'name' => 'Thailand'],
            ['code' => 'VN', 'name' => 'Viet Nam'],
            ['code' => 'TL', 'name' => 'Timor-Leste'],
        ]);

        $byCodeFromDb = Country::query()
            ->whereIn('code', $defaults->pluck('code')->all())
            ->get(['code', 'name'])
            ->keyBy('code');

        return $defaults->map(function (array $c) use ($byCodeFromDb, $adminCode) {
            if ($c['code'] === $adminCode) {
                return $c;
            }

            $dbName = optional($byCodeFromDb->get($c['code']))->name;
            return [
                'code' => $c['code'],
                'name' => (string) ($dbName ?: $c['name']),
            ];
        })->values();
    }
}
