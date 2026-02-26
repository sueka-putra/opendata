<?php

namespace App\Models;

class AssessmentPeriod extends BaseLegacyModel
{
    protected $table = 'od_trx_assessment_periods';

    protected $casts = [
        'active' => 'boolean',
    ];

    public function rows()
    {
        return $this->hasMany(AssessmentPeriodRow::class, 'period_id');
    }

    public function countries()
    {
        return $this->hasMany(AssessmentCountry::class, 'period_id');
    }
}
