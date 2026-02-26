<?php

namespace App\Providers;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Listeners\LogLogin;
use App\Listeners\LogPasswordReset;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Login::class => [
            LogLogin::class,
        ],
        PasswordReset::class => [
            LogPasswordReset::class,
        ],
    ];
}
