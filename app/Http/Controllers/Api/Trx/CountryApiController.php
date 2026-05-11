<?php

namespace App\Http\Controllers\Api\Trx;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\JsonEnvelope;
use App\Models\AssessmentCountry;
use App\Models\AssessmentPeriod;
use App\Services\AuditLogger;
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
        $summaryByCountry = collect();
        $progressByCountry = collect();

        if ($countryIds->isNotEmpty()) {
            $summaryByCountry = DB::table('od_trx_assessment_summaries')
                ->whereIn('assessment_country_id', $countryIds->all())
                ->get([
                    'assessment_country_id',
                    'section_id',
                    'coverage_sub_score',
                    'opennes_sub_score',
                    'overall_score',
                ])
                ->groupBy('assessment_country_id')
                ->map(function ($rows) {
                    $weighted = collect($rows)->first(fn ($r) => (int) ($r->section_id ?? 0) === 0);
                    if (!$weighted) {
                        return [
                            'coverage_sub_score_ratio' => 0,
                            'opennes_sub_score_ratio' => 0,
                            'overall_score_ratio' => 0,
                        ];
                    }

                    return [
                        'coverage_sub_score_ratio' => round(((float) ($weighted->coverage_sub_score ?? 0)) / 100, 6),
                        'opennes_sub_score_ratio' => round(((float) ($weighted->opennes_sub_score ?? 0)) / 100, 6),
                        'overall_score_ratio' => round(((float) ($weighted->overall_score ?? 0)) / 100, 6),
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

    public function unlock(Request $request, int $assessmentCountryId)
    {
        $ac = AssessmentCountry::findOrFail($assessmentCountryId);
        $period = AssessmentPeriod::findOrFail((int) $ac->period_id);

        if (!$period->active) {
            return $this->fail('Assessment period is completed', 409);
        }

        if (!$ac->is_submitted) {
            return $this->fail('Country is not in submitted status', 409);
        }

        $ac->update([
            'is_submitted' => 0,
            'modified_by' => (int) auth()->id(),
        ]);

        AuditLogger::log($request, 'unlock assessment', $ac->id);

        return $this->ok([
            'assessment_country' => [
                'id' => $ac->id,
                'period_id' => (int) $ac->period_id,
                'country_code' => (string) $ac->country_code,
                'is_submitted' => false,
            ],
        ]);
    }
}
