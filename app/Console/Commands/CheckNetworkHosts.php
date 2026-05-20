<?php

// app/Console/Commands/CheckNetworkHosts.php
namespace App\Console\Commands;

use App\Jobs\CheckNetworkHostJob;
use App\Models\NetworkHost;
use Illuminate\Console\Command;

class CheckNetworkHosts extends Command
{
    protected $signature = 'network:hosts-check {--strategy=auto} {--sync}';
    protected $description = 'Check all network hosts and update their status';

    public function handle(): int
    {
        $strategy = $this->option('strategy');
        $sync     = $this->option('sync');

        NetworkHost::query()->orderBy('id')->chunk(100, function ($hosts) use ($strategy, $sync) {
            foreach ($hosts as $host) {
                $job = new CheckNetworkHostJob($host->id, $strategy, null);
                $sync ? dispatch_sync($job) : dispatch($job);
            }
        });

        $this->info("Queued checks for all hosts using strategy={$strategy}.");
        return self::SUCCESS;
    }
}
