<?php

namespace App\Http\Controllers\Api\Trx;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\JsonEnvelope;
use App\Models\AssessmentCountry;
use App\Models\AssessmentPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CountryApiController extends Controller
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

    public function index(int $periodId)
    {
        $period = AssessmentPeriod::with('countries.country')->findOrFail($periodId);
        $countryIds = $period->countries->pluck('id')->map(fn ($id) => (int) $id)->values();
        $weightedSectionId = (int) config('opendata.weighted_section_id', 0);

        $summaryByCountry = collect();
        $progressByCountry = collect();

        if ($countryIds->isNotEmpty()) {
            $summaryByCountry = DB::table('od_trx_assessment_summaries')
                ->whereIn('assessment_country_id', $countryIds->all())
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
                ->whereIn('assessment_country_id', $countryIds->all())
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

        $rows = $period->countries->map(function (AssessmentCountry $ac) use ($summaryByCountry, $progressByCountry) {
            $summary = $summaryByCountry->get((int) $ac->id, [
                'coverage_sub_score_ratio' => 0,
                'opennes_sub_score_ratio' => 0,
                'overall_score_ratio' => 0,
            ]);
            $progress = $progressByCountry->get((int) $ac->id, [
                'progress' => 0,
            ]);

            return [
                'assessment_country_id' => $ac->id,
                'country_code' => $ac->country_code,
                'country_name' => optional($ac->country)->name,
                'is_submitted' => (bool) $ac->is_submitted,
                'progress' => (float) ($progress['progress'] ?? 0),
                'coverage_sub_score_ratio' => (float) ($summary['coverage_sub_score_ratio'] ?? 0),
                'opennes_sub_score_ratio' => (float) ($summary['opennes_sub_score_ratio'] ?? 0),
                'overall_score_ratio' => (float) ($summary['overall_score_ratio'] ?? 0),
                'modified_by' => $ac->modified_by,
                'modified_date' => $ac->modified_date,
                'created_date' => $ac->created_date,
            ];
        });

        return $this->ok([
            'period' => [
                'id' => $period->id,
                'year' => $period->year,
                'description' => $period->description,
                'active' => (bool) $period->active,
            ],
            'countries' => $rows,
        ]);
    }
}
