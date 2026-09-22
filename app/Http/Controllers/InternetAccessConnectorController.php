<?php

namespace App\Http\Controllers;

use App\Models\InternetAccessConnectorToken;
use App\Models\InternetAccessRequest;
use App\Services\Mikrotik\RouterOsClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use ZipArchive;

class InternetAccessConnectorController extends Controller
{
    private const CONNECTOR_VERSION = '1.1.0';

    private const SUPPORTED_CONNECTOR_VERSIONS = ['1.0.0', '1.1.0'];

    public function issue(Request $request, InternetAccessRequest $internetAccessRequest): JsonResponse
    {
        $this->ensureConnectorIsAvailable($request);

        abort_unless($internetAccessRequest->user_id === $request->user()->id, 403);

        $plainToken = $this->newToken();
        $expiresAt = now()->addSeconds($this->tokenTtl());

        DB::transaction(function () use ($internetAccessRequest, $request, $plainToken, $expiresAt): void {
            $lockedRequest = InternetAccessRequest::query()
                ->lockForUpdate()
                ->findOrFail($internetAccessRequest->id);

            abort_unless($lockedRequest->user_id === $request->user()->id, 403);

            if (! $lockedRequest->canConnect()) {
                throw new HttpException(409, 'This internet access request is not ready to connect.');
            }

            $lockedRequest->connectorTokens()
                ->whereNull('consumed_at')
                ->update(['consumed_at' => now()]);

            $lockedRequest->connectorTokens()->create([
                'token_hash' => $this->hashToken($plainToken),
                'issued_ip' => $request->ip(),
                'expires_at' => $expiresAt,
            ]);
        });

        $protocol = (string) config('mikrotik.connector.protocol', 'supportportal-connect');

        if (! preg_match('/^[a-z][a-z0-9+.-]*$/i', $protocol)) {
            throw new HttpException(500, 'The Internet Access connector protocol is invalid.');
        }

        return response()->json([
            'launch_uri' => $protocol.'://connect?token='.rawurlencode($plainToken),
            'expires_at' => $expiresAt->toIso8601String(),
        ])->withHeaders($this->noStoreHeaders());
    }

    public function exchange(Request $request): JsonResponse
    {
        $this->ensureConnectorIsAvailable($request);
        $this->ensureExpectedConnectorRequest($request);

        $plainToken = $this->bearerToken($request);
        $connectorToken = DB::transaction(function () use ($request, $plainToken) {
            $connectorToken = InternetAccessConnectorToken::query()
                ->with('internetAccessRequest')
                ->where('token_hash', $this->hashToken($plainToken))
                ->lockForUpdate()
                ->first();

            if (! $this->canExchange($connectorToken, $request)) {
                return null;
            }

            $consumed = InternetAccessConnectorToken::query()
                ->whereKey($connectorToken->id)
                ->whereNull('consumed_at')
                ->update([
                    'consumed_at' => now(),
                    'consumed_ip' => $request->ip(),
                ]);

            if ($consumed !== 1) {
                return null;
            }

            $connectorToken->refresh();

            return $connectorToken;
        });

        if (! $connectorToken) {
            return $this->invalidTokenResponse();
        }

        $internetRequest = $connectorToken->internetAccessRequest;

        return response()->json([
            'connection_name' => (string) config('mikrotik.connector.connection_name', 'Broadband Connection'),
            'username' => $internetRequest->username,
            'password' => $internetRequest->password,
            'verification_url' => $this->connectorPortalBaseUrl().'/api/internet-access/connector/verify',
        ])->withHeaders($this->noStoreHeaders());
    }

