<?php

namespace Pterodactyl\Tests\Unit;

use Illuminate\Routing\Route;
use Illuminate\Database\Eloquent\Collection;
use Pterodactyl\Enums\Daemon\JwtScope;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Subuser;
use Pterodactyl\Models\User;
use Pterodactyl\Repositories\Wings\DaemonServerRepository;
use Pterodactyl\Services\Nodes\NodeJWTService;
use Pterodactyl\Http\Requests\Api\Client\Servers\Files\WriteFileContentRequest;
use PHPUnit\Framework\TestCase;

class DaemonSecurityTest extends TestCase
{
    public function test_revocation_uses_the_identifier_in_the_issued_token(): void
    {
        $server = new Server();
        $server->forceFill(['id' => 7, 'uuid' => '11111111-1111-4111-a111-111111111111']);
        $node = $this->createMock(Node::class);
        $server->setRelation('node', $node);
        $node->method('getDecryptedKey')->willReturn(str_repeat('a', 64));
        $node->method('getConnectionAddress')->willReturn('http://localhost:8081');
        $identity = 42 . $server->uuid;
        $token = (new NodeJWTService())->setScopes(JwtScope::Websocket)
            ->setExpiresAt(new \DateTimeImmutable('+10 minutes'))->handle($node, $identity);
        foreach ([DaemonServerRepository::class, \Pterodactyl\Repositories\Elytra\DaemonServerRepository::class] as $implementation) {
            $repository = $this->getMockBuilder($implementation)
                ->disableOriginalConstructor()->onlyMethods(['revokeJTIs'])->getMock();
            $repository->expects($this->once())->method('revokeJTIs')->with([
                $token->claims()->get('jti'), hash('md5', $identity),
            ]);
            $repository->setServer($server)->revokeUserJTI(42);
        }
    }

    public function test_file_permissions_choose_an_atomic_scoped_endpoint(): void
    {
        foreach ([
            [[], false, null],
            [['file.create'], true, 'create'],
            [['file.update'], true, 'update'],
            [['file.create', 'file.update'], true, 'write'],
        ] as [$permissions, $authorized, $mode]) {
            $user = new User();
            $user->forceFill(['id' => 42, 'root_admin' => false]);
            $server = new Server();
            $server->forceFill(['id' => 7, 'owner_id' => 99]);
            $subuser = new Subuser();
            $subuser->forceFill(['user_id' => 42, 'server_id' => 7, 'permissions' => $permissions]);
            $server->setRelation('subusers', new Collection([$subuser]));
            $route = new Route('POST', '/files/write', []);
            $route->bind(\Illuminate\Http\Request::create('/files/write', 'POST'));
            $route->setParameter('server', $server);
            $request = new WriteFileContentRequest();
            $request->setRouteResolver(fn () => $route);
            $request->setUserResolver(fn () => $user);
            $this->assertSame($authorized, $request->authorize());
            if ($authorized) {
                $this->assertSame($mode, $request->writeMode());
            }
        }
    }
    public function test_failed_resource_sync_is_reported_instead_of_silently_accepted(): void
    {
        $server = $this->getMockBuilder(Server::class)->onlyMethods(['saveOrFail', 'refresh'])->getMock();
        $server->method('saveOrFail')->willReturn(true);
        $server->method('refresh')->willReturnSelf();
        $connection = $this->createMock(\Illuminate\Database\ConnectionInterface::class);
        $connection->method('transaction')->willReturnCallback(fn ($callback) => $callback());
        $structure = $this->getMockBuilder(\Pterodactyl\Services\Servers\ServerConfigurationStructureService::class)
            ->disableOriginalConstructor()->onlyMethods(['handle'])->getMock();
        $structure->method('handle')->willReturn(['build' => ['memory_limit' => 128]]);
        $daemon = $this->getMockBuilder(DaemonServerRepository::class)
            ->disableOriginalConstructor()->onlyMethods(['setServer', 'sync'])->getMock();
        $daemon->method('setServer')->willReturnSelf();
        $failure = new \Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException(
            new \GuzzleHttp\Exception\ClientException('Rejected configuration',
                new \GuzzleHttp\Psr7\Request('POST', 'http://audit.invalid/sync'),
                new \GuzzleHttp\Psr7\Response(409, [], '{"error":"Restart required"}'))
        );
        $daemon->method('sync')->willThrowException($failure);
        $this->expectExceptionObject($failure);
        (new \Pterodactyl\Services\Servers\BuildModificationService($connection, $daemon, $structure))
            ->handle($server, ['memory' => 128]);
    }

}
