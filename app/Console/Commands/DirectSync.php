<?php

namespace App\Console\Commands;

use App\Enums\Provider;
use App\Jobs\SyncDirectDailyReport;
use App\Models\OauthToken;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class DirectSync extends Command
{
    protected $signature = 'direct:sync {--days=14} {--user_id=*}';
    protected $description = 'Sync Yandex Direct daily stats for last N days (default 14)';

    public function handle(): int
    {
        $days = (int)$this->option('days');
        $users = collect($this->option('user_id'))->filter()->map(fn($v)=>(int)$v);

        $query = OauthToken::where('provider', Provider::DIRECT);
        if ($users->isNotEmpty()) $query->whereIn('user_id', $users);
        $tokens = $query->pluck('user_id')->unique();

        if ($tokens->isEmpty()) {
            $this->warn('No users with DIRECT token.');
            return self::SUCCESS;
        }

        $to = CarbonImmutable::today()->toDateString();
        $from = CarbonImmutable::today()->subDays($days-1)->toDateString();

        foreach ($tokens as $uid) {
            SyncDirectDailyReport::dispatch($uid, $from, $to);
            $this->info("Dispatched report sync for user {$uid} {$from}..{$to}");
        }
        return self::SUCCESS;
    }
}
