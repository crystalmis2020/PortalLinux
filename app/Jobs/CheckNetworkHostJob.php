<?php

// app/Jobs/CheckNetworkHostJob.php
namespace App\Jobs;

use App\Models\NetworkHost;
use App\Models\ActivityLog; // adjust namespace to your project
use App\Services\NetworkProbe;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckNetworkHostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $hostId,
        public string $strategy = 'auto',
        public ?int $triggeredBy = null, // user id if manual
    ) {}

    public function handle(NetworkProbe $probe): void
    {
        $host = NetworkHost::find($this->hostId);
        if (!$host) return;

        $online = $probe->isOnline($host->ip_address, $this->strategy);

        $oldStatus = $host->status;
        $host->update([
            'status'     => $online ? 'online' : 'offline',
            'last_check' => now(),
        ]);

        // // Activity log (adjust fields/shape to your ActivityLog schema)
        // ActivityLog::create([
        //     'action'       => 'network_host.check',
        //     'description'  => "Checked {$host->ip_address} ({$host->server_name}) via {$this->strategy}: " . ($online ? 'online' : 'offline'),
        //     'user_id'      => $this->triggeredBy,     // may be null for scheduled
        //     'subject_type' => NetworkHost::class,
        //     'subject_id'   => $host->id,
        //     'meta'         => ['from' => $oldStatus, 'to' => $host->status, 'strategy' => $this->strategy],
        // ]);
    }
}
