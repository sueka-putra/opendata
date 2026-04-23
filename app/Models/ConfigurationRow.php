<?php

namespace App\Models;

class ConfigurationRow extends BaseLegacyModel
{
    protected $table = 'od_mst_configuration_rows';

    public $timestamps = false;

    protected $casts = [
        'config_id' => 'integer',
        'seq_no' => 'integer',
        'section_id' => 'integer',
        'category_id' => 'integer',
        'indicator_id' => 'integer',
        'sub_indicator_id' => 'integer',
        'prefix' => 'string',
        'aseanstats_only' => 'boolean',
    ];
}
