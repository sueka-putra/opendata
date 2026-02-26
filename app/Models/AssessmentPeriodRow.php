<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentPeriodRow extends Model
{
    protected $table = 'od_trx_assessment_period_rows';

    public const CREATED_AT = 'created_date';
    public const UPDATED_AT = 'modified_date';

    protected $guarded = [];

    public function section(){ return $this->belongsTo(Section::class, 'section_id'); }
    public function category(){ return $this->belongsTo(Category::class, 'category_id'); }
    public function indicator(){ return $this->belongsTo(Indicator::class, 'indicator_id'); }
    public function aggregation(){ return $this->belongsTo(Aggregation::class, 'sub_indicator_id'); }
}
