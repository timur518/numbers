<?php

namespace App\Jobs;

use App\Models\SyncRun;
use App\Models\YdStatDaily;
use App\Services\YandexDirect\DirectApi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class DirectSyncDailyStatsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** $dateFrom/$dateTo — строки 'YYYY-MM-DD' */
    public function __construct(public int $userId, public string $dateFrom, public string $dateTo) {}
    public int $timeout = 1800;

    public function handle(): void
    {
        $run = SyncRun::create([
            'status' => 'running', 'scope' => 'direct-stats', 'started_at' => now(),
        ]);
        $this->progress($run->id, 0, "Отчёт {$this->dateFrom}..{$this->dateTo}");

        try {
            $api = new DirectApi($this->userId);

            // Уровень AD (по объявлениям) — самый полезный для склейки
            $rows = $api->reportDailyStats($this->dateFrom, $this->dateTo, 'AD');

            $total = 0;
            // упсертим пачками
            foreach (array_chunk($rows, 1000) as $chunk) {
                $up = [];
                foreach ($chunk as $r) {
                    $up[] = [
                        'user_id'        => $this->userId,
                        'date'           => $r['Date'],
                        'yd_campaign_id' => (int)$r['CampaignId'],
                        'yd_ad_id'       => (int)($r['AdId'] ?? 0) ?: null,
                        'yd_keyword_id'  => null, // при желании второй прогон на KEYWORD
                        'impressions'    => (int)($r['Impressions'] ?? 0),
                        'clicks'         => (int)($r['Clicks'] ?? 0),
                        'cost_micros'    => (int)round((float)($r['Cost'] ?? 0)), // уже в micros
                        'currency'       => 'RUB', // или парсить из аккаунта/кампании
                        'meta'           => null,
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ];
                }
                if ($up) {
                    // upsert по уникальному индексу (user_id,date,campaign,ad,keyword)
                    YdStatDaily::query()->upsert(
                        $up,
                        ['user_id','date','yd_campaign_id','yd_ad_id','yd_keyword_id'],
                        ['impressions','clicks','cost_micros','currency','updated_at']
                    );
                    $total += count($up);
                }
            }

            $run->update([
                'status' => 'success',
                'finished_at' => now(),
                'message' => "Строк загружено: {$total}",
            ]);
            $this->progress($run->id, 100, "Готово. Строк: {$total}");

        } catch (Throwable $e) {
            $run->update(['status'=>'failed','finished_at'=>now(),'message'=>$e->getMessage()]);
            $this->progress($run->id, 0, 'Ошибка: '.$e->getMessage());
            throw $e;
        }
    }

    protected function progress(int $runId, int $pct, string $msg): void
    {
        Cache::put('direct.sync.status', [
            'status' => $pct < 100 && $pct > 0 ? 'running' : ($pct === 100 ? 'success' : 'idle'),
            'run_id' => $runId,
            'progress' => $pct,
            'message' => $msg,
            'updated_at' => now()->toIso8601String(),
        ], now()->addHour());
    }
}
