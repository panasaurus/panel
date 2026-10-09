<?php

namespace Pterodactyl\Console\Commands\Panasaurus;

use Illuminate\Console\Command;
use Pterodactyl\BlueprintFramework\Libraries\ExtensionLibrary\Console\BlueprintConsoleLibrary as BlueprintExtensionLibrary;

class InfoCommand extends Command
{
    protected $description = 'Display Panasaurus panel and Blueprint runtime information.';
    protected $signature = 'panasaurus:info {--json : Output as JSON}';

    public function handle(BlueprintExtensionLibrary $blueprint): int
    {
        $info = [
            'panel' => [
                'name' => config('app.name'),
                'version' => config('app.version'),
                'environment' => app()->environment(),
                'php' => PHP_VERSION,
                'laravel' => app()->version(),
                'url' => config('app.url'),
                'database' => config('database.default'),
                'cache' => config('cache.default'),
                'queue' => config('queue.default'),
            ],
            'blueprint' => [
                'engine' => 'panasaurus-core',
                'version' => $blueprint->dbGet('blueprint', 'internal:version', 'unknown'),
                'installed' => file_exists(base_path('.blueprint/extensions/blueprint/private/db/is_installed')),
                'extensions' => $blueprint->extensions(),
            ],
        ];

        if ($this->option('json')) {
            $this->line(json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $panel = $info['panel'];
        $this->info('  Panasaurus — the dino-strong game server panel');
        $this->line('  https://github.com/panasaurus/panel');
        $this->newLine();

        $this->table(
            ['Component', 'Value'],
            [
                ['Panel version', $panel['version']],
                ['Environment', $panel['environment']],
                ['PHP', $panel['php']],
                ['Laravel', $panel['laravel']],
                ['App URL', $panel['url']],
                ['Database driver', $panel['database']],
                ['Cache driver', $panel['cache']],
                ['Queue driver', $panel['queue']],
                ['Blueprint engine', $info['blueprint']['engine']],
                ['Blueprint installed', $info['blueprint']['installed'] ? 'yes' : 'no'],
                ['Extensions', count($info['blueprint']['extensions']) ? implode(', ', $info['blueprint']['extensions']) : 'none installed'],
            ]
        );

        return self::SUCCESS;
    }
}
