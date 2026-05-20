<?php

namespace App\Console\Commands;

use App\Models\InternetAccessRequest;
use App\Services\Mikrotik\RouterOsClient;
use Illuminate\Console\Command;
use Throwable;

class SyncInternetAccessRequests extends Command
{
    protected $signature = 'internet-access:sync';

    protected $description = 'Start internet access countdowns after first MikroTik connection and expire old access.';

    public function handle(RouterOsClient $mikrotik): int
    {
        $this->activateConnectedRequests($mikrotik);
        $this->expireActiveRequests($mikrotik);

        return self::SUCCESS;
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

                        $request->update([
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
}
