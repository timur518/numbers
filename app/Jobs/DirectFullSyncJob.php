<?php

namespace App\Jobs;

use App\Models\DirectSyncRun;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class DirectFullSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800; // 30 мин
    public int $tries = 1;

    public function handle(): void
    {
        $run = DirectSyncRun::create([
            'status' => 'running',
            'started_at' => now(),
        ]);
        Cache::put('direct.sync.status', [
            'status' => 'running',
            'run_id' => $run->id,
            'progress' => 0,
            'message' => 'Запуск синхронизации…',
            'updated_at' => now()->toIso8601String(),
        ], now()->addHour());

        try {
            // TODO: здесь твоя реальная логика обхода Директа:
            // примеры инкремента: кампании, объявления, расходы, статусы и т.д.
            $campaigns = 0; $ads = 0; $units = 0;

            // Пример: шаги с прогрессом
            $steps = [
                'Загрузка кампаний' => 20,
                'Загрузка групп/объявлений' => 60,
                'Загрузка статистики' => 90,
                'Финализация' => 100,
            ];
            foreach ($steps as $msg => $pct) {
                // ... вызовы API, подсчёты, $units += consumedUnits()
                // ... $campaigns += x; $ads += y;
                usleep(300000); // имитация
                Cache::put('direct.sync.status', [
                    'status' => 'running',
                    'run_id' => $run->id,
                    'progress' => $pct,
                    'message' => $msg,
                    'updated_at' => now()->toIso8601String(),
                ], now()->addHour());
            }

            $run->update([
                'status' => 'success',
                'finished_at' => now(),
                'api_units_used' => $units,
                'campaigns_synced' => $campaigns,
                'ads_synced' => $ads,
                'errors_count' => 0,
                'message' => 'Синхронизация завершена успешно',
            ]);

            Cache::put('direct.sync.status', [
                'status' => 'success',
                'run_id' => $run->id,
                'progress' => 100,
                'message' => 'Готово',
                'updated_at' => now()->toIso8601String(),
                'last_run' => [
                    'finished_at' => $run->finished_at?->toIso8601String(),
                    'api_units_used' => $run->api_units_used,
                    'campaigns_synced' => $run->campaigns_synced,
                    'ads_synced' => $run->ads_synced,
                ],
            ], now()->addHour());

        } catch (Throwable $e) {
            $run->update([
                'status' => 'failed',
                'finished_at' => now(),
                'message' => $e->getMessage(),
            ]);
            Cache::put('direct.sync.status', [
                'status' => 'failed',
                'run_id' => $run->id,
                'progress' => 0,
                'message' => 'Ошибка: '.$e->getMessage(),
                'updated_at' => now()->toIso8601String(),
            ], now()->addHour());

            throw $e;
        }
    }
}
