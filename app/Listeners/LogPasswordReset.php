<?php

namespace App\Listeners;

use Illuminate\Auth\Events\PasswordReset;
use App\Services\AuditLogger;

class LogPasswordReset
{
    public function handle(PasswordReset $event): void
    {
        $request = request();
        AuditLogger::log($request, 'reset', 0);
    }
}
