<?php

namespace Pterodactyl\Console;

use Ramsey\Uuid\Uuid;
use Pterodactyl\Models\ActivityLog;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Console\PruneCommand;
use Pterodactyl\Repositories\Eloquent\SettingsRepository;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Pterodactyl\Console\Commands\Schedule\ProcessRunnableCommand;
use Pterodactyl\Console\Commands\Maintenance\PruneOrphanedBackupsCommand;
use Pterodactyl\Console\Commands\Maintenance\CleanServiceBackupFilesCommand;

// Blueprint extension framework (built into Panasaurus)
use Pterodactyl\Services\Telemetry\RegisterBlueprintTelemetry;
use Pterodactyl\BlueprintFramework\GetExtensionSchedules;
use Pterodactyl\BlueprintFramework\Libraries\ExtensionLibrary\Console\BlueprintConsoleLibrary as BlueprintExtensionLibrary;

class Kernel extends ConsoleKernel
{
    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
    }

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // https://laravel.com/docs/10.x/upgrade#redis-cache-tags
        $schedule->command('cache:prune-stale-tags')->hourly();

        $schedule->command(ProcessRunnableCommand::class)->everyMinute()->withoutOverlapping();
        $schedule->command(CleanServiceBackupFilesCommand::class)->daily();

        if (config('backups.prune_age')) {
            $schedule->command(PruneOrphanedBackupsCommand::class)->everyThirtyMinutes();
        }

        if (config('activity.prune_days')) {
            $schedule->command(PruneCommand::class, ['--model' => [ActivityLog::class]])->daily();
        }

        // ============================
        //    BLUEPRINT SCHEDULES
        // ============================

        // Blueprint telemetry
        $blueprint = app()->make(BlueprintExtensionLibrary::class);
        if ($blueprint->dbGet('blueprint', 'flags:telemetry_enabled', 0)) {
            $registerBlueprintTelemetry = app()->make(RegisterBlueprintTelemetry::class);
            $registerBlueprintTelemetry->register($schedule);
        }

        // Blueprint-related utilities
        $randTime = str_pad(rand(0, 23), 2, '0', STR_PAD_LEFT) . ':' . str_pad(rand(0, 59), 2, '0', STR_PAD_LEFT);
        $schedule->command('bp:version:cache')->dailyAt($randTime);
        $schedule->command('bp:meta')->dailyAt($randTime);

        // Blueprint extension schedules
        GetExtensionSchedules::schedules($schedule);
    }
}
