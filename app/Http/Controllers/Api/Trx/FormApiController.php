<?php

namespace App\Http\Controllers\Api\Trx;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\JsonEnvelope;
use App\Models\AssessmentCountry;
use App\Models\AssessmentPeriod;
use App\Models\AssessmentSummary;
use App\Services\AuditLogger;
use App\Services\ScoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FormApiController extends Controller
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

    public function show(Request $request)
    {
        $periodId = (int) $request->query('periodid');
        $countryCode = (string) $request->query('country_code', $request->user()->country_code);
        $isAseanstatsStaff = ((string) $request->user()->country_code) === '00';

        $period = AssessmentPeriod::findOrFail($periodId);
        $ac = AssessmentCountry::where('period_id', $periodId)->where('country_code', $countryCode)->firstOrFail();

        // Access control
        if (!$request->user()->isAdmin() && $request->user()->country_code !== $countryCode) {
            return $this->fail('Assessment not found', 404);
        }

        $schema = DB::getSchemaBuilder();
        $sectionCol = $schema->hasColumn('od_mst_sections', 'description') ? 'description' : 'title';
        $categoryCol = $schema->hasColumn('od_mst_categories', 'description') ? 'description' : 'title';
        $indicatorCol = $schema->hasColumn('od_mst_indicators', 'description') ? 'description' : 'title';
        $aggregationCol = $schema->hasColumn('od_mst_aggregations', 'description') ? 'description' : 'title';
        $hasAseanstatsOnlyCol = $schema->hasColumn('od_mst_configuration_rows', 'aseanstats_only');

        $detailQuery = DB::table('od_trx_assessment_periods as p')
            ->join('od_trx_assessment_countries as ac', 'ac.period_id', '=', 'p.id')
            ->join('od_trx_assessment_country_rows as cr', 'cr.assessment_country_id', '=', 'ac.id')
            ->join('od_mst_configuration_rows as cfg', function ($join) {
                $join->on('cfg.config_id', '=', 'p.config_id');
                $join->on('cfg.id', '=', 'cr.row_id');
            })
            ->leftJoin('od_mst_sections as s', 's.id', '=', 'cfg.section_id')
            ->leftJoin('od_mst_categories as c', 'c.id', '=', 'cfg.category_id')
            ->leftJoin('od_mst_indicators as i', 'i.id', '=', 'cfg.indicator_id')
            ->leftJoin('od_mst_aggregations as a', 'a.id', '=', 'cfg.sub_indicator_id')
            ->select([
                'cr.id',
                'p.year',
                'ac.country_code',
                'cr.row_id',
                'cfg.section_id',
                'cfg.category_id',
                'cfg.indicator_id',
                'cfg.sub_indicator_id',
                DB::raw("s.{$sectionCol} as section"),
                DB::raw("c.{$categoryCol} as category"),
                DB::raw("i.{$indicatorCol} as indicator"),
                DB::raw("COALESCE(a.{$aggregationCol}, '') as aggregation"),
                'cr.series',
                'cr.machine_readability',
                'cr.proprietary',
                'cr.download_options',
                'cr.metadata',
                'cr.term_of_use',
                'cr.urls',
                'cr.remarks',
            ])
            ->where('p.id', $periodId)
            ->where('ac.country_code', $countryCode)
            ->where('cr.assessment_country_id', $ac->id);

        if ($hasAseanstatsOnlyCol && !$isAseanstatsStaff) {
            $detailQuery->where(function ($q) {
                $q->whereNull('cfg.aseanstats_only')
                    ->orWhere('cfg.aseanstats_only', 0);
            });
        }

        $detail = $detailQuery
            ->orderBy('cfg.seq_no')
            ->get()
            ->map(function ($r) use ($period) {
                $cov = ScoreService::computeRowCoverage((string) $r->series, (int) $period->year);
                $o = (float) $r->machine_readability + (float) $r->proprietary + (float) $r->download_options + (float) $r->metadata + (float) $r->term_of_use;
                return array_merge((array) $r, [
                    'count_all' => $cov['count_all'],
                    'count_5' => $cov['count_5'],
                    'count_10' => $cov['count_10'],
                    'c1' => $cov['c1'],
                    'c2' => $cov['c2'],
                    'c3' => $cov['c3'],
                    'c' => $cov['c'],
                    'o' => $cov['is_na'] ? null : $o,
                ]);
            });

        $summaryCollection = AssessmentSummary::with('section')
            ->where('assessment_country_id', $ac->id)
            ->get();

        $weightedSectionId = config('opendata.weighted_section_id');
        $summaryBase = $summaryCollection->filter(function ($s) use ($weightedSectionId) {
            if (!$weightedSectionId) {
                return true;
            }
            return (int) $s->section_id !== (int) $weightedSectionId;
        })->values();

        $detailBySection = collect($detail)->groupBy(fn ($r) => (string) ($r['section_id'] ?? ''));
        $progressMap = $detailBySection->map(function ($rows) {
            $total = $rows->count();
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
                'total_rows' => $total,
                'in_progress_rows' => $inProgress,
                'completed_rows' => $completed,
                'progress' => round($progress, 2),
            ];
        });

        $summary = $summaryBase->map(function ($s) use ($progressMap) {
            $sectionKey = (string) ($s->section_id ?? '');
            $progress = $progressMap->get($sectionKey, [
                'total_rows' => 0,
                'in_progress_rows' => 0,
                'completed_rows' => 0,
                'progress' => 0,
            ]);

            $coverageMax = (float) $s->coverage_max_score;
            $coverageActual = (float) $s->coverage_actual_score;
            $opennessMax = (float) $s->opennes_max_score;
            $opennessActual = (float) $s->opennes_actual_score;
            $coverageSubRatio = $coverageMax > 0 ? ($coverageActual / $coverageMax) : 0;
            $opennessSubRatio = $opennessMax > 0 ? ($opennessActual / $opennessMax) : 0;
            $overallRatio = (0.5 * $coverageSubRatio) + (0.5 * $opennessSubRatio);

            return array_merge($s->toArray(), $progress, [
                'coverage_sub_score_ratio' => round($coverageSubRatio, 6),
                'opennes_sub_score_ratio' => round($opennessSubRatio, 6),
                'overall_score_ratio' => round($overallRatio, 6),
            ]);
        })->values();

        $sectionCount = $summaryBase->count();
        $weightedCoverage = 0.0;
        $weightedOpenness = 0.0;
        if ($sectionCount > 0) {
            foreach ($summaryBase as $s) {
                $coverageMax = (float) $s->coverage_max_score;
                $coverageActual = (float) $s->coverage_actual_score;
                $opennessMax = (float) $s->opennes_max_score;
                $opennessActual = (float) $s->opennes_actual_score;
                $weightedCoverage += ($coverageMax > 0 ? ($coverageActual / $coverageMax) : 0);
                $weightedOpenness += ($opennessMax > 0 ? ($opennessActual / $opennessMax) : 0);
            }
            $weightedCoverage = $weightedCoverage / $sectionCount;
            $weightedOpenness = $weightedOpenness / $sectionCount;
        }
        $weightedOverall = (0.5 * $weightedCoverage) + (0.5 * $weightedOpenness);

        return $this->ok([
            'period' => ['id' => $period->id, 'year' => $period->year, 'active' => (bool) $period->active, 'description' => $period->description],
            'assessment_country' => ['id' => $ac->id, 'country_code' => $ac->country_code, 'is_submitted' => (bool) $ac->is_submitted],
            'detail' => $detail,
            'detail_meta' => [
                'total_rows' => $detail->count(),
                'sorted_by' => 'seq_no',
                'sort_order' => 'asc',
            ],
            'summary' => $summary,
            'weighted_score' => [
                'coverage_sub_score_ratio' => round($weightedCoverage, 6),
                'opennes_sub_score_ratio' => round($weightedOpenness, 6),
                'overall_score_ratio' => round($weightedOverall, 6),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $payload = $request->validate([
            'periodid' => 'required|integer',
            'countryid' => 'required|integer',
            'rows' => 'required|array',
            'rows.*.row_id' => 'required|integer',
            'rows.*.series' => 'nullable|string|max:300',
            'rows.*.machine_readability' => 'nullable|numeric|in:0,1',
            'rows.*.proprietary' => 'nullable|numeric|in:0,1',
            'rows.*.download_options' => 'nullable|numeric|in:0,0.5,1',
            'rows.*.metadata' => 'nullable|numeric|in:0,0.5,1',
            'rows.*.term_of_use' => 'nullable|numeric|in:0,0.5,1',
            'rows.*.urls' => 'nullable|string|max:2000',
            'rows.*.remarks' => 'nullable|string|max:2000',
        ]);

        $ac = AssessmentCountry::where('id', $payload['countryid'])
            ->where('period_id', $payload['periodid'])
            ->firstOrFail();

        // Access control
        if (!$request->user()->isAdmin() && $request->user()->country_code !== $ac->country_code) {
            return $this->fail('Assessment not found', 404);
        }

        $period = AssessmentPeriod::findOrFail($payload['periodid']);
        if (!$period->active) {
            return $this->fail('Assessment period is completed', 409);
        }

        $schema = DB::getSchemaBuilder();
        $isAseanstatsStaff = ((string) $request->user()->country_code) === '00';
        if ($schema->hasColumn('od_mst_configuration_rows', 'aseanstats_only') && !$isAseanstatsStaff) {
            $requestedRowIds = collect($payload['rows'] ?? [])
                ->pluck('row_id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->values();
            if ($requestedRowIds->isNotEmpty()) {
                $hasRestrictedRows = DB::table('od_mst_configuration_rows')
                    ->where('config_id', (int) $period->config_id)
                    ->whereIn('id', $requestedRowIds)
                    ->where('aseanstats_only', 1)
                    ->exists();
                if ($hasRestrictedRows) {
                    return $this->fail('Some rows are restricted to ASEANstats staff.', 403);
                }
            }
        }

        DB::transaction(function () use ($payload, $ac) {
            foreach ($payload['rows'] as $r) {
                $machineReadability = array_key_exists('machine_readability', $r) ? $r['machine_readability'] : null;
                $proprietary = array_key_exists('proprietary', $r) ? $r['proprietary'] : null;
                $downloadOptions = array_key_exists('download_options', $r) ? $r['download_options'] : null;
                $metadata = array_key_exists('metadata', $r) ? $r['metadata'] : null;
                $termOfUse = array_key_exists('term_of_use', $r) ? $r['term_of_use'] : null;

                DB::table('od_trx_assessment_country_rows')
                    ->where('assessment_country_id', $ac->id)
                    ->where('row_id', $r['row_id'])
                    ->update([
                        'series' => $r['series'] ?? '',
                        'machine_readability' => $machineReadability,
                        'proprietary' => $proprietary,
                        'download_options' => $downloadOptions,
                        'metadata' => $metadata,
                        'term_of_use' => $termOfUse,
                        'urls' => $r['urls'] ?? null,
                        'remarks' => $r['remarks'] ?? null,
                    ]);
            }

            // mark as modified
            $ac->update(['modified_by' => (int) auth()->id()]);
        });

        // recompute server-side
        $summaries = ScoreService::recomputeAndPersist($ac);

        AuditLogger::log($request, 'update data (save)', $ac->id);

        return $this->ok(['summary' => $summaries]);
    }

    public function submit(Request $request)
    {
        $payload = $request->validate([
            'periodid' => 'required|integer',
            'countryid' => 'required|integer',
        ]);

        $ac = AssessmentCountry::where('id', $payload['countryid'])
            ->where('period_id', $payload['periodid'])
            ->firstOrFail();

        if (!$request->user()->isAdmin() && $request->user()->country_code !== $ac->country_code) {
            return $this->fail('Assessment not found', 404);
        }

        $period = AssessmentPeriod::findOrFail($payload['periodid']);
        if (!$period->active) {
            return $this->fail('Assessment period is completed', 409);
        }

        if (!$ac->is_submitted) {
            $ac->update([
                'is_submitted' => 1,
                'modified_by' => (int) auth()->id(),
            ]);
        }

        $summaries = ScoreService::recomputeAndPersist($ac);

        AuditLogger::log($request, 'submit assessment', $ac->id);

        return $this->ok([
            'assessment_country' => [
                'id' => $ac->id,
                'country_code' => $ac->country_code,
                'is_submitted' => true,
            ],
            'summary' => $summaries,
        ]);
    }
}
