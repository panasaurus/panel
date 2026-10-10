<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Illuminate\View\View;
use Illuminate\View\Factory as ViewFactory;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\User;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Helpers\SoftwareVersionService;

class BaseController extends Controller
{
    public function __construct(private SoftwareVersionService $version, private ViewFactory $view)
    {
    }

    public function index(): View
    {
        return $this->view->make('admin.overview', [
            'version' => $this->version,
            'stats' => [
                'servers' => Server::query()->count(),
                'users' => User::query()->count(),
                'nodes' => Node::query()->count(),
                'locations' => Location::query()->count(),
                'eggs' => Egg::query()->count(),
            ],
            'recentServers' => Server::query()->with(['user', 'node'])->latest()->limit(6)->get(),
            'recentNodes' => Node::query()->withCount('servers')->latest()->limit(4)->get(),
            'blueprintInstalled' => file_exists(base_path('.blueprint/extensions/blueprint/private/db/is_installed')),
        ]);
    }
}
