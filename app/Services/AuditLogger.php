<?php

namespace App\Services;

use App\Models\BdLog;
use Illuminate\Http\Request;

class AuditLogger
{
    public static function log(Request $request, string $note, ?int $headerId = 0): void
    {
        try {
            BdLog::create([
                'event' => config('opendata.audit_event', 'opendata'),
                'header_id' => $headerId ?? 0,
                'email' => optional($request->user())->email ?? ($request->input('email') ?? ''),
                'note' => $note,
                'ip_address' => $request->ip() ?? '0.0.0.0',
            ]);
        } catch (\Throwable $e) {
            // Never break user flow due to logging
        }
    }
}
