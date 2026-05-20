<?php

namespace App\Http\Controllers\Api\Trx;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\JsonEnvelope;
use App\Models\AssessmentCountry;
use App\Models\AssessmentPeriod;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CountryApiController extends Controller
{
    use JsonEnvelope;

    private function sanitizeTemplateScore($value, array $allowed): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        $num = round((float) $value, 2);
        foreach ($allowed as $a) {
            if (abs($num - (float) $a) < 0.0001) {
                return (float) $a;
            }
        }

        return null;
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
                'template_file' => $ac->template_file,
                'template_ori' => $ac->template_ori,
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

    public function templatePrefixes(int $assessmentCountryId)
    {
        $ac = AssessmentCountry::findOrFail($assessmentCountryId);
        $period = AssessmentPeriod::findOrFail((int) $ac->period_id);

        $prefixes = DB::table('od_mst_configuration_rows')
            ->where('config_id', (int) $period->config_id)
            ->whereNotNull('prefix')
            ->orderBy('seq_no')
            ->orderBy('id')
            ->pluck('prefix')
            ->map(fn ($p) => strtoupper(trim((string) $p)))
            ->filter(fn ($p) => $p !== '')
            ->values();

        return $this->ok([
            'assessment_country_id' => (int) $ac->id,
            'country_code' => (string) $ac->country_code,
            'prefixes' => $prefixes,
        ]);
    }

    public function uploadTemplate(Request $request)
    {
        $payload = $request->validate([
            'periodid' => 'required|integer',
            'countryid' => 'required|integer',
            'rows' => 'required|array',
            'rows.*.code' => 'required|string|max:100',
            'rows.*.source_row' => 'nullable|integer|min:1',
            'rows.*.series' => 'nullable|string|max:'.\App\Models\AssessmentCountryRow::SERIES_MAX_LENGTH,
            'rows.*.count_all' => 'nullable|numeric',
            'rows.*.count_5' => 'nullable|numeric',
            'rows.*.count_10' => 'nullable|numeric',
            'rows.*.c1' => 'nullable|numeric',
            'rows.*.c2' => 'nullable|numeric',
            'rows.*.c3' => 'nullable|numeric',
            'rows.*.coverage_sub_score' => 'nullable|numeric',
            'rows.*.machine_readability' => 'nullable|numeric|in:-1,0,1',
            'rows.*.proprietary' => 'nullable|numeric|in:-1,0,1',
            'rows.*.download_options' => 'nullable|numeric|in:-1,0,0.5,1',
            'rows.*.metadata' => 'nullable|numeric|in:-1,0,0.5,1',
            'rows.*.term_of_use' => 'nullable|numeric|in:-1,0,0.5,1',
            'rows.*.opennes_sub_score' => 'nullable|numeric',
            'rows.*.urls' => 'nullable|string',
            'rows.*.remarks' => 'nullable|string',
            'summary' => 'required|array',
            'summary.sections' => 'required|array|min:1',
            'summary.sections.*.coverage_max_score' => 'required|numeric',
            'summary.sections.*.coverage_actual_score' => 'required|numeric',
            'summary.sections.*.coverage_sub_score_ratio' => 'required|numeric',
            'summary.sections.*.opennes_max_score' => 'required|numeric',
            'summary.sections.*.opennes_actual_score' => 'required|numeric',
            'summary.sections.*.opennes_sub_score_ratio' => 'required|numeric',
            'summary.sections.*.overall_score_ratio' => 'required|numeric',
            'summary.weighted' => 'required|array',
            'summary.weighted.coverage_sub_score_ratio' => 'required|numeric',
            'summary.weighted.opennes_sub_score_ratio' => 'required|numeric',
            'summary.weighted.overall_score_ratio' => 'required|numeric',
        ]);

        $ac = AssessmentCountry::where('id', $payload['countryid'])
            ->where('period_id', $payload['periodid'])
            ->firstOrFail();
        $period = AssessmentPeriod::findOrFail($payload['periodid']);
        if (!$period->active) {
            return $this->fail('Assessment period is completed', 409);
        }

        $configRows = DB::table('od_mst_configuration_rows')
            ->where('config_id', (int) $period->config_id)
            ->whereNotNull('prefix')
            ->orderBy('seq_no')
            ->orderBy('id')
            ->get(['id', 'prefix']);
        $configRowByPrefix = $configRows->mapWithKeys(
            fn ($r) => [strtoupper(trim((string) $r->prefix)) => (int) $r->id]
        );
        $orderedRowIds = $configRows
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        $orderedSummarySectionIds = DB::table('od_mst_configuration_rows')
            ->where('config_id', (int) $period->config_id)
            ->whereNotNull('section_id')
            ->distinct()
            ->orderBy('section_id')
            ->pluck('section_id')
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($orderedSummarySectionIds->isEmpty()) {
            return $this->fail('No section mapping found for active configuration.', 422);
        }

        if ($orderedSummarySectionIds->count() < count($payload['summary']['sections'])) {
            return $this->fail('Summary section count does not match existing assessment structure.', 422);
        }

        $ratioToPercent = static fn ($value): float => round(((float) $value) * 100, 2);

        $matched = 0;
        $unmatched = [];
        DB::transaction(function () use ($payload, $ac, $configRowByPrefix, $orderedRowIds, $orderedSummarySectionIds, $ratioToPercent, &$matched, &$unmatched) {
            foreach ($payload['rows'] as $idx => $r) {
                $prefix = strtoupper(trim((string) ($r['code'] ?? '')));
                $sourceRow = isset($r['source_row']) ? (int) $r['source_row'] : null;
                $rowId = 0;
                if ($prefix !== '' && $configRowByPrefix->has($prefix)) {
                    $rowId = (int) $configRowByPrefix->get($prefix);
                } elseif (preg_match('/^__ORDER__(\d+)$/', $prefix, $m) === 1) {
                    $order = max(1, (int) ($m[1] ?? 0));
                    $mapped = $orderedRowIds->get($order - 1);
                    $rowId = (int) ($mapped ?? 0);
                } else {
                    $mapped = $orderedRowIds->get((int) $idx);
                    $rowId = (int) ($mapped ?? 0);
                }

                if ($rowId <= 0) {
                    $unmatched[] = [
                        'code' => $prefix,
                        'source_row' => $sourceRow,
                        'reason' => 'Code not mapped to active configuration rows.',
                    ];
                    continue;
                }
                $series = trim((string) ($r['series'] ?? ''));
                $isSeriesNa = strtoupper($series) === 'NA';
                $countAll = isset($r['count_all']) ? (float) $r['count_all'] : null;
                $count5 = isset($r['count_5']) ? (float) $r['count_5'] : null;
                $count10 = isset($r['count_10']) ? (float) $r['count_10'] : null;
                $c1 = isset($r['c1']) ? (float) $r['c1'] : null;
                $c2 = isset($r['c2']) ? (float) $r['c2'] : null;
                $c3 = isset($r['c3']) ? (float) $r['c3'] : null;
                $coverageSub = isset($r['coverage_sub_score']) ? (float) $r['coverage_sub_score'] : null;
                $machineReadability = $this->sanitizeTemplateScore($r['machine_readability'] ?? null, [-1.0, 0.0, 1.0]);
                $proprietary = $this->sanitizeTemplateScore($r['proprietary'] ?? null, [-1.0, 0.0, 1.0]);
                $downloadOptions = $this->sanitizeTemplateScore($r['download_options'] ?? null, [-1.0, 0.0, 0.5, 1.0]);
                $metadata = $this->sanitizeTemplateScore($r['metadata'] ?? null, [-1.0, 0.0, 0.5, 1.0]);
                $termOfUse = $this->sanitizeTemplateScore($r['term_of_use'] ?? null, [-1.0, 0.0, 0.5, 1.0]);
                $opennesSub = isset($r['opennes_sub_score']) ? (float) $r['opennes_sub_score'] : null;
                $urls = isset($r['urls']) ? mb_substr((string) $r['urls'], 0, 3000) : null;
                $remarks = isset($r['remarks']) ? mb_substr((string) $r['remarks'], 0, 3000) : null;

                if ($isSeriesNa) {
                    $countAll = null;
                    $count5 = null;
                    $count10 = null;
                    $c1 = null;
                    $c2 = null;
                    $c3 = null;
                    $coverageSub = 0;
                    $machineReadability = -1;
                    $proprietary = -1;
                    $downloadOptions = -1;
                    $metadata = -1;
                    $termOfUse = -1;
                    $opennesSub = 0;
                }

                DB::table('od_trx_assessment_country_rows')->updateOrInsert(
                    [
                        'assessment_country_id' => $ac->id,
                        'row_id' => $rowId,
                    ],
                    [
                        'series' => $series,
                        'count_all' => $countAll,
                        'count_5' => $count5,
                        'count_10' => $count10,
                        'c1' => $c1,
                        'c2' => $c2,
                        'c3' => $c3,
                        'coverage_sub_score' => $coverageSub,
                        'machine_readability' => $machineReadability,
                        'proprietary' => $proprietary,
                        'download_options' => $downloadOptions,
                        'metadata' => $metadata,
                        'term_of_use' => $termOfUse,
                        'opennes_sub_score' => $opennesSub,
                        'urls' => $urls,
                        'remarks' => $remarks,
                    ]
                );
                $matched++;
            }

            foreach ($payload['summary']['sections'] as $idx => $section) {
                $sectionId = (int) $orderedSummarySectionIds[$idx];
                DB::table('od_trx_assessment_summaries')->updateOrInsert(
                    [
                        'assessment_country_id' => (int) $ac->id,
                        'section_id' => $sectionId,
                    ],
                    [
                        'coverage_max_score' => round((float) $section['coverage_max_score'], 2),
                        'coverage_actual_score' => round((float) $section['coverage_actual_score'], 2),
                        'coverage_sub_score' => $ratioToPercent($section['coverage_sub_score_ratio']),
                        'opennes_max_score' => round((float) $section['opennes_max_score'], 2),
                        'opennes_actual_score' => round((float) $section['opennes_actual_score'], 2),
                        'opennes_sub_score' => $ratioToPercent($section['opennes_sub_score_ratio']),
                        'overall_score' => $ratioToPercent($section['overall_score_ratio']),
                    ]
                );
            }

            $weighted = $payload['summary']['weighted'];
            DB::table('od_trx_assessment_summaries')->updateOrInsert(
                [
                    'assessment_country_id' => (int) $ac->id,
                    'section_id' => 0,
                ],
                [
                    'coverage_max_score' => 0,
                    'coverage_actual_score' => 0,
                    'coverage_sub_score' => $ratioToPercent($weighted['coverage_sub_score_ratio']),
                    'opennes_max_score' => 0,
                    'opennes_actual_score' => 0,
                    'opennes_sub_score' => $ratioToPercent($weighted['opennes_sub_score_ratio']),
                    'overall_score' => $ratioToPercent($weighted['overall_score_ratio']),
                ]
            );

            $ac->update(['modified_by' => (int) auth()->id()]);
        });

        AuditLogger::log($request, 'Populate from template (admin)', $ac->id);

        return $this->ok([
            'uploaded' => count($payload['rows']),
            'matched' => $matched,
            'unmatched' => $unmatched,
            'saved_summary_sections' => count($payload['summary']['sections']),
        ]);
    }

    public function attachTemplate(Request $request)
    {
        $payload = $request->validate([
            'periodid' => 'required|integer',
            'countryid' => 'required|integer',
            'template' => 'required|file|max:20480',
        ]);

        $ac = AssessmentCountry::where('id', (int) $payload['countryid'])
            ->where('period_id', (int) $payload['periodid'])
            ->firstOrFail();
        $period = AssessmentPeriod::findOrFail((int) $payload['periodid']);
        if (!$period->active) {
            return $this->fail('Assessment period is completed', 409);
        }

        $file = $request->file('template');
        if (!$file) {
            return $this->fail('Template file is required', 422);
        }

        $originalName = (string) $file->getClientOriginalName();
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $ext = strtolower((string) $file->getClientOriginalExtension());
        $safeBase = Str::limit(Str::slug($baseName, '_'), 40, '');
        $safeBase = $safeBase !== '' ? $safeBase : 'template';
        $safeExt = preg_replace('/[^a-z0-9]/', '', $ext) ?: 'bin';
        $uniqueName = sprintf(
            'p%d_c%d_%s_%s.%s',
            (int) $ac->period_id,
            (int) $ac->id,
            $safeBase,
            now('UTC')->format('YmdHis'),
            $safeExt
        );
        $dir = 'trx/templates';

        DB::transaction(function () use ($ac, $file, $dir, $uniqueName, $originalName) {
            $previousFile = trim((string) ($ac->template_file ?? ''));
            if ($previousFile !== '') {
                Storage::disk('local')->delete($dir.'/'.$previousFile);
            }

            Storage::disk('local')->putFileAs($dir, $file, $uniqueName);

            $ac->update([
                'template_file' => $uniqueName,
                'template_ori' => Str::limit($originalName, 100, ''),
                'modified_by' => (int) auth()->id(),
            ]);
        });

        AuditLogger::log($request, 'attach template file (admin)', $ac->id);

        return $this->ok([
            'assessment_country_id' => (int) $ac->id,
            'period_id' => (int) $ac->period_id,
            'country_code' => (string) $ac->country_code,
            'template_file' => $uniqueName,
            'template_ori' => Str::limit($originalName, 100, ''),
            'storage_path' => 'storage/app/'.$dir.'/'.$uniqueName,
        ]);
    }
}
