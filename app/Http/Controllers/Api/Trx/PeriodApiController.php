<?php

namespace App\Http\Controllers\Api\Trx;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\JsonEnvelope;
use App\Models\AssessmentCountry;
use App\Models\AssessmentCountryRow;
use App\Models\AssessmentPeriod;
use App\Models\AssessmentPeriodRow;
use App\Models\Country;
use App\Services\AuditLogger;
use App\Services\ScoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PeriodApiController extends Controller
{
    use JsonEnvelope;

    public function index()
    {
        $periods = AssessmentPeriod::orderByDesc('year')->get();
        return $this->ok($periods);
    }

    public function rows(int $periodId)
    {
        AssessmentPeriod::findOrFail($periodId);

        $rows = DB::table('od_trx_assessment_period_rows as pr')
            ->join('od_mst_sections as s', 'pr.section_id', '=', 's.id')
            ->join('od_mst_categories as c', 'pr.category_id', '=', 'c.id')
            ->join('od_mst_indicators as i', 'pr.indicator_id', '=', 'i.id')
            ->join('od_mst_aggregations as a', 'pr.sub_indicator_id', '=', 'a.id')
            ->where('pr.period_id', $periodId)
            ->orderBy('pr.id')
            ->get([
                'pr.id',
                'pr.section_id',
                'pr.category_id',
                'pr.indicator_id',
                'pr.sub_indicator_id',
                's.title as section_title',
                'c.title as category_title',
                'i.title as indicator_title',
                'a.title as disaggregation_title',
            ]);

        return $this->ok($rows);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'year' => 'required|integer|min:2000|max:2100',
            'description' => 'required|string|max:300',
            'rows' => 'required|array|min:1',
            'rows.*.section' => 'required|integer',
            'rows.*.category' => 'required|integer',
            'rows.*.indicator' => 'required|integer',
            'rows.*.dissagregation' => 'required|integer',
        ]);

        $hasActive = AssessmentPeriod::where('active', 1)->exists();
        if ($hasActive) {
            return $this->fail('Active assessment period already exists', 409);
        }

        $userId = (int) $request->user()->id;

        $periodId = null;

        DB::transaction(function () use ($data, $userId, &$periodId) {
            $period = AssessmentPeriod::create([
                'year' => $data['year'],
                'description' => $data['description'],
                'active' => 1,
                'created_by' => $userId,
                'modified_by' => $userId,
            ]);
            $periodId = $period->id;

            // Insert configuration rows (ensure unique combination)
            $seen = [];
            foreach ($data['rows'] as $r) {
                $key = implode('-', [$r['section'], $r['category'], $r['indicator'], $r['dissagregation']]);
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                AssessmentPeriodRow::create([
                    'period_id' => $period->id,
                    'section_id' => $r['section'],
                    'category_id' => $r['category'],
                    'indicator_id' => $r['indicator'],
                    'sub_indicator_id' => $r['dissagregation'],
                ]);
            }

            $periodRowIds = AssessmentPeriodRow::where('period_id', $period->id)->pluck('id');

            // Create 11 AMS + ASEANstats ('00') as assessed entities
            $ams = Country::where('is_asean', 1)->pluck('code')->toArray();
            if (!in_array(config('opendata.admin_country_code', '00'), $ams, true)) {
                $ams[] = config('opendata.admin_country_code', '00');
            }

            foreach ($ams as $cc) {
                $ac = AssessmentCountry::create([
                    'period_id' => $period->id,
                    'country_code' => $cc,
                    'is_submitted' => 0,
                    'created_by' => $userId,
                    'modified_by' => $userId,
                ]);

                // Pre-create detail rows
                foreach ($periodRowIds as $prId) {
                    AssessmentCountryRow::create([
                        'assessment_country_id' => $ac->id,
                        'assessment_period_row_id' => $prId,
                        'series' => '',
                        'machine_readability' => 0,
                        'proprietary' => 0,
                        'download_options' => 0,
                        'metadata' => 0,
                        'term_of_use' => 0,
                        'urls' => null,
                        'remarks' => null,
                    ]);
                }
            }
        });

        AuditLogger::log($request, 'create period', 0);

        return $this->ok(['periodId' => $periodId]);
    }

    public function close(Request $request, int $periodId)
    {
        $period = AssessmentPeriod::findOrFail($periodId);
        if (!$period->active) {
            return $this->fail('Period already closed', 409);
        }

        DB::transaction(function () use ($period) {
            // recompute all countries
            $countries = $period->countries()->get();
            foreach ($countries as $ac) {
                ScoreService::recomputeAndPersist($ac);
            }
            $period->update(['active' => 0]);
        });

        AuditLogger::log($request, 'close period', 0);
        return $this->ok(null, 'closed');
    }
}
