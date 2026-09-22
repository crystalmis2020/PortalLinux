<?php

namespace Tests\Feature;

use App\Http\Controllers\InternetAccessRequestController;
use App\Models\InternetAccessConnectorToken;
use App\Models\InternetAccessRequest;
use App\Models\User;
use App\Services\Mikrotik\RouterOsClient;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Mockery;
use Tests\TestCase;

class InternetAccessConnectorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $testConnection = $this->connectorTestConnection();

        if ($testConnection === null) {
            $this->markTestSkipped('The connector tests require pdo_sqlite or CONNECTOR_TEST_DB_SOCKET.');
        }

        config([
            'app.key' => 'base64:'.base64_encode(str_repeat('c', 32)),
            'database.default' => 'connector_testing',
            'database.connections.connector_testing' => $testConnection,
            'mikrotik.connector.enabled' => true,
            'mikrotik.connector.protocol' => 'supportportal-connect',
            'mikrotik.connector.connection_name' => 'Broadband Connection',
            'mikrotik.connector.token_ttl_seconds' => 120,
            'mikrotik.connector.verification_window_seconds' => 300,
            'mikrotik.connector.bind_token_to_ip' => true,
            'mikrotik.connector.require_https' => false,
        ]);

        $this->app->forgetInstance('encrypter');
        DB::purge('connector_testing');
        DB::setDefaultConnection('connector_testing');
        URL::forceRootUrl('http://localhost');
        $this->dropSchema();
        $this->createSchema();
    }

    protected function tearDown(): void
    {
        if (config('database.default') === 'connector_testing') {
            $this->dropSchema();
            DB::disconnect('connector_testing');
        }

        parent::tearDown();
    }

    public function test_owner_can_prepare_a_connector_launch_without_receiving_credentials(): void
    {
        $user = $this->user('requester');
        $internetRequest = $this->internetRequest($user);

        $response = $this->actingAs($user)
            ->withServerVariables(['REMOTE_ADDR' => '10.20.30.40'])
            ->postJson(route('internet-access.connector.token', $internetRequest));

        $response->assertOk()
            ->assertJsonStructure(['launch_uri', 'expires_at'])
            ->assertJsonMissing(['username' => $internetRequest->username]);
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));

        $launchUri = $response->json('launch_uri');
        $this->assertStringStartsWith('supportportal-connect://connect?token=', $launchUri);
        $this->assertStringNotContainsString($internetRequest->username, $launchUri);
        $this->assertStringNotContainsString('server-only-password', $launchUri);

        parse_str((string) parse_url($launchUri, PHP_URL_QUERY), $query);
        $storedToken = InternetAccessConnectorToken::query()->sole();

        $this->assertSame(hash('sha256', $query['token']), $storedToken->token_hash);
        $this->assertSame('10.20.30.40', $storedToken->issued_ip);
        $this->assertDatabaseMissing('internet_access_connector_tokens', [
            'token_hash' => $query['token'],
        ]);
    }

    public function test_guest_non_owner_and_non_ready_requests_cannot_prepare_a_launch(): void
    {
        $owner = $this->user('owner');
        $otherUser = $this->user('other');
        $readyRequest = $this->internetRequest($owner);

        $this->postJson(route('internet-access.connector.token', $readyRequest))->assertUnauthorized();

        $this->actingAs($otherUser)
            ->postJson(route('internet-access.connector.token', $readyRequest))
            ->assertForbidden();

        $pendingRequest = $this->internetRequest($otherUser, InternetAccessRequest::STATUS_PENDING);

        $this->actingAs($otherUser)
            ->postJson(route('internet-access.connector.token', $pendingRequest))
            ->assertConflict();
    }

    public function test_connector_can_exchange_a_valid_token_only_once(): void
    {
        $user = $this->user('requester');
        $internetRequest = $this->internetRequest($user);
        $plainToken = $this->issueToken($user, $internetRequest, '10.10.0.25');

        $firstResponse = $this->withToken($plainToken)
            ->withServerVariables(['REMOTE_ADDR' => '10.10.0.25'])
            ->postJson(route('api.internet-access.connector.exchange'), $this->connectorPayload());

        $firstResponse->assertOk()
            ->assertJson([
                'connection_name' => 'Broadband Connection',
                'username' => $internetRequest->username,
                'password' => 'server-only-password',
            ])
            ->assertJsonStructure(['verification_url']);
        $this->assertStringContainsString('no-store', (string) $firstResponse->headers->get('Cache-Control'));

        $this->withToken($plainToken)
            ->withServerVariables(['REMOTE_ADDR' => '10.10.0.25'])
            ->postJson(route('api.internet-access.connector.exchange'), $this->connectorPayload())
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'The connector token is invalid or has expired.']);

        $token = InternetAccessConnectorToken::query()->sole();
        $this->assertNotNull($token->consumed_at);
        $this->assertSame('10.10.0.25', $token->consumed_ip);
        $this->assertSame(InternetAccessRequest::STATUS_READY, $internetRequest->fresh()->status);
    }

    public function test_previous_connector_version_remains_supported_during_certificate_rollout(): void
    {
        $user = $this->user('requester');
        $internetRequest = $this->internetRequest($user);
        $plainToken = $this->issueToken($user, $internetRequest, '10.10.0.25');

        $this->withToken($plainToken)
            ->withServerVariables(['REMOTE_ADDR' => '10.10.0.25'])
            ->postJson(route('api.internet-access.connector.exchange'), [
                'connector_version' => '1.0.0',
                'connection_name' => 'Broadband Connection',
            ])
            ->assertOk();
    }

    public function test_connector_rejects_an_unsupported_version_without_consuming_the_token(): void
    {
        $user = $this->user('requester');
        $internetRequest = $this->internetRequest($user);
        $plainToken = $this->issueToken($user, $internetRequest, '10.10.0.25');

        $this->withToken($plainToken)
            ->withServerVariables(['REMOTE_ADDR' => '10.10.0.25'])
            ->postJson(route('api.internet-access.connector.exchange'), [
                'connector_version' => '2.0.0',
                'connection_name' => 'Broadband Connection',
            ])
            ->assertUnprocessable();

        $this->assertNull(InternetAccessConnectorToken::query()->sole()->consumed_at);
    }

    public function test_expired_or_wrong_ip_tokens_do_not_disclose_credentials(): void
    {
        $user = $this->user('requester');
        $internetRequest = $this->internetRequest($user);
        $plainToken = $this->issueToken($user, $internetRequest, '10.0.0.10');

        $this->withToken($plainToken)
            ->withServerVariables(['REMOTE_ADDR' => '10.0.0.11'])
            ->postJson(route('api.internet-access.connector.exchange'), $this->connectorPayload())
            ->assertUnauthorized()
            ->assertJsonMissing(['password' => 'server-only-password']);

        InternetAccessConnectorToken::query()->update(['expires_at' => now()->subSecond()]);

        $this->withToken($plainToken)
            ->withServerVariables(['REMOTE_ADDR' => '10.0.0.10'])
            ->postJson(route('api.internet-access.connector.exchange'), $this->connectorPayload())
            ->assertUnauthorized()
            ->assertJsonMissing(['password' => 'server-only-password']);
    }

    public function test_only_mikrotik_confirmation_activates_the_request_and_does_not_extend_it_twice(): void
    {
        $user = $this->user('requester');
        $internetRequest = $this->internetRequest($user);
        $plainToken = $this->issueToken($user, $internetRequest, '10.0.0.20');

        $this->withToken($plainToken)
            ->withServerVariables(['REMOTE_ADDR' => '10.0.0.20'])
            ->postJson(route('api.internet-access.connector.exchange'), $this->connectorPayload())
            ->assertOk();

        $mikrotik = Mockery::mock(RouterOsClient::class);
        $mikrotik->shouldReceive('isUserConnected')
            ->twice()
            ->with($internetRequest->username)
            ->andReturn(true);
        $this->app->instance(RouterOsClient::class, $mikrotik);

        $this->withToken($plainToken)
            ->withServerVariables(['REMOTE_ADDR' => '198.51.100.80'])
            ->postJson(route('api.internet-access.connector.verify'), $this->connectorPayload())
            ->assertOk()
            ->assertJson(['connected' => true, 'status' => InternetAccessRequest::STATUS_ACTIVE]);

        $activatedRequest = $internetRequest->fresh();
        $connectedAt = $activatedRequest->connected_at->copy();
        $expiresAt = $activatedRequest->expires_at->copy();

        $this->travel(30)->seconds();

        $this->withToken($plainToken)
            ->withServerVariables(['REMOTE_ADDR' => '203.0.113.90'])
            ->postJson(route('api.internet-access.connector.verify'), $this->connectorPayload())
            ->assertOk()
            ->assertJson(['connected' => true, 'status' => InternetAccessRequest::STATUS_ACTIVE]);

        $activatedRequest->refresh();
        $this->assertTrue($connectedAt->equalTo($activatedRequest->connected_at));
        $this->assertTrue($expiresAt->equalTo($activatedRequest->expires_at));
    }

    public function test_verification_cannot_reactivate_a_request_that_expired_during_router_lookup(): void
    {
        $user = $this->user('requester');
        $internetRequest = $this->internetRequest($user);
        $plainToken = $this->issueToken($user, $internetRequest, '10.0.0.20');

        $this->withToken($plainToken)
            ->withServerVariables(['REMOTE_ADDR' => '10.0.0.20'])
            ->postJson(route('api.internet-access.connector.exchange'), $this->connectorPayload())
            ->assertOk();

        $mikrotik = Mockery::mock(RouterOsClient::class);
        $mikrotik->shouldReceive('isUserConnected')
            ->once()
            ->andReturnUsing(function () use ($internetRequest): bool {
                InternetAccessRequest::query()
                    ->whereKey($internetRequest->id)
                    ->update(['status' => InternetAccessRequest::STATUS_EXPIRED]);

                return true;
            });
        $this->app->instance(RouterOsClient::class, $mikrotik);

        $this->withToken($plainToken)
            ->postJson(route('api.internet-access.connector.verify'), $this->connectorPayload())
            ->assertConflict();

        $this->assertSame(InternetAccessRequest::STATUS_EXPIRED, $internetRequest->fresh()->status);
        $this->assertNull($internetRequest->fresh()->connected_at);
    }

    public function test_password_is_encrypted_at_rest_and_hidden_from_serialization(): void
    {
        $user = $this->user('requester');
        $internetRequest = $this->internetRequest($user);

        $storedPassword = DB::table('internet_access_requests')
            ->where('id', $internetRequest->id)
            ->value('password');

        $this->assertNotSame('server-only-password', $storedPassword);
        $this->assertSame('server-only-password', $internetRequest->fresh()->password);
        $this->assertArrayNotHasKey('password', $internetRequest->fresh()->toArray());
    }

    public function test_authenticated_user_can_download_a_portal_bound_connector_package(): void
    {
        $user = $this->user('requester');

        config([
            'app.url' => 'https://portal.example.test',
            'mikrotik.connector.require_https' => true,
        ]);

        $response = $this->actingAs($user)
            ->withServerVariables([
                'HTTPS' => 'on',
                'SERVER_PORT' => '443',
                'HTTP_HOST' => 'portal.example.test',
            ])
            ->get('https://portal.example.test/internet-access/connector/download');

        $response->assertOk()
            ->assertDownload('Support-Portal-Internet-Connector-1.1.0.zip');

        $archive = new \ZipArchive;
        $this->assertTrue($archive->open($response->baseResponse->getFile()->getPathname()));
        $this->assertSame("https://portal.example.test\r\n", $archive->getFromName('portal-url.txt'));

        foreach ([
            'README.md',
            'install.cmd',
            'install.ps1',
            'uninstall.cmd',
            'uninstall.ps1',
            'certs/support-portal-root-ca.cer',
            'src/connector.ps1',
        ] as $packageFile) {
            $this->assertNotFalse($archive->locateName($packageFile));
        }

        $archive->close();
    }

    public function test_active_request_can_reconnect_without_resetting_its_expiration(): void
    {
        $user = $this->user('requester');
        $internetRequest = $this->internetRequest($user, InternetAccessRequest::STATUS_ACTIVE);
        $internetRequest->update(['connected_at' => now()->subMinutes(20), 'expires_at' => now()->addMinutes(40)]);
        $originalExpiry = $internetRequest->expires_at->copy();
        $originalConnection = $internetRequest->connected_at->copy();
        $token = $this->issueToken($user, $internetRequest, '10.0.0.20');

        $this->withToken($token)->withServerVariables(['REMOTE_ADDR' => '10.0.0.20'])
            ->postJson(route('api.internet-access.connector.exchange'), $this->connectorPayload())
            ->assertOk()->assertJson(['username' => $internetRequest->username]);

        $mikrotik = Mockery::mock(RouterOsClient::class);
        $mikrotik->shouldReceive('isUserConnected')->twice()->andReturn(false, true);
        $this->app->instance(RouterOsClient::class, $mikrotik);

        $this->withToken($token)
            ->postJson(route('api.internet-access.connector.verify'), $this->connectorPayload())
            ->assertStatus(202)->assertJson(['connected' => false, 'status' => 'active']);
        $this->withToken($token)
            ->postJson(route('api.internet-access.connector.verify'), $this->connectorPayload())
            ->assertOk()->assertJson(['connected' => true, 'status' => 'active']);

        $internetRequest->refresh();
        $this->assertTrue($originalExpiry->equalTo($internetRequest->expires_at));
        $this->assertTrue($originalConnection->equalTo($internetRequest->connected_at));
    }

    public function test_reconnect_is_rejected_when_time_runs_out_before_scheduler_cleanup(): void
    {
        $user = $this->user('requester');
        $internetRequest = $this->internetRequest($user, InternetAccessRequest::STATUS_ACTIVE);
        $internetRequest->update(['connected_at' => now()->subHour(), 'expires_at' => now()->addSeconds(5)]);
        $token = $this->issueToken($user, $internetRequest, '10.0.0.20');
        $this->travel(6)->seconds();

        $this->actingAs($user)->postJson(route('internet-access.connector.token', $internetRequest))
            ->assertConflict();
        $this->withToken($token)->withServerVariables(['REMOTE_ADDR' => '10.0.0.20'])
            ->postJson(route('api.internet-access.connector.exchange'), $this->connectorPayload())
            ->assertUnauthorized();
    }

    public function test_submission_immediately_provisions_access_without_starting_the_timer(): void
    {
        $user = $this->user('requester');
        $mikrotik = Mockery::mock(RouterOsClient::class);
        $mikrotik->shouldReceive('createTemporaryAccess')->once()->andReturn('*immediate-secret');
        $this->app->instance(RouterOsClient::class, $mikrotik);

        $this->actingAs($user)->post(route('internet-access.store'), [
            'requested_hours' => '1h',
            'purpose' => 'Work research',
        ])->assertRedirect(route('internet-access.index'))->assertSessionHas('success');

        $internetRequest = InternetAccessRequest::where('user_id', $user->id)->sole();
        $this->assertSame(InternetAccessRequest::STATUS_READY, $internetRequest->status);
        $this->assertSame('*immediate-secret', $internetRequest->mikrotik_reference_id);
        $this->assertNull($internetRequest->connected_at);
        $this->assertNull($internetRequest->expires_at);
    }

    public function test_submission_reports_provisioning_failure_without_claiming_approval(): void
    {
        $user = $this->user('requester');
        $mikrotik = Mockery::mock(RouterOsClient::class);
        $mikrotik->shouldReceive('createTemporaryAccess')->once()
            ->andThrow(new \RuntimeException('Router unavailable'));
        $this->app->instance(RouterOsClient::class, $mikrotik);

        $this->actingAs($user)->post(route('internet-access.store'), [
            'requested_hours' => '4h',
            'purpose' => 'Work research',
        ])->assertRedirect(route('internet-access.index'))
            ->assertSessionHas('error')->assertSessionMissing('success');

        $internetRequest = InternetAccessRequest::where('user_id', $user->id)->sole();
        $this->assertSame(InternetAccessRequest::STATUS_FAILED, $internetRequest->status);
        $this->assertNull($internetRequest->expires_at);
    }

    public function test_stale_approval_attempts_provision_a_request_only_once(): void
    {
        $user = $this->user('requester');
        $internetRequest = $this->internetRequest($user, InternetAccessRequest::STATUS_PENDING);
        $secondStaleInstance = InternetAccessRequest::query()->findOrFail($internetRequest->id);

        $mikrotik = Mockery::mock(RouterOsClient::class);
        $mikrotik->shouldReceive('createTemporaryAccess')
            ->once()
            ->andReturn('*temporary-secret');

        $controller = app(InternetAccessRequestController::class);
        $controller->provision($internetRequest, $mikrotik);
        $controller->provision($secondStaleInstance, $mikrotik);

        $internetRequest->refresh();
        $this->assertSame(InternetAccessRequest::STATUS_READY, $internetRequest->status);
        $this->assertSame('*temporary-secret', $internetRequest->mikrotik_reference_id);
    }

    private function issueToken(User $user, InternetAccessRequest $internetRequest, string $ip): string
    {
        $response = $this->actingAs($user)
            ->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson(route('internet-access.connector.token', $internetRequest))
            ->assertOk();

        parse_str((string) parse_url($response->json('launch_uri'), PHP_URL_QUERY), $query);

        return $query['token'];
    }

    private function connectorPayload(): array
    {
        return [
            'connector_version' => '1.1.0',
            'connection_name' => 'Broadband Connection',
        ];
    }

    private function user(string $username): User
    {
        return User::withoutEvents(fn () => User::query()->create([
            'name' => ucfirst($username),
            'full_name' => ucfirst($username).' User',
            'username' => $username,
            'password' => hash('sha256', 'password'),
            'user_type' => 'user',
            'is_active' => true,
        ]));
    }

    private function internetRequest(User $user, string $status = InternetAccessRequest::STATUS_READY): InternetAccessRequest
    {
        return InternetAccessRequest::query()->create([
            'user_id' => $user->id,
            'requester_ip' => '192.0.2.10',
            'purpose' => 'Feature test',
            'requested_hours' => '1h',
            'duration_minutes' => 60,
            'username' => '1h'.bin2hex(random_bytes(4)),
            'password' => 'server-only-password',
            'mikrotik_profile' => 'test-profile',
            'status' => $status,
        ]);
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('full_name')->nullable();
            $table->string('username')->nullable()->unique();
            $table->string('password');
            $table->string('user_type')->nullable();
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('internet_access_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('requester_ip', 45)->nullable();
            $table->text('purpose');
            $table->string('requested_hours');
            $table->unsignedSmallInteger('duration_minutes');
            $table->string('username')->unique();
            $table->text('password');
            $table->string('mikrotik_profile');
            $table->string('mikrotik_reference_id')->nullable();
            $table->string('status')->index();
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamp('last_seen_online_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('internet_access_connector_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('internet_access_request_id');
            $table->foreign('internet_access_request_id', 'iac_tokens_request_fk')
                ->references('id')
                ->on('internet_access_requests')
                ->cascadeOnDelete();
            $table->char('token_hash', 64)->unique();
            $table->string('issued_ip', 45)->nullable();
            $table->string('consumed_ip', 45)->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();
        });
    }

    private function dropSchema(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('internet_access_connector_tokens');
        Schema::dropIfExists('internet_access_requests');
        Schema::dropIfExists('users');
        Schema::enableForeignKeyConstraints();
    }

    private function connectorTestConnection(): ?array
    {
        if (extension_loaded('pdo_sqlite')) {
            return [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ];
        }

        $socket = getenv('CONNECTOR_TEST_DB_SOCKET');

        if (! is_string($socket) || $socket === '') {
            return null;
        }

        return [
            'driver' => 'mysql',
            'host' => 'localhost',
            'port' => 3306,
            'database' => getenv('CONNECTOR_TEST_DB_DATABASE') ?: 'support_portal_connector_test',
            'username' => getenv('CONNECTOR_TEST_DB_USERNAME') ?: 'root',
            'password' => getenv('CONNECTOR_TEST_DB_PASSWORD') ?: '',
            'unix_socket' => $socket,
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ];
    }
}
