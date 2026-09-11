<?php

namespace App\Console\Commands;

use App\Http\Controllers\InternetAccessRequestController;
use App\Models\InternetAccessConnectorToken;
use App\Models\InternetAccessRequest;
use App\Services\Mikrotik\RouterOsClient;
use Illuminate\Console\Command;
use Throwable;

class SyncInternetAccessRequests extends Command
{
    protected $signature = 'internet-access:sync';

    protected $description = 'Start internet access countdowns after first MikroTik connection and expire old access.';

    public function handle(RouterOsClient $mikrotik, InternetAccessRequestController $controller): int
    {
        $this->autoApprovePendingRequests($mikrotik, $controller);
        $this->activateConnectedRequests($mikrotik);
        $this->expireActiveRequests($mikrotik);
        $this->deleteOldConnectorTokens();

        return self::SUCCESS;
    }

    protected function autoApprovePendingRequests(RouterOsClient $mikrotik, InternetAccessRequestController $controller): void
    {
        InternetAccessRequest::where('status', InternetAccessRequest::STATUS_PENDING)
            ->where('created_at', '<=', now()->subMinute())
            ->orderBy('id')
            ->chunkById(50, function ($requests) use ($mikrotik, $controller) {
                foreach ($requests as $request) {
                    try {
                        $controller->provision($request, $mikrotik);
                    } catch (Throwable) {
                        // provision() records and reports the failure.
                    }
                }
            });
    }

    protected function activateConnectedRequests(RouterOsClient $mikrotik): void
    {
        InternetAccessRequest::where('status', InternetAccessRequest::STATUS_READY)
            ->orderBy('id')
            ->chunkById(50, function ($requests) use ($mikrotik) {
                foreach ($requests as $request) {
                    try {
                        if (! $mikrotik->isUserConnected($request->username)) {
                            continue;
                        }

                        $connectedAt = now();

                        InternetAccessRequest::query()
                            ->whereKey($request->id)
                            ->where('status', InternetAccessRequest::STATUS_READY)
                            ->update([
                                'status' => InternetAccessRequest::STATUS_ACTIVE,
                                'connected_at' => $connectedAt,
                                'expires_at' => $connectedAt->copy()->addMinutes($request->duration_minutes),
                                'last_seen_online_at' => $connectedAt,
                                'failure_reason' => null,
                            ]);

                    } catch (Throwable $exception) {
                        report($exception);

                        $request->update(['failure_reason' => $exception->getMessage()]);
                    }
                }
            });
    }

    protected function expireActiveRequests(RouterOsClient $mikrotik): void
    {
        InternetAccessRequest::where('status', InternetAccessRequest::STATUS_ACTIVE)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->orderBy('id')
            ->chunkById(50, function ($requests) use ($mikrotik) {
                foreach ($requests as $request) {
                    try {
                        $mikrotik->removeAccess($request->username);

                        $request->update([
                            'status' => InternetAccessRequest::STATUS_EXPIRED,
                            'expired_at' => now(),
                            'failure_reason' => null,
                        ]);

                    } catch (Throwable $exception) {
                        report($exception);

                        $request->update(['failure_reason' => $exception->getMessage()]);
                    }
                }
            });
    }

    protected function deleteOldConnectorTokens(): void
    {
        InternetAccessConnectorToken::query()
            ->where('expires_at', '<', now()->subDay())
            ->delete();
    }
}
