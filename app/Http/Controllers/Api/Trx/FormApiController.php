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

    public function show(Request $request)
    {
        $periodId = (int) $request->query('periodid');
        $countryCode = (string) $request->query('country_code', $request->user()->country_code);

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

        $detail = DB::table('od_trx_assessment_periods as p')
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
            ->where('cr.assessment_country_id', $ac->id)
            ->orderBy('cfg.seq_no')
            ->get()
            ->map(function ($r) {
                $cov = ScoreService::computeRowCoverage((string) $r->series);
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

        $summary = AssessmentSummary::with('section')
            ->where('assessment_country_id', $ac->id)
            ->get();

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

        DB::transaction(function () use ($payload, $ac) {
            foreach ($payload['rows'] as $r) {
                DB::table('od_trx_assessment_country_rows')
                    ->where('assessment_country_id', $ac->id)
                    ->where('row_id', $r['row_id'])
                    ->update([
                        'series' => $r['series'] ?? '',
                        'machine_readability' => $r['machine_readability'] ?? 0,
                        'proprietary' => $r['proprietary'] ?? 0,
                        'download_options' => $r['download_options'] ?? 0,
                        'metadata' => $r['metadata'] ?? 0,
                        'term_of_use' => $r['term_of_use'] ?? 0,
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
