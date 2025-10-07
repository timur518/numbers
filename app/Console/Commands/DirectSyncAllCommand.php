<?php

namespace App\Console\Commands;

use App\Jobs\DirectSyncDailyStatsJob;
use App\Jobs\DirectSyncDictionariesJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DirectSyncAllCommand extends Command
{
    protected $signature = 'direct:sync:all
        {--dicts : Синк справочников (кампании/группы/объявления/ключи)}
        {--stats : Синк дневной статистики}
        {--from= : Дата с (YYYY-MM-DD) для статистики}
        {--to=   : Дата по (YYYY-MM-DD) для статистики}';

    protected $description = 'Запустить синк Direct для всех активных пользователей с подключенной интеграцией';

    public function handle(): int
    {
        $doDicts = (bool) $this->option('dicts');
        $doStats = (bool) $this->option('stats');

        if (!$doDicts && !$doStats) {
            // по умолчанию делаем статистику за вчера
            $doStats = true;
        }

        $from = $this->option('from') ?: now()->subDay()->toDateString();
        $to   = $this->option('to')   ?: now()->subDay()->toDateString();

        // выбираем user_id, у которых есть подключённый Direct + валидный токен
        $userIds = DB::table('users')
            ->whereIn('id', function ($q) {
                $q->select('user_id')->from('integrations')
                    ->where('provider', 'direct')
                    ->where('status', 'connected');
            })
            ->whereExists(function ($q) {
                $q->selectRaw(1)->from('oauth_tokens')
                    ->whereColumn('oauth_tokens.user_id', 'users.id')
                    ->where('provider', 'direct')
                    ->where(function ($qq) {
                        $qq->whereNull('expires_at')->orWhere('expires_at', '>', now());
                    });
            })
            ->pluck('id');

        if ($userIds->isEmpty()) {
            $this->warn('Нет пользователей с активной интеграцией Direct.');
            return self::SUCCESS;
        }

        $this->info('Найдено пользователей: ' . $userIds->count());

        foreach ($userIds as $uid) {
            if ($doDicts) {
                dispatch(new DirectSyncDictionariesJob((int)$uid));
                $this->line("  • dicts → user_id={$uid}");
            }
            if ($doStats) {
                dispatch(new DirectSyncDailyStatsJob((int)$uid, $from, $to));
                $this->line("  • stats {$from}..{$to} → user_id={$uid}");
            }
        }

        $this->info('Фан-аут синков отправлен в очередь.');
        return self::SUCCESS;
    }
}
