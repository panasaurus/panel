<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\BlueprintFramework\Libraries\ExtensionLibrary\Admin\BlueprintAdminLibrary as BlueprintExtensionLibrary;
use Pterodactyl\Http\Controllers\Controller;

class ExtensionsMarketplaceController extends Controller
{
    private const DIRECTORY_URL = 'https://blueprint.zip/api/extensions';
    private const CACHE_KEY = 'panasaurus::marketplace::directory::v2';
    private const CACHE_TTL = 3600;

    public function __construct(
        private AlertsMessageBag $alert,
        private BlueprintExtensionLibrary $blueprint,
    ) {
    }

    public function index(Request $request, ViewFactory $view): View|RedirectResponse
    {
        $filters = $this->filters($request);
        [$directory, $fetchError] = $this->fetchDirectory();
        $catalog = collect($directory)->keyBy('identifier');
        $installed = [];

        foreach ($this->blueprint->extensions() as $identifier) {
            if (!is_string($identifier) || !preg_match('/^[a-zA-Z0-9_-]{1,48}$/D', $identifier)) {
                continue;
            }

            $config = $this->blueprint->extensionConfig($identifier);
            if (!is_array($config) || !isset($config['info'])) {
                continue;
            }

            $installed[$identifier] = $config['info']['version'] ?? 'unknown';
            if (!$catalog->has($identifier)) {
                $catalog->put($identifier, $this->normalize([
                    'identifier' => $identifier,
                    'name' => $config['info']['name'] ?? $identifier,
                    'summary' => $config['info']['description'] ?? '',
                    'author' => ['name' => $config['info']['author'] ?? 'Unknown author'],
                    'type' => 'extension',
                ]));
            }
        }

        $catalog = $catalog->map(function ($extension) use ($installed) {
            $identifier = $extension['identifier'];
            $extension['installed_version'] = $installed[$identifier] ?? null;
            $route = "admin.extensions.{$identifier}.index";
            $extension['configure_url'] = isset($installed[$identifier]) && Route::has($route) ? route($route) : null;

            return $extension;
        });

        $tabEntries = $catalog->filter(fn ($extension) => $filters['tab'] !== 'installed' || $extension['installed_version'] !== null);
        $categoryCounts = [
            'all' => $tabEntries->count(),
            'extension' => $tabEntries->where('type', 'extension')->count(),
            'theme' => $tabEntries->where('type', 'theme')->count(),
        ];
        $query = Str::lower(trim($filters['q']));
        $entries = $tabEntries->filter(function ($extension) use ($filters, $query) {
            $search = Str::lower(implode(' ', [
                $extension['name'], $extension['identifier'], $extension['summary'],
                $extension['author'], implode(' ', $extension['keywords']),
            ]));

            return ($query === '' || str_contains($search, $query))
                && ($filters['category'] === 'all' || $extension['type'] === $filters['category'])
                && ($filters['price'] === 'all' || ($filters['price'] === 'free' ? $extension['price'] === 0.0 : $extension['price'] > 0));
        });

        $entries = match ($filters['sort']) {
            'name' => $entries->sortBy(fn ($extension) => Str::lower($extension['name'])),
            'newest' => $entries->sortByDesc('created'),
            default => $entries->sortByDesc('downloads'),
        };
        $lastPage = max(1, (int) ceil($entries->count() / 12));
        if ($filters['page'] > $lastPage) {
            return redirect()->route('admin.extensions.marketplace', array_merge($filters, ['page' => $lastPage]));
        }

        $extensions = new LengthAwarePaginator(
            $entries->values()->forPage($filters['page'], 12)->values(),
            $entries->count(),
            12,
            $filters['page'],
            ['path' => route('admin.extensions.marketplace'), 'query' => array_diff_key($filters, ['page' => true])],
        );

        return $view->make('admin.extensions.marketplace', [
            'extensions' => $extensions,
            'filters' => $filters,
            'categoryCounts' => $categoryCounts,
            'browseCount' => $catalog->count(),
            'installedCount' => count($installed),
            'fetchError' => $fetchError,
        ]);
    }

    public function refresh(Request $request): RedirectResponse
    {
        $filters = $this->filters($request);
        Cache::forget(self::CACHE_KEY);
        $this->alert->success('Extension catalog refreshed.')->flash();

        return redirect()->route('admin.extensions.marketplace', $filters);
    }

    private function filters(Request $request): array
    {
        $values = $request->validate([
            'q' => 'nullable|string|max:160',
            'tab' => 'sometimes|in:browse,installed',
            'category' => 'sometimes|in:all,extension,theme',
            'price' => 'sometimes|in:all,free,paid',
            'sort' => 'sometimes|in:popular,newest,name',
            'page' => 'sometimes|integer|min:1|max:1000000',
        ]);

        return [
            'q' => $values['q'] ?? '',
            'tab' => $values['tab'] ?? 'browse',
            'category' => $values['category'] ?? 'all',
            'price' => $values['price'] ?? 'all',
            'sort' => $values['sort'] ?? 'popular',
            'page' => (int) ($values['page'] ?? 1),
        ];
    }

