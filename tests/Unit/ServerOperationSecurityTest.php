<?php

namespace Pterodactyl\Tests\Unit;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\ServerOperation;
use Pterodactyl\Models\User;
use Pterodactyl\Http\Middleware\Api\Client\Server\ServerOperationRateLimit;
use Pterodactyl\Services\ServerOperations\ServerOperationService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class ServerOperationSecurityTest extends TestCase
{
    private string $previousConnection;
    private Server $server;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousConnection = DB::getDefaultConnection();
        config(['database.connections.security_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        DB::setDefaultConnection('security_test');
        Schema::create('servers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('uuid');
        });
        Schema::create('server_operations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('server_id');
            $table->integer('user_id');
            $table->string('operation_id');
            $table->string('type');
            $table->string('status');
            $table->text('message')->nullable();
            $table->text('parameters')->nullable();
            $table->timestamps();
        });
        DB::table('servers')->insert(['id' => 7, 'uuid' => '11111111-1111-4111-a111-111111111111']);
        $this->server = (new Server())->forceFill(['id' => 7, 'uuid' => '11111111-1111-4111-a111-111111111111']);
        $this->user = (new User())->forceFill(['id' => 42]);
    }

    protected function tearDown(): void
    {
        DB::purge('security_test');
        DB::setDefaultConnection($this->previousConnection);
        Schema::clearResolvedInstance('db.schema');
        parent::tearDown();
    }

    private function request(): Request
    {
        $request = Request::create('/settings/egg/apply', 'POST');
        $route = new Route('POST', '/settings/egg/apply', []);
        $route->bind($request);
        $route->setParameter('server', $this->server);
        $request->setRouteResolver(fn () => $route);
        $request->setUserResolver(fn () => $this->user);
        return $request;
    }

    public function test_active_operation_blocks_the_next_handler(): void
    {
        (new ServerOperationService())->createOperation($this->server, $this->user, 'egg_change');
        $reached = false;
        try {
            (new ServerOperationRateLimit())->handle($this->request(), function () use (&$reached) {
                $reached = true;
            });
            $this->fail('An active operation was accepted.');
        } catch (TooManyRequestsHttpException $exception) {
            $this->assertSame(429, $exception->getStatusCode());
            $this->assertFalse($reached);
        }
    }

    public function test_completed_operations_allow_the_next_handler(): void
    {
        $operation = (new ServerOperationService())->createOperation($this->server, $this->user, 'egg_change');
        $operation->update(['status' => ServerOperation::STATUS_COMPLETED]);
        $result = (new ServerOperationRateLimit())->handle($this->request(), fn () => 'allowed');
        $this->assertSame('allowed', $result);
    }

    public function test_second_operation_is_rejected_without_inserting_a_row(): void
    {
        $service = new ServerOperationService();
        $service->createOperation($this->server, $this->user, 'egg_change');
        try {
            $service->createOperation($this->server, $this->user, 'egg_change');
            $this->fail('A conflicting operation was inserted.');
        } catch (ConflictHttpException $exception) {
            $this->assertSame(409, $exception->getStatusCode());
            $this->assertSame(1, ServerOperation::count());
        }
    }

    public function test_broken_operation_storage_fails_closed(): void
    {
        Schema::drop('server_operations');
        $this->assertFalse((new ServerOperationService())->canAcceptOperation($this->server));
        try {
            (new ServerOperationRateLimit())->handle($this->request(), function () {
                $this->fail('A request passed without operation tracking.');
            });
            $this->fail('Missing operation tracking was accepted.');
        } catch (ServiceUnavailableHttpException $exception) {
            $this->assertSame(503, $exception->getStatusCode());
        }
        $schema = Schema::getFacadeRoot();
        Schema::swap(new class {
            public function hasTable(string $table): bool
            {
                throw new \RuntimeException('Isolated storage failure');
            }
        });
        try {
            $this->expectException(ServiceUnavailableHttpException::class);
            (new ServerOperationRateLimit())->handle($this->request(), function () {
                $this->fail('A request passed despite unavailable operation storage.');
            });
        } finally {
            Schema::swap($schema);
        }
    }
}
