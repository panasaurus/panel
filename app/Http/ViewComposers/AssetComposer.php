<?php

namespace Pterodactyl\Http\ViewComposers;

use Illuminate\View\View;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Services\Helpers\AssetHashService;
use Pterodactyl\Services\Captcha\CaptchaManager;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;
use Pterodactyl\BlueprintFramework\Libraries\ExtensionLibrary\Admin\BlueprintAdminLibrary as BlueprintExtensionLibrary;

class AssetComposer
{
  protected CaptchaManager $captcha;
  protected SettingsRepositoryInterface $settings;
  protected BlueprintExtensionLibrary $blueprint;

  public function __construct(
    CaptchaManager $captcha,
    SettingsRepositoryInterface $settings,
    BlueprintExtensionLibrary $blueprint,
  ) {
    $this->captcha = $captcha;
    $this->settings = $settings;
    $this->blueprint = $blueprint;
  }

  /**
   * Provide access to the asset service in the views.
   */
  public function compose(View $view): void
  {
    $blueprintConfiguration = $this->blueprint->dbGetMany('blueprint', [
      'flags:disable_attribution',
    ]);

    $view->with('siteConfiguration', [
      'name' => config('app.name') ?? 'Panasaurus',
      'locale' => config('app.locale') ?? 'en',
      'timezone' => config('app.timezone') ?? '',
      'captcha' => [
        'enabled' => $this->captcha->getDefaultDriver() !== 'none',
        'provider' => $this->captcha->getDefaultDriver(),
        'siteKey' => $this->getSiteKeyForCurrentProvider(),
        'scriptIncludes' => $this->captcha->getScriptIncludes(),
      ],
      'blueprint' => [
        'disable_attribution' => ($blueprintConfiguration['flags:disable_attribution'] ?? null) === '1',
      ],
    ]);
  }

  /**
   * Get the site key for the currently active captcha provider.
   */
  private function getSiteKeyForCurrentProvider(): string
  {
    $provider = $this->captcha->getDefaultDriver();

    if ($provider === 'none') {
      return '';
    }

    try {
      $driver = $this->captcha->driver();
      if (method_exists($driver, 'getSiteKey')) {
        return $driver->getSiteKey();
      }
    } catch (\Exception $e) {
      // Silently fail to avoid exposing errors to frontend
    }

    return '';
  }
}
