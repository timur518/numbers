<?php

namespace App\Console\Commands;

use App\Enums\Provider;
use App\Jobs\DirectSyncDailyStatsJob;
use App\Jobs\DirectSyncDictionariesJob;
use App\Models\Integration;
use App\Models\OauthToken;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class DirectSyncCommand extends Command
{
    protected $signature = 'direct:sync
        {--user_id=* : Один или несколько ID пользователей}
        {--days=14 : Глубина подкачки статистики}
        {--dicts : Принудительно синхронизировать справочники}
        {--stats : Принудительно синхронизировать статистику}
    ';

    protected $description = 'Запланировать синхронизацию Яндекс.Директа для указанных пользователей';

    public function handle(): int
    {
        $userIds = collect($this->option('user_id'))->filter()->map(fn ($id) => (int) $id);
        $days = max(1, (int) $this->option('days'));
        $doDicts = (bool) $this->option('dicts');
        $doStats = (bool) $this->option('stats');

        if (!$doDicts && !$doStats) {
            $doDicts = $doStats = true;
        }

        $eligibleUsers = $this->eligibleUsers($userIds);

        if ($eligibleUsers->isEmpty()) {
            $this->warn('Нет пользователей с активным Яндекс.Директ.');
            return self::SUCCESS;
        }

        $from = CarbonImmutable::today()->subDays($days - 1)->toDateString();
        $to = CarbonImmutable::today()->toDateString();

        foreach ($eligibleUsers as $userId) {
            if ($doDicts) {
                DirectSyncDictionariesJob::dispatch($userId);
                $this->line(" • Справочники → user_id={$userId}");
            }

            if ($doStats) {
                DirectSyncDailyStatsJob::dispatch($userId, $from, $to);
                $this->line(" • Статистика {$from}..{$to} → user_id={$userId}");
            }
        }

        $this->info('Задачи синхронизации отправлены в очередь.');
        return self::SUCCESS;
    }

    protected function eligibleUsers($requestedUserIds)
    {
        $query = Integration::query()
            ->select('user_id')
            ->where('provider', Provider::DIRECT)
            ->where('status', 'connected');

        if ($requestedUserIds->isNotEmpty()) {
            $query->whereIn('user_id', $requestedUserIds);
        }

        $userIds = $query->pluck('user_id');

        if ($userIds->isEmpty()) {
            return collect();
        }

        $validTokens = OauthToken::query()
            ->where('provider', Provider::DIRECT)
            ->whereIn('user_id', $userIds)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->pluck('user_id');

        return $validTokens->unique()->values();
    }
}
