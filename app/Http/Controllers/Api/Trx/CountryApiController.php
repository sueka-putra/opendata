<?php

namespace App\Http\Controllers\Api\Trx;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\JsonEnvelope;
use App\Models\AssessmentCountry;
use App\Models\AssessmentPeriod;
use Illuminate\Http\Request;

class CountryApiController extends Controller
{
    use JsonEnvelope;

    public function index(int $periodId)
    {
        $period = AssessmentPeriod::with('countries.country')->findOrFail($periodId);

        $rows = $period->countries->map(function (AssessmentCountry $ac) {
            return [
                'assessment_country_id' => $ac->id,
                'country_code' => $ac->country_code,
                'country_name' => optional($ac->country)->name,
                'is_submitted' => (bool) $ac->is_submitted,
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
