<?php

namespace Tests\Unit;

use App\Services\Mikrotik\RouterOsClient;
use Mockery;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class RouterOsActiveSessionTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function client(array $response): RouterOsClient
    {
        $client = Mockery::mock(RouterOsClient::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $client->shouldReceive('withConnection')->andReturnUsing(fn (callable $callback) => $callback());
        $client->shouldReceive('comm')->with('/ppp/active/print', ['?name' => 'temporary-user'])->andReturn($response);

        return $client;
    }

    public function test_active_session_includes_the_assigned_address(): void
    {
        $session = ['name' => 'temporary-user', 'address' => '10.50.0.10'];
        $client = $this->client([$session]);

        $this->assertSame($session, $client->getActiveSession('temporary-user'));
        $this->assertTrue($client->isUserConnected('temporary-user'));
    }

    public function test_offline_user_has_no_active_session(): void
    {
        $client = $this->client([]);

        $this->assertNull($client->getActiveSession('temporary-user'));
        $this->assertFalse($client->isUserConnected('temporary-user'));
    }

    public function test_router_failure_is_not_treated_as_an_offline_user(): void
    {
        $client = $this->client(['!trap' => [['message' => 'Access denied']]]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Access denied');
        $client->getActiveSession('temporary-user');
    }
}
