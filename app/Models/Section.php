<?php

namespace App\Models;

class Section extends BaseLegacyModel
{
    protected $table = 'od_mst_sections';

    protected $casts = [
        'active' => 'boolean',
        'prefix' => 'string',
    ];

    public function periodRows()
    {
        return $this->hasMany(AssessmentPeriodRow::class, 'section_id');
    }
}
