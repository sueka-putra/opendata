<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class BdContact extends Authenticatable
{
    protected $table = 'bd_contacts';

    protected $hidden = ['password', 'remember_token'];

    protected $guarded = [];

    protected $casts = [
        'isSelected' => 'boolean',
        'must_change_password' => 'boolean',
        'password_generated_at' => 'datetime',
        'password_changed_at' => 'datetime',
    ];

    // NOTE: this assumes your project adds a `password` column to bd_contacts OR
    // uses a custom user provider. If your existing schema uses another user table,
    // adjust `config/auth.php` user provider accordingly.

    public function isAdmin(): bool
    {
        return $this->country_code === config('opendata.admin_country_code');
    }
}