    public function verify(Request $request, RouterOsClient $mikrotik): JsonResponse
    {
        $this->ensureConnectorIsAvailable($request);
        $this->ensureExpectedConnectorRequest($request);

        $plainToken = $this->bearerToken($request);
        $connectorToken = InternetAccessConnectorToken::query()
            ->with('internetAccessRequest')
            ->where('token_hash', $this->hashToken($plainToken))
            ->first();

        if (! $this->canVerify($connectorToken)) {
            return $this->invalidTokenResponse();
        }

        $internetRequest = $connectorToken->internetAccessRequest;

        try {
            $connected = $mikrotik->isUserConnected($internetRequest->username);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'The PPPoE connection was started, but MikroTik verification is temporarily unavailable.',
            ], 503)->withHeaders($this->noStoreHeaders());
        }

        $internetRequest->refresh();

        if (! $internetRequest->canConnect()) {
            return response()->json([
                'message' => 'This internet access request is no longer available.',
            ], 409)->withHeaders($this->noStoreHeaders());
        }

        if (! $connected) {
            return response()->json([
                'connected' => false,
                'status' => $internetRequest->status,
            ], 202)->withHeaders($this->noStoreHeaders());
        }

        if ($internetRequest->status === InternetAccessRequest::STATUS_ACTIVE) {
            $internetRequest->update(['last_seen_online_at' => now()]);

            return response()->json([
                'connected' => true,
                'status' => InternetAccessRequest::STATUS_ACTIVE,
            ])->withHeaders($this->noStoreHeaders());
        }

        $connectedAt = now();

        $updated = InternetAccessRequest::query()
            ->whereKey($internetRequest->id)
            ->where('status', InternetAccessRequest::STATUS_READY)
            ->update([
                'status' => InternetAccessRequest::STATUS_ACTIVE,
                'connected_at' => $connectedAt,
                'expires_at' => $connectedAt->copy()->addMinutes($internetRequest->duration_minutes),
                'last_seen_online_at' => $connectedAt,
                'failure_reason' => null,
            ]);

        if ($updated !== 1) {
            $currentStatus = InternetAccessRequest::query()
                ->whereKey($internetRequest->id)
                ->value('status');

            if ($currentStatus === InternetAccessRequest::STATUS_ACTIVE) {
                return response()->json([
                    'connected' => true,
                    'status' => InternetAccessRequest::STATUS_ACTIVE,
                ])->withHeaders($this->noStoreHeaders());
            }

            return response()->json([
                'message' => 'This internet access request is no longer ready to connect.',
            ], 409)->withHeaders($this->noStoreHeaders());
        }

        return response()->json([
            'connected' => true,
            'status' => InternetAccessRequest::STATUS_ACTIVE,
        ])->withHeaders($this->noStoreHeaders());
    }

    public function download(Request $request): BinaryFileResponse
    {
        $this->ensureConnectorIsAvailable($request);

        $packageDirectory = base_path('tools/internet-access-connector');
        $packageFiles = [
            'README.md',
            'install.cmd',
            'install.ps1',
            'uninstall.cmd',
            'uninstall.ps1',
            'certs/support-portal-root-ca.cer',
            'src/connector.ps1',
        ];

        foreach ($packageFiles as $packageFile) {
            if (! is_file($packageDirectory.DIRECTORY_SEPARATOR.$packageFile)) {
                throw new HttpException(503, 'The Windows connector package is incomplete.');
            }
        }

        $temporaryPath = tempnam(sys_get_temp_dir(), 'support-portal-connector-');

        if ($temporaryPath === false) {
            throw new HttpException(503, 'The Windows connector package could not be prepared.');
        }

        $archive = new ZipArchive;

        if ($archive->open($temporaryPath, ZipArchive::OVERWRITE) !== true) {
            @unlink($temporaryPath);
            throw new HttpException(503, 'The Windows connector package could not be prepared.');
        }

        try {
            foreach ($packageFiles as $packageFile) {
                $archive->addFile(
                    $packageDirectory.DIRECTORY_SEPARATOR.$packageFile,
                    $packageFile
                );
            }

            $archive->addFromString('portal-url.txt', $this->connectorPortalBaseUrl()."\r\n");
        } finally {
            $archive->close();
        }

        return response()
            ->download($temporaryPath, 'Support-Portal-Internet-Connector-'.self::CONNECTOR_VERSION.'.zip', [
                'Cache-Control' => 'no-store, private, max-age=0',
                'Pragma' => 'no-cache',
            ])
            ->deleteFileAfterSend(true);
    }

    private function canExchange(?InternetAccessConnectorToken $token, Request $request): bool
    {
        if (! $token || $token->consumed_at || $token->expires_at->isPast()) {
            return false;
        }

        if (! $token->internetAccessRequest?->canConnect()) {
            return false;
        }

        return $this->tokenIpMatches($token, $request);
    }

    private function canVerify(?InternetAccessConnectorToken $token): bool
    {
        if (! $token || ! $token->consumed_at || ! $token->consumed_ip) {
            return false;
        }

        if ($token->consumed_at->copy()->addSeconds($this->verificationWindow())->isPast()) {
            return false;
        }

        if (! $token->internetAccessRequest?->canConnect()) {
            return false;
        }

        // Verification happens after Windows establishes PPPoE, which can
        // legitimately change the connector's source IP. It returns no secret
        // and still requires the short-lived token that was consumed above.
        return true;
    }

    private function tokenIpMatches(InternetAccessConnectorToken $token, Request $request): bool
    {
        if (! config('mikrotik.connector.bind_token_to_ip', true)) {
            return true;
        }

        return $token->issued_ip !== null
            && hash_equals($token->issued_ip, (string) $request->ip());
    }

    private function bearerToken(Request $request): string
    {
        $token = $request->bearerToken();

        if (! is_string($token) || ! preg_match('/^[A-Za-z0-9_-]{43}$/', $token)) {
            throw new HttpException(401, 'The connector token is invalid or has expired.');
        }

        return $token;
    }

    private function ensureExpectedConnectorRequest(Request $request): void
    {
        $connectionName = (string) $request->input('connection_name');
        $connectorVersion = (string) $request->input('connector_version');

        if (! hash_equals(
            (string) config('mikrotik.connector.connection_name', 'Broadband Connection'),
            $connectionName
        ) || ! in_array($connectorVersion, self::SUPPORTED_CONNECTOR_VERSIONS, true)) {
            throw new HttpException(422, 'The connector request is invalid.');
        }
    }

    private function ensureConnectorIsAvailable(Request $request): void
    {
        if (! config('mikrotik.connector.enabled', true)) {
            throw new HttpException(503, 'The Internet Access connector is disabled.');
        }

        if (config('mikrotik.connector.protocol') !== 'supportportal-connect'
            || config('mikrotik.connector.connection_name') !== 'Broadband Connection') {
            throw new HttpException(503, 'The installed connector configuration does not match this portal.');
        }

        if (config('mikrotik.connector.require_https', true) && ! $request->secure()) {
            throw new HttpException(503, 'A secure HTTPS connection is required to use the Internet Access connector.');
        }

        $this->connectorPortalBaseUrl();
    }

    private function connectorPortalBaseUrl(): string
    {
        $baseUrl = rtrim((string) config('app.url'), '/');
        $parts = parse_url($baseUrl);
        $allowedSchemes = config('mikrotik.connector.require_https', true)
            ? ['https']
            : ['http', 'https'];

        if (filter_var($baseUrl, FILTER_VALIDATE_URL) === false
            || ! is_array($parts)
            || ! isset($parts['scheme'], $parts['host'])
            || ! in_array(strtolower($parts['scheme']), $allowedSchemes, true)
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['query'])
            || isset($parts['fragment'])) {
            throw new HttpException(503, 'APP_URL is not a valid connector portal URL.');
        }

        return $baseUrl;
    }

    private function invalidTokenResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'The connector token is invalid or has expired.',
        ], 401)->withHeaders($this->noStoreHeaders());
    }

    private function newToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    private function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    private function tokenTtl(): int
    {
        return max(30, min(300, (int) config('mikrotik.connector.token_ttl_seconds', 120)));
    }

    private function verificationWindow(): int
    {
        return max(60, min(900, (int) config('mikrotik.connector.verification_window_seconds', 300)));
    }

    private function noStoreHeaders(): array
    {
        return [
            'Cache-Control' => 'no-store, private, max-age=0',
            'Pragma' => 'no-cache',
        ];
    }
}
