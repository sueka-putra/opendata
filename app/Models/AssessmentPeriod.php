<?php

namespace App\Models;

class AssessmentPeriod extends BaseLegacyModel
{
    protected $table = 'od_trx_assessment_periods';

    protected $casts = [
        'active' => 'boolean',
        'config_id' => 'integer',
        'due_date' => 'datetime',
    ];

    public function rows()
    {
        return $this->hasMany(AssessmentPeriodRow::class, 'period_id');
    }

    public function countries()
    {
        return $this->hasMany(AssessmentCountry::class, 'period_id');
    }

    public function configuration()
    {
        return $this->belongsTo(Configuration::class, 'config_id');
    }
}
