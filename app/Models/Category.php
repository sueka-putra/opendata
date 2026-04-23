<?php

namespace App\Models;

class Category extends BaseLegacyModel
{
    protected $table = 'od_mst_categories';

    protected $casts = [
        'active' => 'boolean',
        'prefix' => 'string',
    ];
}
