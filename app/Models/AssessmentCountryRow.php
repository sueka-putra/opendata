<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentCountryRow extends Model
{
    protected $table = 'od_trx_assessment_country_rows';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'machine_readability' => 'decimal:1',
        'proprietary' => 'decimal:1',
        'download_options' => 'decimal:1',
        'metadata' => 'decimal:1',
        'term_of_use' => 'decimal:1',
    ];

    public function assessmentCountry(){ return $this->belongsTo(AssessmentCountry::class, 'assessment_country_id'); }
    public function periodRow(){ return $this->belongsTo(AssessmentPeriodRow::class, 'assessment_period_row_id'); }
}
