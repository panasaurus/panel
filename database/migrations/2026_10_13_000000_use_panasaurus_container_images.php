<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const IMAGES = [
        'ghcr.io/parkervcp/installers:alpine' => 'ghcr.io/panasaurus/images:installer_alpine',
        'ghcr.io/parkervcp/installers:debian' => 'ghcr.io/panasaurus/images:installer_debian',
        'ghcr.io/ptero-eggs/installers:alpine' => 'ghcr.io/panasaurus/images:installer_alpine',
        'ghcr.io/ptero-eggs/installers:debian' => 'ghcr.io/panasaurus/images:installer_debian',
        'ghcr.io/ptero-eggs/yolks:debian' => 'ghcr.io/panasaurus/images:debian',
        'ghcr.io/ptero-eggs/yolks:dotnet_7' => 'ghcr.io/panasaurus/images:dotnet_7',
        'ghcr.io/ptero-eggs/yolks:dotnet_8' => 'ghcr.io/panasaurus/images:dotnet_8',
        'ghcr.io/ptero-eggs/yolks:dotnet_9' => 'ghcr.io/panasaurus/images:dotnet_9',
        'ghcr.io/ptero-eggs/yolks:java_11' => 'ghcr.io/panasaurus/images:java_11',
        'ghcr.io/ptero-eggs/yolks:java_11j9' => 'ghcr.io/panasaurus/images:java_11j9',
        'ghcr.io/ptero-eggs/yolks:java_16' => 'ghcr.io/panasaurus/images:java_16',
        'ghcr.io/ptero-eggs/yolks:java_16j9' => 'ghcr.io/panasaurus/images:java_16j9',
        'ghcr.io/ptero-eggs/yolks:java_17' => 'ghcr.io/panasaurus/images:java_17',
        'ghcr.io/ptero-eggs/yolks:java_17j9' => 'ghcr.io/panasaurus/images:java_17j9',
        'ghcr.io/ptero-eggs/yolks:java_18' => 'ghcr.io/panasaurus/images:java_18',
        'ghcr.io/ptero-eggs/yolks:java_18j9' => 'ghcr.io/panasaurus/images:java_18j9',
        'ghcr.io/ptero-eggs/yolks:java_19' => 'ghcr.io/panasaurus/images:java_19',
        'ghcr.io/ptero-eggs/yolks:java_19j9' => 'ghcr.io/panasaurus/images:java_19j9',
        'ghcr.io/ptero-eggs/yolks:java_21' => 'ghcr.io/panasaurus/images:java_21',
        'ghcr.io/ptero-eggs/yolks:java_22' => 'ghcr.io/panasaurus/images:java_22',
        'ghcr.io/ptero-eggs/yolks:java_23' => 'ghcr.io/panasaurus/images:java_23',
        'ghcr.io/ptero-eggs/yolks:java_24' => 'ghcr.io/panasaurus/images:java_24',
        'ghcr.io/ptero-eggs/yolks:java_25' => 'ghcr.io/panasaurus/images:java_25',
        'ghcr.io/ptero-eggs/yolks:java_26' => 'ghcr.io/panasaurus/images:java_26',
        'ghcr.io/ptero-eggs/yolks:java_8' => 'ghcr.io/panasaurus/images:java_8',
        'ghcr.io/ptero-eggs/yolks:java_8j9' => 'ghcr.io/panasaurus/images:java_8j9',
        'ghcr.io/pterodactyl/games:hytale' => 'ghcr.io/panasaurus/images:hytale',
        'ghcr.io/pterodactyl/games:rust' => 'ghcr.io/panasaurus/images:rust',
        'ghcr.io/pterodactyl/games:source' => 'ghcr.io/panasaurus/images:source',
        'ghcr.io/pterodactyl/installers:alpine' => 'ghcr.io/panasaurus/images:installer_alpine',
        'ghcr.io/pterodactyl/installers:debian' => 'ghcr.io/panasaurus/images:installer_debian',
        'ghcr.io/pterodactyl/yolks:debian' => 'ghcr.io/panasaurus/images:debian',
        'ghcr.io/pterodactyl/yolks:dotnet_7' => 'ghcr.io/panasaurus/images:dotnet_7',
        'ghcr.io/pterodactyl/yolks:dotnet_8' => 'ghcr.io/panasaurus/images:dotnet_8',
        'ghcr.io/pterodactyl/yolks:dotnet_9' => 'ghcr.io/panasaurus/images:dotnet_9',
        'ghcr.io/pterodactyl/yolks:java_11' => 'ghcr.io/panasaurus/images:java_11',
        'ghcr.io/pterodactyl/yolks:java_11j9' => 'ghcr.io/panasaurus/images:java_11j9',
        'ghcr.io/pterodactyl/yolks:java_16' => 'ghcr.io/panasaurus/images:java_16',
        'ghcr.io/pterodactyl/yolks:java_16j9' => 'ghcr.io/panasaurus/images:java_16j9',
        'ghcr.io/pterodactyl/yolks:java_17' => 'ghcr.io/panasaurus/images:java_17',
        'ghcr.io/pterodactyl/yolks:java_17j9' => 'ghcr.io/panasaurus/images:java_17j9',
        'ghcr.io/pterodactyl/yolks:java_18' => 'ghcr.io/panasaurus/images:java_18',
        'ghcr.io/pterodactyl/yolks:java_18j9' => 'ghcr.io/panasaurus/images:java_18j9',
        'ghcr.io/pterodactyl/yolks:java_19' => 'ghcr.io/panasaurus/images:java_19',
        'ghcr.io/pterodactyl/yolks:java_19j9' => 'ghcr.io/panasaurus/images:java_19j9',
        'ghcr.io/pterodactyl/yolks:java_21' => 'ghcr.io/panasaurus/images:java_21',
        'ghcr.io/pterodactyl/yolks:java_22' => 'ghcr.io/panasaurus/images:java_22',
        'ghcr.io/pterodactyl/yolks:java_23' => 'ghcr.io/panasaurus/images:java_23',
        'ghcr.io/pterodactyl/yolks:java_24' => 'ghcr.io/panasaurus/images:java_24',
        'ghcr.io/pterodactyl/yolks:java_25' => 'ghcr.io/panasaurus/images:java_25',
        'ghcr.io/pterodactyl/yolks:java_26' => 'ghcr.io/panasaurus/images:java_26',
        'ghcr.io/pterodactyl/yolks:java_8' => 'ghcr.io/panasaurus/images:java_8',
        'ghcr.io/pterodactyl/yolks:java_8j9' => 'ghcr.io/panasaurus/images:java_8j9',
        'openjdk:8-jdk-slim' => 'ghcr.io/panasaurus/images:java_8',
    ];

    public function up(): void
    {
        DB::table('eggs')->orderBy('id')->chunkById(100, function ($eggs) {
            foreach ($eggs as $egg) {
                $updates = [];
                if ($egg->docker_images !== null) {
                    $images = json_decode($egg->docker_images, true, 512, JSON_THROW_ON_ERROR);
                    $mapped = [];
                    foreach ($images as $label => $image) {
                        $mapped[is_string($label) ? $this->image($label) : $label] = $this->image($image);
                    }
                    if ($mapped !== $images) {
                        $updates['docker_images'] = json_encode($mapped, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
                    }
                }
                $installer = $this->image($egg->script_container);
                if ($installer !== $egg->script_container) {
                    $updates['script_container'] = $installer;
                }
                if ($updates !== []) {
                    DB::table('eggs')->where('id', $egg->id)->update($updates);
                }
            }
        });

        DB::table('servers')->orderBy('id')->chunkById(100, function ($servers) {
            foreach ($servers as $server) {
                $image = $this->image($server->image);
                if ($image !== $server->image) {
                    DB::table('servers')->where('id', $server->id)
                        ->where('image', $server->image)->update(['image' => $image]);
                }
            }
        });
    }

    private function image(?string $image): ?string
    {
        return self::IMAGES[$image] ?? $image;
    }

    public function down(): void
    {
    }
};
