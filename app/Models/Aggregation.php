<?php

namespace App\Models;

class Aggregation extends BaseLegacyModel
{
    protected $table = 'od_mst_aggregations';

    protected $casts = [
        'active' => 'boolean',
        'prefix' => 'string',
    ];
}
