<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Base model for legacy tables that use created_date / modified_date.
 */
abstract class BaseLegacyModel extends Model
{
    public const CREATED_AT = 'created_date';
    public const UPDATED_AT = 'modified_date';

    protected $guarded = [];
}
