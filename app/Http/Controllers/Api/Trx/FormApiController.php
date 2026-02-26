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

        $detail = DB::table('od_trx_assessment_country_rows as cr')
            ->join('od_trx_assessment_period_rows as pr', 'cr.assessment_period_row_id', '=', 'pr.id')
            ->join('od_mst_sections as s', 'pr.section_id', '=', 's.id')
            ->join('od_mst_categories as c', 'pr.category_id', '=', 'c.id')
            ->join('od_mst_indicators as i', 'pr.indicator_id', '=', 'i.id')
            ->join('od_mst_aggregations as a', 'pr.sub_indicator_id', '=', 'a.id')
            ->select([
                'cr.id as country_row_id',
                'cr.assessment_period_row_id',
                'pr.section_id',
                'pr.category_id',
                'pr.indicator_id',
                'pr.sub_indicator_id',
                's.title as section_title',
                'c.title as category_title',
                'i.title as indicator_title',
                'a.title as aggregation_title',
                'cr.series',
                'cr.machine_readability',
                'cr.proprietary',
                'cr.download_options',
                'cr.metadata',
                'cr.term_of_use',
                'cr.urls',
                'cr.remarks',
            ])
            ->where('cr.assessment_country_id', $ac->id)
            ->orderBy('pr.section_id')
            ->orderBy('pr.category_id')
            ->orderBy('pr.indicator_id')
            ->orderBy('pr.sub_indicator_id')
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
            'summary' => $summary,
        ]);
    }

    public function update(Request $request)
    {
        $payload = $request->validate([
            'periodid' => 'required|integer',
            'countryid' => 'required|integer',
            'rows' => 'required|array',
            'rows.*.assessment_period_row_id' => 'required|integer',
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
            return $this->fail('Assessment period is closed', 409);
        }

        DB::transaction(function () use ($payload, $ac) {
            foreach ($payload['rows'] as $r) {
                DB::table('od_trx_assessment_country_rows')
                    ->where('assessment_country_id', $ac->id)
                    ->where('assessment_period_row_id', $r['assessment_period_row_id'])
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
}
