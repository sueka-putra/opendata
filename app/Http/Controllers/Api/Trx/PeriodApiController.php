<?php

namespace App\Http\Controllers\Api\Trx;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\JsonEnvelope;
use App\Models\AssessmentCountry;
use App\Models\AssessmentCountryRow;
use App\Models\AssessmentPeriod;
use App\Models\Country;
use App\Services\AuditLogger;
use App\Services\ScoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PeriodApiController extends Controller
{
    use JsonEnvelope;

    public function index()
    {
        $periods = DB::table('od_trx_assessment_periods as p')
            ->leftJoin('od_mst_configurations as cfg', 'p.config_id', '=', 'cfg.id')
            ->orderByDesc('p.year')
            ->select([
                'p.id',
                'p.year',
                'p.description',
                'p.active',
                'p.config_id',
                'p.closed_date',
                'p.created_date',
                'p.modified_date',
                'cfg.title as config_title',
            ])
            ->get();
        return $this->ok($periods);
    }

    public function configurations()
    {
        $configs = DB::table('od_mst_configurations as cfg')
            ->leftJoin('od_mst_configuration_rows as r', 'r.config_id', '=', 'cfg.id')
            ->groupBy('cfg.id', 'cfg.title', 'cfg.description')
            ->orderByDesc('cfg.id')
            ->select([
                'cfg.id',
                'cfg.title',
                'cfg.description',
                DB::raw('COUNT(r.id) as row_count'),
            ])
            ->get();

        return $this->ok($configs);
    }

    public function configurationRows(int $configId)
    {
        $exists = DB::table('od_mst_configurations')->where('id', $configId)->exists();
        if (!$exists) {
            abort(404);
        }

        $rows = $this->fetchConfigurationRows($configId);
        return $this->ok($rows);
    }

    public function rows(int $periodId)
    {
        $period = AssessmentPeriod::findOrFail($periodId);
        $rows = $this->fetchConfigurationRows((int) $period->config_id);

        return $this->ok($rows);
    }

    public function store(Request $request)
    {
        $hasAseanstatsOnly = Schema::hasColumn('od_mst_configuration_rows', 'aseanstats_only');

        $data = $request->validate([
            'year' => 'required|integer|min:2000|max:2100',
            'description' => 'required|string|max:300',
            'config_id' => 'required|integer|exists:od_mst_configurations,id',
            'aseanstats_only_rows' => 'nullable|array',
            'aseanstats_only_rows.*.id' => 'required_with:aseanstats_only_rows|integer',
            'aseanstats_only_rows.*.aseanstats_only' => $hasAseanstatsOnly
                ? 'required_with:aseanstats_only_rows|boolean'
                : 'nullable',
        ]);

        $hasActive = AssessmentPeriod::where('active', 1)->exists();
        if ($hasActive) {
            return $this->fail('Active assessment period already exists', 409);
        }

        $yearExists = AssessmentPeriod::where('year', $data['year'])->exists();
        if ($yearExists) {
            return $this->fail('Selected year already exists. Please choose a different year.', 409);
        }

        $configRowIds = DB::table('od_mst_configuration_rows')
            ->where('config_id', $data['config_id'])
            ->orderBy('seq_no')
            ->pluck('id');
        if ($configRowIds->isEmpty()) {
            return $this->fail('Selected configuration has no rows', 422);
        }

        $userId = (int) $request->user()->id;

        $periodId = null;

        $flagPayload = collect($data['aseanstats_only_rows'] ?? [])
            ->filter(fn ($r) => is_array($r) && isset($r['id']) && array_key_exists('aseanstats_only', $r))
            ->mapWithKeys(function ($r) {
                return [(int) $r['id'] => (int) ((bool) $r['aseanstats_only'])];
            });

        DB::transaction(function () use ($data, $userId, $configRowIds, $hasAseanstatsOnly, $flagPayload, &$periodId) {
            if ($hasAseanstatsOnly && $flagPayload->isNotEmpty()) {
                $allowedRowIds = $configRowIds->map(fn ($id) => (int) $id)->all();
                foreach ($flagPayload as $rowId => $flag) {
                    if (!in_array((int) $rowId, $allowedRowIds, true)) {
                        continue;
                    }
                    DB::table('od_mst_configuration_rows')
                        ->where('config_id', $data['config_id'])
                        ->where('id', (int) $rowId)
                        ->update(['aseanstats_only' => (int) $flag]);
                }
            }

            $period = AssessmentPeriod::create([
                'year' => $data['year'],
                'description' => $data['description'],
                'active' => 1,
                'config_id' => $data['config_id'],
                'created_by' => $userId,
                'modified_by' => $userId,
            ]);
            $periodId = $period->id;

            // Create assessed entities from ASEAN countries only
            $countryCodes = Country::where('is_asean', 1)->pluck('code')->toArray();

            foreach ($countryCodes as $cc) {
                $ac = AssessmentCountry::create([
                    'period_id' => $period->id,
                    'country_code' => $cc,
                    'is_submitted' => 0,
                    'created_by' => $userId,
                    'modified_by' => $userId,
                ]);

                // Pre-create detail rows
                foreach ($configRowIds as $configRowId) {
                    AssessmentCountryRow::create([
                        'assessment_country_id' => $ac->id,
                        'row_id' => $configRowId,
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

    private function fetchConfigurationRows(int $configId)
    {
        $query = DB::table('od_mst_configuration_rows as r')
            ->join('od_mst_sections as s', 'r.section_id', '=', 's.id')
            ->join('od_mst_categories as c', 'r.category_id', '=', 'c.id')
            ->join('od_mst_indicators as i', 'r.indicator_id', '=', 'i.id')
            ->leftJoin('od_mst_aggregations as a', 'r.sub_indicator_id', '=', 'a.id')
            ->where('r.config_id', $configId)
            ->orderBy('r.seq_no')
            ->orderBy('r.id');

        $select = [
            'r.id',
            'r.seq_no',
            'r.section_id',
            'r.category_id',
            'r.indicator_id',
            'r.sub_indicator_id',
            's.title as section_title',
            'c.title as category_title',
            'i.title as indicator_title',
            'a.title as disaggregation_title',
        ];

        if (Schema::hasColumn('od_mst_configuration_rows', 'aseanstats_only')) {
            $select[] = 'r.aseanstats_only';
        }

        return $query->get($select);
    }
}
