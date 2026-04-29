<?php

namespace App\Http\Controllers\Api\Trx;

use App\Http\Controllers\Concerns\JsonEnvelope;
use App\Http\Controllers\Controller;
use App\Models\AssessmentCountry;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardAssessmentApiController extends Controller
{
    use JsonEnvelope;

    private function classifyRowProgress(array $row): string
    {
        $seriesRaw = trim((string) ($row['series'] ?? ''));
        $urlsRaw = trim((string) ($row['urls'] ?? ''));
        $remarksRaw = trim((string) ($row['remarks'] ?? ''));
        $isNA = strtoupper($seriesRaw) === 'NA';

        $opennessFields = [
            $row['machine_readability'] ?? null,
            $row['proprietary'] ?? null,
            $row['download_options'] ?? null,
            $row['metadata'] ?? null,
            $row['term_of_use'] ?? null,
        ];
        $hasAnyOpenness = collect($opennessFields)->contains(
            fn ($v) => $v !== null && trim((string) $v) !== ''
        );
        $hasAnyInput = $seriesRaw !== '' || $urlsRaw !== '' || $remarksRaw !== '' || $hasAnyOpenness;

        if (!$hasAnyInput) {
            return 'empty';
        }

        $isCoverageFilled = $seriesRaw !== '';
        $isOpennessFilled = $isNA || $hasAnyOpenness;
        $isUrlFilled = $isNA || $urlsRaw !== '';

        if ($isCoverageFilled && $isOpennessFilled && $isUrlFilled) {
            return 'completed';
        }

        return 'in_progress';
    }

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

        $baseRows = AssessmentCountry::query()
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
            ]);

        $assessmentCountryIds = $baseRows->pluck('assessment_country_id')->map(fn ($id) => (int) $id)->values();
        $weightedSectionId = (int) config('opendata.weighted_section_id', 0);

        $summaryByCountry = collect();
        $progressByCountry = collect();
        if ($assessmentCountryIds->isNotEmpty()) {
            $summaryByCountry = DB::table('od_trx_assessment_summaries')
                ->whereIn('assessment_country_id', $assessmentCountryIds->all())
                ->get([
                    'assessment_country_id',
                    'section_id',
                    'coverage_max_score',
                    'coverage_actual_score',
                    'opennes_max_score',
                    'opennes_actual_score',
                ])
                ->groupBy('assessment_country_id')
                ->map(function ($rows) use ($weightedSectionId) {
                    $baseRows = collect($rows)->filter(function ($r) use ($weightedSectionId) {
                        if ($weightedSectionId <= 0) {
                            return true;
                        }
                        return (int) ($r->section_id ?? 0) !== $weightedSectionId;
                    })->values();

                    $sectionCount = $baseRows->count();
                    if ($sectionCount === 0) {
                        return [
                            'coverage_sub_score_ratio' => 0,
                            'opennes_sub_score_ratio' => 0,
                            'overall_score_ratio' => 0,
                        ];
                    }

                    $coverageSum = 0.0;
                    $opennessSum = 0.0;
                    foreach ($baseRows as $row) {
                        $coverageMax = (float) ($row->coverage_max_score ?? 0);
                        $coverageActual = (float) ($row->coverage_actual_score ?? 0);
                        $opennessMax = (float) ($row->opennes_max_score ?? 0);
                        $opennessActual = (float) ($row->opennes_actual_score ?? 0);

                        $coverageSum += $coverageMax > 0 ? ($coverageActual / $coverageMax) : 0;
                        $opennessSum += $opennessMax > 0 ? ($opennessActual / $opennessMax) : 0;
                    }

                    $weightedCoverage = $coverageSum / $sectionCount;
                    $weightedOpenness = $opennessSum / $sectionCount;
                    $overall = (0.5 * $weightedCoverage) + (0.5 * $weightedOpenness);

                    return [
                        'coverage_sub_score_ratio' => round($weightedCoverage, 6),
                        'opennes_sub_score_ratio' => round($weightedOpenness, 6),
                        'overall_score_ratio' => round($overall, 6),
                    ];
                });

            $progressByCountry = DB::table('od_trx_assessment_country_rows')
                ->whereIn('assessment_country_id', $assessmentCountryIds->all())
                ->get([
                    'assessment_country_id',
                    'series',
                    'machine_readability',
                    'proprietary',
                    'download_options',
                    'metadata',
                    'term_of_use',
                    'urls',
                    'remarks',
                ])
                ->groupBy('assessment_country_id')
                ->map(function ($rows) {
                    $total = collect($rows)->count();
                    $completed = 0;
                    $inProgress = 0;

                    foreach ($rows as $row) {
                        $status = $this->classifyRowProgress((array) $row);
                        if ($status === 'completed') {
                            $completed += 1;
                        } elseif ($status === 'in_progress') {
                            $inProgress += 1;
                        }
                    }

                    $progress = $total > 0
                        ? (((0.5 * $inProgress) + $completed) / $total) * 100
                        : 0;

                    return [
                        'progress' => round($progress, 2),
                    ];
                });
        }

        $rows = $baseRows
            ->map(function ($row) use ($adminCode, $summaryByCountry, $progressByCountry) {
                $countryName = $row->country_name;
                if (!$countryName && $row->country_code === $adminCode) {
                    $countryName = 'ASEANstats';
                }
                $summary = $summaryByCountry->get((int) $row->assessment_country_id, [
                    'coverage_sub_score_ratio' => 0,
                    'opennes_sub_score_ratio' => 0,
                    'overall_score_ratio' => 0,
                ]);
                $progress = $progressByCountry->get((int) $row->assessment_country_id, [
                    'progress' => 0,
                ]);

                return [
                    'assessment_country_id' => (int) $row->assessment_country_id,
                    'period_id' => (int) $row->period_id,
                    'year' => (int) $row->year,
                    'description' => (string) ($row->description ?? ''),
                    'country_code' => (string) $row->country_code,
                    'country_name' => (string) ($countryName ?? ''),
                    'is_submitted' => (bool) $row->is_submitted,
                    'active' => (bool) $row->active,
                    'progress' => (float) ($progress['progress'] ?? 0),
                    'coverage_sub_score_ratio' => (float) ($summary['coverage_sub_score_ratio'] ?? 0),
                    'opennes_sub_score_ratio' => (float) ($summary['opennes_sub_score_ratio'] ?? 0),
                    'overall_score_ratio' => (float) ($summary['overall_score_ratio'] ?? 0),
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
