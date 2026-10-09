<?php

namespace Pterodactyl\Repositories\Elytra;

use Webmozart\Assert\Assert;
use Pterodactyl\Models\Server;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\TransferException;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;

/**
 * @method \Pterodactyl\Repositories\Elytra\DaemonPowerRepository setNode(\Pterodactyl\Models\Node $node)
 * @method \Pterodactyl\Repositories\Elytra\DaemonPowerRepository setServer(\Pterodactyl\Models\Server $server)
 */
class DaemonPowerRepository extends DaemonRepository
{
    /**
     * Sends a power action to the server instance.
     *
     * @throws DaemonConnectionException
     */
    public function send(string $action): ResponseInterface
    {
        Assert::isInstanceOf($this->server, Server::class);

        try {
            return $this->getHttpClient()->post(
                sprintf('/api/servers/%s/power', $this->server->uuid),
                ['json' => ['action' => $action]]
            );
        } catch (TransferException $exception) {
            throw new DaemonConnectionException($exception);
        }
    }
}
