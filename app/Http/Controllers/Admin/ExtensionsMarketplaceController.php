<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Prologue\Alerts\AlertsMessageBag;
use Illuminate\View\Factory as ViewFactory;
use Pterodactyl\BlueprintFramework\Libraries\ExtensionLibrary\Admin\BlueprintAdminLibrary as BlueprintExtensionLibrary;
use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

/**
 * Panasaurus Extension Marketplace.
 *
 * Surfaces the Blueprint extension directory (blueprint.zip) inside the
 * admin area so administrators can discover extensions and install them
 * with a single CLI command — no custom tooling required.
 */
class ExtensionsMarketplaceController extends Controller
{
    private const DIRECTORY_URL = 'https://blueprint.zip/api/extensions';
    private const CACHE_KEY = 'panasaurus::marketplace::directory';
    private const CACHE_TTL = 3600; // 1 hour

    public function __construct(
        private AlertsMessageBag $alert,
        private BlueprintExtensionLibrary $blueprint,
    ) {
    }

    /**
     * Render the marketplace index.
     */
    public function index(ViewFactory $view): View
    {
        [$extensions, $fetchError] = $this->fetchDirectory();

        $installed = collect($this->blueprint->extensions())
            ->mapWithKeys(function ($identifier) {
                $config = $this->blueprint->extensionConfig($identifier);

                return [$identifier => $config['info']['version'] ?? 'unknown'];
            });

        return $view->make('admin.extensions.marketplace', [
            'extensions' => $extensions,
            'installed' => $installed,
            'fetchError' => $fetchError,
        ]);
    }

    /**
     * Force a marketplace cache refresh.
     */
    public function refresh(): RedirectResponse
    {
        Cache::forget(self::CACHE_KEY);
        $this->alert->success('Marketplace cache has been refreshed.')->flash();

        return redirect()->route('admin.extensions.marketplace');
    }

    /**
     * Fetch (and cache) the Blueprint extension directory.
     *
     * @return array{0: array, 1: ?string}
     */
    private function fetchDirectory(): array
    {
        $cached = Cache::get(self::CACHE_KEY);
        if (is_array($cached)) {
            return [$cached, null];
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders(['Accept' => 'application/json'])
                ->get(self::DIRECTORY_URL);

            if ($response->failed()) {
                throw new \RuntimeException('bad response');
            }

            $payload = collect($response->json())
                ->filter(fn ($item) => is_array($item) && ($item['type'] ?? '') === 'extension')
                ->map(fn ($item) => $this->normalize($item))
                ->sortBy(fn ($item) => -($item['downloads'] ?? 0))
                ->values()
                ->all();

            Cache::put(self::CACHE_KEY, $payload, self::CACHE_TTL);

            return [$payload, null];
        } catch (\Throwable $e) {
            // Serve stale data if we have any, otherwise surface an error banner.
            $stale = Cache::get(self::CACHE_KEY . '::stale');
            if (is_array($stale)) {
                return [$stale, 'Live fetch failed — showing a cached snapshot.'];
            }

            return [[], 'Could not reach blueprint.zip to load the extension directory. You can still install extensions manually using the CLI.'];
        }
    }

    /**
     * Normalize a directory entry into the shape the view expects.
     */
    private function normalize(array $item): array
    {
        $versions = collect($item['versions'] ?? []);
        $latest = $versions->sortByDesc('created')->first();

        return [
            'identifier' => $item['identifier'] ?? '',
            'name' => $item['name'] ?? $item['identifier'] ?? 'Unknown',
            'summary' => $item['summary'] ?? '',
            'author' => $item['author']['name'] ?? 'Unknown author',
            'banner' => $item['banner'] ?? null,
            'latest_version' => $latest['version'] ?? null,
            'downloads' => $item['stats']['downloads'] ?? 0,
            'created' => $item['created'] ?? null,
            'keywords' => collect($item['keywords'] ?? [])->take(4)->all(),
            'platforms' => collect($item['platforms'] ?? [])->all(),
        ];
    }
}
