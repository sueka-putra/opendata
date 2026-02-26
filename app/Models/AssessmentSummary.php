<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentSummary extends Model
{
    protected $table = 'od_trx_assessment_summaries';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'coverage_max_score' => 'decimal:2',
        'coverage_actual_score' => 'decimal:2',
        'coverage_sub_score' => 'decimal:2',
        'opennes_max_score' => 'decimal:2',
        'opennes_actual_score' => 'decimal:2',
        'opennes_sub_score' => 'decimal:2',
        'overall_score' => 'decimal:2',
    ];

    public function section(){ return $this->belongsTo(Section::class, 'section_id'); }
}
