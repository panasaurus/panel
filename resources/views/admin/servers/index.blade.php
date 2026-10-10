@extends('layouts.admin')

@section('title')
    List Servers
@endsection

@section('content-header')
    <h1>Servers<small>All servers available on the system.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Servers</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Server List</h3>
                <div class="box-tools admin-list-toolbar">
                    <form action="{{ route('admin.servers') }}" method="GET" class="admin-list-search">
                        <input type="search" name="filter[*]" class="form-control" value="{{ request()->input('filter.*', '') }}" placeholder="Search servers" aria-label="Search servers">
                        <button type="submit" class="btn btn-default" aria-label="Search servers">@include('partials.admin.icon', ['name' => 'search'])</button>
                    </form>
                    <a href="{{ route('admin.servers.new') }}" class="btn btn-primary">Create server</a>
                </div>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover">
                    <tbody>
                        <tr>
                            <th>Server Name</th>
                            <th>UUID</th>
                            <th>Owner</th>
                            <th>Node</th>
                            <th>Connection</th>
                            <th></th>
                            <th></th>
                        </tr>
                        @forelse ($servers as $server)
                            <tr data-server="{{ $server->uuidShort }}">
                                <td><a href="{{ route('admin.servers.view', $server->id) }}">{{ $server->name }}</a></td>
                                <td><code title="{{ $server->uuid }}">{{ $server->uuid }}</code></td>
                                <td><a href="{{ route('admin.users.view', $server->user->id) }}">{{ $server->user->username }} ({{ $server->user->email }})</a></td>
                                <td><a href="{{ route('admin.nodes.view', $server->node->id) }}">{{ $server->node->name }}</a></td>
                                <td>
                                    <code>{{ $server->allocation->alias }}:{{ $server->allocation->port }}</code>
                                </td>
                                <td class="text-center">
                                    @if($server->isSuspended())
                                        <span class="label bg-maroon">Suspended</span>
                                    @elseif(! $server->isInstalled())
                                        <span class="label label-warning">Installing</span>
                                    @else
                                        <span class="label label-success">Active</span>
                                    @endif

                                    @if($server->exclude_from_resource_calculation)
                                        <br><small><span class="label label-info" title="Excluded from resource calculations">Excluded</span></small>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a class="btn btn-xs btn-default" href="/server/{{ $server->uuidShort }}"><i class="fa fa-wrench"></i></a>
                                </td>
                            </tr>
                        @empty
                            <tr class="admin-empty-row"><td colspan="7">
                                <h3>{{ request()->filled('filter.*') ? 'No matching servers' : 'No servers yet' }}</h3>
                                <p>{{ request()->filled('filter.*') ? 'Try a different name or clear your search.' : 'Connect a node and create your first game server to get started.' }}</p>
                                <a class="btn btn-default" href="{{ request()->filled('filter.*') ? route('admin.servers') : route('admin.nodes') }}">{{ request()->filled('filter.*') ? 'Clear search' : 'Manage nodes' }}</a>
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($servers->hasPages())
                <div class="box-footer with-border">
                    <div class="col-md-12 text-center">{!! $servers->appends(['filter' => Request::input('filter')])->render() !!}</div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        $('.console-popout').on('click', function (event) {
            event.preventDefault();
            window.open($(this).attr('href'), 'Panasaurus Console', 'width=800,height=400');
        });
    </script>
@endsection
