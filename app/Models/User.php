<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;
    
    protected $table = "bd_contacts";
    protected $primaryKey = 'id';
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'person_name',
        'email',
        'password',
        'country_code',
        'event'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    
    protected static function booted(): void
    {
        static::addGlobalScope('opendata_event', function (\Illuminate\Database\Eloquent\Builder $builder) {
            $builder->where('event', 'opendata');
        });
    }
    
    public function isAdmin(): bool
    {
        return $this->country_code === config('opendata.admin_country_code');
    }

    public function getNameAttribute($value): ?string
    {
        if (!empty($value)) {
            return $value;
        }

        return $this->attributes['person_name'] ?? null;
    }

    public function setNameAttribute($value): void
    {
        $this->attributes['person_name'] = $value;
    }
}
