<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use App\Services\AuditLogger;

class LogLogin
{
    public function handle(Login $event): void
    {
        $request = request();
        AuditLogger::log($request, 'login', 0);
    }
}
