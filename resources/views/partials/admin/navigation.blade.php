@php
    $adminNavigation = [
        'Workspace' => [
            ['Overview', 'admin.index', 'overview', 'admin.index'],
            ['Servers', 'admin.servers', 'servers', 'admin.servers'],
            ['Users', 'admin.users', 'users', 'admin.users'],
        ],
        'Infrastructure' => [
            ['Nodes', 'admin.nodes', 'nodes', 'admin.nodes'],
            ['Locations', 'admin.locations', 'globe', 'admin.locations'],
            ['Databases', 'admin.databases', 'database', 'admin.databases'],
            ['Mounts', 'admin.mounts', 'mounts', 'admin.mounts'],
        ],
        'Configuration' => [
            ['Nests & eggs', 'admin.nests', 'nests', 'admin.nests'],
            ['Extensions', 'admin.extensions', 'extensions', 'admin.extensions'],
            ['Marketplace', 'admin.extensions.marketplace', 'marketplace', 'admin.extensions.marketplace'],
            ['Application API', 'admin.api.index', 'key', 'admin.api'],
            ['Settings', 'admin.settings', 'settings', 'admin.settings'],
        ],
    ];
    $currentAdminRoute = Route::currentRouteName() ?? '';
@endphp
<header class="main-header">
    <a href="{{ route('admin.index') }}" class="logo" aria-label="{{ config('app.name') }} administration">
        <svg width="30" height="30" viewBox="0 0 48 48" fill="none" aria-hidden="true">
            <g fill="#23d18d">
                <path d="M4 41 L13 31.5 L18.5 37 L11 45 Q6.5 46.5 4 41 Z" />
                <rect x="11.5" y="21" width="22.5" height="15.5" rx="7.75" />
                <circle cx="18" cy="30.5" r="6" />
                <rect x="27.5" y="11.5" width="10.5" height="17" rx="5.25" />
                <rect x="30.5" y="10.5" width="14" height="9.5" rx="4.25" />
                <rect x="38" y="16" width="6.5" height="4.5" rx="2.25" />
                <rect x="14" y="33" width="5" height="10.5" rx="2.5" />
                <rect x="24.5" y="33" width="5" height="10.5" rx="2.5" />
            </g>
            <circle cx="37.8" cy="14.4" r="1.35" fill="#09090b" />
        </svg>
        <span class="logoText">{{ config('app.name', 'Panasaurus') }}</span>
    </a>
    <nav class="navbar navbar-static-top" aria-label="Workspace controls">
        <button type="button" class="admin-icon-button admin-menu-toggle" data-admin-menu-toggle aria-label="Open navigation" aria-controls="admin-sidebar" aria-expanded="false">@include('partials.admin.icon', ['name' => 'menu'])</button>
        <span class="admin-workspace-label">Workspace <span aria-hidden="true">/</span> <strong>Administration</strong></span>
        <div class="navbar-custom-menu">
            <button type="button" class="admin-search-trigger" data-admin-search-open>@include('partials.admin.icon', ['name' => 'search']) <span>Go to a page…</span><kbd data-admin-shortcut>⌘ K</kbd></button>
            <a href="{{ route('index') }}" class="admin-client-link">Client view @include('partials.admin.icon', ['name' => 'external'])</a>
            @yield('blueprint.navigation')
            <div class="dropdown admin-account">
                <button type="button" class="admin-account-trigger" id="admin-account-trigger" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-controls="admin-account-dropdown" aria-label="Account menu for {{ Auth::user()->name_first }} {{ Auth::user()->name_last }}">
                    <span class="admin-avatar" aria-hidden="true">{{ mb_substr(Auth::user()->name_first, 0, 1) }}{{ mb_substr(Auth::user()->name_last, 0, 1) }}</span>
                </button>
                <ul class="dropdown-menu admin-account-dropdown" id="admin-account-dropdown" aria-labelledby="admin-account-trigger">
                    <li class="admin-account-summary"><strong>{{ Auth::user()->name_first }} {{ Auth::user()->name_last }}</strong><small>Administrator</small></li>
                    <li><a href="{{ route('account') }}">@include('partials.admin.icon', ['name' => 'settings']) Account settings</a></li>
                    <li><a href="{{ route('auth.logout') }}" id="logoutButton">@include('partials.admin.icon', ['name' => 'logout']) Log out</a></li>
                </ul>
            </div>
        </div>
    </nav>
</header>
<button type="button" class="admin-menu-backdrop" data-admin-menu-close aria-label="Close navigation" tabindex="-1"></button>
<aside class="main-sidebar" id="admin-sidebar" aria-label="Administration navigation">
    <nav class="sidebar" aria-label="Admin pages">
        <ul class="sidebar-menu">
            @foreach($adminNavigation as $group => $items)
                <li class="header">{{ $group }}</li>
                @foreach($items as [$label, $route, $icon, $prefix])
                    @php
                        $active = $prefix === 'admin.index' ? $currentAdminRoute === $prefix : starts_with($currentAdminRoute, $prefix);
                        if ($route === 'admin.extensions' && starts_with($currentAdminRoute, 'admin.extensions.marketplace')) $active = false;
                    @endphp
                    <li class="{{ $active ? 'active' : '' }}">
                        <a href="{{ route($route) }}" @if($active) aria-current="page" @endif data-admin-page="{{ $label }}">
                            @include('partials.admin.icon', ['name' => $icon])<span>{{ $label }}</span>
                        </a>
                    </li>
                @endforeach
            @endforeach
            @yield('blueprint.sidenav')
        </ul>
    </nav>
</aside>
<dialog id="admin-command-dialog" class="admin-command-dialog" aria-labelledby="admin-command-title">
    <div class="admin-command-header"><h2 id="admin-command-title">Go to a page</h2><button type="button" class="admin-icon-button" data-admin-search-close aria-label="Close search">@include('partials.admin.icon', ['name' => 'close'])</button></div>
    <label class="admin-command-input" for="admin-command-search">@include('partials.admin.icon', ['name' => 'search'])<input id="admin-command-search" type="search" placeholder="Search admin pages…" autocomplete="off" /></label>
    <nav id="admin-command-results" aria-label="Search results">
        @foreach($adminNavigation as $items)
            @foreach($items as [$label, $route, $icon])
                <a href="{{ route($route) }}" data-search-label="{{ $label }}">@include('partials.admin.icon', ['name' => $icon])<span>{{ $label }}</span>@include('partials.admin.icon', ['name' => 'arrow'])</a>
            @endforeach
        @endforeach
    </nav>
    <p class="admin-command-empty" hidden>No matching pages. Try “nodes”, “users”, or “settings”.</p>
    <div class="admin-command-footer"><span>Tab to navigate · Enter to open</span><span><kbd>Esc</kbd> to close</span></div>
</dialog>
