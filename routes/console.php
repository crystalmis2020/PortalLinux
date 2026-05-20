<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();


// run your LAN scanner every 5 minutes
Schedule::command('network:hosts-check --strategy=auto')
    ->everyFiveMinutes()
    ->withoutOverlapping();

Schedule::command('internet-access:sync')
    ->everyMinute()
    ->withoutOverlapping();
