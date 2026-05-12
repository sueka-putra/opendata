<?php

namespace App\Models;

class Configuration extends BaseLegacyModel
{
    protected $table = 'od_mst_configurations';

    public $timestamps = false;

    protected $casts = [
        'header_row' => 'integer',
        'detail_row' => 'integer',
        'detail_rows' => 'integer',
    ];

    public function rows()
    {
        return $this->hasMany(ConfigurationRow::class, 'config_id');
    }
}
