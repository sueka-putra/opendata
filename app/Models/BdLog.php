<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BdLog extends Model
{
    protected $table = 'bd_logs';

    public const CREATED_AT = 'event_date';
    public const UPDATED_AT = 'event_date';

    protected $guarded = [];
}
