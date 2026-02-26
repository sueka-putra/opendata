<?php

namespace App\Models;

class Indicator extends BaseLegacyModel
{
    protected $table = 'od_mst_indicators';

    protected $casts = [
        'active' => 'boolean',
    ];
}
