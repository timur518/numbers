<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $schedule->job(new \App\Jobs\FetchDirectStatsJob(now()->subDay()->toDateString(), now()->toDateString()))->dailyAt('03:00');
        $schedule->job(new \App\Jobs\FetchAmoLeadsJob(now()->subDay()->toDateString(), now()->toDateString()))->dailyAt('03:10');
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}
