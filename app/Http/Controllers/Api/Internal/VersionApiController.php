<?php

namespace App\Http\Controllers\Api\Internal;

use App\Http\Controllers\Concerns\JsonEnvelope;
use App\Http\Controllers\Controller;
use Illuminate\Support\Carbon;

class VersionApiController extends Controller
{
    use JsonEnvelope;

    public function show()
    {
        $version = config('app.version');
        $commitSha = config('app.commit_sha');
        $buildDate = config('app.build_date');
        $commitDate = null;

        if (is_string($buildDate) && trim($buildDate) !== '') {
            try {
                $commitDate = Carbon::parse($buildDate)->toIso8601String();
            } catch (\Throwable) {
                $commitDate = null;
            }
        }

        if (!$commitSha || !$commitDate) {
            $git = $this->readGitMetadata();
            $commitSha = $commitSha ?: $git['commit_sha'];
            $commitDate = $commitDate ?: $git['commit_date'];
        }

        return $this->ok([
            'app_name' => config('app.name'),
            'environment' => config('app.env'),
            'version' => $version ?: 'dev',
            'commit_sha' => $commitSha,
            'commit_date' => $commitDate,
            'server_time' => now()->toIso8601String(),
        ]);
    }

    private function readGitMetadata(): array
    {
        if (!is_dir(base_path('.git')) || !function_exists('shell_exec')) {
            return ['commit_sha' => null, 'commit_date' => null];
        }

        $sha = shell_exec('git rev-parse --short HEAD');
        $date = shell_exec('git show -s --format=%cI HEAD');

        $sha = is_string($sha) ? trim($sha) : null;
        $date = is_string($date) ? trim($date) : null;

        return [
            'commit_sha' => $sha !== '' ? $sha : null,
            'commit_date' => $date !== '' ? $date : null,
        ];
    }
}
