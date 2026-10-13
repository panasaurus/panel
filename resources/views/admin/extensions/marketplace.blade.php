@extends('layouts.admin')

@section('title', 'Extension Marketplace')

@section('scripts')
  @parent
  <link rel="stylesheet" href="{{ asset('themes/panasaurus/css/marketplace.css') }}?v={{ filemtime(public_path('themes/panasaurus/css/marketplace.css')) }}">
@endsection

@section('content-header')
  <h1>Marketplace<small>A little more power for your panel. Find your next extension.</small></h1>
@endsection

@section('content')
  @php
    $marketUrl = fn (array $changes = []) => route('admin.extensions.marketplace', array_merge($filters, ['page' => 1], $changes));
    $resetUrl = route('admin.extensions.marketplace', ['tab' => $filters['tab']]);
    $pageWindow = Illuminate\Pagination\UrlWindow::make($extensions->onEachSide(1));
    $pageGroups = [$pageWindow['first'], $pageWindow['slider'], $pageWindow['last']];
    $previousPage = 0;
  @endphp
  <div class="marketplace" data-marketplace>
    <div class="marketplace-topline">
      <nav class="marketplace-tabs" aria-label="Extension library">
        <a href="{{ $marketUrl(['tab' => 'browse']) }}" class="{{ $filters['tab'] === 'browse' ? 'is-active' : '' }}" @if($filters['tab'] === 'browse') aria-current="page" @endif>
          @include('partials.admin.icon', ['name' => 'marketplace']) Browse <span>{{ $browseCount }}</span>
        </a>
        <a href="{{ $marketUrl(['tab' => 'installed']) }}" class="{{ $filters['tab'] === 'installed' ? 'is-active' : '' }}" @if($filters['tab'] === 'installed') aria-current="page" @endif>
          @include('partials.admin.icon', ['name' => 'extensions']) Installed <span>{{ $installedCount }}</span>
        </a>
      </nav>
      <a class="marketplace-refresh" href="{{ route('admin.extensions.marketplace.refresh', $filters) }}" aria-label="Refresh extension catalog" title="Refresh catalog">
        @include('partials.admin.icon', ['name' => 'refresh']) <span>Refresh</span>
      </a>
    </div>

    @if($fetchError)
      <div class="marketplace-notice" role="status">@include('partials.admin.icon', ['name' => 'activity']) <span>{{ $fetchError }}</span></div>
    @endif

    <form class="marketplace-toolbar" action="{{ route('admin.extensions.marketplace') }}" method="GET" role="search" data-marketplace-filters>
      <input type="hidden" name="tab" value="{{ $filters['tab'] }}">
      <input type="hidden" name="category" value="{{ $filters['category'] }}">
      <div class="marketplace-search">
        @include('partials.admin.icon', ['name' => 'search'])
        <label for="marketplace-search" class="sr-only">Search extensions</label>
        <input id="marketplace-search" name="q" type="search" value="{{ $filters['q'] }}" placeholder="Search extensions, themes, or creators" maxlength="160" autocomplete="off">
        <button type="submit" aria-label="Search marketplace">Search</button>
      </div>
      <div class="marketplace-select">
        <label for="marketplace-price" class="sr-only">Price</label>
        <select id="marketplace-price" name="price" data-marketplace-select>
          @foreach(['all' => 'All prices', 'free' => 'Free', 'paid' => 'Paid'] as $value => $label)
            <option value="{{ $value }}" @selected($filters['price'] === $value)>{{ $label }}</option>
          @endforeach
        </select>
      </div>
      <div class="marketplace-select marketplace-sort">
        <label for="marketplace-sort" class="sr-only">Sort extensions</label>
        <select id="marketplace-sort" name="sort" data-marketplace-select>
          @foreach(['popular' => 'Most downloaded', 'newest' => 'Newest first', 'name' => 'Name: A to Z'] as $value => $label)
            <option value="{{ $value }}" @selected($filters['sort'] === $value)>{{ $label }}</option>
          @endforeach
        </select>
      </div>
      <noscript><button type="submit" class="marketplace-button">Apply filters</button></noscript>
    </form>

    <div class="marketplace-discovery">
      <nav class="marketplace-categories" aria-label="Extension categories">
        @foreach(['all' => 'All', 'extension' => 'Extensions', 'theme' => 'Themes'] as $value => $label)
          <a href="{{ $marketUrl(['category' => $value]) }}" class="{{ $filters['category'] === $value ? 'is-active' : '' }}" @if($filters['category'] === $value) aria-current="page" @endif>
            {{ $label }} <span>{{ $categoryCounts[$value] }}</span>
          </a>
        @endforeach
      </nav>
      @if($filters['q'] !== '' || $filters['category'] !== 'all' || $filters['price'] !== 'all')
        <a href="{{ $resetUrl }}" class="marketplace-clear">@include('partials.admin.icon', ['name' => 'close']) Clear filters</a>
      @else
        <span class="marketplace-page-size">12 per page</span>
      @endif
    </div>

    @if($extensions->count())
      <div class="marketplace-grid" id="marketplace-grid">
        @foreach($extensions as $extension)
          <article class="marketplace-card" data-extension="{{ $extension['identifier'] }}" data-type="{{ $extension['type'] }}" data-price="{{ $extension['price'] }}">
            <div class="marketplace-preview">
              <div class="marketplace-preview-fallback" @if($extension['banner']) hidden @endif>
                @include('partials.admin.icon', ['name' => $extension['type'] === 'theme' ? 'overview' : 'extensions'])
                <span>{{ $extension['name'] }}</span>
              </div>
              @if($extension['banner'])
                <img src="{{ $extension['banner'] }}" alt="{{ $extension['name'] }} preview" loading="lazy" decoding="async" referrerpolicy="no-referrer" width="480" height="240" data-marketplace-preview>
              @endif
              <span class="marketplace-kind">{{ $extension['type'] === 'theme' ? 'Theme' : 'Extension' }}</span>
              @if($extension['installed_version'] !== null)
                <span class="marketplace-installed">@include('partials.admin.icon', ['name' => 'check']) Installed</span>
              @endif
            </div>
            <div class="marketplace-card-body">
              <div class="marketplace-card-heading">
                <h2>{{ $extension['name'] }}</h2>
                @if($extension['latest_version']) <span class="marketplace-version">v{{ $extension['latest_version'] }}</span> @endif
              </div>
              <p class="marketplace-author">by {{ $extension['author'] }}</p>
              <p class="marketplace-summary">{{ $extension['summary'] ?: 'Installed on your panel. Open its settings to explore.' }}</p>
              <div class="marketplace-card-meta">
                <span class="marketplace-downloads" title="{{ number_format($extension['downloads']) }} downloads">@include('partials.admin.icon', ['name' => 'activity']) {{ number_format($extension['downloads']) }} downloads</span>
                @if($extension['installed_version'] !== null)
                  <span title="Installed version">v{{ $extension['installed_version'] }}</span>
                @endif
              </div>
            </div>
            <div class="marketplace-card-footer">
              <span class="marketplace-price {{ $extension['price'] === 0.0 ? 'is-free' : '' }}">
                @if($extension['price'] === 0.0)
                  Free
                @elseif($extension['price'] !== null)
                  <small>From</small> {{ $extension['currency'] === 'USD' ? '$' : $extension['currency'].' ' }}{{ number_format($extension['price'], 2) }}
                @else
                  <small>{{ $extension['installed_version'] !== null ? 'On your panel' : 'See developer' }}</small>
                @endif
              </span>
              @if($extension['configure_url'])
                <a href="{{ $extension['configure_url'] }}" class="marketplace-button marketplace-button-secondary">@include('partials.admin.icon', ['name' => 'settings']) Configure</a>
              @else
                <button type="button" class="marketplace-button {{ $extension['installed_version'] !== null ? 'marketplace-button-secondary' : '' }}" data-toggle="modal" data-target="#marketplace-install-{{ $extension['identifier'] }}" aria-label="{{ $extension['installed_version'] !== null ? 'Details for' : 'Install' }} {{ $extension['name'] }}">
                  @include('partials.admin.icon', ['name' => $extension['installed_version'] !== null ? 'extensions' : 'plus']) {{ $extension['installed_version'] !== null ? 'Details' : 'Install' }}
                </button>
              @endif
            </div>
          </article>
        @endforeach
      </div>

      <div class="marketplace-pagination-bar">
        <p>Showing <strong>{{ $extensions->firstItem() }}–{{ $extensions->lastItem() }}</strong> of <strong>{{ number_format($extensions->total()) }}</strong></p>
        <nav class="marketplace-pagination" aria-label="Marketplace pages">
          @if($extensions->onFirstPage())
            <span class="marketplace-page-edge is-disabled" aria-disabled="true">Previous</span>
          @else
            <a class="marketplace-page-edge" href="{{ $extensions->previousPageUrl() }}" rel="prev">Previous</a>
          @endif
          @foreach($pageGroups as $pages)
            @if(is_array($pages))
              @foreach($pages as $page => $url)
                @if($previousPage > 0 && $page > $previousPage + 1)
                  <span class="marketplace-page-gap" aria-hidden="true">…</span>
                @endif
                @if($page === $extensions->currentPage())
                  <span class="is-current" aria-current="page" aria-label="Page {{ $page }}">{{ $page }}</span>
                @else
                  <a href="{{ $url }}" aria-label="Page {{ $page }}">{{ $page }}</a>
                @endif
                @php($previousPage = $page)
              @endforeach
            @endif
          @endforeach
          @if($extensions->hasMorePages())
            <a class="marketplace-page-edge" href="{{ $extensions->nextPageUrl() }}" rel="next">Next</a>
          @else
            <span class="marketplace-page-edge is-disabled" aria-disabled="true">Next</span>
          @endif
        </nav>
      </div>
    @else
      <div class="marketplace-empty">
        <span class="marketplace-empty-icon">@include('partials.admin.icon', ['name' => $filters['tab'] === 'installed' ? 'extensions' : 'search'])</span>
        <h2>{{ $filters['tab'] === 'installed' && $installedCount === 0 ? 'Your next upgrade starts here' : 'No extensions found' }}</h2>
        <p>{{ $filters['tab'] === 'installed' && $installedCount === 0 ? 'Explore the catalog to find something for your panel.' : ($fetchError && $browseCount === 0 ? 'Refresh the catalog to try again.' : 'Try another search or clear your filters.') }}</p>
        <a href="{{ $filters['tab'] === 'installed' && $installedCount === 0 ? route('admin.extensions.marketplace') : ($fetchError && $browseCount === 0 ? route('admin.extensions.marketplace.refresh', $filters) : $resetUrl) }}" class="marketplace-button">
          {{ $filters['tab'] === 'installed' && $installedCount === 0 ? 'Browse extensions' : ($fetchError && $browseCount === 0 ? 'Refresh catalog' : 'Clear filters') }}
        </a>
      </div>
    @endif
    <div class="marketplace-credit">Built for your panel. Powered by <a href="https://blueprint.zip/" target="_blank" rel="noopener noreferrer">Blueprint @include('partials.admin.icon', ['name' => 'external'])</a></div>
  </div>

  @foreach($extensions as $extension)
    <div class="modal fade marketplace-install-modal" id="marketplace-install-{{ $extension['identifier'] }}" tabindex="-1" role="dialog" aria-labelledby="marketplace-title-{{ $extension['identifier'] }}">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <div>
              <h2 class="modal-title" id="marketplace-title-{{ $extension['identifier'] }}">{{ $extension['name'] }}</h2>
              <p>by {{ $extension['author'] }} @if($extension['latest_version']) <span>Version {{ $extension['latest_version'] }}</span> @endif</p>
            </div>
            <button type="button" class="admin-icon-button" data-dismiss="modal" aria-label="Close extension details">@include('partials.admin.icon', ['name' => 'close'])</button>
          </div>
          <div class="modal-body">
            <p class="marketplace-modal-summary">{{ $extension['summary'] }}</p>
            @if($extension['platforms'])
              <h3>Get the extension</h3>
              <div class="marketplace-sources">
                @foreach($extension['platforms'] as $platform)
                  <a class="marketplace-source" href="{{ $platform['url'] }}" target="_blank" rel="noopener noreferrer">
                    <span>{{ $platform['name'] }}</span>
                    <span>{{ $platform['price'] === 0.0 ? 'Free' : ($platform['price'] === null ? 'View' : ($platform['currency'] === 'USD' ? '$' : $platform['currency'].' ').number_format($platform['price'], 2)) }} @include('partials.admin.icon', ['name' => 'external'])</span>
                  </a>
                @endforeach
              </div>
            @endif
            @if($extension['installed_version'] !== null)
              <p class="marketplace-modal-installed">@include('partials.admin.icon', ['name' => 'check']) Installed version {{ $extension['installed_version'] }}</p>
            @else
              <h3>Install on your panel</h3>
              <ol class="marketplace-install-steps">
                <li><strong>Download the package</strong><span>Get <code>{{ $extension['identifier'] }}.blueprint</code> from the developer's page{{ $extension['platforms'] ? ' above' : '' }}.</span></li>
                <li><strong>Add it to the panel folder</strong><span>Copy the package to <code>/app/</code> in Docker, or your panel directory.</span></li>
                <li><strong>Install and rebuild</strong><code>panasaurus -install {{ $extension['identifier'] }}</code><span>Let the asset build finish, then refresh your browser.</span></li>
              </ol>
            @endif
          </div>
          <div class="modal-footer"><button type="button" class="marketplace-button marketplace-button-secondary" data-dismiss="modal">Done</button></div>
        </div>
      </div>
    </div>
  @endforeach
@endsection

@section('footer-scripts')
  @parent
  <script src="{{ asset('themes/panasaurus/js/admin/marketplace.js') }}?v={{ filemtime(public_path('themes/panasaurus/js/admin/marketplace.js')) }}" defer></script>
@endsection
