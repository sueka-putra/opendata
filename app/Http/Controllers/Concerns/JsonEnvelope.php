<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\JsonResponse;

trait JsonEnvelope
{
    protected function ok($data = null, string $message = 'success', int $http = 200): JsonResponse
    {
        $payload = ['status' => 'success', 'message' => $message];
        if (!is_null($data)) {
            $payload['data'] = $data;
        }
        return response()->json($payload, $http);
    }

    protected function fail(string $message = 'error', int $http = 400, $data = null): JsonResponse
    {
        $payload = ['status' => 'error', 'message' => $message];
        if (!is_null($data)) {
            $payload['data'] = $data;
        }
        return response()->json($payload, $http);
    }
}