    private function fetchDirectory(): array
    {
        $cached = Cache::get(self::CACHE_KEY);
        if (is_array($cached)) {
            return [$cached, null];
        }

        try {
            $response = Http::connectTimeout(3)->timeout(10)->acceptJson()->get(self::DIRECTORY_URL)->throw();
            $data = $response->json();
            if (!is_array($data) || !array_is_list($data)) {
                throw new \RuntimeException('Invalid extension catalog.');
            }

            $payload = collect($data)
                ->filter(fn ($item) => is_array($item)
                    && in_array($item['type'] ?? '', ['extension', 'theme'], true)
                    && is_string($item['identifier'] ?? null)
                    && preg_match('/^[a-zA-Z0-9_-]{1,48}$/D', $item['identifier']))
                ->map(fn ($item) => $this->normalize($item))
                ->values()->all();

            Cache::put(self::CACHE_KEY, $payload, self::CACHE_TTL);
            Cache::put(self::CACHE_KEY . '::stale', $payload, 86400);

            return [$payload, null];
        } catch (\Throwable $e) {
            $stale = Cache::get(self::CACHE_KEY . '::stale');
            if (is_array($stale)) {
                return [$stale, 'The catalog is temporarily unavailable. Showing the last saved catalog.'];
            }

            return [[], 'Could not load the extension catalog. Refresh to try again. Your installed extensions are still available.'];
        }
    }

    private function normalize(array $item): array
    {
        $versions = collect(is_array($item['versions'] ?? null) ? $item['versions'] : [])->filter(fn ($version) => is_array($version));
        $latest = $versions->sortByDesc('created')->first();
        $platforms = [];
        foreach (is_array($item['platforms'] ?? null) ? $item['platforms'] : [] as $name => $platform) {
            if (!is_array($platform)) {
                continue;
            }

            $url = $this->safeUrl($platform['url'] ?? null, ['github.com', 'builtbybit.com', 'www.builtbybit.com', 'sourcexchange.net', 'www.sourcexchange.net']);
            if ($url === null) {
                continue;
            }

            $platforms[] = [
                'name' => match ($name) { 'GITHUB' => 'GitHub', 'BUILTBYBIT' => 'BuiltByBit', 'SOURCEXCHANGE' => 'sourceXchange', default => 'Developer page' },
                'url' => $url,
                'price' => is_numeric($platform['price'] ?? null) ? max(0.0, (float) $platform['price']) : null,
                'currency' => is_string($platform['currency'] ?? null) && preg_match('/^[A-Z]{3}$/D', $platform['currency']) ? $platform['currency'] : 'USD',
            ];
        }

        $offer = collect($platforms)->whereNotNull('price')->sortBy('price')->first();
        $banner = $item['banner'] ?? null;

        return [
            'identifier' => $item['identifier'],
            'type' => $item['type'] ?? 'extension',
            'name' => is_string($item['name'] ?? null) ? $item['name'] : $item['identifier'],
            'summary' => is_string($item['summary'] ?? null) ? $item['summary'] : '',
            'author' => is_string($item['author']['name'] ?? null) ? $item['author']['name'] : 'Unknown author',
            'banner' => $this->safeUrl(is_array($banner) ? ($banner['lowres'] ?? $banner['fullres'] ?? null) : $banner, ['s3.blueprint.zip']),
            'latest_version' => $latest['name'] ?? $latest['version'] ?? null,
            'downloads' => (int) $versions->sum(fn ($version) => is_numeric($version['downloads'] ?? null) ? max(0, (int) $version['downloads']) : 0),
            'created' => is_string($item['created'] ?? null) ? $item['created'] : '',
            'keywords' => collect(is_array($item['keywords'] ?? null) ? $item['keywords'] : [])->filter(fn ($keyword) => is_string($keyword))->take(4)->values()->all(),
            'platforms' => $platforms,
            'price' => $offer['price'] ?? null,
            'currency' => $offer['currency'] ?? null,
        ];
    }

    private function safeUrl(mixed $url, array $hosts): ?string
    {
        if (!is_string($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        $parts = parse_url($url);

        return ($parts['scheme'] ?? '') === 'https'
            && in_array(strtolower($parts['host'] ?? ''), $hosts, true)
            && !isset($parts['user']) && !isset($parts['pass'])
            && (!isset($parts['port']) || $parts['port'] === 443) ? $url : null;
    }
}
