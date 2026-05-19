<?php

namespace App\Http\Controllers\Api\Trx;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\JsonEnvelope;
use App\Models\AssessmentCountry;
use App\Models\AssessmentCountryRow;
use App\Models\AssessmentPeriod;
use App\Models\AssessmentSummary;
use App\Models\Country;
use App\Services\AuditLogger;
use App\Services\ScoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FormApiController extends Controller
{
    use JsonEnvelope;

    private function sanitizeTemplateScore($value, array $allowed)
    {
        if ($value === null || $value === '') {
            return null;
        }
        $num = (float) $value;
        return in_array($num, $allowed, true) ? $num : null;
    }

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
        $allowExport = $isAseanstatsStaff || (bool) config('opendata.allow_export', false);

        $period = AssessmentPeriod::findOrFail($periodId);
        $configMeta = DB::table('od_mst_configurations')
            ->where('id', (int) $period->config_id)
            ->first([
                'id',
                'header_row',
                'detail_row',
                'detail_rows',
            ]);
        $templatePrefixes = DB::table('od_mst_configuration_rows')
            ->where('config_id', (int) $period->config_id)
            ->orderBy('seq_no')
            ->orderBy('id')
            ->pluck('prefix')
            ->map(fn ($prefix) => strtoupper(trim((string) $prefix)))
            ->filter(fn ($prefix) => $prefix !== '')
            ->values();
        $ac = AssessmentCountry::where('period_id', $periodId)->where('country_code', $countryCode)->firstOrFail();
        $adminCode = (string) config('opendata.admin_country_code', '00');
        $countryName = Country::query()
            ->where('code', (string) $ac->country_code)
            ->value('name');
        if (!$countryName && (string) $ac->country_code === $adminCode) {
            $countryName = 'ASEANstats';
        }

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
                'cfg.prefix',
                'cfg.section_id',
                'cfg.category_id',
                'cfg.indicator_id',
                'cfg.sub_indicator_id',
                $hasAseanstatsOnlyCol ? 'cfg.aseanstats_only' : DB::raw('0 as aseanstats_only'),
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
                'cr.count_all',
                'cr.count_5',
                'cr.count_10',
                'cr.c1',
                'cr.c2',
                'cr.c3',
                'cr.coverage_sub_score',
                'cr.opennes_sub_score',
            ])
            ->where('p.id', $periodId)
            ->where('ac.country_code', $countryCode)
            ->where('cr.assessment_country_id', $ac->id);

        $detail = $detailQuery
            ->orderBy('cfg.seq_no')
            ->get()
            ->map(function ($r) use ($isAseanstatsStaff) {
                $isRestricted = ((int) ($r->aseanstats_only ?? 0) === 1);
                if ($isRestricted && !$isAseanstatsStaff) {
                    $r->series = 'NA';
                    $r->machine_readability = -1;
                    $r->proprietary = -1;
                    $r->download_options = -1;
                    $r->metadata = -1;
                    $r->term_of_use = -1;
                }

                $isSeriesNa = strtoupper(trim((string) ($r->series ?? ''))) === 'NA';
                if ($isSeriesNa) {
                    $r->machine_readability = -1;
                    $r->proprietary = -1;
                    $r->download_options = -1;
                    $r->metadata = -1;
                    $r->term_of_use = -1;
                    $r->count_all = null;
                    $r->count_5 = null;
                    $r->count_10 = null;
                    $r->c1 = null;
                    $r->c2 = null;
                    $r->c3 = null;
                    $r->coverage_sub_score = 0;
                    $r->opennes_sub_score = 0;
                }

                $coverageSub = $r->coverage_sub_score;
                $opennessSub = $r->opennes_sub_score;

                return array_merge((array) $r, [
                    'c' => $coverageSub !== null ? (float) $coverageSub : 0.0,
                    'o' => $opennessSub !== null ? (float) $opennessSub : 0.0,
                ]);
            });

        $summaryCollection = AssessmentSummary::with('section')
            ->where('assessment_country_id', $ac->id)
            ->get();

        $summaryBase = $summaryCollection->filter(fn ($s) => (int) $s->section_id !== 0)->values();
        $weightedSummary = $summaryCollection->first(fn ($s) => (int) $s->section_id === 0);

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

            $coverageSubRatio = ((float) $s->coverage_sub_score) / 100;
            $opennessSubRatio = ((float) $s->opennes_sub_score) / 100;
            $overallRatio = (0.5 * $coverageSubRatio) + (0.5 * $opennessSubRatio);

            return array_merge($s->toArray(), $progress, [
                'coverage_sub_score_ratio' => round($coverageSubRatio, 6),
                'opennes_sub_score_ratio' => round($opennessSubRatio, 6),
                'overall_score_ratio' => round($overallRatio, 6),
            ]);
        })->values();

        $weightedCoverage = $weightedSummary ? (((float) $weightedSummary->coverage_sub_score) / 100) : 0;
        $weightedOpenness = $weightedSummary ? (((float) $weightedSummary->opennes_sub_score) / 100) : 0;
        $weightedOverall = $weightedSummary ? (((float) $weightedSummary->overall_score) / 100) : ((0.5 * $weightedCoverage) + (0.5 * $weightedOpenness));

        return $this->ok([
            'period' => [
                'id' => $period->id,
                'title' => $period->title,
                'year' => $period->year,
                'active' => (bool) $period->active,
                'description' => $period->description,
                'config_id' => (int) $period->config_id,
            ],
            'upload_template' => [
                'header_row' => (int) ($configMeta->header_row ?? 3),
                'detail_row' => (int) ($configMeta->detail_row ?? 5),
                'detail_rows' => (int) ($configMeta->detail_rows ?? 0),
                'prefixes' => $templatePrefixes,
            ],
            'assessment_country' => [
                'id' => $ac->id,
                'country_code' => $ac->country_code,
                'country_name' => (string) ($countryName ?? ''),
                'is_submitted' => (bool) $ac->is_submitted,
            ],
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
            'viewer' => [
                'is_aseanstats_staff' => $isAseanstatsStaff,
                'can_export' => $allowExport,
            ],
        ]);
    }

    public function logs(Request $request)
    {
        $periodId = (int) $request->query('periodid');
        $countryCode = (string) $request->query('country_code', $request->user()->country_code);

        if (!$request->user()->isAdmin() && $request->user()->country_code !== $countryCode) {
            return $this->fail('Assessment not found', 404);
        }

        $auditEvent = (string) config('opendata.audit_event', 'opendata');
        $logs = DB::table('bd_logs as l')
            ->join('bd_contacts as c', function ($join) use ($auditEvent) {
                $join->on('c.email', '=', 'l.email')
                    ->whereRaw('LOWER(c.event) = ?', [strtolower($auditEvent)]);
            })
            ->join('od_trx_assessment_countries as cr', 'cr.id', '=', 'l.header_id')
            ->join('od_trx_assessment_periods as p', 'p.id', '=', 'cr.period_id')
            ->whereRaw('LOWER(l.event) = ?', [strtolower($auditEvent)])
            ->where('p.id', $periodId)
            ->where('cr.country_code', $countryCode)
            ->orderByDesc('l.event_date')
            ->orderByDesc('l.id')
            ->select([
                'l.id',
                'l.event_date',
                'l.email',
                'l.note',
                DB::raw("COALESCE(NULLIF(c.person_name, ''), '-') as actor_name"),
            ])
            ->get()
            ->map(function ($row) {
                return [
                    'id' => (int) $row->id,
                    'event_date' => $row->event_date,
                    'actor_name' => (string) ($row->actor_name ?? '-'),
                    'actor_email' => (string) ($row->email ?? ''),
                    'action_text' => (string) ($row->note ?? ''),
                ];
            })
            ->values();

        return $this->ok([
            'logs' => $logs,
        ]);
    }

    public function update(Request $request)
    {
        $payload = $request->validate([
            'periodid' => 'required|integer',
            'countryid' => 'required|integer',
            'rows' => 'required|array',
            'rows.*.row_id' => 'required|integer',
            'rows.*.series' => 'nullable|string|max:'.AssessmentCountryRow::SERIES_MAX_LENGTH,
            'rows.*.machine_readability' => 'nullable|numeric|in:-1,0,1',
            'rows.*.proprietary' => 'nullable|numeric|in:-1,0,1',
            'rows.*.download_options' => 'nullable|numeric|in:-1,0,0.5,1',
            'rows.*.metadata' => 'nullable|numeric|in:-1,0,0.5,1',
            'rows.*.term_of_use' => 'nullable|numeric|in:-1,0,0.5,1',
            'rows.*.urls' => 'nullable|string|max:'.AssessmentCountryRow::URLS_MAX_LENGTH,
            'rows.*.remarks' => 'nullable|string|max:'.AssessmentCountryRow::REMARKS_MAX_LENGTH,
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
        $restrictedRequestedRowIds = collect();
        if ($schema->hasColumn('od_mst_configuration_rows', 'aseanstats_only') && !$isAseanstatsStaff) {
            $requestedRowIds = collect($payload['rows'] ?? [])
                ->pluck('row_id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->values();
            if ($requestedRowIds->isNotEmpty()) {
                $restrictedRequestedRowIds = DB::table('od_mst_configuration_rows')
                    ->where('config_id', (int) $period->config_id)
                    ->whereIn('id', $requestedRowIds)
                    ->where('aseanstats_only', 1)
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->values();
            }
        }

        DB::transaction(function () use ($payload, $ac, $isAseanstatsStaff, $restrictedRequestedRowIds) {
            foreach ($payload['rows'] as $r) {
                $rowId = (int) ($r['row_id'] ?? 0);
                $isRestricted = !$isAseanstatsStaff && $restrictedRequestedRowIds->contains($rowId);
                $series = $isRestricted
                    ? 'NA'
                    : trim((string) ($r['series'] ?? ''));
                $isSeriesNa = strtoupper($series) === 'NA';
                $machineReadability = array_key_exists('machine_readability', $r) ? $r['machine_readability'] : null;
                $proprietary = array_key_exists('proprietary', $r) ? $r['proprietary'] : null;
                $downloadOptions = array_key_exists('download_options', $r) ? $r['download_options'] : null;
                $metadata = array_key_exists('metadata', $r) ? $r['metadata'] : null;
                $termOfUse = array_key_exists('term_of_use', $r) ? $r['term_of_use'] : null;
                $urls = $isRestricted ? null : ($r['urls'] ?? null);
                $remarks = $isRestricted ? null : ($r['remarks'] ?? null);

                if ($isSeriesNa) {
                    $machineReadability = -1;
                    $proprietary = -1;
                    $downloadOptions = -1;
                    $metadata = -1;
                    $termOfUse = -1;
                }

                DB::table('od_trx_assessment_country_rows')
                    ->updateOrInsert(
                        [
                            'assessment_country_id' => $ac->id,
                            'row_id' => $rowId,
                        ],
                        [
                        'series' => $series,
                        'machine_readability' => $machineReadability,
                        'proprietary' => $proprietary,
                        'download_options' => $downloadOptions,
                        'metadata' => $metadata,
                        'term_of_use' => $termOfUse,
                        'urls' => $urls,
                        'remarks' => $remarks,
                        ]
                    );
            }

            // mark as modified
            $ac->update(['modified_by' => (int) auth()->id()]);
        });

        // recompute server-side
        $summaries = ScoreService::recomputeAndPersist($ac);

        AuditLogger::log($request, 'update data (save)', $ac->id);

        return $this->ok(['summary' => $summaries]);
    }

    public function uploadTemplate(Request $request)
    {
        $payload = $request->validate([
            'periodid' => 'required|integer',
            'countryid' => 'required|integer',
            'rows' => 'required|array',
            'rows.*.code' => 'required|string|max:100',
            'rows.*.source_row' => 'nullable|integer|min:1',
            'rows.*.series' => 'nullable|string|max:'.AssessmentCountryRow::SERIES_MAX_LENGTH,
            'rows.*.machine_readability' => 'nullable|numeric|in:-1,0,1',
            'rows.*.proprietary' => 'nullable|numeric|in:-1,0,1',
            'rows.*.download_options' => 'nullable|numeric|in:-1,0,0.5,1',
            'rows.*.metadata' => 'nullable|numeric|in:-1,0,0.5,1',
            'rows.*.term_of_use' => 'nullable|numeric|in:-1,0,0.5,1',
            'rows.*.urls' => 'nullable|string|max:'.AssessmentCountryRow::URLS_MAX_LENGTH,
            'rows.*.remarks' => 'nullable|string|max:'.AssessmentCountryRow::REMARKS_MAX_LENGTH,
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
        $isAseanstatsStaff = ((string) $request->user()->country_code) === '00';

        $configRows = DB::table('od_mst_configuration_rows')
            ->where('config_id', (int) $period->config_id)
            ->whereNotNull('prefix')
            ->get(['id', 'prefix', 'aseanstats_only']);

        $configRowByPrefix = $configRows->mapWithKeys(function ($r) {
            return [strtoupper(trim((string) $r->prefix)) => [
                'id' => (int) $r->id,
                'aseanstats_only' => (int) ($r->aseanstats_only ?? 0),
            ]];
        });

        $restrictedRowIds = $configRows
            ->filter(fn ($r) => (int) ($r->aseanstats_only ?? 0) === 1)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        $matched = 0;
        $unmatched = [];
        DB::transaction(function () use ($payload, $ac, $configRowByPrefix, $isAseanstatsStaff, $restrictedRowIds, &$matched, &$unmatched) {
            foreach ($payload['rows'] as $r) {
                $prefix = strtoupper(trim((string) ($r['code'] ?? '')));
                $sourceRow = isset($r['source_row']) ? (int) $r['source_row'] : null;
                if ($prefix === '' || !$configRowByPrefix->has($prefix)) {
                    $unmatched[] = [
                        'code' => $prefix,
                        'source_row' => $sourceRow,
                        'reason' => 'Code not mapped to active configuration rows.',
                    ];
                    continue;
                }
                $rowConfig = $configRowByPrefix->get($prefix);
                $rowId = (int) ($rowConfig['id'] ?? 0);
                $isRestricted = (int) ($rowConfig['aseanstats_only'] ?? 0) === 1;
                $series = $isRestricted && !$isAseanstatsStaff
                    ? 'NA'
                    : trim((string) ($r['series'] ?? ''));
                $isSeriesNa = strtoupper($series) === 'NA';

                $machineReadability = $this->sanitizeTemplateScore($r['machine_readability'] ?? null, [-1.0, 0.0, 1.0]);
                $proprietary = $this->sanitizeTemplateScore($r['proprietary'] ?? null, [-1.0, 0.0, 1.0]);
                $downloadOptions = $this->sanitizeTemplateScore($r['download_options'] ?? null, [-1.0, 0.0, 0.5, 1.0]);
                $metadata = $this->sanitizeTemplateScore($r['metadata'] ?? null, [-1.0, 0.0, 0.5, 1.0]);
                $termOfUse = $this->sanitizeTemplateScore($r['term_of_use'] ?? null, [-1.0, 0.0, 0.5, 1.0]);
                $urls = $isRestricted && !$isAseanstatsStaff ? null : ($r['urls'] ?? null);
                $remarks = $isRestricted && !$isAseanstatsStaff ? null : ($r['remarks'] ?? null);

                if ($isSeriesNa) {
                    $machineReadability = -1;
                    $proprietary = -1;
                    $downloadOptions = -1;
                    $metadata = -1;
                    $termOfUse = -1;
                }

                DB::table('od_trx_assessment_country_rows')
                    ->updateOrInsert(
                        [
                            'assessment_country_id' => $ac->id,
                            'row_id' => $rowId,
                        ],
                        [
                        'series' => $series,
                        'machine_readability' => $machineReadability,
                        'proprietary' => $proprietary,
                        'download_options' => $downloadOptions,
                        'metadata' => $metadata,
                        'term_of_use' => $termOfUse,
                        'urls' => $urls,
                        'remarks' => $remarks,
                        ]
                    );
                $matched++;
            }

            if (!$isAseanstatsStaff && $restrictedRowIds->isNotEmpty()) {
                DB::table('od_trx_assessment_country_rows')
                    ->where('assessment_country_id', $ac->id)
                    ->whereIn('row_id', $restrictedRowIds->all())
                    ->update([
                        'series' => 'NA',
                        'machine_readability' => -1,
                        'proprietary' => -1,
                        'download_options' => -1,
                        'metadata' => -1,
                        'term_of_use' => -1,
                        'urls' => null,
                        'remarks' => null,
                    ]);
            }

            $ac->update(['modified_by' => (int) auth()->id()]);
        });

        $summaries = ScoreService::recomputeAndPersist($ac);
        AuditLogger::log($request, 'upload template', $ac->id);

        return $this->ok([
            'uploaded' => count($payload['rows']),
            'matched' => $matched,
            'unmatched' => $unmatched,
            'summary' => $summaries,
        ]);
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

    public function updateSummary(Request $request)
    {
        $payload = $request->validate([
            'periodid' => 'required|integer',
            'countryid' => 'required|integer',
            'sections' => 'required|array|min:1',
            'sections.*.section_id' => 'required|integer|min:1',
            'sections.*.coverage_max_score' => 'required|numeric',
            'sections.*.coverage_actual_score' => 'required|numeric',
            'sections.*.coverage_sub_score' => 'required|numeric',
            'sections.*.opennes_max_score' => 'required|numeric',
            'sections.*.opennes_actual_score' => 'required|numeric',
            'sections.*.opennes_sub_score' => 'required|numeric',
            'sections.*.overall_score' => 'required|numeric',
            'weighted' => 'required|array',
            'weighted.coverage_sub_score' => 'required|numeric',
            'weighted.opennes_sub_score' => 'required|numeric',
            'weighted.overall_score' => 'required|numeric',
        ]);

        $isAseanstatsStaff = ((string) $request->user()->country_code) === '00';
        if (!$isAseanstatsStaff) {
            return $this->fail('Forbidden', 403);
        }

        $ac = AssessmentCountry::where('id', $payload['countryid'])
            ->where('period_id', $payload['periodid'])
            ->firstOrFail();

        $ratioToPercent = static function ($value): float {
            return round(((float) $value) * 100, 2);
        };

        DB::transaction(function () use ($payload, $ac, $ratioToPercent) {
            foreach ($payload['sections'] as $row) {
                $sectionId = (int) $row['section_id'];
                DB::table('od_trx_assessment_summaries')
                    ->updateOrInsert(
                        [
                            'assessment_country_id' => (int) $ac->id,
                            'section_id' => $sectionId,
                        ],
                        [
                            'coverage_max_score' => round((float) $row['coverage_max_score'], 2),
                            'coverage_actual_score' => round((float) $row['coverage_actual_score'], 2),
                            'coverage_sub_score' => $ratioToPercent($row['coverage_sub_score']),
                            'opennes_max_score' => round((float) $row['opennes_max_score'], 2),
                            'opennes_actual_score' => round((float) $row['opennes_actual_score'], 2),
                            'opennes_sub_score' => $ratioToPercent($row['opennes_sub_score']),
                            'overall_score' => $ratioToPercent($row['overall_score']),
                        ]
                    );
            }

            $weighted = $payload['weighted'];
            DB::table('od_trx_assessment_summaries')
                ->updateOrInsert(
                    [
                        'assessment_country_id' => (int) $ac->id,
                        'section_id' => 0,
                    ],
                    [
                        'coverage_max_score' => 0,
                        'coverage_actual_score' => 0,
                        'coverage_sub_score' => $ratioToPercent($weighted['coverage_sub_score']),
                        'opennes_max_score' => 0,
                        'opennes_actual_score' => 0,
                        'opennes_sub_score' => $ratioToPercent($weighted['opennes_sub_score']),
                        'overall_score' => $ratioToPercent($weighted['overall_score']),
                    ]
                );

            $ac->update(['modified_by' => (int) auth()->id()]);
        });

        AuditLogger::log($request, 'update summary', $ac->id);

        return $this->ok(['saved' => true]);
    }
}
