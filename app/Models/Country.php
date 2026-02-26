<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $table = 'countries';
    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';

    public const CREATED_AT = 'created_date';
    public const UPDATED_AT = null;

    protected $casts = [
        'is_asean' => 'boolean',
    ];

    protected $guarded = [];
}
