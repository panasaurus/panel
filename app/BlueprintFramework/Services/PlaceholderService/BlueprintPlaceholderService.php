<?php

namespace Pterodactyl\BlueprintFramework\Services\PlaceholderService;

class BlueprintPlaceholderService
{
  public function version(): string
  {
    // Panasaurus ships Blueprint pre-installed; version lives in the db file.
    $file = base_path('.blueprint/extensions/blueprint/private/db/version');
    if (file_exists($file)) {
      return trim(file_get_contents($file)) ?: 'beta-2026-08';
    }

    $ver = "::v";
    if ($ver == '::'.'v') {
      return 'unknown';
    }
    return $ver;
  }
  public function folder(): string
  {
    return base_path();
  }
  public function installed(): string
  {
    // Pre-installed with the Panasaurus panel.
    return "INSTALLED";
  }
  public function api_url(): string
  {
    return "https://blueprint.zip";
  }
}
