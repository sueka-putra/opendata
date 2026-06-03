<?php

namespace App\Models;

class AssessmentCountry extends BaseLegacyModel
{
    protected $table = 'od_trx_assessment_countries';

    protected $casts = [
        'is_submitted' => 'boolean',
    ];

    protected $fillable = [
        'period_id',
        'country_code',
        'is_submitted',
        'template_file',
        'template_ori',
        'created_by',
        'modified_by',
    ];

    public function period(){ return $this->belongsTo(AssessmentPeriod::class, 'period_id'); }
    public function country(){ return $this->belongsTo(Country::class, 'country_code', 'code'); }
    public function rows(){ return $this->hasMany(AssessmentCountryRow::class, 'assessment_country_id'); }
    public function summaries(){ return $this->hasMany(AssessmentSummary::class, 'assessment_country_id'); }
}
