<?php

namespace Pterodactyl\Http\Controllers\Api\Client;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Repositories\Wings\DaemonPowerRepository;

/**
 * Public health endpoint for Panasaurus.
 *
 * Returns a lightweight, unauthenticated health summary suitable for
 * uptime monitors, orchestrators, and the admin status widget.
 */
class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $startedAt = microtime(true);

        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'panel' => $this->checkPanel(),
        ];

        $healthy = !in_array(false, array_column($checks, 'healthy'), true);

        return JsonResponse::create([
            'status' => $healthy ? 'ok' : 'degraded',
            'checks' => $checks,
            'version' => config('app.version'),
            'blueprint' => [
                'installed' => file_exists(base_path('.blueprint/extensions/blueprint/private/db/is_installed')),
                'version' => $this->blueprintVersion(),
            ],
            'latency_ms' => round((microtime(true) - $startedAt) * 1000, 2),
            'timestamp' => now()->toIso8601String(),
        ], $healthy ? 200 : 503)->withHeaders([
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    private function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::select('SELECT 1');

            return [
                'healthy' => true,
                'latency_ms' => round((microtime(true) - $start) * 1000, 2),
            ];
        } catch (\Throwable $e) {
            return ['healthy' => false, 'error' => 'database unavailable'];
        }
    }

    private function checkRedis(): array
    {
        try {
            if (config('cache.default') !== 'redis') {
                return ['healthy' => true, 'skipped' => true];
            }

            $start = microtime(true);
            Redis::connection()->ping();

            return [
                'healthy' => true,
                'latency_ms' => round((microtime(true) - $start) * 1000, 2),
            ];
        } catch (\Throwable $e) {
            return ['healthy' => false, 'error' => 'redis unavailable'];
        }
    }

    private function checkPanel(): array
    {
        $writable = [
            'storage' => is_writable(storage_path()),
            'bootstrap-cache' => is_writable(base_path('bootstrap/cache')),
        ];

        return [
            'healthy' => !in_array(false, $writable, true),
            'writable' => $writable,
        ];
    }

    private function blueprintVersion(): ?string
    {
        $file = base_path('.blueprint/extensions/blueprint/private/db/version');

        return file_exists($file) ? trim(file_get_contents($file)) : null;
    }
}
