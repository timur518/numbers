<?php

namespace App\Console\Commands;

use App\Jobs\DirectSyncDailyStatsJob;
use Illuminate\Console\Command;

class DirectSyncStatsCommand extends Command
{
    protected $signature = 'direct:sync-stats {user_id} {--from=} {--to=}';
    protected $description = 'Sync Yandex Direct daily stats for date range (YYYY-MM-DD)';

    public function handle(): int
    {
        $from = $this->option('from') ?: now()->subDays(7)->toDateString();
        $to   = $this->option('to') ?: now()->toDateString();

        dispatch(new DirectSyncDailyStatsJob((int)$this->argument('user_id'), $from, $to));
        $this->info("Stats sync dispatched for {$from}..{$to}");
        return self::SUCCESS;
    }
}
