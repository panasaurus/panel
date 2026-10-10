@extends('layouts.admin')
@section('title', 'Overview')

@section('content-header')
    <h1>Overview<small>Your hosting operation, at a glance.</small></h1>
    <div class="admin-page-actions">
        <a class="btn btn-primary" href="{{ $stats['nodes'] > 0 ? route('admin.servers.new') : ($stats['locations'] > 0 ? route('admin.nodes.new') : route('admin.locations')) }}">
            @include('partials.admin.icon', ['name' => 'plus'])
            {{ $stats['nodes'] > 0 ? 'Create server' : ($stats['locations'] > 0 ? 'Add node' : 'Add location') }}
        </a>
    </div>
@endsection

@section('content')
    <div class="admin-stats" aria-label="Workspace inventory">
        @foreach([
            ['Servers', 'servers', 'servers', 'admin.servers', 'Game servers in your workspace'],
            ['Users', 'users', 'users', 'admin.users', 'Registered accounts'],
            ['Nodes', 'nodes', 'nodes', 'admin.nodes', 'Registered host machines'],
            ['Game templates', 'eggs', 'nests', 'admin.nests', 'Available server configurations'],
        ] as [$label, $key, $icon, $route, $description])
            <a class="admin-stat" href="{{ route($route) }}">
                <span class="admin-stat-label">{{ $label }} @include('partials.admin.icon', ['name' => $icon])</span>
                <strong>{{ number_format($stats[$key]) }}</strong>
                <small>{{ $description }}</small>
            </a>
        @endforeach
    </div>

    <div class="admin-overview-grid">
        <div>
            <section class="admin-panel" aria-labelledby="admin-servers-heading">
                <header class="admin-panel-header">
                    <h2 id="admin-servers-heading">Recent servers</h2>
                    <a href="{{ route('admin.servers') }}">View all @include('partials.admin.icon', ['name' => 'arrow'])</a>
                </header>
                @if($recentServers->isEmpty())
                    <div class="admin-empty">
                        <span class="admin-empty-icon">@include('partials.admin.icon', ['name' => 'servers'])</span>
                        <h3>A home for your game servers.</h3>
                        <p>Connect a node, pick a game template, and manage your first server from here.</p>
                        <a class="btn btn-default" href="{{ $stats['nodes'] > 0 ? route('admin.servers.new') : ($stats['locations'] > 0 ? route('admin.nodes.new') : route('admin.locations')) }}">
                            {{ $stats['nodes'] > 0 ? 'Create your first server' : 'Set up your infrastructure' }} @include('partials.admin.icon', ['name' => 'arrow'])
                        </a>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead><tr><th scope="col">Server</th><th scope="col">Owner</th><th scope="col">Node</th><th scope="col">State</th></tr></thead>
                            <tbody>
                                @foreach($recentServers as $server)
                                    @php
                                        [$statusLabel, $statusStyle] = match ($server->status) {
                                            'suspended' => ['Suspended', 'danger'],
                                            'installing' => ['Installing', 'warning'],
                                            'install_failed', 'reinstall_failed' => ['Install failed', 'danger'],
                                            'restoring_backup' => ['Restoring', 'warning'],
                                            default => ['Installed', 'success'],
                                        };
                                    @endphp
                                    <tr>
                                        <td><a href="{{ route('admin.servers.view', $server->id) }}">{{ $server->name }}</a></td>
                                        <td><a href="{{ route('admin.users.view', $server->user->id) }}">{{ $server->user->username }}</a></td>
                                        <td><a href="{{ route('admin.nodes.view', $server->node->id) }}">{{ $server->node->name }}</a></td>
                                        <td><span class="admin-status admin-status--{{ $statusStyle }}">{{ $statusLabel }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            @if($stats['servers'] === 0)
                <section class="admin-panel" aria-labelledby="admin-setup-heading">
                    <header class="admin-panel-header"><h2 id="admin-setup-heading">Getting started</h2><span class="admin-status">{{ ($stats['locations'] > 0 ? 1 : 0) + ($stats['nodes'] > 0 ? 1 : 0) }} of 3 complete</span></header>
                    <p class="admin-setup-intro">A few steps to get your hosting workspace ready.</p>
                    <ol class="admin-setup-list">
                        @foreach([
                            ['Add a location', 'Group your host machines by region.', 'admin.locations', $stats['locations'] > 0],
                            ['Connect a node', 'Link a machine running Wings to your panel.', 'admin.nodes.new', $stats['nodes'] > 0],
                            ['Create a game server', 'Choose a template and assign its resources.', 'admin.servers.new', false],
                        ] as [$label, $description, $route, $complete])
                            <li><a href="{{ route($route) }}">
                                <span class="admin-step-marker {{ $complete ? 'is-complete' : '' }}">@if($complete) @include('partials.admin.icon', ['name' => 'check']) @else {{ $loop->iteration }} @endif</span>
                                <span><strong>{{ $label }} @if($complete)<span class="sr-only">— complete</span>@endif</strong><small>{{ $description }}</small></span>
                                @include('partials.admin.icon', ['name' => 'arrow'])
                            </a></li>
                        @endforeach
                    </ol>
                </section>
            @else
                <section class="admin-panel" aria-labelledby="admin-infrastructure-heading">
                    <header class="admin-panel-header"><h2 id="admin-infrastructure-heading">Infrastructure</h2><a href="{{ route('admin.nodes') }}">Manage nodes @include('partials.admin.icon', ['name' => 'arrow'])</a></header>
                    <div class="table-responsive"><table class="table table-hover"><thead><tr><th scope="col">Node</th><th scope="col">Servers</th><th scope="col">Memory capacity</th><th scope="col">State</th></tr></thead><tbody>
                        @foreach($recentNodes as $node)
                            <tr><td><a href="{{ route('admin.nodes.view', $node->id) }}">{{ $node->name }}</a></td><td>{{ $node->servers_count }}</td><td>{{ number_format($node->memory / 1024, 1) }} GiB</td><td><span class="admin-status">{{ $node->maintenance_mode ? 'Maintenance' : 'Available' }}</span></td></tr>
                        @endforeach
                    </tbody></table></div>
                </section>
            @endif
        </div>

        <aside class="admin-overview-aside" aria-label="System information">
            <section class="admin-panel" aria-labelledby="admin-health-heading">
                <header class="admin-panel-header"><h2 id="admin-health-heading" class="admin-health-heading">@include('partials.admin.icon', ['name' => 'activity']) Panel health</h2><button type="button" class="admin-icon-button" data-admin-health-refresh aria-label="Check service status" title="Check service status">@include('partials.admin.icon', ['name' => 'refresh'])</button></header>
                <div class="admin-health-body">
                    @foreach([['database', 'Database', 'database'], ['redis', 'Cache', 'nodes'], ['panel', 'Storage', 'servers']] as [$key, $label, $icon])
                        <div class="admin-health-row"><span class="admin-health-name">@include('partials.admin.icon', ['name' => $icon]) {{ $label }}</span><span class="admin-status" data-admin-health="{{ $key }}">Checking…</span></div>
                    @endforeach
                    <p class="admin-health-footer" data-admin-health-checked role="status" aria-live="polite">Checking your panel services…</p>
                </div>
            </section>
            <section class="admin-panel" aria-labelledby="admin-workspace-heading">
                <header class="admin-panel-header"><h2 id="admin-workspace-heading">Your workspace</h2>@include('partials.admin.icon', ['name' => 'shield'])</header>
                <dl class="admin-system-details">
                    <div><dt>Panel version</dt><dd>{{ config('app.version') }}</dd></div>
                    <div><dt>Blueprint</dt><dd>{{ $blueprintInstalled ? 'Installed' : 'Not installed' }}</dd></div>
                    <div><dt>Timezone</dt><dd>{{ config('app.timezone') }}</dd></div>
                    <div><dt>Locations</dt><dd>{{ number_format($stats['locations']) }}</dd></div>
                </dl>
                <div class="admin-shortcuts"><a href="{{ route('admin.settings') }}">Panel settings</a><a href="{{ route('admin.extensions') }}">Extensions</a></div>
            </section>
        </aside>
    </div>
@endsection
