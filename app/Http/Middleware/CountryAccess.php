<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Ensures non-admin users can only access their own country_code.
 *
 * Expected route parameter: `country_code` (or `countryCode`).
 */
class CountryAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (!$user) {
            abort(401);
        }

        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return $next($request);
        }

        $param = $request->route('country_code') ?? $request->route('countryCode');
        if ($param && $param !== $user->country_code) {
            abort(404, 'Assessment not found');
        }

        return $next($request);
    }
}
