<?php

namespace Pterodactyl\Console\Commands\Panasaurus;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class StatusCommand extends Command
{
    protected $description = 'Check Panasaurus core service health (database, redis, storage).';
    protected $signature = 'panasaurus:status';

    public function handle(): int
    {
        $this->info('  Panasaurus status check');
        $this->newLine();

        $rows = [];

        // Database
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $rows[] = ['database', 'ok', round((microtime(true) - $start) * 1000, 2) . ' ms'];
        } catch (\Throwable $e) {
            $rows[] = ['database', 'FAIL', 'connection error'];
        }

        // Redis
        try {
            if (config('cache.default') === 'redis') {
                $start = microtime(true);
                Redis::connection()->ping();
                $rows[] = ['redis', 'ok', round((microtime(true) - $start) * 1000, 2) . ' ms'];
            } else {
                $rows[] = ['redis', 'skipped', "cache driver is '" . config('cache.default') . "'"];
            }
        } catch (\Throwable $e) {
            $rows[] = ['redis', 'FAIL', 'connection error'];
        }

        // Storage writability
        $writable = is_writable(storage_path());
        $rows[] = ['storage', $writable ? 'ok' : 'FAIL', storage_path()];

        // Bootstrap cache
        $writable = is_writable(base_path('bootstrap/cache'));
        $rows[] = ['bootstrap-cache', $writable ? 'ok' : 'FAIL', base_path('bootstrap/cache')];

        // Blueprint state
        $installed = file_exists(base_path('.blueprint/extensions/blueprint/private/db/is_installed'));
        $rows[] = ['blueprint', $installed ? 'ok' : 'not-installed', base_path('.blueprint')];

        $this->table(['service', 'status', 'detail'], $rows);

        $failed = collect($rows)->filter(fn ($row) => $row[1] === 'FAIL')->count();

        if ($failed > 0) {
            $this->error("  {$failed} service(s) unhealthy.");

            return self::FAILURE;
        }

        $this->info('  All services healthy.');

        return self::SUCCESS;
    }
}
