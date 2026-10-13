<?php

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Files;

use Pterodactyl\Models\Permission;
use Pterodactyl\Contracts\Http\ClientPermissionsRequest;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class WriteFileContentRequest extends ClientApiRequest implements ClientPermissionsRequest
{
    public function authorize(): bool
    {
        $server = $this->route()->parameter('server');

        return $server instanceof \Pterodactyl\Models\Server
            && ($this->user()->can(Permission::ACTION_FILE_CREATE, $server)
                || $this->user()->can(Permission::ACTION_FILE_UPDATE, $server));
    }

    public function writeMode(): string
    {
        $server = $this->route()->parameter('server');
        $create = $this->user()->can(Permission::ACTION_FILE_CREATE, $server);
        $update = $this->user()->can(Permission::ACTION_FILE_UPDATE, $server);

        return $create && $update ? 'write' : ($create ? 'create' : 'update');
    }

    public function permission(): string
    {
        return Permission::ACTION_FILE_UPDATE;
    }

    public function rules(): array
    {
        return [
            'file' => 'required|string',
        ];
    }
}
